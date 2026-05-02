<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Fournisseur (global)
 * ====================================================================
 * Gère les fournisseurs communs à tous les modules (restauration, ménage,
 * jardinage, piscine, travaux électricité, travaux plomberie, autre).
 *
 * Relations :
 * - N:M avec coproprietees via `fournisseur_residence` (contact/livraison local)
 * - N:M avec produits modules via `menage_produit_fournisseurs`,
 *   `rest_produit_fournisseurs`, `jardin_produit_fournisseurs` (phase 1b)
 */

class Fournisseur extends Model {

    public const TYPES_SERVICE = [
        'restauration'      => 'Restauration',
        'menage'            => 'Ménage',
        'jardinage'         => 'Jardinage',
        'piscine'           => 'Piscine',
        'travaux_elec'      => 'Électricité',
        'travaux_plomberie' => 'Plomberie',
        'autre'             => 'Autre',
    ];

    // ─────────────────────────────────────────────────────────────
    //  CRUD fournisseurs
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste complète, filtrable par type_service (contenant au moins un type) et recherche libre.
     */
    public function getAll(?string $typeService = null, ?string $search = null, bool $actifsOnly = false): array {
        $sql = "SELECT f.*,
                       (SELECT COUNT(*) FROM fournisseur_residence fr WHERE fr.fournisseur_id = f.id AND fr.statut = 'actif') as nb_residences
                FROM fournisseurs f
                WHERE 1=1";
        $params = [];
        if ($actifsOnly) $sql .= " AND f.actif = 1";
        if ($typeService) {
            $sql .= " AND FIND_IN_SET(?, f.type_service) > 0";
            $params[] = $typeService;
        }
        if ($search) {
            $sql .= " AND (f.nom LIKE ? OR f.email LIKE ? OR f.ville LIKE ? OR f.siret LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        $sql .= " ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function get(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM fournisseurs WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create(array $d): int {
        // type_service : array → CSV pour SET MariaDB
        $types = $this->normalizeTypes($d['type_service'] ?? []);
        $sql = "INSERT INTO fournisseurs
                (nom, siret, adresse, code_postal, ville, telephone, email, contact_nom,
                 type_service, iban, actif, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($d['nom']),
            !empty($d['siret']) ? trim($d['siret']) : null,
            !empty($d['adresse']) ? trim($d['adresse']) : null,
            !empty($d['code_postal']) ? trim($d['code_postal']) : null,
            !empty($d['ville']) ? trim($d['ville']) : null,
            !empty($d['telephone']) ? trim($d['telephone']) : null,
            !empty($d['email']) ? trim($d['email']) : null,
            !empty($d['contact_nom']) ? trim($d['contact_nom']) : null,
            $types,
            !empty($d['iban']) ? trim($d['iban']) : null,
            isset($d['actif']) ? (int)$d['actif'] : 1,
            !empty($d['notes']) ? trim($d['notes']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): void {
        $types = $this->normalizeTypes($d['type_service'] ?? []);
        $sql = "UPDATE fournisseurs SET
                    nom = ?, siret = ?, adresse = ?, code_postal = ?, ville = ?,
                    telephone = ?, email = ?, contact_nom = ?, type_service = ?,
                    iban = ?, actif = ?, notes = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($d['nom']),
            !empty($d['siret']) ? trim($d['siret']) : null,
            !empty($d['adresse']) ? trim($d['adresse']) : null,
            !empty($d['code_postal']) ? trim($d['code_postal']) : null,
            !empty($d['ville']) ? trim($d['ville']) : null,
            !empty($d['telephone']) ? trim($d['telephone']) : null,
            !empty($d['email']) ? trim($d['email']) : null,
            !empty($d['contact_nom']) ? trim($d['contact_nom']) : null,
            $types,
            !empty($d['iban']) ? trim($d['iban']) : null,
            isset($d['actif']) ? (int)$d['actif'] : 1,
            !empty($d['notes']) ? trim($d['notes']) : null,
            $id
        ]);
    }

    /**
     * Soft delete (actif = 0). Les liens pivot sont conservés mais le fournisseur
     * n'apparaît plus dans les listes de création de commande.
     */
    public function softDelete(int $id): void {
        $this->db->prepare("UPDATE fournisseurs SET actif = 0 WHERE id = ?")->execute([$id]);
    }

    public function activate(int $id): void {
        $this->db->prepare("UPDATE fournisseurs SET actif = 1 WHERE id = ?")->execute([$id]);
    }

    /**
     * Conversion tableau de types → CSV pour SET MariaDB, en filtrant les valeurs valides.
     */
    private function normalizeTypes($types): string {
        if (is_string($types)) return $types; // déjà en CSV
        if (!is_array($types)) return '';
        $valid = array_keys(self::TYPES_SERVICE);
        $filtered = array_filter($types, fn($t) => in_array($t, $valid, true));
        return implode(',', $filtered);
    }

    // ─────────────────────────────────────────────────────────────
    //  Pivot fournisseur_residence
    // ─────────────────────────────────────────────────────────────

    /**
     * Résidences liées à un fournisseur (pour page détail fournisseur).
     */
    public function getResidencesLiees(int $fournisseurId): array {
        $sql = "SELECT c.id, c.nom, c.ville,
                       fr.id as pivot_id, fr.statut, fr.contact_local, fr.telephone_local,
                       fr.jour_livraison, fr.delai_livraison_jours, fr.notes as pivot_notes
                FROM fournisseur_residence fr
                JOIN coproprietees c ON fr.residence_id = c.id
                WHERE fr.fournisseur_id = ?
                ORDER BY fr.statut DESC, c.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$fournisseurId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Fournisseurs liés à une résidence, filtrable par type_service (pour UI des modules).
     */
    public function getFournisseursResidence(int $residenceId, ?string $typeService = null, bool $actifsOnly = true): array {
        $sql = "SELECT f.*, fr.id as pivot_id, fr.statut as lien_statut,
                       fr.contact_local, fr.telephone_local,
                       fr.jour_livraison, fr.delai_livraison_jours, fr.notes as pivot_notes
                FROM fournisseurs f
                JOIN fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.residence_id = ?
                WHERE 1=1";
        $params = [$residenceId];
        if ($actifsOnly) $sql .= " AND f.actif = 1 AND fr.statut = 'actif'";
        if ($typeService) {
            $sql .= " AND FIND_IN_SET(?, f.type_service) > 0";
            $params[] = $typeService;
        }
        $sql .= " ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Fournisseurs actifs NON liés à cette résidence (pour ajouter une liaison).
     * Filtre optionnel par type_service.
     */
    public function getFournisseursDisponibles(int $residenceId, ?string $typeService = null): array {
        $sql = "SELECT f.id, f.nom, f.type_service
                FROM fournisseurs f
                WHERE f.actif = 1
                  AND f.id NOT IN (
                    SELECT fournisseur_id FROM fournisseur_residence
                    WHERE residence_id = ? AND statut = 'actif'
                  )";
        $params = [$residenceId];
        if ($typeService) {
            $sql .= " AND FIND_IN_SET(?, f.type_service) > 0";
            $params[] = $typeService;
        }
        $sql .= " ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getLien(int $pivotId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM fournisseur_residence WHERE id = ?");
        $stmt->execute([$pivotId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function lier(int $fournisseurId, int $residenceId, array $data = []): int {
        $sql = "INSERT INTO fournisseur_residence
                (fournisseur_id, residence_id, statut, contact_local, telephone_local, jour_livraison, delai_livraison_jours, notes)
                VALUES (?, ?, 'actif', ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    statut = 'actif',
                    contact_local = VALUES(contact_local),
                    telephone_local = VALUES(telephone_local),
                    jour_livraison = VALUES(jour_livraison),
                    delai_livraison_jours = VALUES(delai_livraison_jours),
                    notes = VALUES(notes)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $fournisseurId, $residenceId,
            !empty($data['contact_local']) ? trim($data['contact_local']) : null,
            !empty($data['telephone_local']) ? trim($data['telephone_local']) : null,
            !empty($data['jour_livraison']) ? trim($data['jour_livraison']) : null,
            !empty($data['delai_livraison_jours']) ? (int)$data['delai_livraison_jours'] : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        $sel = $this->db->prepare("SELECT id FROM fournisseur_residence WHERE fournisseur_id = ? AND residence_id = ?");
        $sel->execute([$fournisseurId, $residenceId]);
        return (int)$sel->fetchColumn();
    }

    public function updateLien(int $pivotId, array $data): void {
        $sql = "UPDATE fournisseur_residence
                SET contact_local = ?, telephone_local = ?, jour_livraison = ?, delai_livraison_jours = ?, notes = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            !empty($data['contact_local']) ? trim($data['contact_local']) : null,
            !empty($data['telephone_local']) ? trim($data['telephone_local']) : null,
            !empty($data['jour_livraison']) ? trim($data['jour_livraison']) : null,
            !empty($data['delai_livraison_jours']) ? (int)$data['delai_livraison_jours'] : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
            $pivotId
        ]);
    }

    public function delier(int $pivotId): void {
        $this->db->prepare("UPDATE fournisseur_residence SET statut = 'inactif' WHERE id = ?")->execute([$pivotId]);
    }

    /**
     * Commandes passées à un fournisseur (table unifiée `commandes`, tous modules).
     */
    public function getCommandesDuFournisseur(int $fournisseurId, int $limit = 50): array {
        $sql = "SELECT c.module, c.id, c.numero_commande, c.date_commande, c.statut,
                       c.montant_total_ttc, r.nom as residence_nom
                FROM commandes c
                JOIN coproprietees r ON c.residence_id = r.id
                WHERE c.fournisseur_id = ?
                ORDER BY c.date_commande DESC, c.id DESC
                LIMIT ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $fournisseurId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Compte les commandes actives (non annulées, non facturées clôturées) — pour garde-fou suppression.
     */
    public function countCommandesActives(int $fournisseurId): int {
        $sql = "SELECT COUNT(*) FROM commandes
                WHERE fournisseur_id = ? AND statut NOT IN ('annulee','facturee')";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fournisseurId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) { $this->logError($e->getMessage()); return 0; }
    }

    /**
     * Statistiques agrégées d'un fournisseur pour la page détail.
     * Retourne KPIs globaux + répartition CA par module.
     */
    public function getStatsFournisseur(int $fournisseurId): array {
        $kpis = [
            'nb_total'          => 0,
            'nb_en_cours'       => 0,
            'ca_total_ttc'      => 0.0,
            'delai_moyen_jours' => null,
            'par_module'        => [],
        ];

        // KPIs globaux
        $sql = "SELECT
                    COUNT(*) AS nb_total,
                    SUM(CASE WHEN statut IN ('envoyee','livree_partiel') THEN 1 ELSE 0 END) AS nb_en_cours,
                    SUM(CASE WHEN statut NOT IN ('annulee','brouillon') THEN montant_total_ttc ELSE 0 END) AS ca_total_ttc,
                    AVG(CASE WHEN date_livraison_effective IS NOT NULL
                                  AND date_commande IS NOT NULL
                                  AND statut NOT IN ('annulee')
                             THEN DATEDIFF(date_livraison_effective, date_commande)
                             ELSE NULL END) AS delai_moyen_jours
                FROM commandes WHERE fournisseur_id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fournisseurId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $kpis['nb_total']          = (int)$row['nb_total'];
                $kpis['nb_en_cours']       = (int)$row['nb_en_cours'];
                $kpis['ca_total_ttc']      = (float)$row['ca_total_ttc'];
                $kpis['delai_moyen_jours'] = $row['delai_moyen_jours'] !== null ? round((float)$row['delai_moyen_jours'], 1) : null;
            }
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); }

        // Répartition CA par module (commandes valides uniquement)
        $sql2 = "SELECT module, COUNT(*) AS nb, SUM(montant_total_ttc) AS total_ttc
                 FROM commandes
                 WHERE fournisseur_id = ? AND statut NOT IN ('annulee','brouillon')
                 GROUP BY module
                 ORDER BY total_ttc DESC";
        try {
            $stmt = $this->db->prepare($sql2);
            $stmt->execute([$fournisseurId]);
            $kpis['par_module'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql2); }

        return $kpis;
    }

    /**
     * Liste simple des résidences (pour la modal "lier").
     */
    public function getAllResidences(): array {
        $sql = "SELECT id, nom, ville FROM coproprietees WHERE actif = 1 AND type_residence = 'residence_seniors' ORDER BY nom";
        try { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ═════════════════════════════════════════════════════════════════
    //  PIVOT POLYMORPHE produit_fournisseurs (Option B)
    // ═════════════════════════════════════════════════════════════════

    public const MODULES_AUTORISES = ['restauration','menage','jardinage','travaux','piscine','entretien','laverie','autre'];

    private function assertModule(string $module): void {
        if (!in_array($module, self::MODULES_AUTORISES, true)) {
            throw new Exception("Module invalide : $module");
        }
    }

    /**
     * Liste des fournisseurs attachés à un produit donné, avec données du pivot.
     * @param string $module 'restauration'|'menage'|'jardinage'|...
     */
    public function getFournisseursDuProduit(string $module, int $produitId): array {
        $this->assertModule($module);
        $sql = "SELECT f.id, f.nom, f.type_service, f.email, f.telephone, f.actif,
                       pf.id as pivot_id, pf.prix_unitaire_specifique, pf.reference_fournisseur,
                       pf.fournisseur_prefere, pf.notes as pivot_notes
                FROM produit_fournisseurs pf
                JOIN fournisseurs f ON pf.fournisseur_id = f.id
                WHERE pf.produit_module = ? AND pf.produit_id = ?
                ORDER BY pf.fournisseur_prefere DESC, f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$module, $produitId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Produits attachés à un fournisseur donné, filtrable par module.
     * LEFT JOIN sur les 3 tables produits + COALESCE : une seule ligne matche par pivot (produit_module).
     */
    public function getProduitsDuFournisseur(int $fournisseurId, ?string $module = null): array {
        $sql = "SELECT pf.produit_module, pf.produit_id,
                       COALESCE(r.nom, m.nom, j.nom) as produit_nom,
                       COALESCE(r.unite, m.unite, j.unite) as unite,
                       pf.prix_unitaire_specifique, pf.reference_fournisseur,
                       pf.fournisseur_prefere, pf.notes as pivot_notes, pf.id as pivot_id
                FROM produit_fournisseurs pf
                LEFT JOIN rest_produits   r ON pf.produit_id = r.id AND pf.produit_module = 'restauration'
                LEFT JOIN menage_produits m ON pf.produit_id = m.id AND pf.produit_module = 'menage'
                LEFT JOIN jardin_produits j ON pf.produit_id = j.id AND pf.produit_module = 'jardinage'
                WHERE pf.fournisseur_id = ?";
        $params = [$fournisseurId];
        if ($module) {
            $sql .= " AND pf.produit_module = ?";
            $params[] = $module;
        }
        $sql .= " ORDER BY pf.produit_module, produit_nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Lier un fournisseur à un produit (INSERT ou UPDATE si déjà existant).
     */
    public function attachToProduit(string $module, int $produitId, int $fournisseurId, array $data = []): int {
        $this->assertModule($module);
        $sql = "INSERT INTO produit_fournisseurs
                (produit_module, produit_id, fournisseur_id, prix_unitaire_specifique,
                 reference_fournisseur, fournisseur_prefere, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    prix_unitaire_specifique = VALUES(prix_unitaire_specifique),
                    reference_fournisseur    = VALUES(reference_fournisseur),
                    fournisseur_prefere      = VALUES(fournisseur_prefere),
                    notes                    = VALUES(notes)";

        // Prix : isset (pas !empty) pour préserver 0 (échantillon/offert).
        $prix = isset($data['prix_unitaire_specifique']) ? (float)$data['prix_unitaire_specifique'] : null;

        // Textes : trim puis null si vide (plus propre en DB que stocker "").
        $ref = trim((string)($data['reference_fournisseur'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $module, $produitId, $fournisseurId,
                $prix,
                $ref !== '' ? $ref : null,
                !empty($data['fournisseur_prefere']) ? 1 : 0,
                $notes !== '' ? $notes : null,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return 0;
        }
    }

    /**
     * Détacher un fournisseur d'un produit.
     */
    public function detachFromProduit(string $module, int $produitId, int $fournisseurId): void {
        $this->assertModule($module);
        $this->db->prepare("DELETE FROM produit_fournisseurs
                            WHERE produit_module = ? AND produit_id = ? AND fournisseur_id = ?")
                 ->execute([$module, $produitId, $fournisseurId]);
    }

    /**
     * Synchronise les fournisseurs d'un produit en une seule opération.
     * $fournisseursData = [
     *   ['fournisseur_id' => 1, 'prix_unitaire_specifique' => 12.50, 'reference_fournisseur' => 'ABC',
     *    'fournisseur_prefere' => 1, 'notes' => '...'],
     *   ['fournisseur_id' => 2, 'fournisseur_prefere' => 0],
     *   ...
     * ]
     * Les fournisseurs absents de $fournisseursData sont détachés du produit.
     * Un seul peut avoir fournisseur_prefere = 1.
     */
   public function syncFournisseursForProduit(string $module, int $produitId, array $fournisseursData): void {
        $this->assertModule($module);
        $this->db->beginTransaction();
        try {
            // IDs voulus (dédupliqués)
            $nouveauxIds = array_values(array_unique(
                array_map(fn($d) => (int)$d['fournisseur_id'], $fournisseursData)
            ));

            // DELETE en batch : tout ce qui n'est pas dans la liste voulue
            if (!empty($nouveauxIds)) {
                $ph = implode(',', array_fill(0, count($nouveauxIds), '?'));
                $sql = "DELETE FROM produit_fournisseurs
                        WHERE produit_module = ? AND produit_id = ?
                          AND fournisseur_id NOT IN ($ph)";
                $this->db->prepare($sql)
                         ->execute(array_merge([$module, $produitId], $nouveauxIds));
            } else {
                $this->db->prepare("DELETE FROM produit_fournisseurs WHERE produit_module = ? AND produit_id = ?")
                         ->execute([$module, $produitId]);
            }

            // Garantir qu'un seul préféré max
            $preferes = array_filter($fournisseursData, fn($d) => !empty($d['fournisseur_prefere']));
            $seulPrefereId = count($preferes) > 0 ? (int)(reset($preferes)['fournisseur_id']) : null;

            // Upsert chaque fournisseur
            foreach ($fournisseursData as $d) {
                $fid = (int)$d['fournisseur_id'];
                $isPrefere = ($seulPrefereId === $fid) ? 1 : 0;
                $this->attachToProduit($module, $produitId, $fid, array_merge($d, ['fournisseur_prefere' => $isPrefere]));
            }

            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprime tous les liens fournisseurs d'un produit (à appeler avant DELETE produit).
     */
    public function purgeForProduit(string $module, int $produitId): void {
        $this->assertModule($module);
        $this->db->prepare("DELETE FROM produit_fournisseurs WHERE produit_module = ? AND produit_id = ?")
                 ->execute([$module, $produitId]);
    }

    /**
     * Retourne le fournisseur préféré d'un produit (ou null).
     */
    public function getFournisseurPrefere(string $module, int $produitId): ?array {
        $this->assertModule($module);
        $sql = "SELECT f.*, pf.prix_unitaire_specifique, pf.reference_fournisseur
                FROM produit_fournisseurs pf
                JOIN fournisseurs f ON pf.fournisseur_id = f.id
                WHERE pf.produit_module = ? AND pf.produit_id = ? AND pf.fournisseur_prefere = 1
                LIMIT 1";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$module, $produitId]);
              $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }
}
