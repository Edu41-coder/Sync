<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Service
 * ====================================================================
 * Gestion du catalogue de services (inclus et supplémentaires)
 */

class Service extends Model {

    protected $table = 'services';

    /**
     * Récupérer tous les services actifs, triés par catégorie puis ordre
     */
    public function getAllActifs(): array {
        $sql = "SELECT * FROM services WHERE actif = 1
                ORDER BY FIELD(categorie,'inclus','supplementaire'), ordre_affichage, nom";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les services par catégorie
     */
    public function getByCategorie(string $categorie): array {
        $stmt = $this->db->prepare("SELECT * FROM services WHERE actif = 1 AND categorie = ?
                                    ORDER BY ordre_affichage, nom");
        $stmt->execute([$categorie]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les services (actifs et inactifs) pour l'admin
     */
    public function getAll(): array {
        $sql = "SELECT s.*,
                    (SELECT COUNT(*) FROM occupation_services os WHERE os.service_id = s.id AND os.actif = 1) as nb_utilisations
                FROM services s
                ORDER BY FIELD(categorie,'inclus','supplementaire'), ordre_affichage, nom";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un service
     */
    public function createService(array $data): int|false {
        $sql = "INSERT INTO services (nom, slug, categorie, prix_defaut, description, icone, ordre_affichage, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nom'],
                $data['slug'],
                $data['categorie'] ?? 'inclus',
                $data['prix_defaut'] ?? 0,
                $data['description'] ?? null,
                $data['icone'] ?? 'fas fa-concierge-bell',
                $data['ordre_affichage'] ?? 0,
                $data['actif'] ?? 1
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logError("Erreur createService: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un service
     */
    public function updateService(int $id, array $data): bool {
        $sql = "UPDATE services SET nom=?, slug=?, categorie=?, prix_defaut=?, description=?, icone=?, ordre_affichage=?, actif=?, updated_at=NOW()
                WHERE id=?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['nom'],
                $data['slug'],
                $data['categorie'] ?? 'inclus',
                $data['prix_defaut'] ?? 0,
                $data['description'] ?? null,
                $data['icone'] ?? 'fas fa-concierge-bell',
                $data['ordre_affichage'] ?? 0,
                $data['actif'] ?? 1,
                $id
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur updateService: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un service (soft delete)
     */
    public function deleteService(int $id): bool {
        $sql = "UPDATE services SET actif = 0, updated_at = NOW() WHERE id = ?";
        try {
            return $this->db->prepare($sql)->execute([$id]);
        } catch (PDOException $e) {
            $this->logError("Erreur deleteService: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les services d'une occupation avec leur statut
     */
    public function getByOccupation(int $occupationId): array {
        $sql = "SELECT s.*, os.prix_applique, os.actif as souscrit, os.date_debut, os.date_fin, os.notes as os_notes
                FROM services s
                LEFT JOIN occupation_services os ON s.id = os.service_id AND os.occupation_id = ?
                WHERE s.actif = 1
                ORDER BY FIELD(s.categorie,'inclus','supplementaire'), s.ordre_affichage";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$occupationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Synchroniser les services d'une occupation
     * @param int $occupationId
     * @param array $serviceIds [service_id => prix_applique, ...]
     */
    public function syncOccupationServices(int $occupationId, array $serviceIds): bool {
        try {
            // Supprimer les anciens liens
            $this->db->prepare("DELETE FROM occupation_services WHERE occupation_id = ?")->execute([$occupationId]);

            if (!empty($serviceIds)) {
                $stmt = $this->db->prepare("INSERT INTO occupation_services (occupation_id, service_id, prix_applique, actif)
                                            VALUES (?, ?, ?, 1)");
                foreach ($serviceIds as $svcId => $prix) {
                    $stmt->execute([$occupationId, (int)$svcId, (float)$prix]);
                }
            }
            return true;
        } catch (PDOException $e) {
            $this->logError("Erreur syncOccupationServices: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer le montant total des services supplémentaires d'une occupation
     */
    public function getTotalSupplementaires(int $occupationId): float {
        $sql = "SELECT COALESCE(SUM(os.prix_applique), 0)
                FROM occupation_services os
                JOIN services s ON s.id = os.service_id
                WHERE os.occupation_id = ? AND os.actif = 1 AND s.categorie = 'supplementaire'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$occupationId]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Vérifier si un slug existe déjà
     */
    public function slugExists(string $slug, int $excludeId = 0): bool {
        $sql = "SELECT COUNT(*) FROM services WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
