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
        $sql = "SELECT p.*, f.nom as fournisseur_nom
                FROM jardin_produits p
                LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id
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
        $sql = "SELECT * FROM jardin_produits WHERE id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    public function createProduit(array $data): int {
        $sql = "INSERT INTO jardin_produits (nom, categorie, type, unite, prix_unitaire, fournisseur_id, marque, bio, danger, notes, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($data['nom']),
            $data['categorie'] ?? 'autre',
            $data['type'] ?? 'produit',
            $data['unite'] ?? 'piece',
            !empty($data['prix_unitaire']) ? (float)$data['prix_unitaire'] : null,
            !empty($data['fournisseur_id']) ? (int)$data['fournisseur_id'] : null,
            !empty($data['marque']) ? trim($data['marque']) : null,
            !empty($data['bio']) ? 1 : 0,
            !empty($data['danger']) ? trim($data['danger']) : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProduit(int $id, array $data): void {
        $sql = "UPDATE jardin_produits SET nom = ?, categorie = ?, type = ?, unite = ?, prix_unitaire = ?,
                       fournisseur_id = ?, marque = ?, bio = ?, danger = ?, notes = ?, actif = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            trim($data['nom']),
            $data['categorie'] ?? 'autre',
            $data['type'] ?? 'produit',
            $data['unite'] ?? 'piece',
            !empty($data['prix_unitaire']) ? (float)$data['prix_unitaire'] : null,
            !empty($data['fournisseur_id']) ? (int)$data['fournisseur_id'] : null,
            !empty($data['marque']) ? trim($data['marque']) : null,
            !empty($data['bio']) ? 1 : 0,
            !empty($data['danger']) ? trim($data['danger']) : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
            isset($data['actif']) ? (int)$data['actif'] : 1,
            $id
        ]);
    }

    public function deleteProduit(int $id): void {
        $stmt = $this->db->prepare("UPDATE jardin_produits SET actif = 0 WHERE id = ?");
        $stmt->execute([$id]);
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

    public function createEcriture(array $d): int {
        $date = $d['date_ecriture'] ?? date('Y-m-d');
        $ht = (float)$d['montant_ht'];
        $tva = (float)($d['montant_tva'] ?? 0);
        $ttc = (float)($d['montant_ttc'] ?? ($ht + $tva));

        $sql = "INSERT INTO jardin_comptabilite
                (residence_id, date_ecriture, type_ecriture, categorie, reference_id, reference_type,
                 espace_id, libelle, montant_ht, montant_tva, montant_ttc, compte_comptable, mois, annee, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$d['residence_id'],
            $date,
            $d['type_ecriture'],
            $d['categorie'],
            !empty($d['reference_id']) ? (int)$d['reference_id'] : null,
            $d['reference_type'] ?? 'manuel',
            !empty($d['espace_id']) ? (int)$d['espace_id'] : null,
            trim($d['libelle']),
            $ht, $tva, $ttc,
            !empty($d['compte_comptable']) ? trim($d['compte_comptable']) : null,
            (int)($d['mois'] ?? date('m', strtotime($date))),
            (int)($d['annee'] ?? date('Y', strtotime($date))),
            !empty($d['notes']) ? trim($d['notes']) : null,
            !empty($d['created_by']) ? (int)$d['created_by'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteEcriture(int $id): void {
        $this->db->prepare("DELETE FROM jardin_comptabilite WHERE id = ?")->execute([$id]);
    }

    public function getEcriture(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM jardin_comptabilite WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function getEcritures(array $residenceIds, ?int $annee = null, ?int $mois = null, ?string $type = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);
        $sql = "SELECT c.*, r.nom as residence_nom, e.nom as espace_nom
                FROM jardin_comptabilite c
                JOIN coproprietees r ON c.residence_id = r.id
                LEFT JOIN jardin_espaces e ON c.espace_id = e.id
                WHERE c.residence_id IN ($ph)";
        if ($annee) { $sql .= " AND c.annee = ?"; $params[] = $annee; }
        if ($mois)  { $sql .= " AND c.mois = ?";  $params[] = $mois; }
        if ($type)  { $sql .= " AND c.type_ecriture = ?"; $params[] = $type; }
        $sql .= " ORDER BY c.date_ecriture DESC, c.id DESC";
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
                           COALESCE(SUM(montant_ht),0) as ht,
                           COALESCE(SUM(montant_tva),0) as tva,
                           COALESCE(SUM(montant_ttc),0) as ttc,
                           COUNT(*) as n
                    FROM jardin_comptabilite
                    WHERE residence_id IN ($ph) AND annee = ?
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
        $sql = "SELECT mois,
                       COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END),0) as recettes_ttc,
                       COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END),0) as depenses_ttc
                FROM jardin_comptabilite
                WHERE residence_id IN ($ph) AND annee = ?
                GROUP BY mois ORDER BY mois";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Coût par espace : somme des écritures `espace_id = X` (dépenses type achat_fournisseur
     * + autres), + estimation via sorties inventaire affectées à l'espace.
     */
    public function getCoutParEspace(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));

        // Part 1 : écritures compta directement imputées
        $sql1 = "SELECT e.id as espace_id, e.nom as espace_nom, e.type as espace_type,
                        r.nom as residence_nom,
                        COALESCE(SUM(c.montant_ttc),0) as total_compta
                 FROM jardin_espaces e
                 JOIN coproprietees r ON e.residence_id = r.id
                 LEFT JOIN jardin_comptabilite c ON c.espace_id = e.id AND c.type_ecriture = 'depense' AND c.annee = ?
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
                JOIN jardin_commandes c ON c.fournisseur_id = f.id
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
                    SELECT 1 FROM jardin_comptabilite c
                    WHERE c.reference_type = 'ruche_visite' AND c.reference_id = v.id
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
    //  COMMANDES FOURNISSEURS
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste des commandes, filtrable par résidence(s) et statut.
     */
    public function getCommandes(array $residenceIds, ?string $statut = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);
        $sql = "SELECT c.*, f.nom as fournisseur_nom, r.nom as residence_nom,
                       (SELECT COUNT(*) FROM jardin_commande_lignes WHERE commande_id = c.id) as nb_lignes
                FROM jardin_commandes c
                JOIN fournisseurs f ON c.fournisseur_id = f.id
                JOIN coproprietees r ON c.residence_id = r.id
                WHERE c.residence_id IN ($ph)";
        if ($statut) { $sql .= " AND c.statut = ?"; $params[] = $statut; }
        $sql .= " ORDER BY c.date_commande DESC, c.id DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Détail d'une commande + lignes + fournisseur + résidence.
     */
    public function getCommande(int $id): ?array {
        $sql = "SELECT c.*, f.nom as fournisseur_nom, f.email as fournisseur_email,
                       f.telephone as fournisseur_telephone, f.adresse as fournisseur_adresse,
                       f.code_postal as fournisseur_cp, f.ville as fournisseur_ville,
                       r.nom as residence_nom,
                       u.prenom as created_by_prenom, u.nom as created_by_nom
                FROM jardin_commandes c
                JOIN fournisseurs f ON c.fournisseur_id = f.id
                JOIN coproprietees r ON c.residence_id = r.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.id = ?";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute([$id]);
            $cmd = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cmd) return null;

            $lstmt = $this->db->prepare("SELECT l.*, p.nom as produit_nom, p.unite, p.categorie
                                         FROM jardin_commande_lignes l
                                         JOIN jardin_produits p ON l.produit_id = p.id
                                         WHERE l.commande_id = ?
                                         ORDER BY l.id");
            $lstmt->execute([$id]);
            $cmd['lignes'] = $lstmt->fetchAll(PDO::FETCH_ASSOC);
            return $cmd;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Génère un numéro séquentiel : CMD-JARD-YYYY-NNNN
     */
    private function generateNumeroCommande(): string {
        $year = date('Y');
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM jardin_commandes WHERE YEAR(date_commande) = ?");
            $stmt->execute([$year]);
            $n = (int)$stmt->fetchColumn() + 1;
        } catch (PDOException $e) { $n = 1; }
        return 'CMD-JARD-' . $year . '-' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Création d'une commande avec ses lignes (transaction).
     */
    public function createCommande(array $data, array $lignes): int {
        $this->db->beginTransaction();
        try {
            $numero = $this->generateNumeroCommande();

            // Totaux
            $totalHt = 0.0; $totalTva = 0.0;
            foreach ($lignes as $l) {
                $ligneHt = (float)$l['quantite_commandee'] * (float)$l['prix_unitaire_ht'];
                $totalHt += $ligneHt;
                $totalTva += $ligneHt * ((float)($l['taux_tva'] ?? 20) / 100);
            }
            $totalTtc = $totalHt + $totalTva;

            $stmt = $this->db->prepare("INSERT INTO jardin_commandes
                (residence_id, fournisseur_id, numero_commande, date_commande,
                 date_livraison_prevue, statut, montant_total_ht, montant_tva, montant_total_ttc,
                 notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
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

            $lstmt = $this->db->prepare("INSERT INTO jardin_commande_lignes
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

    public function updateCommandeStatut(int $id, string $statut): void {
        $stmt = $this->db->prepare("UPDATE jardin_commandes SET statut = ? WHERE id = ?");
        $stmt->execute([$statut, $id]);
    }

    /**
     * Réception d'une commande : met à jour les quantités reçues,
     * crée les mouvements d'entrée en inventaire, ajuste le statut.
     * @param array $quantitesRecues [ligne_id => quantite_recue]
     */
    public function receptionnerCommande(int $commandeId, array $quantitesRecues, int $userId): bool {
        $this->db->beginTransaction();
        try {
            // Récupérer la commande + ses lignes
            $cstmt = $this->db->prepare("SELECT * FROM jardin_commandes WHERE id = ? FOR UPDATE");
            $cstmt->execute([$commandeId]);
            $cmd = $cstmt->fetch(PDO::FETCH_ASSOC);
            if (!$cmd) throw new Exception("Commande introuvable");
            if (in_array($cmd['statut'], ['livree', 'facturee', 'annulee'], true)) {
                throw new Exception("Commande non modifiable (statut : " . $cmd['statut'] . ")");
            }
            $residenceId = (int)$cmd['residence_id'];

            $lstmt = $this->db->prepare("SELECT * FROM jardin_commande_lignes WHERE commande_id = ?");
            $lstmt->execute([$commandeId]);
            $lignes = $lstmt->fetchAll(PDO::FETCH_ASSOC);

            $touteRecue = true;
            $auMoinsUne = false;

            foreach ($lignes as $ligne) {
                $ligneId = (int)$ligne['id'];
                $qteRecue = (float)($quantitesRecues[$ligneId] ?? 0);
                $qteDeja = (float)($ligne['quantite_recue'] ?? 0);
                $qteCommandee = (float)$ligne['quantite_commandee'];
                $delta = $qteRecue - $qteDeja;

                if ($delta > 0) {
                    $auMoinsUne = true;
                    // 1) Garantir l'existence de l'entrée inventaire (produit_id, residence_id)
                    $invSel = $this->db->prepare("SELECT id FROM jardin_inventaire WHERE produit_id = ? AND residence_id = ?");
                    $invSel->execute([(int)$ligne['produit_id'], $residenceId]);
                    $invId = $invSel->fetchColumn();
                    if (!$invId) {
                        $this->db->prepare("INSERT INTO jardin_inventaire (produit_id, residence_id, quantite_actuelle, seuil_alerte) VALUES (?, ?, 0, 0)")
                                 ->execute([(int)$ligne['produit_id'], $residenceId]);
                        $invId = (int)$this->db->lastInsertId();
                    } else {
                        $invId = (int)$invId;
                    }

                    // 2) Mouvement d'entrée (motif = livraison, notes référençant la commande)
                    //    On n'appelle pas mouvementStock() ici (qui a son propre begin/commit)
                    //    → on fait l'update + l'insert mouvement dans la même transaction.
                    $this->db->prepare("UPDATE jardin_inventaire SET quantite_actuelle = quantite_actuelle + ? WHERE id = ?")
                             ->execute([$delta, $invId]);
                    $this->db->prepare("INSERT INTO jardin_inventaire_mouvements
                        (inventaire_id, type_mouvement, quantite, motif, user_id, notes)
                        VALUES (?, 'entree', ?, 'livraison', ?, ?)")
                             ->execute([$invId, $delta, $userId, 'Réception commande ' . $cmd['numero_commande']]);
                }

                // Update quantite_recue de la ligne
                $this->db->prepare("UPDATE jardin_commande_lignes SET quantite_recue = ? WHERE id = ?")
                         ->execute([$qteRecue, $ligneId]);

                if ($qteRecue < $qteCommandee) $touteRecue = false;
            }

            if (!$auMoinsUne) throw new Exception("Aucune quantité saisie");

            // Statut + date de livraison effective
            $nouveauStatut = $touteRecue ? 'livree' : 'livree_partiel';
            $this->db->prepare("UPDATE jardin_commandes SET statut = ?, date_livraison_effective = CURDATE() WHERE id = ?")
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
     * Suppression : hard delete si brouillon, sinon statut = annulee.
     */
    public function deleteOrCancelCommande(int $id): string {
        $stmt = $this->db->prepare("SELECT statut FROM jardin_commandes WHERE id = ?");
        $stmt->execute([$id]);
        $statut = $stmt->fetchColumn();
        if ($statut === 'brouillon') {
            $this->db->prepare("DELETE FROM jardin_commandes WHERE id = ?")->execute([$id]);
            return 'deleted';
        }
        $this->db->prepare("UPDATE jardin_commandes SET statut = 'annulee' WHERE id = ?")->execute([$id]);
        return 'cancelled';
    }

    /**
     * Fournisseurs actifs liés à la résidence (pour sélecteur commande).
     */
    public function getFournisseursActifsResidence(int $residenceId): array {
        $sql = "SELECT f.id, f.nom FROM fournisseurs f
                JOIN jardin_fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.residence_id = ?
                WHERE f.actif = 1 AND fr.statut = 'actif'
                ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  FOURNISSEURS JARDINERIE (pivot jardin_fournisseur_residence)
    // ─────────────────────────────────────────────────────────────

    public function getFournisseursResidence(int $residenceId): array {
        $sql = "SELECT f.*, fr.id as pivot_id, fr.statut as lien_statut,
                       fr.contact_local, fr.telephone_local,
                       fr.jour_livraison, fr.delai_livraison_jours,
                       fr.notes as pivot_notes
                FROM fournisseurs f
                JOIN jardin_fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.residence_id = ?
                WHERE fr.statut = 'actif' AND f.actif = 1
                ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Fournisseurs actifs PAS encore liés (ou liés en statut 'inactif') à la résidence.
     */
    public function getFournisseursDisponibles(int $residenceId): array {
        $sql = "SELECT f.id, f.nom, f.type_service
                FROM fournisseurs f
                WHERE f.actif = 1
                  AND f.id NOT IN (
                    SELECT fournisseur_id FROM jardin_fournisseur_residence
                    WHERE residence_id = ? AND statut = 'actif'
                  )
                ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getLienFournisseur(int $pivotId): ?array {
        $sql = "SELECT fr.*, f.nom as fournisseur_nom
                FROM jardin_fournisseur_residence fr
                JOIN fournisseurs f ON f.id = fr.fournisseur_id
                WHERE fr.id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$pivotId]); $r = $stmt->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Lie un fournisseur à une résidence (ou réactive un lien 'inactif').
     */
    public function lierFournisseurResidence(int $fournisseurId, int $residenceId, array $data = []): int {
        $sql = "INSERT INTO jardin_fournisseur_residence
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
            $fournisseurId,
            $residenceId,
            !empty($data['contact_local']) ? trim($data['contact_local']) : null,
            !empty($data['telephone_local']) ? trim($data['telephone_local']) : null,
            !empty($data['jour_livraison']) ? trim($data['jour_livraison']) : null,
            !empty($data['delai_livraison_jours']) ? (int)$data['delai_livraison_jours'] : null,
            !empty($data['notes']) ? trim($data['notes']) : null,
        ]);
        // Récupérer le pivot_id (créé ou existant)
        $sel = $this->db->prepare("SELECT id FROM jardin_fournisseur_residence WHERE fournisseur_id = ? AND residence_id = ?");
        $sel->execute([$fournisseurId, $residenceId]);
        return (int)$sel->fetchColumn();
    }

    public function updateLienFournisseur(int $pivotId, array $data): void {
        $sql = "UPDATE jardin_fournisseur_residence
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

    public function delierFournisseurResidence(int $pivotId): void {
        $stmt = $this->db->prepare("UPDATE jardin_fournisseur_residence SET statut = 'inactif' WHERE id = ?");
        $stmt->execute([$pivotId]);
    }

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
