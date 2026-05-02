<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Specialite (maintenance technique)
 * ====================================================================
 * Gère le référentiel des 6 spécialités, l'affectation user × spécialité
 * et les certifications professionnelles.
 */
class Specialite extends Model {

    public const NIVEAUX = ['debutant', 'confirme', 'expert'];

    // ─── RÉFÉRENTIEL ────────────────────────────────────────────

    public function getAll(bool $actifSeul = true): array {
        $sql = "SELECT * FROM specialites" . ($actifSeul ? " WHERE actif = 1" : "") . " ORDER BY ordre";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM specialites WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM specialites WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ─── AFFECTATION USER × SPÉCIALITÉ ──────────────────────────

    /**
     * Liste des spécialités assignées à un user (avec niveau et date).
     */
    public function getUserSpecialites(int $userId): array {
        $sql = "SELECT s.*, us.niveau, us.affecte_le, us.notes,
                       u.prenom AS affecte_par_prenom, u.nom AS affecte_par_nom
                FROM user_specialites us
                JOIN specialites s ON s.id = us.specialite_id
                LEFT JOIN users u ON u.id = us.affecte_par
                WHERE us.user_id = ? AND s.actif = 1
                ORDER BY s.ordre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un user a une spécialité donnée (par slug).
     * Helper utilisable partout dans les contrôleurs.
     */
    public function userHasSpecialite(int $userId, string $slug): bool {
        $sql = "SELECT 1 FROM user_specialites us
                JOIN specialites s ON s.id = us.specialite_id
                WHERE us.user_id = ? AND s.slug = ? AND s.actif = 1
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $slug]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Liste des slugs de spécialités d'un user (pour navbar dynamique).
     */
    public function getUserSpecialitesSlugs(int $userId): array {
        $sql = "SELECT s.slug FROM user_specialites us
                JOIN specialites s ON s.id = us.specialite_id
                WHERE us.user_id = ? AND s.actif = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Affecte une spécialité à un user. Si déjà présente, met à jour le niveau.
     */
    public function affecter(int $userId, int $specialiteId, string $niveau, ?int $affectePar, ?string $notes = null): bool {
        if (!in_array($niveau, self::NIVEAUX, true)) {
            throw new InvalidArgumentException("Niveau invalide : $niveau");
        }
        $sql = "INSERT INTO user_specialites (user_id, specialite_id, niveau, affecte_par, notes)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE niveau = VALUES(niveau), affecte_par = VALUES(affecte_par), notes = VALUES(notes)";
        return $this->db->prepare($sql)->execute([$userId, $specialiteId, $niveau, $affectePar, $notes]);
    }

    public function retirer(int $userId, int $specialiteId): bool {
        $sql = "DELETE FROM user_specialites WHERE user_id = ? AND specialite_id = ?";
        return $this->db->prepare($sql)->execute([$userId, $specialiteId]);
    }

    /**
     * Matrice complète : tous les users staff (technicien, technicien_chef)
     * avec leurs spécialités cochées. Pour la vue de gestion.
     */
    public function getMatriceTechniciens(): array {
        $sql = "SELECT u.id AS user_id, u.username, u.prenom, u.nom, u.role,
                       u.actif, u.email, u.telephone,
                       GROUP_CONCAT(CONCAT(us.specialite_id, ':', us.niveau) SEPARATOR ',') AS specs_brut
                FROM users u
                LEFT JOIN user_specialites us ON us.user_id = u.id
                WHERE u.role IN ('technicien', 'technicien_chef') AND u.actif = 1
                GROUP BY u.id
                ORDER BY u.role DESC, u.nom, u.prenom";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // Parse specs_brut → ['specialite_id' => 'niveau']
        foreach ($rows as &$r) {
            $specs = [];
            if (!empty($r['specs_brut'])) {
                foreach (explode(',', $r['specs_brut']) as $pair) {
                    [$id, $niveau] = explode(':', $pair);
                    $specs[(int)$id] = $niveau;
                }
            }
            $r['specs'] = $specs;
            unset($r['specs_brut']);
        }
        return $rows;
    }

    // ─── CERTIFICATIONS ─────────────────────────────────────────

    public function getCertifications(int $userId, bool $actifSeul = true): array {
        $sql = "SELECT c.*, s.nom AS specialite_nom, s.slug AS specialite_slug
                FROM user_certifications c
                LEFT JOIN specialites s ON s.id = c.specialite_id
                WHERE c.user_id = ?" . ($actifSeul ? " AND c.actif = 1" : "") . "
                ORDER BY c.date_expiration IS NULL, c.date_expiration ASC, c.date_obtention DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCertification(int $id, ?int $userId = null): ?array {
        $sql = "SELECT c.*, s.nom AS specialite_nom FROM user_certifications c
                LEFT JOIN specialites s ON s.id = c.specialite_id
                WHERE c.id = ?";
        $params = [$id];
        if ($userId !== null) { $sql .= " AND c.user_id = ?"; $params[] = $userId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createCertification(array $data): int {
        $sql = "INSERT INTO user_certifications
                (user_id, specialite_id, nom, organisme, numero_certificat, date_obtention, date_expiration, fichier_preuve, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['user_id'],
            $data['specialite_id'] ?: null,
            $data['nom'],
            $data['organisme'] ?: null,
            $data['numero_certificat'] ?: null,
            $data['date_obtention'],
            $data['date_expiration'] ?: null,
            $data['fichier_preuve'] ?: null,
            $data['notes'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteCertification(int $id, ?int $userId = null): ?string {
        $cert = $this->findCertification($id, $userId);
        if (!$cert) return null;
        $sql = "DELETE FROM user_certifications WHERE id = ?";
        $params = [$id];
        if ($userId !== null) { $sql .= " AND user_id = ?"; $params[] = $userId; }
        $this->db->prepare($sql)->execute($params);
        return $cert['fichier_preuve'];
    }

    /**
     * Certifications expirant dans les N mois à venir (pour alertes dashboard).
     */
    public function getCertificationsExpirantes(int $monthsAhead = 3): array {
        $sql = "SELECT c.*, s.nom AS specialite_nom, u.username, u.prenom, u.nom AS user_nom
                FROM user_certifications c
                JOIN users u ON u.id = c.user_id
                LEFT JOIN specialites s ON s.id = c.specialite_id
                WHERE c.actif = 1
                  AND c.date_expiration IS NOT NULL
                  AND c.date_expiration <= DATE_ADD(CURDATE(), INTERVAL ? MONTH)
                  AND c.date_expiration >= CURDATE()
                ORDER BY c.date_expiration ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$monthsAhead]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
