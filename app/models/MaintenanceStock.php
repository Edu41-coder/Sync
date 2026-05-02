<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Stock Maintenance Technique
 * ====================================================================
 * Catalogue produits + inventaire par résidence + commandes fournisseurs.
 *
 * Utilise l'infrastructure unifiée (table commandes/commande_lignes
 * avec module='maintenance', pivot produit_fournisseurs polymorphe,
 * pivot fournisseur_residence global).
 */
class MaintenanceStock extends Model {

    public const MODULE = 'maintenance';

    public const CATEGORIES_PRODUIT = ['consommable','piece_detachee','outillage_main','outillage_motorise','produit_chimique','epi','autre'];
    public const TYPES_PRODUIT = ['produit','outil'];

    public const STATUTS_COMMANDE = ['brouillon','envoyee','livree_partiel','livree','facturee','annulee'];
    public const TYPES_MOUVEMENT = ['entree','sortie','ajustement'];
    public const MOTIFS_MOUVEMENT = ['livraison','usage','perte','casse','inventaire','intervention','autre'];

    // ─── PRODUITS (CATALOGUE) ───────────────────────────────────

    public function getProduits(?int $specialiteId = null, bool $actifsSeuls = true): array {
        $sql = "SELECT p.*, s.nom AS specialite_nom, s.couleur AS specialite_couleur, s.icone AS specialite_icone,
                       (SELECT pf.fournisseur_id FROM produit_fournisseurs pf
                        WHERE pf.produit_module = ? AND pf.produit_id = p.id AND pf.fournisseur_prefere = 1
                        LIMIT 1) AS fournisseur_prefere_id,
                       (SELECT f.nom FROM produit_fournisseurs pf
                        JOIN fournisseurs f ON f.id = pf.fournisseur_id
                        WHERE pf.produit_module = ? AND pf.produit_id = p.id AND pf.fournisseur_prefere = 1
                        LIMIT 1) AS fournisseur_prefere_nom
                FROM maintenance_produits p
                LEFT JOIN specialites s ON s.id = p.specialite_id
                WHERE 1=1";
        $params = [self::MODULE, self::MODULE];
        if ($actifsSeuls) $sql .= " AND p.actif = 1";
        if ($specialiteId) { $sql .= " AND p.specialite_id = ?"; $params[] = $specialiteId; }
        $sql .= " ORDER BY p.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findProduit(int $id): ?array {
        $sql = "SELECT p.*, s.nom AS specialite_nom
                FROM maintenance_produits p
                LEFT JOIN specialites s ON s.id = p.specialite_id
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createProduit(array $data): int {
        $sql = "INSERT INTO maintenance_produits (nom, specialite_id, categorie, type, unite, prix_unitaire, fiche_securite, actif, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['nom'],
            !empty($data['specialite_id']) ? (int)$data['specialite_id'] : null,
            $data['categorie'],
            $data['type'] ?? 'produit',
            $data['unite'] ?: null,
            !empty($data['prix_unitaire']) ? (float)$data['prix_unitaire'] : null,
            $data['fiche_securite'] ?: null,
            !empty($data['actif']) ? 1 : 0,
            $data['notes'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProduit(int $id, array $data): bool {
        $sql = "UPDATE maintenance_produits SET nom = ?, specialite_id = ?, categorie = ?, type = ?,
                  unite = ?, prix_unitaire = ?, fiche_securite = ?, actif = ?, notes = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['nom'],
            !empty($data['specialite_id']) ? (int)$data['specialite_id'] : null,
            $data['categorie'],
            $data['type'] ?? 'produit',
            $data['unite'] ?: null,
            !empty($data['prix_unitaire']) ? (float)$data['prix_unitaire'] : null,
            $data['fiche_securite'] ?: null,
            !empty($data['actif']) ? 1 : 0,
            $data['notes'] ?: null,
            $id,
        ]);
    }

    public function deleteProduit(int $id): bool {
        return $this->db->prepare("DELETE FROM maintenance_produits WHERE id = ?")->execute([$id]);
    }

    // ─── INVENTAIRE ─────────────────────────────────────────────

    public function getInventaire(int $residenceId, ?int $specialiteId = null, bool $alertesSeules = false): array {
        $sql = "SELECT i.*, p.nom, p.categorie, p.type, p.unite, p.prix_unitaire, p.actif,
                       s.nom AS specialite_nom, s.couleur AS specialite_couleur
                FROM maintenance_inventaire i
                JOIN maintenance_produits p ON p.id = i.produit_id
                LEFT JOIN specialites s ON s.id = p.specialite_id
                WHERE i.residence_id = ?";
        $params = [$residenceId];
        if ($specialiteId) { $sql .= " AND p.specialite_id = ?"; $params[] = $specialiteId; }
        if ($alertesSeules) $sql .= " AND i.seuil_alerte IS NOT NULL AND i.quantite_actuelle <= i.seuil_alerte";
        $sql .= " ORDER BY p.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findInventaireItem(int $id): ?array {
        $sql = "SELECT i.*, p.nom, p.unite FROM maintenance_inventaire i JOIN maintenance_produits p ON p.id = i.produit_id WHERE i.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Produits du catalogue PAS encore dans l'inventaire d'une résidence */
    public function getProduitsHorsInventaire(int $residenceId): array {
        $sql = "SELECT p.id, p.nom, p.categorie, p.unite
                FROM maintenance_produits p
                WHERE p.actif = 1 AND p.id NOT IN (SELECT produit_id FROM maintenance_inventaire WHERE residence_id = ?)
                ORDER BY p.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ajouterAuInventaire(int $residenceId, int $produitId, ?float $seuilAlerte, ?string $emplacement): int {
        $sql = "INSERT INTO maintenance_inventaire (residence_id, produit_id, quantite_actuelle, seuil_alerte, emplacement) VALUES (?, ?, 0, ?, ?)";
        $this->db->prepare($sql)->execute([$residenceId, $produitId, $seuilAlerte, $emplacement]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Mouvement de stock (transaction FOR UPDATE + refus stock négatif).
     */
    public function mouvementStock(int $inventaireId, string $type, float $quantite, string $motif, int $userId, ?int $interventionId = null, ?int $chantierId = null, ?string $notes = null): bool {
        if (!in_array($type, self::TYPES_MOUVEMENT, true)) throw new InvalidArgumentException('Type mouvement invalide.');
        if (!in_array($motif, self::MOTIFS_MOUVEMENT, true)) throw new InvalidArgumentException('Motif invalide.');
        if ($quantite <= 0) throw new InvalidArgumentException('Quantité doit être > 0.');

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT quantite_actuelle FROM maintenance_inventaire WHERE id = ? FOR UPDATE");
            $stmt->execute([$inventaireId]);
            $current = (float)$stmt->fetchColumn();

            $delta = $type === 'entree' ? $quantite : ($type === 'sortie' ? -$quantite : 0);
            if ($type === 'ajustement') {
                $nouveau = $quantite; // pour ajustement, $quantite = nouvelle valeur absolue
                $delta = $nouveau - $current;
            } else {
                $nouveau = $current + $delta;
            }
            if ($nouveau < 0) throw new Exception("Stock insuffisant ($current dispo, demandé $quantite).");

            // Mise à jour stock
            $this->db->prepare("UPDATE maintenance_inventaire SET quantite_actuelle = ? WHERE id = ?")->execute([$nouveau, $inventaireId]);

            // Trace mouvement
            $this->db->prepare("INSERT INTO maintenance_inventaire_mouvements (inventaire_id, type_mouvement, quantite, motif, user_id, intervention_id, chantier_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$inventaireId, $type, abs($quantite), $motif, $userId, $interventionId, $chantierId, $notes]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getMouvements(int $inventaireId, int $limit = 50): array {
        $sql = "SELECT m.*, u.prenom AS user_prenom, u.nom AS user_nom
                FROM maintenance_inventaire_mouvements m
                LEFT JOIN users u ON u.id = m.user_id
                WHERE m.inventaire_id = ?
                ORDER BY m.created_at DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$inventaireId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── COMMANDES (table unifiée commandes module='maintenance') ───

    public function getCommandes(array $residencesIds, ?string $statut = null): array {
        if (empty($residencesIds)) return [];
        $ph = implode(',', array_map('intval', $residencesIds));
        $sql = "SELECT c.*, f.nom AS fournisseur_nom, co.nom AS residence_nom,
                       (SELECT COUNT(*) FROM commande_lignes WHERE commande_id = c.id) AS nb_lignes
                FROM commandes c
                JOIN fournisseurs f ON f.id = c.fournisseur_id
                JOIN coproprietees co ON co.id = c.residence_id
                WHERE c.module = ? AND c.residence_id IN ($ph)";
        $params = [self::MODULE];
        if ($statut) { $sql .= " AND c.statut = ?"; $params[] = $statut; }
        $sql .= " ORDER BY c.date_commande DESC, c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCommande(int $id): ?array {
        $sql = "SELECT c.*, f.nom AS fournisseur_nom, f.email AS fournisseur_email, co.nom AS residence_nom
                FROM commandes c
                JOIN fournisseurs f ON f.id = c.fournisseur_id
                JOIN coproprietees co ON co.id = c.residence_id
                WHERE c.id = ? AND c.module = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, self::MODULE]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getCommandeLignes(int $commandeId): array {
        $sql = "SELECT cl.*, p.nom AS produit_nom, p.unite, p.categorie
                FROM commande_lignes cl
                LEFT JOIN maintenance_produits p ON p.id = cl.produit_id
                WHERE cl.commande_id = ?
                ORDER BY cl.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$commandeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une commande + ses lignes (transaction).
     * @param array $lignes [['produit_id' => N, 'designation' => '...', 'quantite' => X, 'prix_unitaire_ht' => Y, 'taux_tva' => 20], ...]
     */
    public function createCommande(array $data, array $lignes): int {
        $this->db->beginTransaction();
        try {
            $numero = $data['numero_commande'] ?? $this->genererNumeroCommande();
            $sql = "INSERT INTO commandes (module, residence_id, fournisseur_id, numero_commande, date_commande, date_livraison_prevue, statut, montant_total_ht, montant_tva, montant_total_ttc, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $totalHt = 0; $totalTva = 0;
            foreach ($lignes as $l) {
                $ligneHt = (float)$l['quantite'] * (float)$l['prix_unitaire_ht'];
                $ligneTva = $ligneHt * ((float)($l['taux_tva'] ?? 20.00) / 100);
                $totalHt += $ligneHt;
                $totalTva += $ligneTva;
            }
            $totalTtc = $totalHt + $totalTva;

            $this->db->prepare($sql)->execute([
                self::MODULE,
                (int)$data['residence_id'],
                (int)$data['fournisseur_id'],
                $numero,
                $data['date_commande'] ?? date('Y-m-d'),
                $data['date_livraison_prevue'] ?? null,
                $data['statut'] ?? 'brouillon',
                $totalHt,
                $totalTva,
                $totalTtc,
                $data['notes'] ?? null,
                $data['created_by'] ?? null,
            ]);
            $commandeId = (int)$this->db->lastInsertId();

            $stmt = $this->db->prepare("INSERT INTO commande_lignes (commande_id, produit_id, designation, quantite_commandee, prix_unitaire_ht, taux_tva) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($lignes as $l) {
                $stmt->execute([
                    $commandeId,
                    !empty($l['produit_id']) ? (int)$l['produit_id'] : null,
                    $l['designation'],
                    (float)$l['quantite'],
                    (float)$l['prix_unitaire_ht'],
                    (float)($l['taux_tva'] ?? 20.00),
                ]);
            }
            $this->db->commit();
            return $commandeId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateCommandeStatut(int $id, string $statut): bool {
        if (!in_array($statut, self::STATUTS_COMMANDE, true)) return false;
        return $this->db->prepare("UPDATE commandes SET statut = ? WHERE id = ? AND module = ?")
            ->execute([$statut, $id, self::MODULE]);
    }

    /**
     * Réception : pour chaque ligne, met à jour quantite_recue + crée mouvement entrée auto dans inventaire.
     * Si tout reçu → statut = 'livree', sinon 'livree_partiel'.
     */
    public function receptionnerCommande(int $commandeId, array $quantitesRecues, int $userId): bool {
        $commande = $this->findCommande($commandeId);
        if (!$commande) throw new Exception('Commande introuvable.');

        $this->db->beginTransaction();
        try {
            $totalCommande = 0; $totalRecu = 0;
            foreach ($quantitesRecues as $ligneId => $qteRecue) {
                $qteRecue = (float)$qteRecue;
                $stmt = $this->db->prepare("SELECT * FROM commande_lignes WHERE id = ? AND commande_id = ?");
                $stmt->execute([(int)$ligneId, $commandeId]);
                $ligne = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$ligne) continue;

                $totalCommande += (float)$ligne['quantite_commandee'];
                $totalRecu += $qteRecue;

                // Mise à jour qte_recue
                $this->db->prepare("UPDATE commande_lignes SET quantite_recue = ? WHERE id = ?")->execute([$qteRecue, $ligneId]);

                // Mouvement entrée auto sur l'inventaire (si produit_id défini et qte > 0)
                if ($qteRecue > 0 && !empty($ligne['produit_id'])) {
                    $stmt = $this->db->prepare("SELECT id FROM maintenance_inventaire WHERE residence_id = ? AND produit_id = ?");
                    $stmt->execute([(int)$commande['residence_id'], (int)$ligne['produit_id']]);
                    $inventaireId = (int)$stmt->fetchColumn();
                    if (!$inventaireId) {
                        // Crée l'entrée inventaire si elle n'existe pas
                        $inventaireId = $this->ajouterAuInventaire((int)$commande['residence_id'], (int)$ligne['produit_id'], null, null);
                    }
                    // Mouvement entrée (sans recursion sur transaction — stock suffit)
                    $this->db->prepare("UPDATE maintenance_inventaire SET quantite_actuelle = quantite_actuelle + ? WHERE id = ?")
                        ->execute([$qteRecue, $inventaireId]);
                    $this->db->prepare("INSERT INTO maintenance_inventaire_mouvements (inventaire_id, type_mouvement, quantite, motif, user_id, notes) VALUES (?, 'entree', ?, 'livraison', ?, ?)")
                        ->execute([$inventaireId, $qteRecue, $userId, "Réception commande #$commandeId"]);
                }
            }

            // Statut
            $statut = ($totalRecu >= $totalCommande) ? 'livree' : 'livree_partiel';
            $this->db->prepare("UPDATE commandes SET statut = ?, date_livraison_effective = ? WHERE id = ?")
                ->execute([$statut, date('Y-m-d'), $commandeId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteCommande(int $id): bool {
        $cmd = $this->findCommande($id);
        if (!$cmd) return false;
        if ($cmd['statut'] === 'brouillon') {
            return $this->db->prepare("DELETE FROM commandes WHERE id = ?")->execute([$id]);
        }
        return $this->db->prepare("UPDATE commandes SET statut = 'annulee' WHERE id = ?")->execute([$id]);
    }

    private function genererNumeroCommande(): string {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM commandes WHERE module = ? AND YEAR(date_commande) = ?");
        $stmt->execute([self::MODULE, date('Y')]);
        $n = (int)$stmt->fetchColumn() + 1;
        return sprintf('CMD-MAIN-%s-%04d', date('Y'), $n);
    }

    // ─── FOURNISSEURS (lecture liste accessibles) ──────────────

    /** Fournisseurs liés à au moins une résidence accessible (via fournisseur_residence) */
    public function getFournisseursAccessibles(array $residencesIds): array {
        if (empty($residencesIds)) return [];
        $ph = implode(',', array_map('intval', $residencesIds));
        $sql = "SELECT DISTINCT f.id, f.nom, f.email, f.telephone, f.contact_nom, f.type_service,
                       GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ', ') AS residences,
                       (SELECT COUNT(*) FROM commandes WHERE fournisseur_id = f.id AND module = 'maintenance') AS nb_commandes
                FROM fournisseurs f
                JOIN fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.statut = 'actif'
                JOIN coproprietees c ON c.id = fr.residence_id
                WHERE fr.residence_id IN ($ph) AND f.actif = 1
                GROUP BY f.id
                ORDER BY f.nom";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tous les fournisseurs actifs (pour sélecteur création commande) */
    public function getTousFournisseursActifs(): array {
        return $this->db->query("SELECT id, nom, type_service FROM fournisseurs WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    }
}
