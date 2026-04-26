<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Coproprietaire (Propriétaires)
 * ====================================================================
 * Encapsule toutes les requêtes SQL liées aux propriétaires,
 * leurs contrats, fiscalité, résidences et calendrier.
 */

class Coproprietaire extends Model {

    protected $table = 'coproprietaires';

    // ─────────────────────────────────────────────────────────────
    //  LOOKUP
    // ─────────────────────────────────────────────────────────────

    /**
     * Trouver un propriétaire par user_id
     */
    public function findByUserId(int $userId): ?array {
        $sql = "SELECT * FROM coproprietaires WHERE user_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return null;
        }
    }

    /**
     * Trouver l'ID propriétaire par user_id (raccourci)
     */
    public function getIdByUserId(int $userId): ?int {
        $sql = "SELECT id FROM coproprietaires WHERE user_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $id = $stmt->fetchColumn();
            return $id !== false ? (int)$id : null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return null;
        }
    }

    /**
     * Trouver un propriétaire avec les infos du user lié
     */
    public function findWithUser(int $id): ?array {
        $sql = "SELECT cp.*, u.username, u.actif as user_actif, u.email as user_email, u.last_login
                FROM coproprietaires cp
                LEFT JOIN users u ON cp.user_id = u.id
                WHERE cp.id = ?";
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
    //  LISTES (ADMIN)
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste de tous les propriétaires avec statistiques (pour admin/index)
     */
    public function getAllWithStats(): array {
        $sql = "SELECT cp.*,
                   u.username, u.actif as user_actif, u.last_login,
                   COUNT(DISTINCT cg.id) as nb_contrats,
                   COUNT(DISTINCT CASE WHEN cg.statut = 'actif' THEN cg.id END) as nb_contrats_actifs,
                   COALESCE(SUM(CASE WHEN cg.statut = 'actif' THEN cg.loyer_mensuel_garanti END), 0) as revenus_mensuels
                FROM coproprietaires cp
                LEFT JOIN users u ON cp.user_id = u.id
                LEFT JOIN contrats_gestion cg ON cg.coproprietaire_id = cp.id
                GROUP BY cp.id
                ORDER BY cp.nom, cp.prenom";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CONTRATS
    // ─────────────────────────────────────────────────────────────

    /**
     * Contrats d'un propriétaire avec lot/résidence/marge (pour show)
     */
    public function getContrats(int $proprioId): array {
        $sql = "SELECT cg.*, l.numero_lot, l.type as lot_type, l.surface,
                   c.nom as residence_nom, c.ville as residence_ville,
                   o.loyer_mensuel_resident,
                   (o.loyer_mensuel_resident - cg.loyer_mensuel_garanti) as marge
                FROM contrats_gestion cg
                LEFT JOIN lots l ON cg.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN occupations_residents o ON o.lot_id = cg.lot_id AND o.statut = 'actif'
                WHERE cg.coproprietaire_id = ?
                ORDER BY cg.statut, c.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Contrats actifs simples (pour calendrier, contexte fiscal)
     */
    public function getContratsActifs(int $proprioId): array {
        $sql = "SELECT cg.*, l.numero_lot, c.nom as residence_nom
                FROM contrats_gestion cg
                LEFT JOIN lots l ON cg.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE cg.coproprietaire_id = ? AND cg.statut = 'actif'
                ORDER BY cg.date_debut DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Contrats détaillés avec info résident (pour monEspace)
     */
    public function getContratsDetailles(int $proprioId): array {
        $sql = "SELECT cg.*, l.numero_lot, l.type as lot_type, l.surface, l.etage, l.terrasse,
                   c.nom as residence_nom, c.ville as residence_ville,
                   o.loyer_mensuel_resident, o.statut as occupation_statut,
                   CONCAT(rs.prenom, ' ', rs.nom) as resident_nom,
                   (o.loyer_mensuel_resident - cg.loyer_mensuel_garanti) as marge
                FROM contrats_gestion cg
                LEFT JOIN lots l ON cg.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN occupations_residents o ON o.lot_id = cg.lot_id AND o.statut = 'actif'
                LEFT JOIN residents_seniors rs ON o.resident_id = rs.id
                WHERE cg.coproprietaire_id = ?
                ORDER BY cg.statut = 'actif' DESC, c.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Contrats pour comptabilité (avec charges)
     */
    public function getContratsComptabilite(int $proprioId): array {
        $sql = "SELECT cg.numero_contrat, cg.loyer_mensuel_garanti, cg.charges_mensuelles, cg.statut,
                   l.numero_lot, l.type as lot_type, c.nom as residence_nom
                FROM contrats_gestion cg
                LEFT JOIN lots l ON cg.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE cg.coproprietaire_id = ? ORDER BY cg.statut, c.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Contrats actifs pour contexte fiscal (avec dispositif, TVA)
     */
    public function getContratsFiscaux(int $proprioId): array {
        $sql = "SELECT cg.loyer_mensuel_garanti, cg.dispositif_fiscal, cg.statut_loueur,
                   cg.recuperation_tva, l.numero_lot, c.nom as residence
                FROM contrats_gestion cg
                LEFT JOIN lots l ON cg.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE cg.coproprietaire_id = ? AND cg.statut = 'actif'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Lots d'un propriétaire via contrats actifs (pour mesLots)
     */
    public function getMesLots(int $proprioId): array {
        $sql = "SELECT l.id, l.numero_lot, l.type, l.surface, l.etage, l.terrasse, l.nombre_pieces,
                   c.id as residence_id, c.nom as residence_nom, c.ville as residence_ville,
                   cg.numero_contrat, cg.loyer_mensuel_garanti, cg.statut as contrat_statut
                FROM contrats_gestion cg
                JOIN lots l ON cg.lot_id = l.id
                JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE cg.coproprietaire_id = ? AND cg.statut IN ('actif','projet')
                ORDER BY c.nom, l.numero_lot";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CONTRATS — CRÉATION (ADMIN)
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifier si un lot a déjà un contrat actif/projet
     */
    public function lotHasContratActif(int $lotId): bool {
        $sql = "SELECT COUNT(*) FROM contrats_gestion WHERE lot_id = ? AND statut IN ('actif','projet')";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lotId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$lotId]);
            return true; // sécurité : bloquer en cas d'erreur
        }
    }

    /**
     * Vérifier si un lot est lié à un autre propriétaire
     */
    public function getAutreProprietaireLot(int $lotId, int $excludeProprioId): ?array {
        $sql = "SELECT cp.prenom, cp.nom FROM contrats_gestion cg
                JOIN coproprietaires cp ON cg.coproprietaire_id = cp.id
                WHERE cg.lot_id = ? AND cg.statut IN ('actif','projet') AND cg.coproprietaire_id != ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lotId, $excludeProprioId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$lotId, $excludeProprioId]);
            return null;
        }
    }

    /**
     * Générer un numéro de contrat unique
     */
    public function generateNumeroContrat(): string {
        $annee = date('Y');
        try {
            $count = $this->db->query("SELECT COUNT(*) FROM contrats_gestion WHERE YEAR(created_at) = $annee")->fetchColumn();
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            $count = 0;
        }
        return 'DOM-' . $annee . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Créer un contrat de gestion
     */
    public function createContrat(int $proprioId, array $data): string {
        $numero = $this->generateNumeroContrat();

        $sql = "INSERT INTO contrats_gestion (
                lot_id, coproprietaire_id, exploitant_id, numero_contrat,
                type_contrat, date_signature, date_effet, date_fin,
                duree_initiale_annees, loyer_mensuel_garanti,
                indexation_type, charges_mensuelles,
                qui_paye_charges, qui_paye_travaux, qui_paye_taxe_fonciere,
                dispositif_fiscal, statut_loueur, recuperation_tva,
                garantie_loyer, meuble, mode_paiement,
                statut, notes, created_at
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$data['lot_id'],
            $proprioId,
            (int)$data['exploitant_id'],
            $numero,
            $data['type_contrat'] ?? 'bail_commercial',
            $data['date_signature'] ?: null,
            $data['date_effet'] ?: null,
            $data['date_fin'] ?: null,
            (int)($data['duree_initiale_annees'] ?? 9),
            (float)($data['loyer_mensuel_garanti'] ?? 0),
            $data['indexation_type'] ?? 'IRL',
            (float)($data['charges_mensuelles'] ?? 0),
            $data['qui_paye_charges'] ?? 'exploitant',
            $data['qui_paye_travaux'] ?? 'partage',
            $data['qui_paye_taxe_fonciere'] ?? 'proprietaire',
            $data['dispositif_fiscal'] ?? 'LMNP',
            $data['statut_loueur'] ?? 'LMNP',
            isset($data['recuperation_tva']) ? 1 : 0,
            isset($data['garantie_loyer']) ? 1 : 0,
            isset($data['meuble']) ? 1 : 0,
            $data['mode_paiement'] ?? 'virement',
            $data['statut_contrat'] ?? 'actif',
            trim($data['notes'] ?? '') ?: null,
        ]);

        return $numero;
    }

    // ─────────────────────────────────────────────────────────────
    //  FISCALITÉ
    // ─────────────────────────────────────────────────────────────

    /**
     * Données fiscales d'un propriétaire
     */
    public function getFiscalite(int $proprioId): array {
        $sql = "SELECT rf.*, l.numero_lot, c.nom as residence_nom
                FROM revenus_fiscaux_proprietaires rf
                LEFT JOIN lots l ON rf.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE rf.coproprietaire_id = ?
                ORDER BY rf.annee_fiscale DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Vérifier si une fiche fiscale existe déjà
     */
    public function fiscaliteExists(int $proprioId, int $annee, ?int $lotId): bool {
        $sql = "SELECT COUNT(*) FROM revenus_fiscaux_proprietaires
                WHERE coproprietaire_id = ? AND annee_fiscale = ?
                AND (lot_id = ? OR (lot_id IS NULL AND ? IS NULL))";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId, $annee, $lotId, $lotId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId, $annee, $lotId]);
            return true;
        }
    }

    /**
     * Créer une fiche fiscale
     */
    public function createFiscalite(int $proprioId, array $data): bool {
        $revenusBruts = (float)($data['revenus_bruts'] ?? 0);
        $charges = (float)($data['charges_deductibles'] ?? 0);
        $interets = (float)($data['interets_emprunt'] ?? 0);
        $travaux = (float)($data['travaux_deductibles'] ?? 0);
        $assurances = (float)($data['assurances_deductibles'] ?? 0);
        $taxeFonciere = (float)($data['taxe_fonciere_deductible'] ?? 0);
        $autresCharges = (float)($data['autres_charges_deductibles'] ?? 0);
        $amortissement = (float)($data['amortissement'] ?? 0);
        $totalCharges = $charges + $interets + $travaux + $assurances + $taxeFonciere + $autresCharges;
        $revenusNets = $revenusBruts - $totalCharges;
        $resultatFiscal = $revenusNets - $amortissement;

        $sql = "INSERT INTO revenus_fiscaux_proprietaires (
                coproprietaire_id, lot_id, contrat_gestion_id, annee_fiscale,
                revenus_bruts, charges_deductibles, interets_emprunt,
                travaux_deductibles, assurances_deductibles, taxe_fonciere_deductible,
                autres_charges_deductibles, revenus_nets, amortissement,
                regime_fiscal, statut_fiscal,
                reduction_censi_bouvard, recuperation_tva, credit_impot,
                resultat_fiscal, impot_estime, notes, created_at
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $proprioId,
            (int)($data['lot_id'] ?? 0) ?: null,
            (int)($data['contrat_gestion_id'] ?? 0) ?: null,
            (int)($data['annee_fiscale'] ?? date('Y') - 1),
            $revenusBruts, $charges, $interets,
            $travaux, $assurances, $taxeFonciere,
            $autresCharges, $revenusNets, $amortissement,
            $data['regime_fiscal'] ?? 'reel_simplifie',
            $data['statut_fiscal'] ?? 'LMNP',
            (float)($data['reduction_censi_bouvard'] ?? 0),
            (float)($data['recuperation_tva_montant'] ?? 0),
            (float)($data['credit_impot'] ?? 0),
            $resultatFiscal,
            (float)($data['impot_estime'] ?? 0),
            trim($data['notes'] ?? '') ?: null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  RÉSIDENCES
    // ─────────────────────────────────────────────────────────────

    /**
     * Résidences d'un propriétaire via contrats (pour mesResidences)
     */
    public function getMesResidences(int $proprioId): array {
        $sql = "SELECT DISTINCT c.*, e.raison_sociale as exploitant,
                    COUNT(DISTINCT cg.lot_id) as mes_lots,
                    COALESCE(SUM(CASE WHEN cg.statut='actif' THEN cg.loyer_mensuel_garanti END), 0) as revenus_mensuels,
                    (SELECT COUNT(DISTINCT l2.id) FROM lots l2 WHERE l2.copropriete_id = c.id) as total_lots
                FROM contrats_gestion cg
                JOIN lots l ON cg.lot_id = l.id
                JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN exploitant_residences er ON er.residence_id = c.id AND er.statut = 'actif'
                LEFT JOIN exploitants e ON er.exploitant_id = e.id
                WHERE cg.coproprietaire_id = ? AND cg.statut IN ('actif','projet')
                GROUP BY c.id
                ORDER BY c.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    /**
     * Résidences détaillées avec coordonnées (pour monEspace)
     */
    public function getMesResidencesDetaillees(int $proprioId): array {
        $sql = "SELECT DISTINCT c.id, c.nom, c.ville, c.adresse, c.code_postal, c.latitude, c.longitude,
                   e.raison_sociale as exploitant,
                   COUNT(DISTINCT cg2.lot_id) as mes_lots
                FROM contrats_gestion cg
                JOIN lots l ON cg.lot_id = l.id
                JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN exploitant_residences er ON er.residence_id = c.id AND er.statut = 'actif'
                LEFT JOIN exploitants e ON er.exploitant_id = e.id
                LEFT JOIN contrats_gestion cg2 ON cg2.coproprietaire_id = cg.coproprietaire_id
                    AND cg2.statut IN ('actif','projet')
                    AND EXISTS (SELECT 1 FROM lots l2 WHERE l2.id = cg2.lot_id AND l2.copropriete_id = c.id)
                WHERE cg.coproprietaire_id = ? AND cg.statut IN ('actif','projet')
                GROUP BY c.id, c.nom, c.ville, c.adresse, c.code_postal, c.latitude, c.longitude, e.raison_sociale
                ORDER BY c.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  FORMULAIRES (lots disponibles, exploitants)
    // ─────────────────────────────────────────────────────────────

    /**
     * Lots disponibles (sans contrat actif) pour formulaire contrat
     */
    public function getLotsDisponibles(): array {
        $sql = "SELECT l.id, l.numero_lot, l.type, l.surface, c.nom as residence_nom
                FROM lots l
                JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN contrats_gestion cg ON cg.lot_id = l.id AND cg.statut IN ('actif','projet')
                WHERE c.actif = 1 AND cg.id IS NULL
                ORDER BY c.nom, l.numero_lot";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Exploitants actifs pour formulaire contrat
     */
    public function getExploitantsActifs(): array {
        $sql = "SELECT id, raison_sociale FROM exploitants WHERE actif = 1 ORDER BY raison_sociale";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CALENDRIER PROPRIÉTAIRE
    // ─────────────────────────────────────────────────────────────

    /**
     * Catégories du planning propriétaire
     */
    public function getPlanningCategories(): array {
        $sql = "SELECT * FROM planning_proprio_categories WHERE actif = 1 ORDER BY ordre";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Catégorie par slug
     */
    public function getCategorieBySlug(string $slug): ?array {
        $sql = "SELECT id, couleur, bg_couleur FROM planning_proprio_categories WHERE slug = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$slug]);
            return null;
        }
    }

    /**
     * Événements manuels du calendrier
     */
    public function getEvenements(int $proprioId, string $start, string $end): array {
        $sql = "SELECT p.*, c.slug as cat_slug, c.couleur, c.bg_couleur, c.icone
                FROM planning_proprietaire p
                LEFT JOIN planning_proprio_categories c ON p.category_id = c.id
                WHERE p.coproprietaire_id = ? AND p.date_debut < ? AND p.date_fin > ?
                ORDER BY p.date_debut";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId, $end, $start]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId, $start, $end]);
            return [];
        }
    }

    /**
     * Sauvegarder un événement calendrier (création ou mise à jour)
     */
    public function saveEvenement(int $proprioId, array $data): int {
        $id = $data['id'] ?? null;

        if ($id && is_numeric($id)) {
            $sql = "UPDATE planning_proprietaire
                    SET category_id = ?, titre = ?, description = ?, date_debut = ?, date_fin = ?, journee_entiere = ?, updated_at = NOW()
                    WHERE id = ? AND coproprietaire_id = ?";
            $this->db->prepare($sql)->execute([
                $data['categoryId'] ?: null,
                $data['title'],
                $data['description'] ?: null,
                $data['start'],
                $data['end'],
                $data['isAllDay'] ? 1 : 0,
                $id,
                $proprioId
            ]);
            return (int)$id;
        }

        $sql = "INSERT INTO planning_proprietaire (coproprietaire_id, category_id, titre, description, date_debut, date_fin, journee_entiere)
                VALUES (?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $proprioId,
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
     * Déplacer un événement calendrier
     */
    public function moveEvenement(int $proprioId, int $id, string $start, string $end): bool {
        $sql = "UPDATE planning_proprietaire SET date_debut = ?, date_fin = ?, updated_at = NOW() WHERE id = ? AND coproprietaire_id = ?";
        return $this->db->prepare($sql)->execute([$start, $end, $id, $proprioId]);
    }

    /**
     * Supprimer un événement calendrier
     */
    public function deleteEvenement(int $proprioId, int $id): bool {
        $sql = "DELETE FROM planning_proprietaire WHERE id = ? AND coproprietaire_id = ?";
        return $this->db->prepare($sql)->execute([$id, $proprioId]);
    }

    // ─────────────────────────────────────────────────────────────
    //  DÉCLARATION FISCALE
    // ─────────────────────────────────────────────────────────────

    /**
     * Trouver ou créer une déclaration fiscale pour une année
     */
    public function getOrCreateDeclaration(int $proprioId, int $annee): array {
        $sql = "SELECT * FROM declarations_fiscales WHERE coproprietaire_id = ? AND annee_fiscale = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId, $annee]);
            $declaration = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($declaration) return $declaration;

            $this->db->prepare("INSERT INTO declarations_fiscales (coproprietaire_id, annee_fiscale) VALUES (?, ?)")
                ->execute([$proprioId, $annee]);

            return [
                'id' => $this->db->lastInsertId(),
                'annee_fiscale' => $annee,
                'statut' => 'en_cours',
                'donnees_extraites' => null,
                'recap_fiscal' => null
            ];
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId, $annee]);
            return ['id' => 0, 'annee_fiscale' => $annee, 'statut' => 'en_cours', 'donnees_extraites' => null, 'recap_fiscal' => null];
        }
    }

    /**
     * Documents d'une déclaration
     */
    public function getDeclarationDocuments(int $declarationId): array {
        $sql = "SELECT * FROM declaration_documents WHERE declaration_id = ? ORDER BY created_at";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$declarationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$declarationId]);
            return [];
        }
    }

    /**
     * Enregistrer un document uploadé
     */
    public function createDocument(int $declarationId, int $proprioId, array $data): int {
        $sql = "INSERT INTO declaration_documents
                (declaration_id, coproprietaire_id, type_document, nom_fichier, chemin_fichier, type_mime, taille, statut)
                VALUES (?,?,?,?,?,?,?,'uploade')";
        $this->db->prepare($sql)->execute([
            $declarationId, $proprioId,
            $data['type_document'],
            $data['nom_fichier'],
            $data['chemin_fichier'],
            $data['type_mime'],
            $data['taille']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Récupérer les documents d'un propriétaire par IDs
     */
    public function getDocumentsByIds(array $ids, int $proprioId): array {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM declaration_documents WHERE id IN ($placeholders) AND coproprietaire_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_merge($ids, [$proprioId]));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Mettre à jour un document après analyse IA
     */
    public function updateDocumentAnalyse(int $docId, string $analyse, ?float $montant = null, ?string $donneesJson = null): bool {
        if ($donneesJson) {
            $sql = "UPDATE declaration_documents SET statut='analyse', analyse_ia=?, montant_extrait=?, donnees_extraites=? WHERE id=?";
            return $this->db->prepare($sql)->execute([$analyse, $montant, $donneesJson, $docId]);
        }
        $sql = "UPDATE declaration_documents SET statut='analyse', analyse_ia=? WHERE id=?";
        return $this->db->prepare($sql)->execute([$analyse, $docId]);
    }

    /**
     * Accumuler les données extraites dans une déclaration
     */
    public function updateDeclarationDonnees(int $declarationId, string $type, array $extractedData): bool {
        try {
            $stmt = $this->db->prepare("SELECT donnees_extraites FROM declarations_fiscales WHERE id = ?");
            $stmt->execute([$declarationId]);
            $currentData = json_decode($stmt->fetchColumn() ?: '{}', true);
            $currentData[$type] = $extractedData;
            return $this->db->prepare("UPDATE declarations_fiscales SET donnees_extraites = ?, updated_at = NOW() WHERE id = ?")
                ->execute([json_encode($currentData), $declarationId]);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    /**
     * Documents déjà analysés pour une déclaration (pour le prompt fiscal)
     */
    public function getDocumentsAnalyses(int $declarationId): array {
        $sql = "SELECT type_document, donnees_extraites FROM declaration_documents WHERE declaration_id = ? AND statut = 'analyse'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$declarationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$declarationId]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CONTEXTE FISCAL (pour prompts IA)
    // ─────────────────────────────────────────────────────────────

    /**
     * Construire le contexte fiscal pour le prompt IA
     */
    public function buildFiscalContext(array $proprio): string {
        $contexte = "Propriétaire : {$proprio['prenom']} {$proprio['nom']}\n";

        $contrats = $this->getContratsFiscaux($proprio['id']);
        $totalLoyer = array_sum(array_column($contrats, 'loyer_mensuel_garanti'));
        $contexte .= "Lots : " . count($contrats) . "\n";
        $contexte .= "Loyer garanti total : {$totalLoyer} €/mois (" . ($totalLoyer * 12) . " €/an)\n";

        if (!empty($contrats[0])) {
            $contexte .= "Dispositif : " . ($contrats[0]['dispositif_fiscal'] ?? 'LMNP') . "\n";
            $contexte .= "Statut : " . ($contrats[0]['statut_loueur'] ?? 'LMNP') . "\n";
            $contexte .= "TVA : " . ($contrats[0]['recuperation_tva'] ? 'Oui' : 'Non') . "\n";
        }

        foreach ($contrats as $i => $c) {
            $contexte .= "Lot " . ($i + 1) . " : {$c['numero_lot']} ({$c['residence']}) - {$c['loyer_mensuel_garanti']} €/mois\n";
        }

        return $contexte;
    }

    /**
     * Construire le prompt système pour la déclaration fiscale guidée
     */
    public function buildFiscalPrompt(int $declarationId, string $contexte): string {
        $analyzedDocs = $this->getDocumentsAnalyses($declarationId);

        $docsContext = "";
        $typesAnalyses = [];
        foreach ($analyzedDocs as $ad) {
            $typesAnalyses[] = $ad['type_document'];
            if ($ad['donnees_extraites']) $docsContext .= "- {$ad['type_document']} : {$ad['donnees_extraites']}\n";
        }

        $typesManquants = array_diff(['releve_loyers','tableau_amortissement','taxe_fonciere','assurance_pno'], $typesAnalyses);
        $docLabels = [
            'releve_loyers' => 'relevé annuel des loyers',
            'tableau_amortissement' => 'tableau d\'amortissement du prêt',
            'taxe_fonciere' => 'avis de taxe foncière',
            'assurance_pno' => 'attestation d\'assurance PNO',
        ];
        $prochainDoc = !empty($typesManquants) ? array_values($typesManquants)[0] : null;
        $prochainLabel = $prochainDoc ? ($docLabels[$prochainDoc] ?? $prochainDoc) : null;

        return "Tu es un expert-comptable fiscal français spécialisé LMNP/LMP et résidences seniors Domitys.

CONTEXTE : {$contexte}

DOCS ANALYSÉS : " . ($docsContext ?: "Aucun.") . "
DOCS MANQUANTS : " . (!empty($typesManquants) ? implode(', ', array_map(fn($t) => $docLabels[$t] ?? $t, $typesManquants)) : "Tous fournis.") . "

INSTRUCTIONS :
1. Analyse chaque document uploadé, extrais les montants et indique les cases fiscales.
2. Après chaque analyse, demande le prochain document : " . ($prochainLabel ? "\"Uploadez votre **$prochainLabel**\"" : "\"Tous les docs sont là, voulez-vous le récapitulatif ?\"") . "
3. Ajoute les montants en JSON entre |||JSON||| et |||/JSON|||.
4. Pour le récapitulatif : liste les cases 2042-C-PRO / 2031/2033 avec numéro + montant.
5. Sois précis, arrondis à l'euro. Informations indicatives.";
    }

    // ─────────────────────────────────────────────────────────────
    //  ADMIN / DASHBOARD
    // ─────────────────────────────────────────────────────────────

    /**
     * IDs des lots d'un propriétaire (pour filtrage admin)
     */
    public function getLotIdsByProprietaire(int $proprioId): array {
        $sql = "SELECT lot_id FROM contrats_gestion WHERE coproprietaire_id = ? AND statut IN ('actif','projet')";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$proprioId]); return $stmt->fetchAll(PDO::FETCH_COLUMN); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql, [$proprioId]); return []; }
    }

    /**
     * Statistiques dashboard d'un propriétaire
     */
    public function getProprioStats(int $proprioId): array {
        $sql = "SELECT COUNT(DISTINCT cg.id) as total_contrats, COUNT(DISTINCT CASE WHEN cg.statut='actif' THEN cg.id END) as contrats_actifs,
                COUNT(DISTINCT cg.lot_id) as total_lots, COALESCE(SUM(CASE WHEN cg.statut='actif' THEN cg.loyer_mensuel_garanti END), 0) as revenus_mensuels,
                COUNT(DISTINCT l.copropriete_id) as total_residences
                FROM contrats_gestion cg LEFT JOIN lots l ON cg.lot_id = l.id WHERE cg.coproprietaire_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprioId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['revenus_annuels'] = $stats['revenus_mensuels'] * 12;
            return $stats;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$proprioId]);
            return ['total_contrats'=>0,'contrats_actifs'=>0,'total_lots'=>0,'revenus_mensuels'=>0,'revenus_annuels'=>0,'total_residences'=>0];
        }
    }
}
