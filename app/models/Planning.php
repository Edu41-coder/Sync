<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Planning Staff
 * ====================================================================
 * Encapsule toutes les requêtes SQL liées au planning des employés.
 */

class Planning extends Model {

    protected $table = 'planning_shifts';

    private const STAFF_ROLES = "'directeur_residence','employe_residence','technicien','jardinier_manager','jardinier_employe','entretien_manager','menage_interieur','menage_exterieur','restauration_manager','restauration_serveur','restauration_cuisine','comptable','employe_laverie'";

    // ─────────────────────────────────────────────────────────────
    //  DONNÉES DE RÉFÉRENCE (vue principale)
    // ─────────────────────────────────────────────────────────────

    /**
     * Catégories de planning actives
     */
    public function getCategories(): array {
        $sql = "SELECT * FROM planning_categories WHERE actif=1 ORDER BY ordre_affichage";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Employés staff pour le select
     */
    public function getStaffEmployees(): array {
        $sql = "SELECT id, nom, prenom, role FROM users WHERE actif=1 AND role IN (" . self::STAFF_ROLES . ") ORDER BY nom, prenom";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Résidences actives
     */
    public function getResidences(): array {
        $sql = "SELECT id, nom, ville FROM coproprietees WHERE actif=1 ORDER BY nom";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Liaisons employé ↔ résidence pour filtrage croisé
     */
    public function getUserResidences(): array {
        $sql = "SELECT user_id, residence_id FROM user_residence WHERE statut='actif'";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  SHIFTS — LECTURE
    // ─────────────────────────────────────────────────────────────

    /**
     * Récupérer les shifts pour une période avec filtres optionnels
     */
    public function getShifts(string $start, string $end, int $residenceId = 0, int $userId = 0): array {
        $sql = "SELECT s.*, u.nom as employe_nom, u.prenom as employe_prenom, u.role as employe_role,
                       c.nom as residence_nom,
                       pc.nom as categorie_nom, pc.couleur, pc.bg_couleur, pc.border_couleur
                FROM planning_shifts s
                JOIN users u ON s.user_id = u.id
                JOIN coproprietees c ON s.residence_id = c.id
                LEFT JOIN planning_categories pc ON s.category_id = pc.id
                WHERE s.date_debut < ? AND s.date_fin > ?";
        $params = [$end, $start];

        if ($residenceId > 0) {
            $sql .= " AND s.residence_id = ?";
            $params[] = $residenceId;
        }
        if ($userId > 0) {
            $sql .= " AND s.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY s.date_debut";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }

    /**
     * Récupérer un shift par ID avec détails
     */
    public function getShift(int $id): ?array {
        $sql = "SELECT s.*, u.nom as employe_nom, u.prenom as employe_prenom,
                       c.nom as residence_nom,
                       pc.nom as categorie_nom, pc.couleur, pc.bg_couleur
                FROM planning_shifts s
                JOIN users u ON s.user_id = u.id
                JOIN coproprietees c ON s.residence_id = c.id
                LEFT JOIN planning_categories pc ON s.category_id = pc.id
                WHERE s.id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  SHIFTS — ÉCRITURE
    // ─────────────────────────────────────────────────────────────

    /**
     * Créer ou modifier un shift (retourne l'ID)
     */
    public function saveShift(array $data): int {
        $id = $data['id'] ?? null;

        if ($id) {
            $sql = "UPDATE planning_shifts SET
                user_id=?, residence_id=?, category_id=?, titre=?, description=?,
                date_debut=?, date_fin=?, journee_entiere=?, type_shift=?, type_heures=?, notes=?,
                updated_at=NOW() WHERE id=?";
            $this->db->prepare($sql)->execute([
                $data['userId'], $data['residenceId'], $data['categoryId'],
                $data['title'], $data['description'] ?: null,
                $data['start'], $data['end'], $data['isAllDay'] ? 1 : 0,
                $data['typeShift'], $data['typeHeures'], $data['notes'] ?: null,
                $id
            ]);
            return (int)$id;
        }

        $sql = "INSERT INTO planning_shifts
            (user_id, residence_id, category_id, titre, description, date_debut, date_fin,
             journee_entiere, type_shift, type_heures, notes, statut, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,'planifie',NOW())";
        $this->db->prepare($sql)->execute([
            $data['userId'], $data['residenceId'], $data['categoryId'],
            $data['title'], $data['description'] ?: null,
            $data['start'], $data['end'], $data['isAllDay'] ? 1 : 0,
            $data['typeShift'], $data['typeHeures'], $data['notes'] ?: null
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Déplacer un shift (drag & drop)
     */
    public function moveShift(int $id, string $start, string $end): bool {
        $sql = "UPDATE planning_shifts SET date_debut=?, date_fin=?, updated_at=NOW() WHERE id=?";
        return $this->db->prepare($sql)->execute([$start, $end, $id]);
    }

    /**
     * Supprimer un shift
     */
    public function deleteShift(int $id): bool {
        $sql = "DELETE FROM planning_shifts WHERE id=?";
        return $this->db->prepare($sql)->execute([$id]);
    }
}
