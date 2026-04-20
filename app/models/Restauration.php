<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Restauration
 * ====================================================================
 * Requêtes SQL pour le module restauration (dashboard, résidents, stats).
 * Les sous-modules (plats, menus, inventaire, commandes, factures)
 * auront leurs propres méthodes ajoutées dans les phases suivantes.
 */

class Restauration extends Model {

    // Rôles autorisés pour le module restauration
    public const ROLES = ['restauration_manager', 'restauration_serveur', 'restauration_cuisine'];
    public const ROLE_MANAGER = 'restauration_manager';
    public const ROLE_SERVEUR = 'restauration_serveur';
    public const ROLE_CUISINE = 'restauration_cuisine';

    // ─────────────────────────────────────────────────────────────
    //  RÉSIDENCES DE L'UTILISATEUR
    // ─────────────────────────────────────────────────────────────

    /**
     * Résidences auxquelles l'utilisateur est affecté
     */
    public function getResidencesByUser(int $userId): array {
        $sql = "SELECT c.id, c.nom, c.ville
                FROM user_residence ur
                JOIN coproprietees c ON ur.residence_id = c.id
                WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1
                ORDER BY c.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return [];
        }
    }

    /**
     * IDs des résidences de l'utilisateur (pour filtrage)
     */
    public function getResidenceIdsByUser(int $userId): array {
        $sql = "SELECT ur.residence_id FROM user_residence ur
                JOIN coproprietees c ON ur.residence_id = c.id
                WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  RÉSIDENTS (par résidence)
    // ─────────────────────────────────────────────────────────────

    /**
     * Résidents actifs des résidences de l'utilisateur
     */
    public function getResidentsByResidences(array $residenceIds): array {
        if (empty($residenceIds)) return [];
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT rs.id, rs.civilite, rs.nom, rs.prenom, rs.telephone_mobile, rs.email,
                       rs.regime_alimentaire, rs.allergies,
                       o.lot_id, l.numero_lot, l.type as lot_type,
                       c.id as residence_id, c.nom as residence_nom,
                       o.forfait_type
                FROM residents_seniors rs
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON o.lot_id = l.id
                JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE rs.actif = 1 AND c.id IN ($placeholders)
                ORDER BY c.nom, rs.nom, rs.prenom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($residenceIds));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  STAFF RESTAURATION (par résidence)
    // ─────────────────────────────────────────────────────────────

    /**
     * Staff restauration des résidences (pour le manager)
     */
    public function getStaffByResidences(array $residenceIds): array {
        if (empty($residenceIds)) return [];
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT u.id, u.nom, u.prenom, u.role, u.email, u.telephone, u.actif, u.last_login,
                       c.nom as residence_nom, c.id as residence_id,
                       r.nom_affichage as role_nom, r.couleur as role_couleur, r.icone as role_icone
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.statut = 'actif'
                JOIN coproprietees c ON ur.residence_id = c.id
                LEFT JOIN roles r ON u.role = r.slug
                WHERE u.role IN ('restauration_manager','restauration_serveur','restauration_cuisine')
                AND c.id IN ($placeholders)
                ORDER BY c.nom, u.role, u.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($residenceIds));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  DASHBOARD — STATISTIQUES DU JOUR
    // ─────────────────────────────────────────────────────────────

    /**
     * Stats du jour pour le dashboard (repas servis, CA, couverts)
     */
    public function getStatsDuJour(array $residenceIds, ?string $date = null): array {
        if (empty($residenceIds)) return ['repas_servis' => 0, 'couverts' => 0, 'ca_jour' => 0, 'pension_complete' => 0];
        $date = $date ?? date('Y-m-d');
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge([$date], array_values($residenceIds));

        $sql = "SELECT
                    COUNT(*) as repas_servis,
                    COALESCE(SUM(nb_couverts), 0) as couverts,
                    COALESCE(SUM(CASE WHEN mode_facturation != 'pension_complete' THEN montant END), 0) as ca_jour,
                    COUNT(CASE WHEN mode_facturation = 'pension_complete' THEN 1 END) as pension_complete
                FROM rest_services_repas
                WHERE date_service = ? AND residence_id IN ($placeholders)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['repas_servis' => 0, 'couverts' => 0, 'ca_jour' => 0, 'pension_complete' => 0];
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return ['repas_servis' => 0, 'couverts' => 0, 'ca_jour' => 0, 'pension_complete' => 0];
        }
    }

    /**
     * Stats du mois en cours
     */
    public function getStatsDuMois(array $residenceIds): array {
        if (empty($residenceIds)) return ['repas_total' => 0, 'ca_mois' => 0, 'commandes_en_cours' => 0, 'alertes_stock' => 0];
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $moisDebut = date('Y-m-01');
        $params = array_merge([$moisDebut], array_values($residenceIds));

        $stats = ['repas_total' => 0, 'ca_mois' => 0, 'commandes_en_cours' => 0, 'alertes_stock' => 0];

        try {
            // Repas du mois
            $sql = "SELECT COUNT(*) as repas_total, COALESCE(SUM(montant), 0) as ca_mois
                    FROM rest_services_repas WHERE date_service >= ? AND residence_id IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['repas_total'] = (int)($row['repas_total'] ?? 0);
            $stats['ca_mois'] = (float)($row['ca_mois'] ?? 0);

            // Commandes en cours
            $sql2 = "SELECT COUNT(*) FROM rest_commandes WHERE statut IN ('brouillon','envoyee','livree_partiel') AND residence_id IN ($placeholders)";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute(array_values($residenceIds));
            $stats['commandes_en_cours'] = (int)$stmt2->fetchColumn();

            // Alertes stock
            $sql3 = "SELECT COUNT(*) FROM rest_inventaire WHERE quantite_stock <= seuil_alerte AND seuil_alerte > 0 AND residence_id IN ($placeholders)";
            $stmt3 = $this->db->prepare($sql3);
            $stmt3->execute(array_values($residenceIds));
            $stats['alertes_stock'] = (int)$stmt3->fetchColumn();

        } catch (PDOException $e) {
            $this->logError($e->getMessage());
        }

        return $stats;
    }

    /**
     * Menu du jour pour une résidence
     */
    public function getMenuDuJour(int $residenceId, ?string $date = null): array {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT m.*, mp.categorie_plat, mp.ordre,
                       p.nom as plat_nom, p.description as plat_description, p.allergenes, p.regime, p.prix_unitaire
                FROM rest_menus m
                JOIN rest_menu_plats mp ON mp.menu_id = m.id
                JOIN rest_plats p ON mp.plat_id = p.id
                WHERE m.residence_id = ? AND m.date_menu = ? AND m.actif = 1
                ORDER BY m.type_service, mp.categorie_plat, mp.ordre";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId, $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$residenceId, $date]);
            return [];
        }
    }

    /**
     * Repas récents (derniers 10)
     */
    public function getRepasRecents(array $residenceIds, int $limit = 10): array {
        if (empty($residenceIds)) return [];
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);

        $sql = "SELECT sr.*, c.nom as residence_nom,
                       CASE
                           WHEN sr.type_client = 'resident' THEN CONCAT(rs.prenom, ' ', rs.nom)
                           WHEN sr.type_client = 'hote' THEN CONCAT(ht.prenom, ' ', ht.nom)
                           ELSE sr.nom_passage
                       END as client_nom,
                       u.prenom as serveur_prenom, u.nom as serveur_nom
                FROM rest_services_repas sr
                JOIN coproprietees c ON sr.residence_id = c.id
                LEFT JOIN residents_seniors rs ON sr.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON sr.hote_id = ht.id
                LEFT JOIN users u ON sr.serveur_id = u.id
                WHERE sr.residence_id IN ($placeholders)
                ORDER BY sr.created_at DESC
                LIMIT ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Alertes stock bas
     */
    public function getAlertesStock(array $residenceIds, int $limit = 5): array {
        if (empty($residenceIds)) return [];
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);

        $sql = "SELECT i.*, p.nom as produit_nom, p.unite, p.categorie as produit_categorie,
                       c.nom as residence_nom
                FROM rest_inventaire i
                JOIN rest_produits p ON i.produit_id = p.id
                JOIN coproprietees c ON i.residence_id = c.id
                WHERE i.quantite_stock <= i.seuil_alerte AND i.seuil_alerte > 0
                AND i.residence_id IN ($placeholders)
                ORDER BY (i.quantite_stock / NULLIF(i.seuil_alerte, 0)) ASC
                LIMIT ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  TARIFS
    // ─────────────────────────────────────────────────────────────

    /**
     * Tarifs d'une résidence
     */
    public function getTarifs(int $residenceId): array {
        $sql = "SELECT * FROM rest_tarifs WHERE residence_id = ? AND actif = 1 ORDER BY FIELD(type_service, 'petit_dejeuner','dejeuner','gouter','diner','snack_bar')";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$residenceId]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PLATS — CATALOGUE (CRUD)
    // ─────────────────────────────────────────────────────────────

    /**
     * Tous les plats (avec filtre optionnel)
     */
    public function getAllPlats(?string $categorie = null, ?string $typeService = null, bool $actifsOnly = false): array {
        $sql = "SELECT * FROM rest_plats WHERE 1=1";
        $params = [];
        if ($actifsOnly) $sql .= " AND actif = 1";
        if ($categorie) { $sql .= " AND categorie = ?"; $params[] = $categorie; }
        if ($typeService && $typeService !== 'tous') { $sql .= " AND (type_service = ? OR type_service = 'tous')"; $params[] = $typeService; }
        $sql .= " ORDER BY categorie, ordre_affichage, nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Un plat par ID
     */
    public function getPlat(int $id): ?array {
        $sql = "SELECT * FROM rest_plats WHERE id = ?";
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
     * Créer un plat
     */
    public function createPlat(array $data): int {
        $sql = "INSERT INTO rest_plats (nom, description, categorie, type_service, prix_unitaire, allergenes, regime, calories, photo, actif, ordre_affichage)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $data['nom'], $data['description'] ?: null, $data['categorie'],
            $data['type_service'] ?? 'tous', (float)($data['prix_unitaire'] ?? 0),
            $data['allergenes'] ?: null, $data['regime'] ?? 'normal',
            !empty($data['calories']) ? (int)$data['calories'] : null,
            $data['photo'] ?: null, isset($data['actif']) ? 1 : 0,
            (int)($data['ordre_affichage'] ?? 0)
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Modifier un plat
     */
    public function updatePlat(int $id, array $data): bool {
        $sql = "UPDATE rest_plats SET nom=?, description=?, categorie=?, type_service=?, prix_unitaire=?,
                allergenes=?, regime=?, calories=?, photo=COALESCE(?, photo), actif=?, ordre_affichage=?, updated_at=NOW()
                WHERE id=?";
        return $this->db->prepare($sql)->execute([
            $data['nom'], $data['description'] ?: null, $data['categorie'],
            $data['type_service'] ?? 'tous', (float)($data['prix_unitaire'] ?? 0),
            $data['allergenes'] ?: null, $data['regime'] ?? 'normal',
            !empty($data['calories']) ? (int)$data['calories'] : null,
            $data['photo'] ?: null, isset($data['actif']) ? 1 : 0,
            (int)($data['ordre_affichage'] ?? 0), $id
        ]);
    }

    /**
     * Supprimer un plat (soft delete)
     */
    public function deletePlat(int $id): bool {
        return $this->db->prepare("UPDATE rest_plats SET actif = 0, updated_at = NOW() WHERE id = ?")->execute([$id]);
    }

    /**
     * Stats des plats
     */
    public function getPlatsStats(): array {
        $sql = "SELECT categorie, COUNT(*) as total, SUM(actif) as actifs FROM rest_plats GROUP BY categorie";
        try { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  MENUS — GESTION QUOTIDIENNE
    // ─────────────────────────────────────────────────────────────

    /**
     * Menus pour une résidence sur une période
     */
    public function getMenus(int $residenceId, string $dateDebut, string $dateFin): array {
        $sql = "SELECT m.*, u.prenom as auteur_prenom, u.nom as auteur_nom,
                       (SELECT COUNT(*) FROM rest_menu_plats WHERE menu_id = m.id) as nb_plats
                FROM rest_menus m
                LEFT JOIN users u ON m.created_by = u.id
                WHERE m.residence_id = ? AND m.date_menu BETWEEN ? AND ?
                ORDER BY m.date_menu, FIELD(m.type_service, 'petit_dejeuner','dejeuner','gouter','diner')";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId, $dateDebut, $dateFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Un menu avec ses plats
     */
    public function getMenu(int $id): ?array {
        $sql = "SELECT m.*, c.nom as residence_nom FROM rest_menus m JOIN coproprietees c ON m.residence_id = c.id WHERE m.id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$menu) return null;

            $sqlP = "SELECT mp.*, p.nom as plat_nom, p.description as plat_description, p.categorie as plat_categorie,
                            p.allergenes, p.regime, p.prix_unitaire, p.calories
                     FROM rest_menu_plats mp JOIN rest_plats p ON mp.plat_id = p.id
                     WHERE mp.menu_id = ? ORDER BY mp.categorie_plat, mp.ordre";
            $stmtP = $this->db->prepare($sqlP);
            $stmtP->execute([$id]);
            $menu['plats'] = $stmtP->fetchAll(PDO::FETCH_ASSOC);
            return $menu;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return null;
        }
    }

    /**
     * Créer un menu
     */
    public function createMenu(array $data): int {
        $sql = "INSERT INTO rest_menus (residence_id, date_menu, type_service, nom, prix_menu, notes, created_by)
                VALUES (?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'], $data['date_menu'], $data['type_service'],
            $data['nom'] ?: null, !empty($data['prix_menu']) ? (float)$data['prix_menu'] : null,
            $data['notes'] ?: null, $data['created_by'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Modifier un menu
     */
    public function updateMenu(int $id, array $data): bool {
        $sql = "UPDATE rest_menus SET date_menu=?, type_service=?, nom=?, prix_menu=?, notes=?, actif=?, updated_at=NOW() WHERE id=?";
        return $this->db->prepare($sql)->execute([
            $data['date_menu'], $data['type_service'],
            $data['nom'] ?: null, !empty($data['prix_menu']) ? (float)$data['prix_menu'] : null,
            $data['notes'] ?: null, isset($data['actif']) ? 1 : 0, $id
        ]);
    }

    /**
     * Supprimer un menu
     */
    public function deleteMenu(int $id): bool {
        return $this->db->prepare("DELETE FROM rest_menus WHERE id = ?")->execute([$id]);
    }

    /**
     * Synchroniser les plats d'un menu
     */
    public function syncMenuPlats(int $menuId, array $plats): void {
        $this->db->prepare("DELETE FROM rest_menu_plats WHERE menu_id = ?")->execute([$menuId]);
        if (empty($plats)) return;
        $stmt = $this->db->prepare("INSERT INTO rest_menu_plats (menu_id, plat_id, categorie_plat, ordre) VALUES (?,?,?,?)");
        foreach ($plats as $p) {
            $stmt->execute([$menuId, (int)$p['plat_id'], $p['categorie_plat'], (int)($p['ordre'] ?? 0)]);
        }
    }

    /**
     * Dupliquer un menu vers une autre date
     */
    public function duplicateMenu(int $menuId, string $newDate): ?int {
        $source = $this->getMenu($menuId);
        if (!$source) return null;

        $newId = $this->createMenu([
            'residence_id' => $source['residence_id'],
            'date_menu' => $newDate,
            'type_service' => $source['type_service'],
            'nom' => $source['nom'],
            'prix_menu' => $source['prix_menu'],
            'notes' => $source['notes'],
            'created_by' => $_SESSION['user_id'] ?? null
        ]);

        if ($newId && !empty($source['plats'])) {
            $plats = array_map(fn($p) => [
                'plat_id' => $p['plat_id'],
                'categorie_plat' => $p['categorie_plat'],
                'ordre' => $p['ordre']
            ], $source['plats']);
            $this->syncMenuPlats($newId, $plats);
        }

        return $newId;
    }

    /**
     * Plats disponibles pour un type de service (pour le formulaire menu)
     */
    public function getPlatsForService(string $typeService): array {
        $sql = "SELECT id, nom, categorie, regime, allergenes, prix_unitaire
                FROM rest_plats
                WHERE actif = 1 AND (type_service = ? OR type_service = 'tous')
                ORDER BY categorie, nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$typeService]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$typeService]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  SERVICE REPAS — ENREGISTREMENT
    // ─────────────────────────────────────────────────────────────

    /**
     * Enregistrer un repas servi
     */
    public function enregistrerRepas(array $data): int {
        $sql = "INSERT INTO rest_services_repas
                (residence_id, date_service, type_service, type_client, resident_id, hote_id, nom_passage,
                 menu_id, mode_facturation, nb_couverts, montant, notes, serveur_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'], $data['date_service'], $data['type_service'],
            $data['type_client'], $data['resident_id'] ?: null, $data['hote_id'] ?: null,
            $data['nom_passage'] ?: null, $data['menu_id'] ?: null,
            $data['mode_facturation'], (int)($data['nb_couverts'] ?? 1),
            (float)($data['montant'] ?? 0), $data['notes'] ?: null, $data['serveur_id'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Repas servis du jour pour une résidence (pour la page service)
     */
    public function getRepasJour(int $residenceId, string $date, ?string $typeService = null): array {
        $sql = "SELECT sr.*,
                   CASE
                       WHEN sr.type_client = 'resident' THEN CONCAT(rs.civilite, ' ', rs.prenom, ' ', rs.nom)
                       WHEN sr.type_client = 'hote' THEN CONCAT(ht.prenom, ' ', ht.nom)
                       ELSE sr.nom_passage
                   END as client_nom,
                   u.prenom as serveur_prenom, u.nom as serveur_nom
                FROM rest_services_repas sr
                LEFT JOIN residents_seniors rs ON sr.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON sr.hote_id = ht.id
                LEFT JOIN users u ON sr.serveur_id = u.id
                WHERE sr.residence_id = ? AND sr.date_service = ?";
        $params = [$residenceId, $date];
        if ($typeService) { $sql .= " AND sr.type_service = ?"; $params[] = $typeService; }
        $sql .= " ORDER BY sr.created_at DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Supprimer un repas enregistré
     */
    public function supprimerRepas(int $id): bool {
        return $this->db->prepare("DELETE FROM rest_services_repas WHERE id = ?")->execute([$id]);
    }

    /**
     * Hôtes temporaires en cours dans une résidence (pour le formulaire service)
     */
    public function getHotesEnCours(int $residenceId): array {
        $sql = "SELECT h.id, h.civilite, h.nom, h.prenom, h.regime_repas, h.date_arrivee, h.date_depart_prevue
                FROM hotes_temporaires h
                WHERE h.residence_id = ? AND h.statut = 'en_cours'
                ORDER BY h.nom, h.prenom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$residenceId]);
            return [];
        }
    }

    /**
     * Résidents en pension complète d'une résidence
     */
    public function getResidentsPensionComplete(int $residenceId): array {
        $sql = "SELECT rs.id FROM residents_seniors rs
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON o.lot_id = l.id
                WHERE l.copropriete_id = ? AND rs.actif = 1 AND o.forfait_type = 'premium'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  FACTURATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Générer un numéro de facture
     */
    public function generateNumeroFacture(int $residenceId): string {
        $prefix = 'REST';
        $annee = date('Y');
        $mois = date('m');
        try {
            $count = $this->db->query("SELECT COUNT(*) FROM rest_factures WHERE YEAR(date_facture) = $annee")->fetchColumn();
        } catch (PDOException $e) { $count = 0; }
        return $prefix . '-' . $annee . $mois . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Créer une facture avec ses lignes
     */
    public function createFacture(array $data, array $lignes): int {
        $numero = $this->generateNumeroFacture($data['residence_id']);

        // Calcul totaux
        $totalHt = 0;
        foreach ($lignes as $l) {
            $totalHt += ($l['quantite'] ?? 1) * $l['prix_unitaire'];
        }
        $tauxTva = (float)($data['taux_tva'] ?? 10.00);
        $montantTva = round($totalHt * $tauxTva / 100, 2);
        $totalTtc = $totalHt + $montantTva;

        $sql = "INSERT INTO rest_factures
                (residence_id, numero_facture, type_client, resident_id, hote_id, nom_passage,
                 date_facture, montant_ht, taux_tva, montant_tva, montant_ttc, statut, mode_paiement, notes, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'], $numero, $data['type_client'],
            $data['resident_id'] ?: null, $data['hote_id'] ?: null, $data['nom_passage'] ?: null,
            $data['date_facture'] ?? date('Y-m-d'),
            $totalHt, $tauxTva, $montantTva, $totalTtc,
            $data['statut'] ?? 'emise', $data['mode_paiement'] ?? null,
            $data['notes'] ?: null, $data['created_by'] ?? null
        ]);
        $factureId = (int)$this->db->lastInsertId();

        // Insérer les lignes
        $stmtL = $this->db->prepare("INSERT INTO rest_facture_lignes
            (facture_id, service_repas_id, designation, type_ligne, quantite, prix_unitaire, taux_tva)
            VALUES (?,?,?,?,?,?,?)");
        foreach ($lignes as $l) {
            $stmtL->execute([
                $factureId, $l['service_repas_id'] ?? null,
                $l['designation'], $l['type_ligne'] ?? 'menu_complet',
                (int)($l['quantite'] ?? 1), (float)$l['prix_unitaire'], $tauxTva
            ]);
        }

        return $factureId;
    }

    /**
     * Liste des factures
     */
    public function getFactures(array $residenceIds, ?string $statut = null, ?string $dateDebut = null, ?string $dateFin = null): array {
        if (empty($residenceIds)) return [];
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);

        $sql = "SELECT f.*, c.nom as residence_nom,
                   CASE
                       WHEN f.type_client = 'resident' THEN CONCAT(rs.prenom, ' ', rs.nom)
                       WHEN f.type_client = 'hote' THEN CONCAT(ht.prenom, ' ', ht.nom)
                       ELSE f.nom_passage
                   END as client_nom,
                   (SELECT COUNT(*) FROM rest_facture_lignes WHERE facture_id = f.id) as nb_lignes
                FROM rest_factures f
                JOIN coproprietees c ON f.residence_id = c.id
                LEFT JOIN residents_seniors rs ON f.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON f.hote_id = ht.id
                WHERE f.residence_id IN ($placeholders)";

        if ($statut) { $sql .= " AND f.statut = ?"; $params[] = $statut; }
        if ($dateDebut) { $sql .= " AND f.date_facture >= ?"; $params[] = $dateDebut; }
        if ($dateFin) { $sql .= " AND f.date_facture <= ?"; $params[] = $dateFin; }
        $sql .= " ORDER BY f.date_facture DESC, f.id DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Détail d'une facture avec ses lignes
     */
    public function getFacture(int $id): ?array {
        $sql = "SELECT f.*, c.nom as residence_nom,
                   CASE
                       WHEN f.type_client = 'resident' THEN CONCAT(rs.prenom, ' ', rs.nom)
                       WHEN f.type_client = 'hote' THEN CONCAT(ht.prenom, ' ', ht.nom)
                       ELSE f.nom_passage
                   END as client_nom
                FROM rest_factures f
                JOIN coproprietees c ON f.residence_id = c.id
                LEFT JOIN residents_seniors rs ON f.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON f.hote_id = ht.id
                WHERE f.id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $facture = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$facture) return null;

            $stmtL = $this->db->prepare("SELECT * FROM rest_facture_lignes WHERE facture_id = ? ORDER BY id");
            $stmtL->execute([$id]);
            $facture['lignes'] = $stmtL->fetchAll(PDO::FETCH_ASSOC);
            return $facture;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return null;
        }
    }

    /**
     * Changer le statut d'une facture
     */
    public function updateFactureStatut(int $id, string $statut, ?string $modePaiement = null): bool {
        $sql = "UPDATE rest_factures SET statut = ?, mode_paiement = COALESCE(?, mode_paiement),
                date_paiement = CASE WHEN ? = 'payee' THEN CURDATE() ELSE date_paiement END,
                updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$statut, $modePaiement, $statut, $id]);
    }

    /**
     * Stats facturation du mois
     */
    public function getFacturationStats(array $residenceIds, ?int $mois = null, ?int $annee = null): array {
        if (empty($residenceIds)) return ['nb_factures' => 0, 'total_ht' => 0, 'total_ttc' => 0, 'payees' => 0, 'en_attente' => 0];
        $mois = $mois ?? (int)date('m');
        $annee = $annee ?? (int)date('Y');
        $placeholders = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge([$annee, $mois], array_values($residenceIds));

        $sql = "SELECT COUNT(*) as nb_factures,
                   COALESCE(SUM(montant_ht), 0) as total_ht,
                   COALESCE(SUM(montant_ttc), 0) as total_ttc,
                   COUNT(CASE WHEN statut = 'payee' THEN 1 END) as payees,
                   COUNT(CASE WHEN statut IN ('brouillon','emise') THEN 1 END) as en_attente
                FROM rest_factures
                WHERE YEAR(date_facture) = ? AND MONTH(date_facture) = ?
                AND residence_id IN ($placeholders)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nb_factures' => 0, 'total_ht' => 0, 'total_ttc' => 0, 'payees' => 0, 'en_attente' => 0];
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return ['nb_factures' => 0, 'total_ht' => 0, 'total_ttc' => 0, 'payees' => 0, 'en_attente' => 0];
        }
    }

    /**
     * Repas non facturés (pour créer une facture groupée)
     */
    public function getRepasNonFactures(int $residenceId, ?int $residentId = null, ?int $hoteId = null): array {
        $sql = "SELECT sr.*,
                   CASE
                       WHEN sr.type_client = 'resident' THEN CONCAT(rs.prenom, ' ', rs.nom)
                       WHEN sr.type_client = 'hote' THEN CONCAT(ht.prenom, ' ', ht.nom)
                       ELSE sr.nom_passage
                   END as client_nom
                FROM rest_services_repas sr
                LEFT JOIN residents_seniors rs ON sr.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON sr.hote_id = ht.id
                LEFT JOIN rest_facture_lignes fl ON fl.service_repas_id = sr.id
                WHERE sr.residence_id = ? AND fl.id IS NULL AND sr.mode_facturation != 'pension_complete'";
        $params = [$residenceId];
        if ($residentId) { $sql .= " AND sr.resident_id = ?"; $params[] = $residentId; }
        if ($hoteId) { $sql .= " AND sr.hote_id = ?"; $params[] = $hoteId; }
        $sql .= " ORDER BY sr.date_service, sr.type_service";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PRODUITS — CATALOGUE GLOBAL (CRUD)
    // ─────────────────────────────────────────────────────���───────

    public function getAllProduits(?string $categorie = null, bool $actifsOnly = false): array {
        $sql = "SELECT p.*, f.nom as fournisseur_nom
                FROM rest_produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id WHERE 1=1";
        $params = [];
        if ($actifsOnly) $sql .= " AND p.actif = 1";
        if ($categorie) { $sql .= " AND p.categorie = ?"; $params[] = $categorie; }
        $sql .= " ORDER BY p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getProduit(int $id): ?array {
        try { $stmt = $this->db->prepare("SELECT p.*, f.nom as fournisseur_nom FROM rest_produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id WHERE p.id = ?"); $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function createProduit(array $d): int {
        $sql = "INSERT INTO rest_produits (nom, categorie, unite, prix_reference, code_barre, fournisseur_id, marque, conditionnement, actif, notes) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([$d['nom'], $d['categorie'], $d['unite'], !empty($d['prix_reference']) ? (float)$d['prix_reference'] : null, $d['code_barre'] ?: null, !empty($d['fournisseur_id']) ? (int)$d['fournisseur_id'] : null, $d['marque'] ?: null, $d['conditionnement'] ?: null, isset($d['actif']) ? 1 : 0, $d['notes'] ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProduit(int $id, array $d): bool {
        $sql = "UPDATE rest_produits SET nom=?, categorie=?, unite=?, prix_reference=?, code_barre=?, fournisseur_id=?, marque=?, conditionnement=?, actif=?, notes=?, updated_at=NOW() WHERE id=?";
        return $this->db->prepare($sql)->execute([$d['nom'], $d['categorie'], $d['unite'], !empty($d['prix_reference']) ? (float)$d['prix_reference'] : null, $d['code_barre'] ?: null, !empty($d['fournisseur_id']) ? (int)$d['fournisseur_id'] : null, $d['marque'] ?: null, $d['conditionnement'] ?: null, isset($d['actif']) ? 1 : 0, $d['notes'] ?: null, $id]);
    }

    public function deleteProduit(int $id): bool {
        return $this->db->prepare("UPDATE rest_produits SET actif = 0, updated_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public function getFournisseursList(): array {
        try { return $this->db->query("SELECT id, nom FROM fournisseurs WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage()); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  INVENTAIRE — STOCK PAR RÉSIDENCE
    // ─────────────────────────────────────────────────────────────

    public function getInventaire(int $residenceId, ?string $categorie = null, bool $alertesOnly = false): array {
        $sql = "SELECT i.*, p.nom as produit_nom, p.categorie as produit_categorie, p.unite, p.prix_reference, p.marque,
                       f.nom as fournisseur_nom
                FROM rest_inventaire i
                JOIN rest_produits p ON i.produit_id = p.id
                LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id
                WHERE i.residence_id = ?";
        $params = [$residenceId];
        if ($categorie) { $sql .= " AND p.categorie = ?"; $params[] = $categorie; }
        if ($alertesOnly) $sql .= " AND i.quantite_stock <= i.seuil_alerte AND i.seuil_alerte > 0";
        $sql .= " ORDER BY p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getInventaireItem(int $id): ?array {
        try { $stmt = $this->db->prepare("SELECT i.*, p.nom as produit_nom, p.unite, p.categorie as produit_categorie FROM rest_inventaire i JOIN rest_produits p ON i.produit_id = p.id WHERE i.id = ?"); $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function addToInventaire(int $produitId, int $residenceId, float $seuil = 0, ?string $emplacement = null): int {
        $sql = "INSERT IGNORE INTO rest_inventaire (produit_id, residence_id, quantite_stock, seuil_alerte, emplacement) VALUES (?,?,0,?,?)";
        $this->db->prepare($sql)->execute([$produitId, $residenceId, $seuil, $emplacement]);
        // Récupérer l'ID (INSERT IGNORE ne retourne pas lastInsertId si doublon)
        $stmt = $this->db->prepare("SELECT id FROM rest_inventaire WHERE produit_id = ? AND residence_id = ?");
        $stmt->execute([$produitId, $residenceId]);
        return (int)$stmt->fetchColumn();
    }

    public function updateInventaireItem(int $id, array $d): bool {
        return $this->db->prepare("UPDATE rest_inventaire SET seuil_alerte=?, emplacement=?, date_peremption=?, updated_at=NOW() WHERE id=?")
            ->execute([(float)($d['seuil_alerte'] ?? 0), $d['emplacement'] ?: null, $d['date_peremption'] ?: null, $id]);
    }

    /**
     * Mouvement de stock (entrée, sortie, ajustement) — met à jour le stock
     */
    public function mouvementStock(int $inventaireId, string $type, float $quantite, string $motif, ?int $commandeId = null, ?string $notes = null): bool {
        try {
            $this->db->beginTransaction();

            // Insérer le mouvement
            $this->db->prepare("INSERT INTO rest_inventaire_mouvements (inventaire_id, type_mouvement, quantite, motif, commande_id, notes, user_id) VALUES (?,?,?,?,?,?,?)")
                ->execute([$inventaireId, $type, $quantite, $motif, $commandeId, $notes, $_SESSION['user_id'] ?? null]);

            // Mettre à jour le stock
            $op = ($type === 'entree') ? '+' : (($type === 'sortie') ? '-' : '');
            if ($op) {
                $this->db->prepare("UPDATE rest_inventaire SET quantite_stock = GREATEST(0, quantite_stock $op ?), updated_at = NOW() WHERE id = ?")
                    ->execute([$quantite, $inventaireId]);
            } else {
                // Ajustement = valeur absolue
                $this->db->prepare("UPDATE rest_inventaire SET quantite_stock = ?, updated_at = NOW() WHERE id = ?")->execute([$quantite, $inventaireId]);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function getMouvements(int $inventaireId, int $limit = 20): array {
        $sql = "SELECT m.*, u.prenom as user_prenom, u.nom as user_nom FROM rest_inventaire_mouvements m LEFT JOIN users u ON m.user_id = u.id WHERE m.inventaire_id = ? ORDER BY m.created_at DESC LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$inventaireId, $limit]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Produits non encore dans l'inventaire de cette résidence
     */
    public function getProduitsHorsInventaire(int $residenceId): array {
        $sql = "SELECT p.id, p.nom, p.categorie, p.unite FROM rest_produits p WHERE p.actif = 1 AND p.id NOT IN (SELECT produit_id FROM rest_inventaire WHERE residence_id = ?) ORDER BY p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  COMMANDES FOURNISSEURS (workflow complet)
    // ─────────────────────────────────────────────────────────────

    public function generateNumeroCommande(): string {
        $annee = date('Y'); $mois = date('m');
        try { $count = $this->db->query("SELECT COUNT(*) FROM rest_commandes WHERE YEAR(date_commande) = $annee")->fetchColumn(); }
        catch (PDOException $e) { $count = 0; }
        return 'CMD-' . $annee . $mois . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getCommandes(array $residenceIds, ?string $statut = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);
        $sql = "SELECT c.*, f.nom as fournisseur_nom, res.nom as residence_nom, u.prenom as auteur_prenom, u.nom as auteur_nom,
                   (SELECT COUNT(*) FROM rest_commande_lignes WHERE commande_id = c.id) as nb_lignes
                FROM rest_commandes c
                JOIN fournisseurs f ON c.fournisseur_id = f.id
                JOIN coproprietees res ON c.residence_id = res.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.residence_id IN ($ph)";
        if ($statut) { $sql .= " AND c.statut = ?"; $params[] = $statut; }
        $sql .= " ORDER BY c.date_commande DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getCommande(int $id): ?array {
        $sql = "SELECT c.*, f.nom as fournisseur_nom, f.email as fournisseur_email, f.telephone as fournisseur_telephone, res.nom as residence_nom
                FROM rest_commandes c JOIN fournisseurs f ON c.fournisseur_id = f.id JOIN coproprietees res ON c.residence_id = res.id WHERE c.id = ?";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $cmd = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cmd) return null;
            $stmtL = $this->db->prepare("SELECT cl.*, p.nom as produit_nom, p.unite FROM rest_commande_lignes cl JOIN rest_produits p ON cl.produit_id = p.id WHERE cl.commande_id = ? ORDER BY cl.id");
            $stmtL->execute([$id]); $cmd['lignes'] = $stmtL->fetchAll(PDO::FETCH_ASSOC);
            return $cmd;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, [$id]); return null; }
    }

    public function createCommande(array $data, array $lignes): int {
        $numero = $this->generateNumeroCommande();
        $totalHt = 0; $totalTva = 0;
        foreach ($lignes as $l) {
            $ligneHt = ($l['quantite_commandee'] ?? 0) * ($l['prix_unitaire_ht'] ?? 0);
            $totalHt += $ligneHt;
            $totalTva += $ligneHt * (($l['taux_tva'] ?? 5.5) / 100);
        }
        $totalTtc = $totalHt + $totalTva;

        $sql = "INSERT INTO rest_commandes (residence_id, fournisseur_id, numero_commande, date_commande, date_livraison_prevue, statut, montant_total_ht, montant_tva, montant_total_ttc, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'], $data['fournisseur_id'], $numero,
            $data['date_commande'] ?? date('Y-m-d'), $data['date_livraison_prevue'] ?: null,
            $data['statut'] ?? 'brouillon', round($totalHt, 2), round($totalTva, 2), round($totalTtc, 2),
            $data['notes'] ?: null, $_SESSION['user_id'] ?? null
        ]);
        $commandeId = (int)$this->db->lastInsertId();

        $stmtL = $this->db->prepare("INSERT INTO rest_commande_lignes (commande_id, produit_id, designation, quantite_commandee, prix_unitaire_ht, taux_tva) VALUES (?,?,?,?,?,?)");
        foreach ($lignes as $l) {
            if (!empty($l['produit_id']) && !empty($l['quantite_commandee'])) {
                $stmtL->execute([$commandeId, (int)$l['produit_id'], $l['designation'] ?? '', (float)$l['quantite_commandee'], (float)($l['prix_unitaire_ht'] ?? 0), (float)($l['taux_tva'] ?? 5.5)]);
            }
        }
        return $commandeId;
    }

    public function updateCommandeStatut(int $id, string $statut): bool {
        $sql = "UPDATE rest_commandes SET statut = ?, updated_at = NOW()";
        $params = [$statut];
        if ($statut === 'livree') { $sql .= ", date_livraison_effective = CURDATE()"; }
        $sql .= " WHERE id = ?"; $params[] = $id;
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Réceptionner une commande : mettre à jour quantités reçues + entrées stock
     */
    public function receptionnerCommande(int $commandeId, array $quantitesRecues): bool {
        try {
            $this->db->beginTransaction();
            $cmd = $this->getCommande($commandeId);
            if (!$cmd) throw new Exception("Commande introuvable");

            $toutRecu = true;
            foreach ($cmd['lignes'] as $ligne) {
                $qteRecue = (float)($quantitesRecues[$ligne['id']] ?? 0);
                $this->db->prepare("UPDATE rest_commande_lignes SET quantite_recue = ? WHERE id = ?")->execute([$qteRecue, $ligne['id']]);
                if ($qteRecue < $ligne['quantite_commandee']) $toutRecu = false;

                // Entrée en stock
                if ($qteRecue > 0) {
                    $invId = $this->addToInventaire($ligne['produit_id'], $cmd['residence_id']);
                    $this->mouvementStock($invId, 'entree', $qteRecue, 'livraison', $commandeId, "Commande $cmd[numero_commande]");
                }
            }

            $newStatut = $toutRecu ? 'livree' : 'livree_partiel';
            $this->updateCommandeStatut($commandeId, $newStatut);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function deleteCommande(int $id): bool {
        return $this->db->prepare("DELETE FROM rest_commandes WHERE id = ? AND statut = 'brouillon'")->execute([$id]);
    }

    // ─────────────────────────────────────────────────────────────
    //  COMPTABILITÉ RESTAURATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Créer une écriture comptable
     */
    public function createEcriture(array $d): int {
        $sql = "INSERT INTO rest_comptabilite
                (residence_id, date_ecriture, type_ecriture, categorie, reference_id, reference_type,
                 libelle, montant_ht, montant_tva, montant_ttc, compte_comptable, mois, annee, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([
            $d['residence_id'], $d['date_ecriture'] ?? date('Y-m-d'),
            $d['type_ecriture'], $d['categorie'],
            $d['reference_id'] ?? null, $d['reference_type'] ?? null,
            $d['libelle'], (float)$d['montant_ht'], (float)($d['montant_tva'] ?? 0), (float)$d['montant_ttc'],
            $d['compte_comptable'] ?? null,
            (int)($d['mois'] ?? date('m')), (int)($d['annee'] ?? date('Y')),
            $d['notes'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Écritures comptables avec filtres
     */
    public function getEcritures(array $residenceIds, ?int $annee = null, ?int $mois = null, ?string $type = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);

        $sql = "SELECT c.*, res.nom as residence_nom
                FROM rest_comptabilite c
                JOIN coproprietees res ON c.residence_id = res.id
                WHERE c.residence_id IN ($ph)";
        if ($annee) { $sql .= " AND c.annee = ?"; $params[] = $annee; }
        if ($mois) { $sql .= " AND c.mois = ?"; $params[] = $mois; }
        if ($type) { $sql .= " AND c.type_ecriture = ?"; $params[] = $type; }
        $sql .= " ORDER BY c.date_ecriture DESC, c.id DESC";

        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Synthèse mensuelle (recettes / dépenses / résultat)
     */
    public function getSyntheseMensuelle(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);

        $sql = "SELECT mois,
                   SUM(CASE WHEN type_ecriture = 'recette' THEN montant_ht ELSE 0 END) as recettes_ht,
                   SUM(CASE WHEN type_ecriture = 'recette' THEN montant_tva ELSE 0 END) as recettes_tva,
                   SUM(CASE WHEN type_ecriture = 'recette' THEN montant_ttc ELSE 0 END) as recettes_ttc,
                   SUM(CASE WHEN type_ecriture = 'depense' THEN montant_ht ELSE 0 END) as depenses_ht,
                   SUM(CASE WHEN type_ecriture = 'depense' THEN montant_tva ELSE 0 END) as depenses_tva,
                   SUM(CASE WHEN type_ecriture = 'depense' THEN montant_ttc ELSE 0 END) as depenses_ttc
                FROM rest_comptabilite
                WHERE residence_id IN ($ph) AND annee = ?
                GROUP BY mois ORDER BY mois";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Synthèse par catégorie (pour camembert)
     */
    public function getSyntheseParCategorie(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);

        $sql = "SELECT type_ecriture, categorie,
                   SUM(montant_ht) as total_ht, SUM(montant_tva) as total_tva, SUM(montant_ttc) as total_ttc,
                   COUNT(*) as nb_ecritures
                FROM rest_comptabilite
                WHERE residence_id IN ($ph) AND annee = ?";
        if ($mois) { $sql .= " AND mois = ?"; $params[] = $mois; }
        $sql .= " GROUP BY type_ecriture, categorie ORDER BY type_ecriture, total_ttc DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * TVA collectée et déductible pour une période
     */
    public function getTVA(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return ['collectee' => 0, 'deductible' => 0, 'a_reverser' => 0];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);

        $sql = "SELECT
                   SUM(CASE WHEN type_ecriture = 'recette' THEN montant_tva ELSE 0 END) as collectee,
                   SUM(CASE WHEN type_ecriture = 'depense' THEN montant_tva ELSE 0 END) as deductible
                FROM rest_comptabilite
                WHERE residence_id IN ($ph) AND annee = ?";
        if ($mois) { $sql .= " AND mois = ?"; $params[] = $mois; }
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $row['a_reverser'] = ($row['collectee'] ?? 0) - ($row['deductible'] ?? 0);
            return $row;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return ['collectee' => 0, 'deductible' => 0, 'a_reverser' => 0]; }
    }

    /**
     * Export des écritures pour comptabilité générale (format tabulaire)
     */
    public function getEcrituresExport(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);

        $sql = "SELECT c.date_ecriture, c.compte_comptable, c.libelle, c.type_ecriture,
                   c.montant_ht, c.montant_tva, c.montant_ttc, c.categorie,
                   c.reference_type, c.reference_id, c.mois, c.annee,
                   res.nom as residence_nom
                FROM rest_comptabilite c
                JOIN coproprietees res ON c.residence_id = res.id
                WHERE c.residence_id IN ($ph) AND c.annee = ?";
        if ($mois) { $sql .= " AND c.mois = ?"; $params[] = $mois; }
        $sql .= " ORDER BY c.date_ecriture, c.id";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Totaux annuels (pour le dashboard comptabilité)
     */
    public function getTotauxAnnuels(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return ['recettes_ht' => 0, 'recettes_ttc' => 0, 'depenses_ht' => 0, 'depenses_ttc' => 0, 'resultat_ht' => 0, 'resultat_ttc' => 0];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);

        $sql = "SELECT
                   COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ht END), 0) as recettes_ht,
                   COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc END), 0) as recettes_ttc,
                   COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ht END), 0) as depenses_ht,
                   COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc END), 0) as depenses_ttc
                FROM rest_comptabilite
                WHERE residence_id IN ($ph) AND annee = ?";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute($params);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $r['resultat_ht'] = $r['recettes_ht'] - $r['depenses_ht'];
            $r['resultat_ttc'] = $r['recettes_ttc'] - $r['depenses_ttc'];
            return $r;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return ['recettes_ht'=>0,'recettes_ttc'=>0,'depenses_ht'=>0,'depenses_ttc'=>0,'resultat_ht'=>0,'resultat_ttc'=>0]; }
    }

    // ─────────────────────────────────────────────────────────────
    //  FOURNISSEURS RESTAURATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Fournisseurs d'une résidence avec stats commandes
     */
    public function getFournisseursResidence(int $residenceId): array {
        $sql = "SELECT f.*, fr.statut as lien_statut, fr.contact_local, fr.telephone_local,
                       fr.jour_livraison, fr.delai_livraison_jours, fr.notes as notes_residence,
                       (SELECT COUNT(*) FROM rest_commandes c WHERE c.fournisseur_id = f.id AND c.residence_id = ?) as nb_commandes,
                       (SELECT COALESCE(SUM(c2.montant_total_ttc), 0) FROM rest_commandes c2 WHERE c2.fournisseur_id = f.id AND c2.residence_id = ? AND c2.statut != 'annulee') as total_commandes,
                       (SELECT MAX(c3.date_commande) FROM rest_commandes c3 WHERE c3.fournisseur_id = f.id AND c3.residence_id = ?) as derniere_commande
                FROM fournisseurs f
                JOIN rest_fournisseur_residence fr ON fr.fournisseur_id = f.id AND fr.residence_id = ?
                WHERE fr.statut = 'actif'
                ORDER BY f.nom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId, $residenceId, $residenceId, $residenceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Détail d'un fournisseur avec ses résidences
     */
    public function getFournisseurDetail(int $fournisseurId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM fournisseurs WHERE id = ?");
            $stmt->execute([$fournisseurId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$f) return null;

            $stmtR = $this->db->prepare("SELECT fr.*, c.nom as residence_nom, c.ville
                FROM rest_fournisseur_residence fr
                JOIN coproprietees c ON fr.residence_id = c.id
                WHERE fr.fournisseur_id = ? ORDER BY c.nom");
            $stmtR->execute([$fournisseurId]);
            $f['residences'] = $stmtR->fetchAll(PDO::FETCH_ASSOC);

            // Dernières commandes
            $stmtC = $this->db->prepare("SELECT c.*, res.nom as residence_nom
                FROM rest_commandes c JOIN coproprietees res ON c.residence_id = res.id
                WHERE c.fournisseur_id = ? ORDER BY c.date_commande DESC LIMIT 10");
            $stmtC->execute([$fournisseurId]);
            $f['commandes_recentes'] = $stmtC->fetchAll(PDO::FETCH_ASSOC);

            return $f;
        } catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    /**
     * Fournisseurs non encore liés à une résidence
     */
    public function getFournisseursNonLies(int $residenceId): array {
        $sql = "SELECT id, nom, type_service FROM fournisseurs WHERE actif = 1
                AND id NOT IN (SELECT fournisseur_id FROM rest_fournisseur_residence WHERE residence_id = ? AND statut = 'actif')
                ORDER BY nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Lier un fournisseur à une résidence
     */
    public function lierFournisseurResidence(int $fournisseurId, int $residenceId, array $data): bool {
        $sql = "INSERT INTO rest_fournisseur_residence (fournisseur_id, residence_id, statut, contact_local, telephone_local, jour_livraison, delai_livraison_jours, notes)
                VALUES (?,?,'actif',?,?,?,?,?)
                ON DUPLICATE KEY UPDATE statut='actif', contact_local=VALUES(contact_local), telephone_local=VALUES(telephone_local),
                    jour_livraison=VALUES(jour_livraison), delai_livraison_jours=VALUES(delai_livraison_jours), notes=VALUES(notes)";
        return $this->db->prepare($sql)->execute([
            $fournisseurId, $residenceId,
            $data['contact_local'] ?: null, $data['telephone_local'] ?: null,
            $data['jour_livraison'] ?: null, !empty($data['delai_livraison_jours']) ? (int)$data['delai_livraison_jours'] : null,
            $data['notes'] ?: null
        ]);
    }

    /**
     * Délier un fournisseur d'une résidence
     */
    public function delierFournisseurResidence(int $fournisseurId, int $residenceId): bool {
        return $this->db->prepare("UPDATE rest_fournisseur_residence SET statut = 'termine' WHERE fournisseur_id = ? AND residence_id = ?")
            ->execute([$fournisseurId, $residenceId]);
    }

    /**
     * Mettre à jour le lien fournisseur ↔ résidence
     */
    public function updateFournisseurResidence(int $fournisseurId, int $residenceId, array $data): bool {
        $sql = "UPDATE rest_fournisseur_residence SET contact_local=?, telephone_local=?, jour_livraison=?, delai_livraison_jours=?, notes=? WHERE fournisseur_id=? AND residence_id=?";
        return $this->db->prepare($sql)->execute([
            $data['contact_local'] ?: null, $data['telephone_local'] ?: null,
            $data['jour_livraison'] ?: null, !empty($data['delai_livraison_jours']) ? (int)$data['delai_livraison_jours'] : null,
            $data['notes'] ?: null, $fournisseurId, $residenceId
        ]);
    }

    /**
     * Récupérer le lien fournisseur ↔ résidence
     */
    public function getFournisseurResidenceLien(int $fournisseurId, int $residenceId): ?array {
        $sql = "SELECT fr.*, f.nom as fournisseur_nom, f.siret, f.telephone, f.email, f.type_service, c.nom as residence_nom
                FROM rest_fournisseur_residence fr
                JOIN fournisseurs f ON fr.fournisseur_id = f.id
                JOIN coproprietees c ON fr.residence_id = c.id
                WHERE fr.fournisseur_id = ? AND fr.residence_id = ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$fournisseurId, $residenceId]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Dépenses par fournisseur pour la comptabilité
     */
    public function getDepensesParFournisseur(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$annee]);

        $sql = "SELECT f.id as fournisseur_id, f.nom as fournisseur_nom,
                   COUNT(c.id) as nb_commandes,
                   COALESCE(SUM(c.montant_total_ht), 0) as total_ht,
                   COALESCE(SUM(c.montant_tva), 0) as total_tva,
                   COALESCE(SUM(c.montant_total_ttc), 0) as total_ttc
                FROM rest_commandes c
                JOIN fournisseurs f ON c.fournisseur_id = f.id
                WHERE c.residence_id IN ($ph) AND YEAR(c.date_commande) = ? AND c.statut != 'annulee'";
        if ($mois) { $sql .= " AND MONTH(c.date_commande) = ?"; $params[] = $mois; }
        $sql .= " GROUP BY f.id, f.nom ORDER BY total_ttc DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ====================================================================
    // LAVERIE RESTAURATION (cycles d'envoi/retour, prestataire interne)
    // ====================================================================

    public function getLaverieCycles(array $residenceIds, ?string $statut = null, ?string $typeLinge = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT l.*, c.nom AS residence_nom,
                       ue.username AS user_envoi_nom, ur.username AS user_reception_nom
                FROM rest_laverie l
                JOIN coproprietees c ON l.residence_id = c.id
                LEFT JOIN users ue ON l.user_envoi_id = ue.id
                LEFT JOIN users ur ON l.user_reception_id = ur.id
                WHERE l.residence_id IN ($ph)";
        $params = $residenceIds;
        if ($statut)    { $sql .= " AND l.statut = ?";     $params[] = $statut; }
        if ($typeLinge) { $sql .= " AND l.type_linge = ?"; $params[] = $typeLinge; }
        $sql .= " ORDER BY l.date_envoi DESC, l.id DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getLaverieCycle(int $id): ?array {
        $sql = "SELECT l.*, c.nom AS residence_nom FROM rest_laverie l
                JOIN coproprietees c ON l.residence_id = c.id WHERE l.id = ? LIMIT 1";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$id]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    public function createLaverieCycle(array $data): int|false {
        $sql = "INSERT INTO rest_laverie
                (residence_id, type_linge, quantite_envoyee, date_envoi, cout, user_envoi_id, notes)
                VALUES (:residence_id, :type_linge, :quantite_envoyee, :date_envoi, :cout, :user_envoi_id, :notes)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'residence_id'     => $data['residence_id'],
                'type_linge'       => $data['type_linge'],
                'quantite_envoyee' => $data['quantite_envoyee'],
                'date_envoi'       => $data['date_envoi'] ?? date('Y-m-d H:i:s'),
                'cout'             => $data['cout'] ?? 0.00,
                'user_envoi_id'    => $data['user_envoi_id'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return false; }
    }

    public function receptionnerLaverieCycle(int $id, int $quantiteRecue, ?int $userId = null, ?string $notes = null): bool {
        $cycle = $this->getLaverieCycle($id);
        if (!$cycle) return false;
        $envoyee = (int)$cycle['quantite_envoyee'];
        if ($quantiteRecue < 0)        $quantiteRecue = 0;
        if ($quantiteRecue > $envoyee) $quantiteRecue = $envoyee;
        $statut = $quantiteRecue === $envoyee ? 'recu' : ($quantiteRecue === 0 ? 'perdu' : 'partiel');
        $sql = "UPDATE rest_laverie
                SET quantite_recue = :q, date_retour = :dr, statut = :s,
                    user_reception_id = :u, notes = COALESCE(:notes, notes)
                WHERE id = :id";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'q'     => $quantiteRecue,
                'dr'    => date('Y-m-d H:i:s'),
                's'     => $statut,
                'u'     => $userId,
                'notes' => $notes,
                'id'    => $id,
            ]);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return false; }
    }

    public function updateLaverieCycle(int $id, array $data): bool {
        $allowed = ['type_linge', 'quantite_envoyee', 'date_envoi', 'cout', 'notes'];
        $sets = []; $params = ['id' => $id];
        foreach ($allowed as $f) { if (array_key_exists($f, $data)) { $sets[] = "$f = :$f"; $params[$f] = $data[$f]; } }
        if (empty($sets)) return false;
        $sql = "UPDATE rest_laverie SET " . implode(', ', $sets) . " WHERE id = :id";
        try { $stmt = $this->db->prepare($sql); return $stmt->execute($params); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return false; }
    }

    public function deleteLaverieCycle(int $id): bool {
        try { $stmt = $this->db->prepare("DELETE FROM rest_laverie WHERE id = ?"); return $stmt->execute([$id]); }
        catch (PDOException $e) { $this->logError($e->getMessage()); return false; }
    }

    public function getLaverieStats(array $residenceIds, ?int $annee = null, ?int $mois = null): array {
        if (empty($residenceIds)) return ['cycles_total' => 0, 'en_cours' => 0, 'cout_total' => 0.0, 'pertes' => 0];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT
                    COUNT(*) AS cycles_total,
                    SUM(CASE WHEN statut IN ('envoye','partiel') THEN 1 ELSE 0 END) AS en_cours,
                    COALESCE(SUM(cout), 0) AS cout_total,
                    COALESCE(SUM(quantite_envoyee - COALESCE(quantite_recue, quantite_envoyee)), 0) AS pertes
                FROM rest_laverie
                WHERE residence_id IN ($ph)";
        $params = $residenceIds;
        if ($annee) { $sql .= " AND YEAR(date_envoi) = ?";  $params[] = $annee; }
        if ($mois)  { $sql .= " AND MONTH(date_envoi) = ?"; $params[] = $mois; }
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetch(PDO::FETCH_ASSOC) ?: []; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }
}
