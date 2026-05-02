<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle ResidentDocument (GED personnelle résident senior)
 * ====================================================================
 * Gère les dossiers et fichiers d'un résident senior.
 * Isolation stricte : toutes les requêtes filtrent par resident_id.
 *
 * Quota : 500 MB par résident, 50 MB par fichier.
 * Stockage physique : uploads/residents/{user_id}/{dossier_id|racine}/{ts}_{nom}.{ext}
 */

class ResidentDocument extends Model {

    public const QUOTA_OCTETS = 524288000;            // 500 MB (500 * 1024 * 1024)
    public const TAILLE_MAX_FICHIER = 52428800;       // 50 MB

    public const MIME_AUTORISES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'application/zip',
    ];

    public const EXT_AUTORISEES = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods',
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        'mp4', 'mov', 'webm',
        'zip',
    ];

    // ─── DOSSIERS ───────────────────────────────────────────────

    public function getDossiers(int $residentId, ?int $parentId = null): array {
        if ($parentId === null) {
            $sql = "SELECT d.*,
                           (SELECT COUNT(*) FROM resident_dossiers WHERE parent_id = d.id) AS nb_sous_dossiers,
                           (SELECT COUNT(*) FROM resident_fichiers WHERE dossier_id = d.id) AS nb_fichiers
                    FROM resident_dossiers d
                    WHERE d.resident_id = ? AND d.parent_id IS NULL
                    ORDER BY d.nom ASC";
            $params = [$residentId];
        } else {
            $sql = "SELECT d.*,
                           (SELECT COUNT(*) FROM resident_dossiers WHERE parent_id = d.id) AS nb_sous_dossiers,
                           (SELECT COUNT(*) FROM resident_fichiers WHERE dossier_id = d.id) AS nb_fichiers
                    FROM resident_dossiers d
                    WHERE d.resident_id = ? AND d.parent_id = ?
                    ORDER BY d.nom ASC";
            $params = [$residentId, $parentId];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDossier(int $residentId, int $dossierId): ?array {
        $sql = "SELECT * FROM resident_dossiers WHERE id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dossierId, $residentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createDossier(int $residentId, string $nom, ?int $parentId = null): int {
        if ($parentId !== null) {
            $parent = $this->findDossier($residentId, $parentId);
            if (!$parent) throw new Exception("Dossier parent introuvable.");
        }
        $sql = "INSERT INTO resident_dossiers (resident_id, parent_id, nom) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residentId, $parentId, $nom]);
        return (int)$this->db->lastInsertId();
    }

    public function renameDossier(int $residentId, int $dossierId, string $nouveauNom): bool {
        $sql = "UPDATE resident_dossiers SET nom = ? WHERE id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nouveauNom, $dossierId, $residentId]);
    }

    /**
     * Supprime un dossier + tous ses fichiers/sous-dossiers en cascade.
     * Retourne la liste des chemins physiques à supprimer sur disque.
     */
    public function deleteDossierCascade(int $residentId, int $dossierId): array {
        $dossier = $this->findDossier($residentId, $dossierId);
        if (!$dossier) throw new Exception("Dossier introuvable.");

        $cheminsASupprimer = $this->collectCheminsRecursif($residentId, $dossierId);

        $sql = "DELETE FROM resident_dossiers WHERE id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dossierId, $residentId]);

        return $cheminsASupprimer;
    }

    private function collectCheminsRecursif(int $residentId, int $dossierId): array {
        $chemins = [];

        $sqlFichiers = "SELECT chemin_stockage FROM resident_fichiers WHERE dossier_id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sqlFichiers);
        $stmt->execute([$dossierId, $residentId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $chemin) {
            $chemins[] = $chemin;
        }

        $sqlSousDoss = "SELECT id FROM resident_dossiers WHERE parent_id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sqlSousDoss);
        $stmt->execute([$dossierId, $residentId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $sousDossId) {
            $chemins = array_merge($chemins, $this->collectCheminsRecursif($residentId, (int)$sousDossId));
        }

        return $chemins;
    }

    public function getBreadcrumb(int $residentId, ?int $dossierId): array {
        if ($dossierId === null) return [];
        $breadcrumb = [];
        $currentId = $dossierId;
        while ($currentId !== null) {
            $d = $this->findDossier($residentId, $currentId);
            if (!$d) break;
            array_unshift($breadcrumb, ['id' => (int)$d['id'], 'nom' => $d['nom']]);
            $currentId = $d['parent_id'] !== null ? (int)$d['parent_id'] : null;
        }
        return $breadcrumb;
    }

    // ─── FICHIERS ────────────────────────────────────────────────

    public function getFichiers(int $residentId, ?int $dossierId = null): array {
        if ($dossierId === null) {
            $sql = "SELECT * FROM resident_fichiers
                    WHERE resident_id = ? AND dossier_id IS NULL
                    ORDER BY uploaded_at DESC";
            $params = [$residentId];
        } else {
            $sql = "SELECT * FROM resident_fichiers
                    WHERE resident_id = ? AND dossier_id = ?
                    ORDER BY uploaded_at DESC";
            $params = [$residentId, $dossierId];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findFichier(int $residentId, int $fichierId): ?array {
        $sql = "SELECT * FROM resident_fichiers WHERE id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fichierId, $residentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createFichier(int $residentId, ?int $dossierId, array $data): int {
        $sql = "INSERT INTO resident_fichiers
                (resident_id, dossier_id, nom_original, chemin_stockage, mime_type, taille_octets)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $residentId,
            $dossierId,
            $data['nom_original'],
            $data['chemin_stockage'],
            $data['mime_type'] ?? null,
            $data['taille_octets'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteFichier(int $residentId, int $fichierId): ?string {
        $fichier = $this->findFichier($residentId, $fichierId);
        if (!$fichier) return null;
        $sql = "DELETE FROM resident_fichiers WHERE id = ? AND resident_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fichierId, $residentId]);
        return $fichier['chemin_stockage'];
    }

    // ─── QUOTA ───────────────────────────────────────────────────

    public function getQuotaUtilise(int $residentId): int {
        $sql = "SELECT COALESCE(SUM(taille_octets), 0) AS total
                FROM resident_fichiers WHERE resident_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residentId]);
        return (int)$stmt->fetchColumn();
    }

    public function quotaSuffisant(int $residentId, int $tailleAjout): bool {
        return ($this->getQuotaUtilise($residentId) + $tailleAjout) <= self::QUOTA_OCTETS;
    }

    // ─── STATS ───────────────────────────────────────────────────

    public function getStats(int $residentId): array {
        $utilise = $this->getQuotaUtilise($residentId);

        $sqlNbFichiers = "SELECT COUNT(*) FROM resident_fichiers WHERE resident_id = ?";
        $stmt = $this->db->prepare($sqlNbFichiers);
        $stmt->execute([$residentId]);
        $nbFichiers = (int)$stmt->fetchColumn();

        $sqlNbDossiers = "SELECT COUNT(*) FROM resident_dossiers WHERE resident_id = ?";
        $stmt = $this->db->prepare($sqlNbDossiers);
        $stmt->execute([$residentId]);
        $nbDossiers = (int)$stmt->fetchColumn();

        return [
            'quota_total'         => self::QUOTA_OCTETS,
            'quota_utilise'       => $utilise,
            'quota_disponible'    => max(0, self::QUOTA_OCTETS - $utilise),
            'pourcentage_utilise' => self::QUOTA_OCTETS > 0 ? round(($utilise / self::QUOTA_OCTETS) * 100, 1) : 0,
            'nb_fichiers'         => $nbFichiers,
            'nb_dossiers'         => $nbDossiers,
        ];
    }
}
