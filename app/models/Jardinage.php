<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Jardinage
 * ====================================================================
 * Phase 1 : espaces, tâches récurrentes, produits, inventaire,
 * mouvements, équipe, dashboard, ruches (lecture).
 * Phase 2 : commandes fournisseurs, comptabilité jardinage, ruches UI.
 */

class Jardinage extends Model {

    public const ROLES_ALL     = ['admin', 'directeur_residence', 'jardinier_manager', 'jardinier_employe'];
    public const ROLES_MANAGER = ['admin', 'directeur_residence', 'jardinier_manager'];

    // ─────────────────────────────────────────────────────────────
    //  RÉSIDENCES DE L'UTILISATEUR
    // ─────────────────────────────────────────────────────────────

    public function getResidencesByUser(int $userId): array {
        $sql = "SELECT c.id, c.nom, c.ville, c.ruches
                FROM user_residence ur
                JOIN coproprietees c ON ur.residence_id = c.id
                WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1
                ORDER BY c.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getAllResidencesSimple(): array {
        $sql = "SELECT id, nom, ville, ruches FROM coproprietees WHERE actif = 1 ORDER BY nom";
        try { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getResidenceIdsByUser(int $userId): array {
        $sql = "SELECT ur.residence_id FROM user_residence ur
                JOIN coproprietees c ON ur.residence_id = c.id
                WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_COLUMN); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  DASHBOARD STATS
    // ─────────────────────────────────────────────────────────────

    public function getDashboardStats(array $residenceIds): array {
        $stats = [
            'espaces'             => 0,
            'espaces_ruchers'     => 0,
            'produits'            => 0,
            'outils'              => 0,
            'alertes_stock'       => 0,
            'ruches_actives'      => 0,
            'visites_mois'        => 0,
            'miel_annee_kg'       => 0,
            'ruches_sans_visite'  => 0,
        ];
        if (empty($residenceIds)) return $stats;
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $ids = array_values($residenceIds);

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_espaces WHERE actif = 1 AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['espaces'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_espaces WHERE actif = 1 AND type = 'rucher' AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['espaces_ruchers'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->query("SELECT COUNT(*) FROM jardin_produits WHERE actif = 1 AND type = 'produit'");
            $stats['produits'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->query("SELECT COUNT(*) FROM jardin_produits WHERE actif = 1 AND type = 'outil'");
            $stats['outils'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_inventaire
                                        WHERE seuil_alerte > 0 AND quantite_actuelle <= seuil_alerte
                                          AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['alertes_stock'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_ruches WHERE statut = 'active' AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['ruches_actives'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_ruches_visites v
                                        JOIN jardin_ruches r ON v.ruche_id = r.id
                                        WHERE v.date_visite >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                          AND r.residence_id IN ($ph)");
            $stmt->execute($ids); $stats['visites_mois'] = (int)$stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT COALESCE(SUM(v.quantite_miel_kg), 0) FROM jardin_ruches_visites v
                                        JOIN jardin_ruches r ON v.ruche_id = r.id
                                        WHERE YEAR(v.date_visite) = YEAR(CURDATE())
                                          AND v.type_intervention = 'recolte'
                                          AND r.residence_id IN ($ph)");
            $stmt->execute($ids); $stats['miel_annee_kg'] = (float)$stmt->fetchColumn();

            // Ruches actives sans visite > 30 jours
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_ruches r
                                        WHERE r.statut = 'active' AND r.residence_id IN ($ph)
                                          AND (
                                            (SELECT MAX(date_visite) FROM jardin_ruches_visites WHERE ruche_id = r.id) IS NULL
                                            OR (SELECT MAX(date_visite) FROM jardin_ruches_visites WHERE ruche_id = r.id) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                          )");
            $stmt->execute($ids); $stats['ruches_sans_visite'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) { $this->logError($e->getMessage()); }

        return $stats;
    }

    public function getAlertesStock(array $residenceIds, int $limit = 5): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);
        $sql = "SELECT i.*, p.nom as produit_nom, p.unite, p.categorie, c.nom as residence_nom
                FROM jardin_inventaire i
                JOIN jardin_produits p ON i.produit_id = p.id
                JOIN coproprietees c ON i.residence_id = c.id
                WHERE i.seuil_alerte > 0 AND i.quantite_actuelle <= i.seuil_alerte
                  AND i.residence_id IN ($ph)
                ORDER BY (i.quantite_actuelle / NULLIF(i.seuil_alerte, 0)) ASC
                LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getMouvementsRecents(array $residenceIds, int $limit = 10): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);
        $sql = "SELECT m.*, p.nom as produit_nom, p.unite, c.nom as residence_nom,
                       e.nom as espace_nom, u.prenom as user_prenom, u.nom as user_nom
                FROM jardin_inventaire_mouvements m
                JOIN jardin_inventaire i ON m.inventaire_id = i.id
                JOIN jardin_produits p ON i.produit_id = p.id
                JOIN coproprietees c ON i.residence_id = c.id
                LEFT JOIN jardin_espaces e ON m.espace_id = e.id
                LEFT JOIN users u ON m.user_id = u.id
                WHERE i.residence_id IN ($ph)
                ORDER BY m.created_at DESC
                LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  STAFF JARDINIER
    // ─────────────────────────────────────────────────────────────

    /**
     * Contacts rapides pour le dashboard jardinage :
     * jardinier_manager + directeur_residence + admin affectés aux résidences de l'utilisateur.
     * Exclut l'utilisateur courant (pas de "contacter soi-même").
     */
    public function getContactsRapides(array $residenceIds, int $excludeUserId = 0): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT DISTINCT u.id, u.prenom, u.nom, u.role, u.email, u.telephone,
                                r.nom_affichage as role_nom, r.couleur as role_couleur, r.icone as role_icone,
                                (SELECT GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ', ')
                                 FROM user_residence ur2
                                 JOIN coproprietees c ON ur2.residence_id = c.id
                                 WHERE ur2.user_id = u.id AND ur2.statut = 'actif' AND c.id IN ($ph)
                                ) as residences_affectees
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.statut = 'actif'
                LEFT JOIN roles r ON u.role = r.slug
                WHERE u.actif = 1
                  AND u.role IN ('admin','directeur_residence','jardinier_manager')
                  AND u.id <> ?
                  AND ur.residence_id IN ($ph)
                ORDER BY FIELD(u.role, 'jardinier_manager','directeur_residence','admin'), u.nom";
        try {
            $params = array_merge(array_values($residenceIds), [$excludeUserId], array_values($residenceIds));
            $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getStaffByResidences(array $residenceIds): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT u.id, u.nom, u.prenom, u.role, u.email, u.telephone, u.actif, u.last_login,
                       c.nom as residence_nom, c.id as residence_id,
                       r.nom_affichage as role_nom, r.couleur as role_couleur, r.icone as role_icone
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.statut = 'actif'
                JOIN coproprietees c ON ur.residence_id = c.id
                LEFT JOIN roles r ON u.role = r.slug
                WHERE u.role IN ('jardinier_manager','jardinier_employe') AND u.actif = 1 AND c.id IN ($ph)
                ORDER BY c.nom, u.role, u.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute(array_values($residenceIds)); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  ESPACES JARDIN (CRUD)
    // ─────────────────────────────────────────────────────────────

    public function getEspaces(int $residenceId, bool $actifsOnly = true): array {
        $sql = "SELECT e.*, (SELECT COUNT(*) FROM jardin_taches WHERE espace_id = e.id AND actif = 1) as nb_taches
                FROM jardin_espaces e
                WHERE e.residence_id = ?" . ($actifsOnly ? " AND e.actif = 1" : "") . "
                ORDER BY e.type, e.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getEspace(int $id): ?array {
        $sql = "SELECT e.*, c.nom as residence_nom FROM jardin_espaces e
                JOIN coproprietees c ON e.residence_id = c.id
                WHERE e.id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    public function createEspace(array $data): int {
        $sql = "INSERT INTO jardin_espaces (residence_id, nom, type, surface_m2, description, actif)
                VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$data['residence_id'],
            trim($data['nom']),
            $data['type'] ?? 'autre',
            !empty($data['surface_m2']) ? (float)$data['surface_m2'] : null,
            !empty($data['description']) ? trim($data['description']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateEspace(int $id, array $data): void {
        $sql = "UPDATE jardin_espaces SET nom = ?, type = ?, surface_m2 = ?, description = ?, actif = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($data['nom']),
            $data['type'] ?? 'autre',
            !empty($data['surface_m2']) ? (float)$data['surface_m2'] : null,
            !empty($data['description']) ? trim($data['description']) : null,
            isset($data['actif']) ? (int)$data['actif'] : 1,
            $id
        ]);
    }

    public function deleteEspace(int $id): void {
        $stmt = $this->db->prepare("UPDATE jardin_espaces SET actif = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function activateEspace(int $id): void {
        $stmt = $this->db->prepare("UPDATE jardin_espaces SET actif = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function updateEspacePhoto(int $id, ?string $path): void {
        $stmt = $this->db->prepare("UPDATE jardin_espaces SET photo = ? WHERE id = ?");
        $stmt->execute([$path, $id]);
    }

    public function getEspacePhoto(int $id): ?string {
        $stmt = $this->db->prepare("SELECT photo FROM jardin_espaces WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetchColumn();
        return $p ?: null;
    }

    // ─────────────────────────────────────────────────────────────
    //  TÂCHES RÉCURRENTES PAR ESPACE
    // ─────────────────────────────────────────────────────────────

    public function getTachesByEspace(int $espaceId): array {
        $sql = "SELECT * FROM jardin_taches WHERE espace_id = ? AND actif = 1 ORDER BY frequence, nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$espaceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function createTache(array $data): int {
        $sql = "INSERT INTO jardin_taches (espace_id, nom, frequence, saison, duree_estimee_min, notes, actif)
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$data['espace_id'],
            trim($data['nom']),
            $data['frequence'] ?? 'hebdo',
            $data['saison'] ?? 'toutes',
            !empty($data['duree_estimee_min']) ? (int)$data['duree_estimee_min'] : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteTache(int $id): void {
        $stmt = $this->db->prepare("UPDATE jardin_taches SET actif = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }

    // ─────────────────────────────────────────────────────────────
    //  PRODUITS & OUTILS (CRUD manager)
    // ─────────────────────────────────────────────────────────────

    public function getAllProduits(?string $categorie = null, ?string $type = null, bool $actifsOnly = false): array {
        // LEFT JOIN sur produit_fournisseurs pour récupérer le fournisseur préféré
        $sql = "SELECT p.*,
                       pf_pref.fournisseur_id as fournisseur_id,
                       f.nom as fournisseur_nom
                FROM jardin_produits p
                LEFT JOIN produit_fournisseurs pf_pref
                    ON pf_pref.produit_module='jardinage' AND pf_pref.produit_id=p.id AND pf_pref.fournisseur_prefere=1
                LEFT JOIN fournisseurs f ON f.id = pf_pref.fournisseur_id
                WHERE 1=1";
        $params = [];
        if ($actifsOnly) $sql .= " AND p.actif = 1";
        if ($categorie) { $sql .= " AND p.categorie = ?"; $params[] = $categorie; }
        if ($type) { $sql .= " AND p.type = ?"; $params[] = $type; }
        $sql .= " ORDER BY p.type, p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getProduit(int $id): ?array {
        $sql = "SELECT p.*,
                       pf_pref.fournisseur_id as fournisseur_id
                FROM jardin_produits p
                LEFT JOIN produit_fournisseurs pf_pref
                    ON pf_pref.produit_module='jardinage' AND pf_pref.produit_id=p.id AND pf_pref.fournisseur_prefere=1
                WHERE p.id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    public function createProduit(array $data): int {
        $sql = "INSERT INTO jardin_produits (nom, categorie, type, unite, prix_unitaire, marque, bio, danger, notes, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($data['nom']),
            $data['categorie'] ?? 'autre',
            $data['type'] ?? 'produit',
            $data['unite'] ?? 'piece',
            !empty($data['prix_unitaire']) ? (float)$data['prix_unitaire'] : null,
            !empty($data['marque']) ? trim($data['marque']) : null,
            !empty($data['bio']) ? 1 : 0,
            !empty($data['danger']) ? trim($data['danger']) : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        $newId = (int)$this->db->lastInsertId();
        $this->syncProduitFournisseurs($newId, $data);
        return $newId;
    }

    public function updateProduit(int $id, array $data): void {
        $sql = "UPDATE jardin_produits SET nom = ?, categorie = ?, type = ?, unite = ?, prix_unitaire = ?,
                       marque = ?, bio = ?, danger = ?, notes = ?, actif = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($data['nom']),
            $data['categorie'] ?? 'autre',
            $data['type'] ?? 'produit',
            $data['unite'] ?? 'piece',
            !empty($data['prix_unitaire']) ? (float)$data['prix_unitaire'] : null,
            !empty($data['marque']) ? trim($data['marque']) : null,
            !empty($data['bio']) ? 1 : 0,
            !empty($data['danger']) ? trim($data['danger']) : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
            isset($data['actif']) ? (int)$data['actif'] : 1,
            $id
        ]);
        $this->syncProduitFournisseurs($id, $data);
    }

    public function deleteProduit(int $id): void {
        (new Fournisseur())->purgeForProduit('jardinage', $id);
        $stmt = $this->db->prepare("UPDATE jardin_produits SET actif = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function syncProduitFournisseurs(int $produitId, array $d): void {
        $fm = new Fournisseur();
        if (isset($d['fournisseurs']) && is_array($d['fournisseurs'])) {
            $data = [];
            foreach ($d['fournisseurs'] as $fid => $row) {
                $fid = (int)$fid;
                if (!$fid) continue;
                $data[] = [
                    'fournisseur_id' => $fid,
                    'prix_unitaire_specifique' => $row['prix'] ?? null,
                    'reference_fournisseur'    => $row['ref'] ?? null,
                    'fournisseur_prefere'      => !empty($d['fournisseur_prefere_id']) && (int)$d['fournisseur_prefere_id'] === $fid ? 1 : 0,
                    'notes'                    => $row['notes'] ?? null,
                ];
            }
            $fm->syncFournisseursForProduit('jardinage', $produitId, $data);
        } elseif (!empty($d['fournisseur_id'])) {
            $fm->syncFournisseursForProduit('jardinage', $produitId, [[
                'fournisseur_id' => (int)$d['fournisseur_id'],
                'fournisseur_prefere' => 1,
            ]]);
        }
    }

    public function updateProduitPhoto(int $id, ?string $path): void {
        $stmt = $this->db->prepare("UPDATE jardin_produits SET photo = ? WHERE id = ?");
        $stmt->execute([$path, $id]);
    }

    public function getProduitPhoto(int $id): ?string {
        $stmt = $this->db->prepare("SELECT photo FROM jardin_produits WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetchColumn();
        return $p ?: null;
    }

    public function getFournisseursList(): array {
        $sql = "SELECT id, nom FROM fournisseurs WHERE actif = 1 ORDER BY nom";
        try { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  INVENTAIRE (stock + mouvements)
    // ─────────────────────────────────────────────────────────────

    public function getInventaire(int $residenceId, ?string $categorie = null, bool $alertesOnly = false): array {
        $sql = "SELECT i.*, p.nom as produit_nom, p.categorie, p.type, p.unite, p.prix_unitaire,
                       p.bio, p.marque, p.danger
                FROM jardin_inventaire i
                JOIN jardin_produits p ON i.produit_id = p.id
                WHERE i.residence_id = ? AND p.actif = 1";
        $params = [$residenceId];
        if ($categorie) { $sql .= " AND p.categorie = ?"; $params[] = $categorie; }
        if ($alertesOnly) $sql .= " AND i.seuil_alerte > 0 AND i.quantite_actuelle <= i.seuil_alerte";
        $sql .= " ORDER BY p.type, p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getInventaireItem(int $id): ?array {
        $sql = "SELECT i.*, p.nom as produit_nom, p.unite, p.categorie, p.type,
                       c.nom as residence_nom
                FROM jardin_inventaire i
                JOIN jardin_produits p ON i.produit_id = p.id
                JOIN coproprietees c ON i.residence_id = c.id
                WHERE i.id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    public function getProduitsHorsInventaire(int $residenceId): array {
        $sql = "SELECT p.id, p.nom, p.categorie, p.type, p.unite
                FROM jardin_produits p
                WHERE p.actif = 1
                  AND p.id NOT IN (SELECT produit_id FROM jardin_inventaire WHERE residence_id = ?)
                ORDER BY p.type, p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function addToInventaire(int $produitId, int $residenceId, float $seuilAlerte = 0, ?string $emplacement = null): int {
        $sql = "INSERT INTO jardin_inventaire (produit_id, residence_id, quantite_actuelle, seuil_alerte, emplacement)
                VALUES (?, ?, 0, ?, ?)
                ON DUPLICATE KEY UPDATE seuil_alerte = VALUES(seuil_alerte), emplacement = VALUES(emplacement)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$produitId, $residenceId, $seuilAlerte, $emplacement]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Mouvement de stock avec traçabilité (entrée, sortie, ajustement)
     * Refuse la sortie si stock insuffisant.
     */
    public function mouvementStock(int $inventaireId, string $type, float $quantite, string $motif = 'autre', ?int $espaceId = null, ?string $notes = null, ?int $userId = null): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT quantite_actuelle FROM jardin_inventaire WHERE id = ? FOR UPDATE");
            $stmt->execute([$inventaireId]);
            $stock = $stmt->fetchColumn();
            if ($stock === false) throw new Exception("Inventaire introuvable");

            $delta = 0;
            if ($type === 'entree')      $delta = $quantite;
            elseif ($type === 'sortie')  $delta = -$quantite;
            elseif ($type === 'ajustement') $delta = $quantite - (float)$stock;
            else throw new Exception("Type de mouvement invalide");

            $nouveauStock = (float)$stock + $delta;
            if ($nouveauStock < 0) throw new Exception("Stock insuffisant (disponible : $stock)");

            $stmt = $this->db->prepare("UPDATE jardin_inventaire SET quantite_actuelle = ? WHERE id = ?");
            $stmt->execute([$nouveauStock, $inventaireId]);

            $stmt = $this->db->prepare("INSERT INTO jardin_inventaire_mouvements
                (inventaire_id, type_mouvement, quantite, motif, espace_id, user_id, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$inventaireId, $type, abs($quantite), $motif, $espaceId, $userId, $notes]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError($e->getMessage());
            throw $e;
        }
    }

    public function getMouvements(int $inventaireId, int $limit = 50): array {
        $sql = "SELECT m.*, e.nom as espace_nom, u.prenom as user_prenom, u.nom as user_nom
                FROM jardin_inventaire_mouvements m
                LEFT JOIN jardin_espaces e ON m.espace_id = e.id
                LEFT JOIN users u ON m.user_id = u.id
                WHERE m.inventaire_id = ?
                ORDER BY m.created_at DESC
                LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->bindValue(1, $inventaireId, PDO::PARAM_INT); $stmt->bindValue(2, $limit, PDO::PARAM_INT); $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  RUCHES (apiculture — CRUD + photo)
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si l'utilisateur a accès à au moins une résidence avec ruches activées.
     * Admin voit toutes les résidences.
     */
    public function userHasAccessToRuches(int $userId, string $role): bool {
        try {
            if ($role === 'admin') {
                $stmt = $this->db->query("SELECT 1 FROM coproprietees WHERE ruches = 1 AND actif = 1 LIMIT 1");
                return (bool)$stmt->fetchColumn();
            }
            $sql = "SELECT 1 FROM user_residence ur
                    JOIN coproprietees c ON ur.residence_id = c.id
                    WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.ruches = 1 AND c.actif = 1
                    LIMIT 1";
            $stmt = $this->db->prepare($sql); $stmt->execute([$userId]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) { $this->logError($e->getMessage()); return false; }
    }

    public function getRuchesByResidence(int $residenceId): array {
        $sql = "SELECT r.*, e.nom as espace_nom,
                       (SELECT MAX(date_visite) FROM jardin_ruches_visites WHERE ruche_id = r.id) as derniere_visite,
                       (SELECT COUNT(*) FROM jardin_ruches_visites WHERE ruche_id = r.id) as nb_visites
                FROM jardin_ruches r
                LEFT JOIN jardin_espaces e ON r.espace_id = e.id
                WHERE r.residence_id = ?
                ORDER BY r.numero";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getRuche(int $id): ?array {
        $sql = "SELECT r.*, e.nom as espace_nom, c.nom as residence_nom, c.ruches as residence_ruches
                FROM jardin_ruches r
                JOIN coproprietees c ON r.residence_id = c.id
                LEFT JOIN jardin_espaces e ON r.espace_id = e.id
                WHERE r.id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Liste des espaces de type "rucher" pour rattacher une ruche.
     */
    public function getEspacesRucher(int $residenceId): array {
        $sql = "SELECT id, nom FROM jardin_espaces WHERE residence_id = ? AND type = 'rucher' AND actif = 1 ORDER BY nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function createRuche(array $data, ?int $userId = null): int {
        $statut = $data['statut'] ?? 'active';
        $sql = "INSERT INTO jardin_ruches (residence_id, espace_id, numero, type_ruche, date_installation, race_abeilles, statut, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$data['residence_id'],
            !empty($data['espace_id']) ? (int)$data['espace_id'] : null,
            trim($data['numero']),
            !empty($data['type_ruche']) ? trim($data['type_ruche']) : null,
            !empty($data['date_installation']) ? $data['date_installation'] : null,
            !empty($data['race_abeilles']) ? trim($data['race_abeilles']) : null,
            $statut,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        $newId = (int)$this->db->lastInsertId();
        $this->logStatutChange($newId, null, $statut, $data['motif_statut'] ?? 'Création de la ruche', $userId);
        return $newId;
    }

    public function updateRuche(int $id, array $data, ?int $userId = null): void {
        // Lire le statut avant pour détecter un changement
        $statutAvant = $this->db->query("SELECT statut FROM jardin_ruches WHERE id = " . (int)$id)->fetchColumn() ?: null;
        $statutApres = $data['statut'] ?? 'active';

        $sql = "UPDATE jardin_ruches SET espace_id = ?, numero = ?, type_ruche = ?, date_installation = ?,
                                         race_abeilles = ?, statut = ?, notes = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            !empty($data['espace_id']) ? (int)$data['espace_id'] : null,
            trim($data['numero']),
            !empty($data['type_ruche']) ? trim($data['type_ruche']) : null,
            !empty($data['date_installation']) ? $data['date_installation'] : null,
            !empty($data['race_abeilles']) ? trim($data['race_abeilles']) : null,
            $statutApres,
            !empty($data['notes']) ? trim($data['notes']) : null,
            $id
        ]);

        if ($statutAvant !== null && $statutAvant !== $statutApres) {
            $this->logStatutChange($id, $statutAvant, $statutApres, $data['motif_statut'] ?? null, $userId);
        }
    }

    public function setRucheStatut(int $id, string $statut, ?string $motif = null, ?int $userId = null): void {
        $statutAvant = $this->db->query("SELECT statut FROM jardin_ruches WHERE id = " . (int)$id)->fetchColumn() ?: null;
        $stmt = $this->db->prepare("UPDATE jardin_ruches SET statut = ? WHERE id = ?");
        $stmt->execute([$statut, $id]);
        if ($statutAvant !== null && $statutAvant !== $statut) {
            $this->logStatutChange($id, $statutAvant, $statut, $motif, $userId);
        }
    }

    /**
     * Enregistre un changement de statut d'une ruche.
     */
    public function logStatutChange(int $rucheId, ?string $avant, string $apres, ?string $motif = null, ?int $userId = null): void {
        $stmt = $this->db->prepare("INSERT INTO jardin_ruches_statut_log
            (ruche_id, statut_avant, statut_apres, motif, user_id)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$rucheId, $avant, $apres, $motif, $userId]);
    }

    /**
     * Historique des changements de statut d'une ruche (plus récent en premier).
     */
    public function getStatutHistory(int $rucheId): array {
        $sql = "SELECT l.*, u.prenom as user_prenom, u.nom as user_nom
                FROM jardin_ruches_statut_log l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.ruche_id = ?
                ORDER BY l.changed_at DESC, l.id DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$rucheId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function updateRuchePhoto(int $id, ?string $path): void {
        $stmt = $this->db->prepare("UPDATE jardin_ruches SET photo = ? WHERE id = ?");
        $stmt->execute([$path, $id]);
    }

    public function getRuchePhoto(int $id): ?string {
        $stmt = $this->db->prepare("SELECT photo FROM jardin_ruches WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetchColumn();
        return $p ?: null;
    }

    /**
     * Ruches actives sans visite depuis > N jours (ou jamais visitées).
     */
    public function getRuchesSansVisite(array $residenceIds, int $joursSeuil = 30): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$joursSeuil]);
        $sql = "SELECT r.id, r.numero, r.residence_id, c.nom as residence_nom,
                       (SELECT MAX(date_visite) FROM jardin_ruches_visites WHERE ruche_id = r.id) as derniere_visite
                FROM jardin_ruches r
                JOIN coproprietees c ON r.residence_id = c.id
                WHERE r.statut = 'active' AND r.residence_id IN ($ph)
                  AND (
                    (SELECT MAX(date_visite) FROM jardin_ruches_visites WHERE ruche_id = r.id) IS NULL
                    OR (SELECT MAX(date_visite) FROM jardin_ruches_visites WHERE ruche_id = r.id) < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                  )
                ORDER BY derniere_visite ASC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  CALENDRIER TRAITEMENTS APICOLES (référentiel FR + alertes)
    // ─────────────────────────────────────────────────────────────

    /**
     * Retourne les traitements applicables à une résidence (templates système + spécifiques).
     */
    public function getCalendrierTraitements(?int $residenceId = null, bool $actifsOnly = true): array {
        $sql = "SELECT t.*, r.nom as residence_nom
                FROM jardin_traitements_calendrier t
                LEFT JOIN coproprietees r ON t.residence_id = r.id
                WHERE 1=1";
        $params = [];
        if ($actifsOnly) $sql .= " AND t.actif = 1";
        if ($residenceId !== null) {
            $sql .= " AND (t.residence_id IS NULL OR t.residence_id = ?)";
            $params[] = $residenceId;
        }
        $sql .= " ORDER BY t.priorite, t.mois_debut, t.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getTraitementCalendrier(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM jardin_traitements_calendrier WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function createTraitementCalendrier(array $d): int {
        $sql = "INSERT INTO jardin_traitements_calendrier
                (residence_id, nom, description, mois_debut, mois_fin, priorite, produit_suggere, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            !empty($d['residence_id']) ? (int)$d['residence_id'] : null,
            trim($d['nom']),
            !empty($d['description']) ? trim($d['description']) : null,
            (int)$d['mois_debut'],
            (int)$d['mois_fin'],
            (int)($d['priorite'] ?? 2),
            !empty($d['produit_suggere']) ? trim($d['produit_suggere']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateTraitementCalendrier(int $id, array $d): void {
        $sql = "UPDATE jardin_traitements_calendrier
                SET residence_id = ?, nom = ?, description = ?, mois_debut = ?, mois_fin = ?,
                    priorite = ?, produit_suggere = ?, actif = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            !empty($d['residence_id']) ? (int)$d['residence_id'] : null,
            trim($d['nom']),
            !empty($d['description']) ? trim($d['description']) : null,
            (int)$d['mois_debut'],
            (int)$d['mois_fin'],
            (int)($d['priorite'] ?? 2),
            !empty($d['produit_suggere']) ? trim($d['produit_suggere']) : null,
            isset($d['actif']) ? (int)$d['actif'] : 1,
            $id
        ]);
    }

    public function deleteTraitementCalendrier(int $id): void {
        $this->db->prepare("UPDATE jardin_traitements_calendrier SET actif = 0 WHERE id = ?")->execute([$id]);
    }

    /**
     * Pour une ruche donnée (et sa résidence), liste les traitements recommandés cette année
     * avec leur statut (fait / à faire / en fenêtre).
     */
    public function getTraitementsPourRuche(int $rucheId, int $residenceId, ?int $annee = null): array {
        $annee = $annee ?? (int)date('Y');
        $traitements = $this->getCalendrierTraitements($residenceId);
        $moisCourant = (int)date('n');
        $result = [];

        foreach ($traitements as $t) {
            $md = (int)$t['mois_debut']; $mf = (int)$t['mois_fin'];
            $enFenetre = ($md <= $mf)
                ? ($moisCourant >= $md && $moisCourant <= $mf)
                : ($moisCourant >= $md || $moisCourant <= $mf);

            // Vérifier si une visite type='traitement' existe dans la fenêtre de l'année
            $dateDebut = sprintf('%04d-%02d-01', $annee, $md);
            $dateFinAnnee = ($md <= $mf) ? $annee : $annee; // simplification : on reste sur l'année courante
            $dateFin = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $dateFinAnnee, $mf)));

            $sql = "SELECT id, date_visite, traitement_produit
                    FROM jardin_ruches_visites
                    WHERE ruche_id = ?
                      AND type_intervention = 'traitement'
                      AND date_visite BETWEEN ? AND ?
                    ORDER BY date_visite DESC LIMIT 1";
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$rucheId, $dateDebut, $dateFin]);
                $visite = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) { $visite = false; }

            $result[] = [
                'id'              => (int)$t['id'],
                'nom'             => $t['nom'],
                'description'     => $t['description'],
                'mois_debut'      => $md,
                'mois_fin'        => $mf,
                'priorite'        => (int)$t['priorite'],
                'produit_suggere' => $t['produit_suggere'],
                'en_fenetre'      => $enFenetre,
                'fait'            => (bool)$visite,
                'date_visite'     => $visite['date_visite'] ?? null,
                'traitement_produit' => $visite['traitement_produit'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Alertes globales : pour toutes les ruches actives des résidences données,
     * liste les traitements en fenêtre actuelle qui n'ont pas été effectués cette année.
     */
    public function getAlertesTraitements(array $residenceIds, ?int $annee = null): array {
        if (empty($residenceIds)) return [];
        $annee = $annee ?? (int)date('Y');
        $moisCourant = (int)date('n');
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));

        // Récupérer toutes les ruches actives des résidences
        $rstmt = $this->db->prepare("SELECT r.id, r.numero, r.residence_id, c.nom as residence_nom
                                     FROM jardin_ruches r
                                     JOIN coproprietees c ON r.residence_id = c.id
                                     WHERE r.statut = 'active' AND r.residence_id IN ($ph)
                                     ORDER BY c.nom, r.numero");
        $rstmt->execute(array_values($residenceIds));
        $ruches = $rstmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($ruches)) return [];

        // Récupérer tous les traitements applicables groupés par residence_id (ou NULL = global)
        $tstmt = $this->db->prepare("SELECT * FROM jardin_traitements_calendrier
                                     WHERE actif = 1 AND (residence_id IS NULL OR residence_id IN ($ph))
                                     ORDER BY priorite, mois_debut");
        $tstmt->execute(array_values($residenceIds));
        $traitements = $tstmt->fetchAll(PDO::FETCH_ASSOC);

        $alertes = [];
        foreach ($traitements as $t) {
            $md = (int)$t['mois_debut']; $mf = (int)$t['mois_fin'];
            $enFenetre = ($md <= $mf)
                ? ($moisCourant >= $md && $moisCourant <= $mf)
                : ($moisCourant >= $md || $moisCourant <= $mf);
            if (!$enFenetre) continue;

            $dateDebut = sprintf('%04d-%02d-01', $annee, $md);
            $dateFin = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $annee, $mf)));

            foreach ($ruches as $r) {
                // Ne pas appliquer un traitement spécifique à une autre résidence
                if ($t['residence_id'] !== null && (int)$t['residence_id'] !== (int)$r['residence_id']) continue;

                $vstmt = $this->db->prepare("SELECT 1 FROM jardin_ruches_visites
                                             WHERE ruche_id = ? AND type_intervention = 'traitement'
                                               AND date_visite BETWEEN ? AND ? LIMIT 1");
                $vstmt->execute([$r['id'], $dateDebut, $dateFin]);
                if ($vstmt->fetchColumn()) continue; // déjà fait

                $alertes[] = [
                    'ruche_id'        => (int)$r['id'],
                    'ruche_numero'    => $r['numero'],
                    'residence_id'    => (int)$r['residence_id'],
                    'residence_nom'   => $r['residence_nom'],
                    'traitement_id'   => (int)$t['id'],
                    'traitement_nom'  => $t['nom'],
                    'produit_suggere' => $t['produit_suggere'],
                    'priorite'        => (int)$t['priorite'],
                    'mois_debut'      => $md,
                    'mois_fin'        => $mf,
                ];
            }
        }
        // Tri par priorité puis résidence puis ruche
        usort($alertes, function($a, $b) {
            return [$a['priorite'], $a['residence_nom'], $a['ruche_numero']]
               <=> [$b['priorite'], $b['residence_nom'], $b['ruche_numero']];
        });
        return $alertes;
    }

    /**
     * Compte les traitements requis pour une ruche (alerte badge dans la liste).
     */
    public function countAlertesRuche(int $rucheId, int $residenceId, ?int $annee = null): int {
        $traitements = $this->getTraitementsPourRuche($rucheId, $residenceId, $annee);
        $n = 0;
        foreach ($traitements as $t) { if ($t['en_fenetre'] && !$t['fait']) $n++; }
        return $n;
    }

    // ─────────────────────────────────────────────────────────────
    //  CARNET DE VISITE
    // ─────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────
    //  APICULTURE CONFIG (table coproprietees_apiculture, 1:1)
    // ─────────────────────────────────────────────────────────────

    public function getApiculture(int $residenceId): ?array {
        $sql = "SELECT a.*, u.prenom as referent_prenom, u.nom as referent_nom, u.role as referent_role
                FROM coproprietees_apiculture a
                LEFT JOIN users u ON a.apiculteur_referent_user_id = u.id
                WHERE a.residence_id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Crée ou met à jour la config apiculture (upsert basé sur la clé primaire residence_id).
     */
    public function upsertApiculture(int $residenceId, array $data): bool {
        $sql = "INSERT INTO coproprietees_apiculture
                (residence_id, numero_napi, date_declaration_prefecture, nombre_max_ruches,
                 apiculteur_referent_user_id, apiculteur_referent_externe, type_rucher,
                 distance_habitations_m, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    numero_napi = VALUES(numero_napi),
                    date_declaration_prefecture = VALUES(date_declaration_prefecture),
                    nombre_max_ruches = VALUES(nombre_max_ruches),
                    apiculteur_referent_user_id = VALUES(apiculteur_referent_user_id),
                    apiculteur_referent_externe = VALUES(apiculteur_referent_externe),
                    type_rucher = VALUES(type_rucher),
                    distance_habitations_m = VALUES(distance_habitations_m),
                    notes = VALUES(notes)";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $residenceId,
                !empty($data['numero_napi']) ? trim($data['numero_napi']) : null,
                !empty($data['date_declaration_prefecture']) ? $data['date_declaration_prefecture'] : null,
                !empty($data['nombre_max_ruches']) ? (int)$data['nombre_max_ruches'] : null,
                !empty($data['apiculteur_referent_user_id']) ? (int)$data['apiculteur_referent_user_id'] : null,
                !empty($data['apiculteur_referent_externe']) ? trim($data['apiculteur_referent_externe']) : null,
                $data['type_rucher'] ?? 'sedentaire',
                !empty($data['distance_habitations_m']) ? (int)$data['distance_habitations_m'] : null,
                !empty($data['notes']) ? trim($data['notes']) : null,
            ]);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); throw $e; }
    }

    /**
     * Candidats apiculteur référent : users des rôles manager/direction/jardinier_manager
     * avec un lien actif à la résidence donnée.
     */
    public function getApiculteursCandidats(int $residenceId): array {
        $sql = "SELECT DISTINCT u.id, u.prenom, u.nom, u.role, r.nom_affichage as role_nom
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.statut = 'actif'
                LEFT JOIN roles r ON u.role = r.slug
                WHERE u.actif = 1
                  AND u.role IN ('admin','directeur_residence','jardinier_manager')
                  AND ur.residence_id = ?
                ORDER BY u.role, u.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  COMPTABILITÉ JARDINERIE
    // ─────────────────────────────────────────────────────────────

    // Phase 1 (refonte) : la table jardin_comptabilite est remplacée par
    // ecritures_comptables filtrée par module_source='jardinage'.
    // L'API publique de ces méthodes reste IDENTIQUE pour ne pas casser le controller.
    // Mappings :
    //   - espace_id  → imputation_type='espace_jardin' + imputation_id
    //   - reference_type/id → reference_externe_type/id
    //   - compte_comptable (numero string) → compte_comptable_id (FK) via lookup auto
    //   - mois/annee → calculés depuis date_ecriture (plus stockés explicitement)

    public function createEcriture(array $d): int {
        $date = $d['date_ecriture'] ?? date('Y-m-d');
        // Résoudre numero_compte string → id FK
        $compteId = null;
        if (!empty($d['compte_comptable'])) {
            $st = $this->db->prepare("SELECT id FROM comptes_comptables WHERE numero_compte = ? LIMIT 1");
            $st->execute([trim($d['compte_comptable'])]);
            $compteId = $st->fetchColumn() ?: null;
        }

        $eModel = new Ecriture();
        return $eModel->create([
            'residence_id'           => (int)$d['residence_id'],
            'module_source'          => 'jardinage',
            'categorie'              => $d['categorie'],
            'date_ecriture'          => $date,
            'type_ecriture'          => $d['type_ecriture'],
            'montant_ht'             => (float)$d['montant_ht'],
            'taux_tva'               => $d['taux_tva'] ?? null,
            'montant_tva'            => (float)($d['montant_tva'] ?? 0),
            'montant_ttc'            => $d['montant_ttc'] ?? null,
            'compte_comptable_id'    => $compteId,
            'reference_externe_type' => $d['reference_type'] ?? null,
            'reference_externe_id'   => !empty($d['reference_id']) ? (int)$d['reference_id'] : null,
            'imputation_type'        => !empty($d['espace_id']) ? 'espace_jardin' : null,
            'imputation_id'          => !empty($d['espace_id']) ? (int)$d['espace_id'] : null,
            'libelle'                => trim($d['libelle']),
            'notes'                  => !empty($d['notes']) ? trim($d['notes']) : null,
            'auto_genere'            => !empty($d['auto_genere']) ? 1 : 0,
            'created_by'             => !empty($d['created_by']) ? (int)$d['created_by'] : null,
        ]);
    }

    public function deleteEcriture(int $id): void {
        // Force statut=brouillon pour que deleteEcriture du model Ecriture passe.
        // (On reste sur du hard delete car l'API publique d'origine était hard delete.)
        $this->db->prepare("UPDATE ecritures_comptables SET statut='brouillon' WHERE id = ? AND module_source='jardinage'")->execute([$id]);
        (new Ecriture())->deleteEcriture($id);
    }

    public function getEcriture(int $id): ?array {
        $sql = "SELECT e.id, e.residence_id, e.date_ecriture, e.type_ecriture, e.categorie,
                       e.reference_externe_id AS reference_id, e.reference_externe_type AS reference_type,
                       e.imputation_id AS espace_id,
                       e.libelle, e.montant_ht, e.taux_tva, e.montant_tva, e.montant_ttc,
                       cc.numero_compte AS compte_comptable,
                       MONTH(e.date_ecriture) AS mois, YEAR(e.date_ecriture) AS annee,
                       e.notes, e.created_by, e.created_at
                FROM ecritures_comptables e
                LEFT JOIN comptes_comptables cc ON cc.id = e.compte_comptable_id
                WHERE e.id = ? AND e.module_source = 'jardinage'";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    public function getEcritures(array $residenceIds, ?int $annee = null, ?int $mois = null, ?string $type = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);
        $sql = "SELECT e.id, e.residence_id, e.date_ecriture, e.type_ecriture, e.categorie,
                       e.reference_externe_id AS reference_id, e.reference_externe_type AS reference_type,
                       e.imputation_id AS espace_id,
                       e.libelle, e.montant_ht, e.taux_tva, e.montant_tva, e.montant_ttc,
                       cc.numero_compte AS compte_comptable,
                       MONTH(e.date_ecriture) AS mois, YEAR(e.date_ecriture) AS annee,
                       e.notes, e.created_at,
                       r.nom AS residence_nom,
                       jesp.nom AS espace_nom
                FROM ecritures_comptables e
                JOIN coproprietees r ON e.residence_id = r.id
                LEFT JOIN comptes_comptables cc ON cc.id = e.compte_comptable_id
                LEFT JOIN jardin_espaces jesp ON jesp.id = e.imputation_id AND e.imputation_type = 'espace_jardin'
                WHERE e.module_source = 'jardinage'
                  AND e.residence_id IN ($ph)
                  AND e.statut != 'brouillon'";
        if ($annee) { $sql .= " AND YEAR(e.date_ecriture) = ?"; $params[] = $annee; }
        if ($mois)  { $sql .= " AND MONTH(e.date_ecriture) = ?"; $params[] = $mois; }
        if ($type)  { $sql .= " AND e.type_ecriture = ?"; $params[] = $type; }
        $sql .= " ORDER BY e.date_ecriture DESC, e.id DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getTotauxAnnuels(array $residenceIds, int $annee): array {
        $res = ['recettes_ht' => 0, 'recettes_tva' => 0, 'recettes_ttc' => 0,
                'depenses_ht' => 0, 'depenses_tva' => 0, 'depenses_ttc' => 0,
                'resultat_ht' => 0, 'resultat_ttc' => 0, 'nb_ecritures' => 0];
        if (empty($residenceIds)) return $res;
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);
        try {
            $sql = "SELECT type_ecriture,
                           COALESCE(SUM(montant_ht),0) AS ht,
                           COALESCE(SUM(montant_tva),0) AS tva,
                           COALESCE(SUM(montant_ttc),0) AS ttc,
                           COUNT(*) AS n
                    FROM ecritures_comptables
                    WHERE module_source = 'jardinage'
                      AND residence_id IN ($ph)
                      AND YEAR(date_ecriture) = ?
                      AND statut != 'brouillon'
                    GROUP BY type_ecriture";
            $stmt = $this->db->prepare($sql); $stmt->execute($params);
            foreach ($stmt as $row) {
                $pfx = $row['type_ecriture'] === 'recette' ? 'recettes' : 'depenses';
                $res["{$pfx}_ht"]  = (float)$row['ht'];
                $res["{$pfx}_tva"] = (float)$row['tva'];
                $res["{$pfx}_ttc"] = (float)$row['ttc'];
                $res['nb_ecritures'] += (int)$row['n'];
            }
            $res['resultat_ht']  = $res['recettes_ht']  - $res['depenses_ht'];
            $res['resultat_ttc'] = $res['recettes_ttc'] - $res['depenses_ttc'];
        } catch (PDOException $e) { $this->logError($e->getMessage()); }
        return $res;
    }

    public function getSyntheseMensuelle(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT MONTH(date_ecriture) AS mois,
                       COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END),0) AS recettes_ttc,
                       COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END),0) AS depenses_ttc
                FROM ecritures_comptables
                WHERE module_source = 'jardinage'
                  AND residence_id IN ($ph)
                  AND YEAR(date_ecriture) = ?
                  AND statut != 'brouillon'
                GROUP BY MONTH(date_ecriture)
                ORDER BY mois";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * TVA collectée (recettes) vs déductible (dépenses) sur la période — pour KPIs et CA3.
     * Mêmes signatures et clés que Restauration::getTVA et Menage (cohérence dashboard).
     */
    public function getTVA(array $residenceIds, int $annee, ?int $mois = null): array {
        $defaut = ['collectee' => 0, 'deductible' => 0, 'a_reverser' => 0];
        if (empty($residenceIds)) return $defaut;
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT
                    SUM(CASE WHEN type_ecriture='recette' THEN montant_tva ELSE 0 END) AS collectee,
                    SUM(CASE WHEN type_ecriture='depense' THEN montant_tva ELSE 0 END) AS deductible
                FROM ecritures_comptables
                WHERE module_source = 'jardinage'
                  AND residence_id IN ($ph)
                  AND YEAR(date_ecriture) = ?
                  AND statut != 'brouillon'";
        if ($mois) { $sql .= " AND MONTH(date_ecriture) = ?"; $params[] = $mois; }
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $defaut;
            $row['a_reverser'] = ($row['collectee'] ?? 0) - ($row['deductible'] ?? 0);
            return $row;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return $defaut; }
    }

    /**
     * Coût par espace : somme des écritures `espace_id = X` (dépenses type achat_fournisseur
     * + autres), + estimation via sorties inventaire affectées à l'espace.
     */
    public function getCoutParEspace(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));

        // Part 1 : écritures compta directement imputées (via imputation_type='espace_jardin')
        $sql1 = "SELECT e.id as espace_id, e.nom as espace_nom, e.type as espace_type,
                        r.nom as residence_nom,
                        COALESCE(SUM(c.montant_ttc),0) as total_compta
                 FROM jardin_espaces e
                 JOIN coproprietees r ON e.residence_id = r.id
                 LEFT JOIN ecritures_comptables c
                        ON c.imputation_type = 'espace_jardin'
                       AND c.imputation_id = e.id
                       AND c.module_source = 'jardinage'
                       AND c.type_ecriture = 'depense'
                       AND YEAR(c.date_ecriture) = ?
                       AND c.statut != 'brouillon'
                 WHERE e.residence_id IN ($ph) AND e.actif = 1
                 GROUP BY e.id";
        try {
            $params1 = array_merge([$annee], array_values($residenceIds));
            $stmt = $this->db->prepare($sql1);
            $stmt->execute($params1);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql1); return []; }

        // Part 2 : sorties inventaire × prix unitaire du produit, groupées par espace_id
        $params2 = array_merge([$annee], array_values($residenceIds));
        $sql2 = "SELECT m.espace_id,
                        COALESCE(SUM(m.quantite * COALESCE(p.prix_unitaire, 0)), 0) as total_sorties
                 FROM jardin_inventaire_mouvements m
                 JOIN jardin_inventaire i ON m.inventaire_id = i.id
                 JOIN jardin_produits p ON i.produit_id = p.id
                 WHERE m.type_mouvement = 'sortie'
                   AND m.espace_id IS NOT NULL
                   AND YEAR(m.created_at) = ?
                   AND i.residence_id IN ($ph)
                 GROUP BY m.espace_id";
        $sorties = [];
        try {
            $stmt = $this->db->prepare($sql2); $stmt->execute($params2);
            foreach ($stmt as $r) $sorties[(int)$r['espace_id']] = (float)$r['total_sorties'];
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql2); }

        foreach ($rows as &$r) {
            $r['total_sorties'] = $sorties[(int)$r['espace_id']] ?? 0.0;
            $r['total_compta']  = (float)$r['total_compta'];
            $r['total_cout']    = (float)$r['total_compta'] + $r['total_sorties'];
        }
        unset($r);
        // Tri par coût total desc
        usort($rows, fn($a, $b) => $b['total_cout'] <=> $a['total_cout']);
        return $rows;
    }

    /**
     * Dépenses groupées par fournisseur (à partir des commandes livrées/facturées).
     */
    public function getDepensesParFournisseur(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT f.id, f.nom,
                       COUNT(c.id) as nb_commandes,
                       COALESCE(SUM(c.montant_total_ttc),0) as total_ttc,
                       COALESCE(SUM(c.montant_total_ht),0) as total_ht
                FROM fournisseurs f
                JOIN commandes c ON c.fournisseur_id = f.id AND c.module = 'jardinage'
                WHERE c.residence_id IN ($ph)
                  AND c.statut IN ('livree','livree_partiel','facturee')
                  AND YEAR(c.date_commande) = ?";
        if ($mois) { $sql .= " AND MONTH(c.date_commande) = ?"; $params[] = $mois; }
        $sql .= " GROUP BY f.id ORDER BY total_ttc DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Récoltes de miel (visites type=recolte) pas encore comptabilisées (pas de ref dans compta).
     */
    public function getRecoltesNonComptabilisees(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT v.id as visite_id, v.date_visite, v.quantite_miel_kg,
                       r.id as ruche_id, r.numero as ruche_numero, r.residence_id,
                       res.nom as residence_nom, u.prenom as user_prenom, u.nom as user_nom
                FROM jardin_ruches_visites v
                JOIN jardin_ruches r ON v.ruche_id = r.id
                JOIN coproprietees res ON r.residence_id = res.id
                LEFT JOIN users u ON v.user_id = u.id
                WHERE v.type_intervention = 'recolte'
                  AND v.quantite_miel_kg > 0
                  AND r.residence_id IN ($ph)
                  AND YEAR(v.date_visite) = ?
                  AND NOT EXISTS (
                    SELECT 1 FROM ecritures_comptables c
                    WHERE c.module_source = 'jardinage'
                      AND c.reference_externe_type = 'ruche_visite'
                      AND c.reference_externe_id = v.id
                      AND c.statut != 'brouillon'
                  )
                ORDER BY v.date_visite DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Données brutes pour export CSV.
     */
    public function getEcrituresExport(array $residenceIds, ?int $annee = null, ?int $mois = null): array {
        return $this->getEcritures($residenceIds, $annee, $mois);
    }

    // ─────────────────────────────────────────────────────────────
    //  COMMANDES FOURNISSEURS — centralisées dans app/models/Commande.php
    //  (table unifiée `commandes` polymorphe, module = 'jardinage')
    // ─────────────────────────────────────────────────────────────

    /**
     * Fournisseurs actifs liés à la résidence (pour sélecteur commande).
     */
    public function getFournisseursActifsResidence(int $residenceId): array {
        $sql = "SELECT f.id, f.nom FROM fournisseurs f
                JOIN fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.residence_id = ?
                WHERE f.actif = 1 AND fr.statut = 'actif'
                  AND FIND_IN_SET('jardinage', f.type_service) > 0
                ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  FOURNISSEURS JARDINAGE (lecture-seule, pivot global fournisseur_residence)
    //  CRUD (lier/modifier/délier) centralisé dans /fournisseur/show/<id>
    // ─────────────────────────────────────────────────────────────

    public function getFournisseursResidence(int $residenceId): array {
        $sql = "SELECT f.*, fr.id as pivot_id, fr.statut as lien_statut,
                       fr.contact_local, fr.telephone_local,
                       fr.jour_livraison, fr.delai_livraison_jours,
                       fr.notes as pivot_notes,
                       (SELECT COUNT(*) FROM commandes c WHERE c.module='jardinage' AND c.fournisseur_id=f.id AND c.residence_id=?) as nb_commandes,
                       (SELECT COALESCE(SUM(c2.montant_total_ttc),0) FROM commandes c2 WHERE c2.module='jardinage' AND c2.fournisseur_id=f.id AND c2.residence_id=? AND c2.statut!='annulee') as total_commandes,
                       (SELECT MAX(c3.date_commande) FROM commandes c3 WHERE c3.module='jardinage' AND c3.fournisseur_id=f.id AND c3.residence_id=?) as derniere_commande
                FROM fournisseurs f
                JOIN fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.residence_id = ?
                WHERE fr.statut = 'actif' AND f.actif = 1
                  AND FIND_IN_SET('jardinage', f.type_service) > 0
                ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId, $residenceId, $residenceId, $residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // Les méthodes getFournisseursDisponibles, getLienFournisseur, lierFournisseurResidence,
    // updateLienFournisseur, delierFournisseurResidence ont été centralisées dans
    // app/models/Fournisseur.php (voir getFournisseursDisponibles/getLien/lier/updateLien/delier).

    public function getVisitesByRuche(int $rucheId, int $limit = 0): array {
        $sql = "SELECT v.*, u.prenom as user_prenom, u.nom as user_nom
                FROM jardin_ruches_visites v
                LEFT JOIN users u ON v.user_id = u.id
                WHERE v.ruche_id = ?
                ORDER BY v.date_visite DESC, v.id DESC";
        if ($limit > 0) $sql .= " LIMIT " . (int)$limit;
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$rucheId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function createVisite(array $data): int {
        $sql = "INSERT INTO jardin_ruches_visites
                (ruche_id, date_visite, user_id, type_intervention, couvain_etat, reine_vue,
                 quantite_miel_kg, traitement_produit, observations)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$data['ruche_id'],
            $data['date_visite'] ?? date('Y-m-d'),
            (int)$data['user_id'],
            $data['type_intervention'] ?? 'inspection',
            !empty($data['couvain_etat']) ? $data['couvain_etat'] : null,
            isset($data['reine_vue']) && $data['reine_vue'] !== '' ? (int)(bool)$data['reine_vue'] : null,
            !empty($data['quantite_miel_kg']) ? (float)$data['quantite_miel_kg'] : null,
            !empty($data['traitement_produit']) ? trim($data['traitement_produit']) : null,
            !empty($data['observations']) ? trim($data['observations']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
