<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle HoteTemporaire (Hôtes Temporaires)
 * ====================================================================
 * Encapsule toutes les requêtes SQL liées aux séjours temporaires
 * (réservations court séjour, pas de compte user).
 */

class HoteTemporaire extends Model {

    protected $table = 'hotes_temporaires';

    // ─────────────────────────────────────────────────────────────
    //  LISTES
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste de tous les hôtes avec résidence/lot et nb_nuits
     */
    public function getAll(): array {
        $sql = "SELECT h.*,
                   c.nom as residence_nom, c.ville as residence_ville,
                   l.numero_lot, l.type as lot_type,
                   DATEDIFF(h.date_depart_prevue, h.date_arrivee) as nb_nuits
                FROM hotes_temporaires h
                LEFT JOIN coproprietees c ON h.residence_id = c.id
                LEFT JOIN lots l ON h.lot_id = l.id
                ORDER BY h.date_arrivee DESC";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  LOOKUP
    // ─────────────────────────────────────────────────────────────

    /**
     * Détail complet d'un hôte avec résidence/lot (pour show)
     */
    public function findById(int $id): ?array {
        $sql = "SELECT h.*,
                   c.nom as residence_nom, c.ville as residence_ville, c.adresse as residence_adresse,
                   l.numero_lot, l.type as lot_type, l.surface, l.etage,
                   DATEDIFF(h.date_depart_prevue, h.date_arrivee) as nb_nuits
                FROM hotes_temporaires h
                LEFT JOIN coproprietees c ON h.residence_id = c.id
                LEFT JOIN lots l ON h.lot_id = l.id
                WHERE h.id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return null;
        }
    }

    /**
     * Enregistrement brut d'un hôte (pour edit)
     */
    public function findForEdit(int $id): ?array {
        $sql = "SELECT * FROM hotes_temporaires WHERE id = ?";
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
    //  FORMULAIRES (données de référence)
    // ─────────────────────────────────────────────────────────────

    /**
     * Résidences actives pour formulaire
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
     * Lots avec residence_id pour formulaire (filtre JS côté client)
     */
    public function getLots(): array {
        $sql = "SELECT l.id, l.numero_lot, l.type, c.id as residence_id
                FROM lots l
                JOIN coproprietees c ON l.copropriete_id = c.id
                ORDER BY c.nom, l.numero_lot";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CRÉATION / MISE À JOUR
    // ─────────────────────────────────────────────────────────────

    /**
     * Créer un hôte temporaire
     */
    public function createHote(array $data): int {
        $sql = "INSERT INTO hotes_temporaires
                (civilite, nom, prenom, date_naissance, nationalite,
                 type_piece_identite, numero_piece_identite,
                 telephone, telephone_mobile, email, adresse_domicile,
                 lot_id, residence_id, date_arrivee, date_depart_prevue,
                 nb_personnes, motif_sejour, prix_nuit, montant_total,
                 statut_paiement, statut, notes, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['civilite'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['nationalite'],
            $data['type_piece_identite'],
            $data['numero_piece_identite'],
            $data['telephone'],
            $data['telephone_mobile'],
            $data['email'],
            $data['adresse_domicile'],
            $data['lot_id'],
            $data['residence_id'],
            $data['date_arrivee'],
            $data['date_depart_prevue'],
            $data['nb_personnes'],
            $data['motif_sejour'],
            $data['prix_nuit'],
            $data['montant_total'],
            $data['statut_paiement'],
            $data['statut'],
            $data['notes'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Mettre à jour un hôte temporaire
     */
    public function updateHote(int $id, array $data): bool {
        $sql = "UPDATE hotes_temporaires SET
                civilite=?, nom=?, prenom=?, date_naissance=?, nationalite=?,
                type_piece_identite=?, numero_piece_identite=?,
                telephone=?, telephone_mobile=?, email=?, adresse_domicile=?,
                lot_id=?, residence_id=?,
                date_arrivee=?, date_depart_prevue=?, date_depart_effective=?,
                nb_personnes=?, motif_sejour=?, prix_nuit=?, montant_total=?,
                statut_paiement=?, statut=?, notes=?, updated_at=NOW()
                WHERE id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['civilite'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['nationalite'],
            $data['type_piece_identite'],
            $data['numero_piece_identite'],
            $data['telephone'],
            $data['telephone_mobile'],
            $data['email'],
            $data['adresse_domicile'],
            $data['lot_id'],
            $data['residence_id'],
            $data['date_arrivee'],
            $data['date_depart_prevue'],
            $data['date_depart_effective'],
            $data['nb_personnes'],
            $data['motif_sejour'],
            $data['prix_nuit'],
            $data['montant_total'],
            $data['statut_paiement'],
            $data['statut'],
            $data['notes'],
            $id,
        ]);
    }
}
