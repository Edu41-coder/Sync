<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Calendrier Résident
 * ====================================================================
 * Gestion du calendrier personnel du résident senior :
 *  - Événements manuels stockés en `planning_resident`
 *  - Catégories en `planning_resident_categories` (6 : loyer, animation,
 *    medical, famille, fiscal, autre)
 *  - Auto-génération côté contrôleur : loyers mensuels, animations
 *    résidence (lecture `planning_shifts`), rappels fiscaux
 */
class ResidentCalendar extends Model {

    /**
     * Catégories du planning résident (avec flag auto_genere calculé)
     */
    public function getPlanningCategories(): array {
        $sql = "SELECT * FROM planning_resident_categories WHERE actif = 1 ORDER BY ordre";
        try {
            $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $autoSlugs = ['loyer', 'animation', 'fiscal'];
            foreach ($rows as &$r) {
                $r['auto_genere'] = in_array($r['slug'], $autoSlugs, true) ? 1 : 0;
            }
            return $rows;
        } catch (PDOException $e) {
            $this->logError("Erreur getPlanningCategories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Catégorie par slug (id, couleur, bg_couleur)
     */
    public function getCategorieBySlug(string $slug): ?array {
        $sql = "SELECT id, couleur, bg_couleur FROM planning_resident_categories WHERE slug = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError("Erreur getCategorieBySlug: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Événements manuels du calendrier sur une période
     */
    public function getEvenements(int $residentId, string $start, string $end): array {
        $sql = "SELECT p.*, c.slug as cat_slug, c.couleur, c.bg_couleur, c.icone
                FROM planning_resident p
                LEFT JOIN planning_resident_categories c ON p.category_id = c.id
                WHERE p.resident_id = ? AND p.date_debut < ? AND p.date_fin > ?
                ORDER BY p.date_debut";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residentId, $end, $start]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getEvenements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Sauvegarder un événement (création ou mise à jour)
     */
    public function saveEvenement(int $residentId, array $data): int {
        $id = $data['id'] ?? null;

        if ($id && is_numeric($id)) {
            $sql = "UPDATE planning_resident
                    SET category_id = ?, titre = ?, description = ?, date_debut = ?, date_fin = ?, journee_entiere = ?, updated_at = NOW()
                    WHERE id = ? AND resident_id = ?";
            $this->db->prepare($sql)->execute([
                $data['categoryId'] ?: null,
                $data['title'],
                $data['description'] ?: null,
                $data['start'],
                $data['end'],
                $data['isAllDay'] ? 1 : 0,
                (int)$id,
                $residentId
            ]);
            return (int)$id;
        }

        $sql = "INSERT INTO planning_resident
                (resident_id, category_id, titre, description, date_debut, date_fin, journee_entiere, auto_genere)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $this->db->prepare($sql)->execute([
            $residentId,
            $data['categoryId'] ?: null,
            $data['title'],
            $data['description'] ?: null,
            $data['start'],
            $data['end'],
            $data['isAllDay'] ? 1 : 0
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Drag & drop : déplacer un événement (manuel uniquement)
     */
    public function moveEvenement(int $residentId, int $id, string $start, string $end): bool {
        $sql = "UPDATE planning_resident SET date_debut = ?, date_fin = ?, updated_at = NOW()
                WHERE id = ? AND resident_id = ? AND auto_genere = 0";
        return $this->db->prepare($sql)->execute([$start, $end, $id, $residentId]);
    }

    /**
     * Supprimer un événement (manuel uniquement)
     */
    public function deleteEvenement(int $residentId, int $id): bool {
        $sql = "DELETE FROM planning_resident WHERE id = ? AND resident_id = ? AND auto_genere = 0";
        return $this->db->prepare($sql)->execute([$id, $residentId]);
    }

    /**
     * Animations des résidences où le résident a une occupation active
     * (lecture de planning_shifts pour la catégorie 'animation')
     */
    public function getAnimationsResidences(int $residentId, string $start, string $end): array {
        $sql = "SELECT s.id, s.titre, s.description, s.date_debut, s.date_fin, s.journee_entiere,
                       cop.nom as residence_nom
                FROM planning_shifts s
                JOIN planning_categories cat ON s.category_id = cat.id
                JOIN coproprietees cop ON s.residence_id = cop.id
                WHERE cat.slug = 'animation'
                  AND s.statut IN ('planifie','confirme','en_cours')
                  AND s.residence_id IN (
                      SELECT DISTINCT l.copropriete_id
                      FROM occupations_residents o
                      JOIN lots l ON o.lot_id = l.id
                      WHERE o.resident_id = ? AND o.statut = 'actif'
                  )
                  AND s.date_debut < ? AND s.date_fin > ?
                ORDER BY s.date_debut";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residentId, $end, $start]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAnimationsResidences: " . $e->getMessage());
            return [];
        }
    }
}
