<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle AdminDocument (GED admin/staff direction)
 * ====================================================================
 * Périmètre :
 *   - scope = 'global'    → residence_id IS NULL → docs Domitys partagés
 *   - scope = 'residence' → residence_id = X     → docs spécifiques à 1 résidence
 *
 * Permissions :
 *   - admin                      : R/W partout
 *   - directeur_residence        : R/W sur ses résidences (user_residence), R-only sur global
 *   - exploitant                 : R/W sur ses résidences (exploitant_residences), R-only sur global
 *   - comptable                  : R-only sur ses résidences (user_residence) + R-only sur global
 *   - autres rôles               : aucun accès
 */

class AdminDocument extends Model {

    public const TAILLE_MAX_FICHIER = 52428800; // 50 MB

    public const MIME_AUTORISES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'video/mp4', 'video/quicktime', 'video/webm',
        'application/zip',
        'text/csv', 'text/plain',
    ];

    public const EXT_AUTORISEES = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods',
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        'mp4', 'mov', 'webm',
        'zip',
        'csv', 'txt',
    ];

    public const ROLES_LECTURE = ['admin', 'directeur_residence', 'exploitant', 'comptable'];
    public const ROLES_GLOBAL_WRITE = ['admin']; // seul l'admin écrit dans le scope global
    public const ROLES_RESIDENCE_WRITE = ['admin', 'directeur_residence', 'exploitant'];

    // ─── PERMISSIONS ──────────────────────────────────────────────

    /**
     * Indique si l'utilisateur peut LIRE le scope demandé.
     */
    public function canRead(string $role, ?int $userId, string $scope, ?int $residenceId): bool {
        if (!in_array($role, self::ROLES_LECTURE, true)) return false;
        if ($scope === 'global')   return true; // tous les rôles autorisés voient le global
        if ($scope === 'residence') {
            if ($residenceId === null) return false;
            if ($role === 'admin') return true;
            return in_array($residenceId, $this->getResidenceIdsForUser($role, $userId), true);
        }
        return false;
    }

    /**
     * Indique si l'utilisateur peut ÉCRIRE (créer dossier, upload, supprimer) dans le scope.
     */
    public function canWrite(string $role, ?int $userId, string $scope, ?int $residenceId): bool {
        if ($scope === 'global') {
            return in_array($role, self::ROLES_GLOBAL_WRITE, true);
        }
        if ($scope === 'residence') {
            if (!in_array($role, self::ROLES_RESIDENCE_WRITE, true)) return false;
            if ($residenceId === null) return false;
            if ($role === 'admin') return true;
            return in_array($residenceId, $this->getResidenceIdsForUser($role, $userId), true);
        }
        return false;
    }

    /**
     * Liste des résidences accessibles à l'utilisateur (pour sélecteur côté résidence).
     * @return array{id:int,nom:string,ville:string}[]
     */
    public function getResidencesForUser(string $role, ?int $userId): array {
        if ($role === 'admin') {
            $sql = "SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        if (in_array($role, ['directeur_residence', 'comptable'], true)) {
            $sql = "SELECT c.id, c.nom, c.ville
                    FROM coproprietees c
                    JOIN user_residence ur ON ur.residence_id = c.id
                    WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1
                    ORDER BY c.nom";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($role === 'exploitant') {
            $sql = "SELECT c.id, c.nom, c.ville
                    FROM coproprietees c
                    JOIN exploitant_residences er ON er.residence_id = c.id
                    JOIN exploitants e ON e.id = er.exploitant_id
                    WHERE e.user_id = ? AND er.statut = 'actif' AND c.actif = 1
                    ORDER BY c.nom";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * IDs de résidences accessibles (helper pour les checks).
     * @return int[]
     */
    private function getResidenceIdsForUser(string $role, ?int $userId): array {
        return array_map('intval', array_column($this->getResidencesForUser($role, $userId), 'id'));
    }

    // ─── DOSSIERS ─────────────────────────────────────────────────

    public function getDossiers(string $scope, ?int $residenceId, ?int $parentId): array {
        $where = ($scope === 'global') ? 'd.residence_id IS NULL' : 'd.residence_id = ?';
        $params = ($scope === 'global') ? [] : [(int)$residenceId];

        if ($parentId === null) {
            $where .= ' AND d.parent_id IS NULL';
        } else {
            $where .= ' AND d.parent_id = ?';
            $params[] = (int)$parentId;
        }

        $sql = "SELECT d.*,
                       (SELECT COUNT(*) FROM admin_dossiers WHERE parent_id = d.id) AS nb_sous_dossiers,
                       (SELECT COUNT(*) FROM admin_fichiers WHERE dossier_id = d.id) AS nb_fichiers
                FROM admin_dossiers d
                WHERE $where
                ORDER BY d.nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDossier(int $dossierId): ?array {
        $sql = "SELECT * FROM admin_dossiers WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dossierId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createDossier(string $scope, ?int $residenceId, ?int $parentId, string $nom, int $createdBy): int {
        $sql = "INSERT INTO admin_dossiers (residence_id, parent_id, nom, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $scope === 'global' ? null : (int)$residenceId,
            $parentId !== null ? (int)$parentId : null,
            $nom,
            $createdBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function renameDossier(int $dossierId, string $nouveauNom): bool {
        $stmt = $this->db->prepare("UPDATE admin_dossiers SET nom = ? WHERE id = ?");
        return $stmt->execute([$nouveauNom, $dossierId]);
    }

    /**
     * Supprime un dossier en cascade (sous-dossiers + fichiers physiques + lignes BDD).
     * Renvoie la liste des chemins physiques à effacer (le controller s'en charge).
     */
    public function deleteDossierCascade(int $dossierId): array {
        // Récupère récursivement tous les sous-dossiers
        $allDossierIds = [$dossierId];
        $queue = [$dossierId];
        while (!empty($queue)) {
            $parents = implode(',', array_map('intval', $queue));
            $rows = $this->db->query("SELECT id FROM admin_dossiers WHERE parent_id IN ($parents)")->fetchAll(PDO::FETCH_COLUMN);
            $queue = array_map('intval', $rows);
            $allDossierIds = array_merge($allDossierIds, $queue);
        }

        $list = implode(',', array_map('intval', $allDossierIds));
        $cheminsToDelete = $this->db->query("SELECT chemin_stockage FROM admin_fichiers WHERE dossier_id IN ($list)")
                                    ->fetchAll(PDO::FETCH_COLUMN);

        // CASCADE FK fait le reste : suppression du dossier racine supprime les sous-dossiers et leurs fichiers
        $this->db->prepare("DELETE FROM admin_dossiers WHERE id = ?")->execute([$dossierId]);
        return $cheminsToDelete;
    }

    public function getBreadcrumb(?int $dossierId): array {
        if ($dossierId === null) return [];
        $crumbs = [];
        $current = $dossierId;
        $safety = 50; // anti-boucle
        while ($current !== null && $safety-- > 0) {
            $row = $this->db->query("SELECT id, parent_id, nom FROM admin_dossiers WHERE id = " . (int)$current)
                            ->fetch(PDO::FETCH_ASSOC);
            if (!$row) break;
            array_unshift($crumbs, $row);
            $current = $row['parent_id'];
        }
        return $crumbs;
    }

    // ─── FICHIERS ─────────────────────────────────────────────────

    public function getFichiers(string $scope, ?int $residenceId, ?int $dossierId): array {
        $where = ($scope === 'global') ? 'f.residence_id IS NULL' : 'f.residence_id = ?';
        $params = ($scope === 'global') ? [] : [(int)$residenceId];

        if ($dossierId === null) {
            $where .= ' AND f.dossier_id IS NULL';
        } else {
            $where .= ' AND f.dossier_id = ?';
            $params[] = (int)$dossierId;
        }

        $sql = "SELECT f.*, CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS uploaded_by_nom
                FROM admin_fichiers f
                LEFT JOIN users u ON u.id = f.uploaded_by
                WHERE $where
                ORDER BY f.uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findFichier(int $fichierId): ?array {
        $sql = "SELECT * FROM admin_fichiers WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fichierId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createFichier(string $scope, ?int $residenceId, ?int $dossierId, array $data, int $uploadedBy): int {
        $sql = "INSERT INTO admin_fichiers
                (residence_id, dossier_id, nom_original, chemin_stockage, mime_type, taille_octets, description, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $scope === 'global' ? null : (int)$residenceId,
            $dossierId !== null ? (int)$dossierId : null,
            $data['nom_original'],
            $data['chemin_stockage'],
            $data['mime_type'] ?? null,
            $data['taille_octets'] ?? null,
            $data['description'] ?? null,
            $uploadedBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Supprime un fichier (BDD + retourne le chemin pour effacement physique).
     * @return string|null chemin_stockage relatif, ou null si introuvable
     */
    public function deleteFichier(int $fichierId): ?string {
        $f = $this->findFichier($fichierId);
        if (!$f) return null;
        $this->db->prepare("DELETE FROM admin_fichiers WHERE id = ?")->execute([$fichierId]);
        return $f['chemin_stockage'];
    }

    // ─── STATS (info dashboard / header) ──────────────────────────

    public function getStats(string $scope, ?int $residenceId): array {
        $where = ($scope === 'global') ? 'residence_id IS NULL' : 'residence_id = ?';
        $params = ($scope === 'global') ? [] : [(int)$residenceId];

        $sqlD = "SELECT COUNT(*) FROM admin_dossiers WHERE $where";
        $sqlF = "SELECT COUNT(*) AS n, COALESCE(SUM(taille_octets), 0) AS taille FROM admin_fichiers WHERE $where";

        $stmtD = $this->db->prepare($sqlD); $stmtD->execute($params);
        $stmtF = $this->db->prepare($sqlF); $stmtF->execute($params);
        $f = $stmtF->fetch(PDO::FETCH_ASSOC);
        return [
            'nb_dossiers'    => (int)$stmtD->fetchColumn(),
            'nb_fichiers'    => (int)($f['n'] ?? 0),
            'taille_totale'  => (int)($f['taille'] ?? 0),
        ];
    }
}
