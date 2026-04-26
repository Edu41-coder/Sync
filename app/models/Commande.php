<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Commande (polymorphe, multi-modules)
 * ====================================================================
 * Table unique `commandes` avec colonne `module` ENUM pour distinguer
 * les commandes restauration / ménage / jardinage / travaux / piscine...
 *
 * Pattern aligné avec Fournisseur (pivot unique polymorphe).
 *
 * Workflow : brouillon → envoyee → livree_partiel → livree → facturee
 * (ou annulee à tout moment avant livrée).
 *
 * La réception (receptionner) route vers la table `{module}_inventaire`
 * correspondante pour créer les mouvements d'entrée automatiques.
 */

class Commande extends Model {

    public const MODULES_AUTORISES = ['restauration','menage','jardinage','travaux','piscine','entretien','laverie','autre'];

    public const STATUTS_LABELS = [
        'brouillon'       => 'Brouillon',
        'envoyee'         => 'Envoyée',
        'livree_partiel'  => 'Livrée partiel',
        'livree'          => 'Livrée',
        'facturee'        => 'Facturée',
        'annulee'         => 'Annulée',
    ];

    public const STATUTS_COLORS = [
        'brouillon'      => 'secondary',
        'envoyee'        => 'info',
        'livree_partiel' => 'warning',
        'livree'         => 'success',
        'facturee'       => 'primary',
        'annulee'        => 'dark',
    ];

    private function assertModule(string $module): void {
        if (!in_array($module, self::MODULES_AUTORISES, true)) {
            throw new Exception("Module invalide : $module");
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  LECTURE
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste des commandes filtrable par module, résidences, statut.
     */
    public function getAll(string $module, array $residenceIds = [], ?string $statut = null): array {
        $this->assertModule($module);
        $sql = "SELECT c.*, f.nom as fournisseur_nom, r.nom as residence_nom,
                       (SELECT COUNT(*) FROM commande_lignes WHERE commande_id = c.id) as nb_lignes
                FROM commandes c
                JOIN fournisseurs f ON c.fournisseur_id = f.id
                JOIN coproprietees r ON c.residence_id = r.id
                WHERE c.module = ?";
        $params = [$module];
        if (!empty($residenceIds)) {
            $ph = implode(',', array_fill(0, count($residenceIds), '?'));
            $sql .= " AND c.residence_id IN ($ph)";
            $params = array_merge($params, array_values($residenceIds));
        }
        if ($statut) { $sql .= " AND c.statut = ?"; $params[] = $statut; }
        $sql .= " ORDER BY c.date_commande DESC, c.id DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Détail d'une commande + lignes (avec nom/unité produit du bon module).
     */
    public function get(int $id): ?array {
        $sql = "SELECT c.*, f.nom as fournisseur_nom, f.email as fournisseur_email,
                       f.telephone as fournisseur_telephone, f.adresse as fournisseur_adresse,
                       f.code_postal as fournisseur_cp, f.ville as fournisseur_ville,
                       r.nom as residence_nom,
                       u.prenom as created_by_prenom, u.nom as created_by_nom
                FROM commandes c
                JOIN fournisseurs f ON c.fournisseur_id = f.id
                JOIN coproprietees r ON c.residence_id = r.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute([$id]);
            $cmd = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cmd) return null;
            $cmd['lignes'] = $this->getLignes($id, $cmd['module']);
            return $cmd;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Lignes d'une commande, joint à la table produits du bon module pour récupérer nom/unité/catégorie.
     */
    public function getLignes(int $commandeId, string $module): array {
        $this->assertModule($module);
        $produitsTable = $this->getProduitsTable($module);
        // Colonnes diffèrent selon module (ménage/resto = prix_reference, jardinage = prix_unitaire)
        // On sélectionne les colonnes communes + nom/unité qui existent partout.
        $sql = "SELECT l.*, p.nom as produit_nom, p.unite, p.categorie
                FROM commande_lignes l
                JOIN $produitsTable p ON l.produit_id = p.id
                WHERE l.commande_id = ?
                ORDER BY l.id";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$commandeId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Génère un numéro séquentiel par module : CMD-{MENAGE|REST|JARD|...}-YYYY-NNNN
     */
    public function generateNumero(string $module): string {
        $this->assertModule($module);
        $prefixes = [
            'restauration' => 'REST',
            'menage'       => 'MENAGE',
            'jardinage'    => 'JARD',
            'travaux'      => 'TRAV',
            'piscine'      => 'PISC',
            'entretien'    => 'ENTR',
            'laverie'      => 'LAV',
            'autre'        => 'AUTRE',
        ];
        $prefix = $prefixes[$module] ?? strtoupper($module);
        $year = date('Y');
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM commandes WHERE module = ? AND YEAR(date_commande) = ?");
            $stmt->execute([$module, $year]);
            $n = (int)$stmt->fetchColumn() + 1;
        } catch (PDOException $e) { $n = 1; }
        return 'CMD-' . $prefix . '-' . $year . '-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }

    // ─────────────────────────────────────────────────────────────
    //  ÉCRITURE
    // ─────────────────────────────────────────────────────────────

    /**
     * Création d'une commande avec ses lignes (transaction).
     */
    public function create(string $module, array $data, array $lignes): int {
        $this->assertModule($module);
        $this->db->beginTransaction();
        try {
            $numero = $this->generateNumero($module);

            $totalHt = 0.0; $totalTva = 0.0;
            foreach ($lignes as $l) {
                $ligneHt = (float)$l['quantite_commandee'] * (float)$l['prix_unitaire_ht'];
                $totalHt += $ligneHt;
                $totalTva += $ligneHt * ((float)($l['taux_tva'] ?? 20) / 100);
            }
            $totalTtc = $totalHt + $totalTva;

            $stmt = $this->db->prepare("INSERT INTO commandes
                (module, residence_id, fournisseur_id, numero_commande, date_commande,
                 date_livraison_prevue, statut, montant_total_ht, montant_tva, montant_total_ttc,
                 notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $module,
                (int)$data['residence_id'],
                (int)$data['fournisseur_id'],
                $numero,
                $data['date_commande'] ?? date('Y-m-d'),
                !empty($data['date_livraison_prevue']) ? $data['date_livraison_prevue'] : null,
                $data['statut'] ?? 'brouillon',
                round($totalHt, 2),
                round($totalTva, 2),
                round($totalTtc, 2),
                !empty($data['notes']) ? trim($data['notes']) : null,
                !empty($data['created_by']) ? (int)$data['created_by'] : null,
            ]);
            $cmdId = (int)$this->db->lastInsertId();

            $lstmt = $this->db->prepare("INSERT INTO commande_lignes
                (commande_id, produit_id, designation, quantite_commandee, prix_unitaire_ht, taux_tva)
                VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($lignes as $l) {
                $lstmt->execute([
                    $cmdId,
                    (int)$l['produit_id'],
                    $l['designation'],
                    (float)$l['quantite_commandee'],
                    (float)$l['prix_unitaire_ht'],
                    (float)($l['taux_tva'] ?? 20),
                ]);
            }
            $this->db->commit();
            return $cmdId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }

    public function updateStatut(int $id, string $statut): void {
        $valides = array_keys(self::STATUTS_LABELS);
        if (!in_array($statut, $valides, true)) throw new Exception("Statut invalide : $statut");
        $stmt = $this->db->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
        $stmt->execute([$statut, $id]);
    }

    /**
     * Réception d'une commande : met à jour les quantités reçues + crée les
     * mouvements d'entrée dans `{module}_inventaire` + ajuste le statut.
     * @param array $quantitesRecues [ligne_id => quantite_recue totale]
     */
    public function receptionner(int $commandeId, array $quantitesRecues, int $userId): bool {
        $this->db->beginTransaction();
        try {
            // Récupérer la commande + son module
            $cstmt = $this->db->prepare("SELECT * FROM commandes WHERE id = ? FOR UPDATE");
            $cstmt->execute([$commandeId]);
            $cmd = $cstmt->fetch(PDO::FETCH_ASSOC);
            if (!$cmd) throw new Exception("Commande introuvable");
            if (in_array($cmd['statut'], ['livree','facturee','annulee'], true)) {
                throw new Exception("Commande non modifiable (statut : " . $cmd['statut'] . ")");
            }
            $module = $cmd['module'];
            $residenceId = (int)$cmd['residence_id'];

            // Récupérer les lignes
            $lstmt = $this->db->prepare("SELECT * FROM commande_lignes WHERE commande_id = ?");
            $lstmt->execute([$commandeId]);
            $lignes = $lstmt->fetchAll(PDO::FETCH_ASSOC);

            // Tables cibles pour l'inventaire (selon module)
            $inventaireTable = $this->getInventaireTable($module);
            $mouvementsTable = $this->getMouvementsTable($module);

            $touteRecue = true;
            $auMoinsUne = false;

            foreach ($lignes as $ligne) {
                $ligneId = (int)$ligne['id'];
                $qteRecueSaisie = (float)($quantitesRecues[$ligneId] ?? 0);
                $qteDeja = (float)($ligne['quantite_recue'] ?? 0);
                $qteCommandee = (float)$ligne['quantite_commandee'];
                $delta = $qteRecueSaisie - $qteDeja;

                if ($delta > 0) {
                    $auMoinsUne = true;
                    $this->createMouvementEntree($module, $residenceId, (int)$ligne['produit_id'], $delta, $userId, $cmd['numero_commande']);
                }

                // Update quantité reçue sur la ligne
                $this->db->prepare("UPDATE commande_lignes SET quantite_recue = ? WHERE id = ?")
                         ->execute([$qteRecueSaisie, $ligneId]);

                if ($qteRecueSaisie < $qteCommandee) $touteRecue = false;
            }

            if (!$auMoinsUne) throw new Exception("Aucune quantité saisie");

            // Statut + date
            $nouveauStatut = $touteRecue ? 'livree' : 'livree_partiel';
            $this->db->prepare("UPDATE commandes SET statut = ?, date_livraison_effective = CURDATE() WHERE id = ?")
                     ->execute([$nouveauStatut, $commandeId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée (ou incrémente) un mouvement d'entrée dans l'inventaire du bon module.
     * Créé l'entrée inventaire si elle n'existe pas.
     */
    private function createMouvementEntree(string $module, int $residenceId, int $produitId, float $delta, int $userId, string $numeroCommande): void {
        $inventaireTable = $this->getInventaireTable($module);
        $mouvementsTable = $this->getMouvementsTable($module);

        // Trouver/créer l'entrée inventaire (produit_id, residence_id)
        $sel = $this->db->prepare("SELECT id FROM $inventaireTable WHERE produit_id = ? AND residence_id = ?");
        $sel->execute([$produitId, $residenceId]);
        $invId = $sel->fetchColumn();

        if (!$invId) {
            // Colonnes diffèrent légèrement :
            //   menage/rest_inventaire → quantite_stock, seuil_alerte DECIMAL(10,3)
            //   jardin_inventaire      → quantite_actuelle, seuil_alerte DECIMAL(10,3)
            if ($module === 'jardinage') {
                $this->db->prepare("INSERT INTO $inventaireTable (produit_id, residence_id, quantite_actuelle, seuil_alerte) VALUES (?, ?, 0, 0)")
                         ->execute([$produitId, $residenceId]);
            } else {
                $this->db->prepare("INSERT INTO $inventaireTable (produit_id, residence_id, quantite_stock, seuil_alerte) VALUES (?, ?, 0, 0)")
                         ->execute([$produitId, $residenceId]);
            }
            $invId = (int)$this->db->lastInsertId();
        } else {
            $invId = (int)$invId;
        }

        // Update quantité (colonne diffère selon module)
        if ($module === 'jardinage') {
            $this->db->prepare("UPDATE $inventaireTable SET quantite_actuelle = quantite_actuelle + ? WHERE id = ?")
                     ->execute([$delta, $invId]);
        } else {
            $this->db->prepare("UPDATE $inventaireTable SET quantite_stock = quantite_stock + ? WHERE id = ?")
                     ->execute([$delta, $invId]);
        }

        // Insert mouvement (colonnes aussi diffèrent un peu)
        // menage/rest : type_mouvement, quantite, motif, user_id, notes, commande_id?
        // jardinage   : type_mouvement, quantite, motif, user_id, notes
        if ($module === 'jardinage') {
            $this->db->prepare("INSERT INTO $mouvementsTable (inventaire_id, type_mouvement, quantite, motif, user_id, notes)
                                VALUES (?, 'entree', ?, 'livraison', ?, ?)")
                     ->execute([$invId, $delta, $userId, 'Réception commande ' . $numeroCommande]);
        } else {
            $this->db->prepare("INSERT INTO $mouvementsTable (inventaire_id, type_mouvement, quantite, motif, user_id, notes)
                                VALUES (?, 'entree', ?, 'livraison', ?, ?)")
                     ->execute([$invId, $delta, $userId, 'Réception commande ' . $numeroCommande]);
        }
    }

    /**
     * Suppression : hard delete si brouillon, sinon statut = annulee.
     */
    public function deleteOrCancel(int $id): string {
        $stmt = $this->db->prepare("SELECT statut FROM commandes WHERE id = ?");
        $stmt->execute([$id]);
        $statut = $stmt->fetchColumn();
        if ($statut === 'brouillon') {
            $this->db->prepare("DELETE FROM commandes WHERE id = ?")->execute([$id]);
            return 'deleted';
        }
        $this->db->prepare("UPDATE commandes SET statut = 'annulee' WHERE id = ?")->execute([$id]);
        return 'cancelled';
    }

    // ─────────────────────────────────────────────────────────────
    //  AGRÉGATS (pour comptabilité, dashboards)
    // ─────────────────────────────────────────────────────────────

    /**
     * Dépenses par fournisseur pour la comptabilité d'un module.
     */
    public function getDepensesParFournisseur(string $module, array $residenceIds, int $annee, ?int $mois = null): array {
        $this->assertModule($module);
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge([$module], array_values($residenceIds), [$annee]);
        $sql = "SELECT f.id as fournisseur_id, f.nom as fournisseur_nom,
                       COUNT(c.id) as nb_commandes,
                       COALESCE(SUM(c.montant_total_ht), 0) as total_ht,
                       COALESCE(SUM(c.montant_total_ttc), 0) as total_ttc,
                       MAX(c.date_commande) as derniere_commande
                FROM fournisseurs f
                JOIN commandes c ON c.fournisseur_id = f.id
                WHERE c.module = ? AND c.residence_id IN ($ph)
                  AND YEAR(c.date_commande) = ? AND c.statut != 'annulee'";
        if ($mois) { $sql .= " AND MONTH(c.date_commande) = ?"; $params[] = $mois; }
        $sql .= " GROUP BY f.id ORDER BY total_ttc DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Stats rapides pour dashboard module (nb commandes en cours, en attente réception, etc.).
     */
    public function getStatsModule(string $module, array $residenceIds): array {
        $this->assertModule($module);
        $res = ['brouillon' => 0, 'envoyee' => 0, 'livree_partiel' => 0, 'livree' => 0, 'facturee' => 0];
        if (empty($residenceIds)) return $res;
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge([$module], array_values($residenceIds));
        $sql = "SELECT statut, COUNT(*) as n FROM commandes
                WHERE module = ? AND residence_id IN ($ph)
                GROUP BY statut";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute($params);
            foreach ($stmt as $row) { if (isset($res[$row['statut']])) $res[$row['statut']] = (int)$row['n']; }
        } catch (PDOException $e) { $this->logError($e->getMessage()); }
        return $res;
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS PRIVÉS — routage selon module
    // ─────────────────────────────────────────────────────────────

    private function getProduitsTable(string $module): string {
        return match ($module) {
            'menage'       => 'menage_produits',
            'restauration' => 'rest_produits',
            'jardinage'    => 'jardin_produits',
            default        => throw new Exception("Table produits inconnue pour module : $module"),
        };
    }

    private function getInventaireTable(string $module): string {
        return match ($module) {
            'menage'       => 'menage_inventaire',
            'restauration' => 'rest_inventaire',
            'jardinage'    => 'jardin_inventaire',
            default        => throw new Exception("Table inventaire inconnue pour module : $module"),
        };
    }

    private function getMouvementsTable(string $module): string {
        return match ($module) {
            'menage'       => 'menage_inventaire_mouvements',
            'restauration' => 'rest_inventaire_mouvements',
            'jardinage'    => 'jardin_inventaire_mouvements',
            default        => throw new Exception("Table mouvements inconnue pour module : $module"),
        };
    }
}
