<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle CoproprietaireDocument (GED personnelle propriétaire)
 * ====================================================================
 * Gère les dossiers et fichiers d'un propriétaire.
 * Isolation stricte : toutes les requêtes filtrent par proprietaire_id.
 */

class CoproprietaireDocument extends Model {

    public const QUOTA_OCTETS = 1073741824;           // 1 GB
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

    public function getDossiers(int $proprietaireId, ?int $parentId = null): array {
        if ($parentId === null) {
            $sql = "SELECT d.*,
                           (SELECT COUNT(*) FROM coproprietaire_dossiers WHERE parent_id = d.id) AS nb_sous_dossiers,
                           (SELECT COUNT(*) FROM coproprietaire_fichiers WHERE dossier_id = d.id) AS nb_fichiers
                    FROM coproprietaire_dossiers d
                    WHERE d.proprietaire_id = ? AND d.parent_id IS NULL
                    ORDER BY d.nom ASC";
            $params = [$proprietaireId];
        } else {
            $sql = "SELECT d.*,
                           (SELECT COUNT(*) FROM coproprietaire_dossiers WHERE parent_id = d.id) AS nb_sous_dossiers,
                           (SELECT COUNT(*) FROM coproprietaire_fichiers WHERE dossier_id = d.id) AS nb_fichiers
                    FROM coproprietaire_dossiers d
                    WHERE d.proprietaire_id = ? AND d.parent_id = ?
                    ORDER BY d.nom ASC";
            $params = [$proprietaireId, $parentId];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDossier(int $proprietaireId, int $dossierId): ?array {
        $sql = "SELECT * FROM coproprietaire_dossiers WHERE id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dossierId, $proprietaireId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createDossier(int $proprietaireId, string $nom, ?int $parentId = null): int {
        if ($parentId !== null) {
            $parent = $this->findDossier($proprietaireId, $parentId);
            if (!$parent) throw new Exception("Dossier parent introuvable.");
        }
        $sql = "INSERT INTO coproprietaire_dossiers (proprietaire_id, parent_id, nom) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$proprietaireId, $parentId, $nom]);
        return (int)$this->db->lastInsertId();
    }

    public function renameDossier(int $proprietaireId, int $dossierId, string $nouveauNom): bool {
        $sql = "UPDATE coproprietaire_dossiers SET nom = ? WHERE id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nouveauNom, $dossierId, $proprietaireId]);
    }

    /**
     * Supprime un dossier + tous ses fichiers/sous-dossiers en cascade.
     * Retourne la liste des chemins physiques à supprimer sur disque.
     */
    public function deleteDossierCascade(int $proprietaireId, int $dossierId): array {
        $dossier = $this->findDossier($proprietaireId, $dossierId);
        if (!$dossier) throw new Exception("Dossier introuvable.");

        $cheminsASupprimer = $this->collectCheminsRecursif($proprietaireId, $dossierId);

        $sql = "DELETE FROM coproprietaire_dossiers WHERE id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dossierId, $proprietaireId]);

        return $cheminsASupprimer;
    }

    private function collectCheminsRecursif(int $proprietaireId, int $dossierId): array {
        $chemins = [];

        $sqlFichiers = "SELECT chemin_stockage FROM coproprietaire_fichiers WHERE dossier_id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sqlFichiers);
        $stmt->execute([$dossierId, $proprietaireId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $chemin) {
            $chemins[] = $chemin;
        }

        $sqlSousDoss = "SELECT id FROM coproprietaire_dossiers WHERE parent_id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sqlSousDoss);
        $stmt->execute([$dossierId, $proprietaireId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $sousDossId) {
            $chemins = array_merge($chemins, $this->collectCheminsRecursif($proprietaireId, (int)$sousDossId));
        }

        return $chemins;
    }

    public function getBreadcrumb(int $proprietaireId, ?int $dossierId): array {
        if ($dossierId === null) return [];
        $breadcrumb = [];
        $currentId = $dossierId;
        while ($currentId !== null) {
            $d = $this->findDossier($proprietaireId, $currentId);
            if (!$d) break;
            array_unshift($breadcrumb, ['id' => (int)$d['id'], 'nom' => $d['nom']]);
            $currentId = $d['parent_id'] !== null ? (int)$d['parent_id'] : null;
        }
        return $breadcrumb;
    }

    // ─── FICHIERS ────────────────────────────────────────────────

    public function getFichiers(int $proprietaireId, ?int $dossierId = null): array {
        if ($dossierId === null) {
            $sql = "SELECT * FROM coproprietaire_fichiers
                    WHERE proprietaire_id = ? AND dossier_id IS NULL
                    ORDER BY uploaded_at DESC";
            $params = [$proprietaireId];
        } else {
            $sql = "SELECT * FROM coproprietaire_fichiers
                    WHERE proprietaire_id = ? AND dossier_id = ?
                    ORDER BY uploaded_at DESC";
            $params = [$proprietaireId, $dossierId];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findFichier(int $proprietaireId, int $fichierId): ?array {
        $sql = "SELECT * FROM coproprietaire_fichiers WHERE id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fichierId, $proprietaireId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createFichier(int $proprietaireId, ?int $dossierId, array $data): int {
        $sql = "INSERT INTO coproprietaire_fichiers
                (proprietaire_id, dossier_id, nom_original, chemin_stockage, mime_type, taille_octets)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $proprietaireId,
            $dossierId,
            $data['nom_original'],
            $data['chemin_stockage'],
            $data['mime_type'] ?? null,
            $data['taille_octets'] ?? 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteFichier(int $proprietaireId, int $fichierId): ?string {
        $fichier = $this->findFichier($proprietaireId, $fichierId);
        if (!$fichier) return null;
        $sql = "DELETE FROM coproprietaire_fichiers WHERE id = ? AND proprietaire_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fichierId, $proprietaireId]);
        return $fichier['chemin_stockage'];
    }

    // ─── QUOTA ───────────────────────────────────────────────────

    public function getQuotaUtilise(int $proprietaireId): int {
        $sql = "SELECT COALESCE(SUM(taille_octets), 0) AS total
                FROM coproprietaire_fichiers WHERE proprietaire_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$proprietaireId]);
        return (int)$stmt->fetchColumn();
    }

    public function quotaSuffisant(int $proprietaireId, int $tailleAjout): bool {
        return ($this->getQuotaUtilise($proprietaireId) + $tailleAjout) <= self::QUOTA_OCTETS;
    }

    // ─── STATS ───────────────────────────────────────────────────

    public function getStats(int $proprietaireId): array {
        $utilise = $this->getQuotaUtilise($proprietaireId);

        $sqlNbFichiers = "SELECT COUNT(*) FROM coproprietaire_fichiers WHERE proprietaire_id = ?";
        $stmt = $this->db->prepare($sqlNbFichiers);
        $stmt->execute([$proprietaireId]);
        $nbFichiers = (int)$stmt->fetchColumn();

        $sqlNbDossiers = "SELECT COUNT(*) FROM coproprietaire_dossiers WHERE proprietaire_id = ?";
        $stmt = $this->db->prepare($sqlNbDossiers);
        $stmt->execute([$proprietaireId]);
        $nbDossiers = (int)$stmt->fetchColumn();

        return [
            'quota_total'       => self::QUOTA_OCTETS,
            'quota_utilise'     => $utilise,
            'quota_disponible'  => max(0, self::QUOTA_OCTETS - $utilise),
            'pourcentage_utilise' => self::QUOTA_OCTETS > 0 ? round(($utilise / self::QUOTA_OCTETS) * 100, 1) : 0,
            'nb_fichiers'       => $nbFichiers,
            'nb_dossiers'       => $nbDossiers,
        ];
    }
}
