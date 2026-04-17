<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Ménage
 * ====================================================================
 * Requêtes SQL pour le module ménage (dashboard, tâches, planning, stats).
 */

class Menage extends Model {

    public const ROLES_ALL = ['admin', 'directeur_residence', 'entretien_manager', 'menage_interieur', 'menage_exterieur', 'employe_laverie'];
    public const ROLES_MANAGER = ['admin', 'directeur_residence', 'entretien_manager'];

    // ─────────────────────────────────────────────────────────────
    //  RÉSIDENCES DE L'UTILISATEUR
    // ─────────────────────────────────────────────────────────────

    public function getResidencesByUser(int $userId): array {
        $sql = "SELECT c.id, c.nom, c.ville FROM user_residence ur JOIN coproprietees c ON ur.residence_id = c.id WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1 ORDER BY c.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getResidenceIdsByUser(int $userId): array {
        $sql = "SELECT ur.residence_id FROM user_residence ur JOIN coproprietees c ON ur.residence_id = c.id WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_COLUMN); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  DASHBOARD — STATS DU JOUR
    // ─────────────────────────────────────────────────────────────

    public function getStatsDuJour(array $residenceIds, ?string $date = null): array {
        if (empty($residenceIds)) return ['total' => 0, 'terminees' => 0, 'en_cours' => 0, 'a_faire' => 0, 'pas_deranger' => 0, 'taux' => 0];
        $date = $date ?? date('Y-m-d');
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge([$date], array_values($residenceIds));

        $sql = "SELECT COUNT(*) as total,
                   COUNT(CASE WHEN statut = 'termine' THEN 1 END) as terminees,
                   COUNT(CASE WHEN statut = 'en_cours' THEN 1 END) as en_cours,
                   COUNT(CASE WHEN statut = 'a_faire' THEN 1 END) as a_faire,
                   COUNT(CASE WHEN statut = 'pas_deranger' THEN 1 END) as pas_deranger
                FROM menage_taches_jour WHERE date_tache = ? AND residence_id IN ($ph)";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute($params);
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            $s['taux'] = $s['total'] > 0 ? round(($s['terminees'] / $s['total']) * 100) : 0;
            return $s;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return ['total'=>0,'terminees'=>0,'en_cours'=>0,'a_faire'=>0,'pas_deranger'=>0,'taux'=>0]; }
    }

    public function getStatsParSection(array $residenceIds, ?string $date = null): array {
        if (empty($residenceIds)) return [];
        $date = $date ?? date('Y-m-d');
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge([$date], array_values($residenceIds));

        $sql = "SELECT type_tache, COUNT(*) as total,
                   COUNT(CASE WHEN statut = 'termine' THEN 1 END) as terminees,
                   COUNT(CASE WHEN statut = 'a_faire' THEN 1 END) as a_faire
                FROM menage_taches_jour WHERE date_tache = ? AND residence_id IN ($ph)
                GROUP BY type_tache";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getStatsMois(array $residenceIds): array {
        if (empty($residenceIds)) return ['alertes_stock' => 0, 'commandes_en_cours' => 0, 'laverie_en_attente' => 0];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $ids = array_values($residenceIds);
        $stats = ['alertes_stock' => 0, 'commandes_en_cours' => 0, 'laverie_en_attente' => 0];
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM menage_inventaire WHERE quantite_stock <= seuil_alerte AND seuil_alerte > 0 AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['alertes_stock'] = (int)$stmt->fetchColumn();
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM menage_commandes WHERE statut IN ('brouillon','envoyee','livree_partiel') AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['commandes_en_cours'] = (int)$stmt->fetchColumn();
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM menage_laverie_demandes WHERE statut IN ('demandee','en_cours') AND residence_id IN ($ph)");
            $stmt->execute($ids); $stats['laverie_en_attente'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) { $this->logError($e->getMessage()); }
        return $stats;
    }

    // ─────────────────────────────────────────────────────────────
    //  STAFF MÉNAGE
    // ─────────────────────────────────────────────────────────────

    public function getStaffByResidences(array $residenceIds, ?string $section = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $roles = "'entretien_manager','menage_interieur','menage_exterieur','employe_laverie'";
        if ($section === 'interieur') $roles = "'entretien_manager','menage_interieur'";
        elseif ($section === 'exterieur') $roles = "'entretien_manager','menage_exterieur'";
        elseif ($section === 'laverie') $roles = "'entretien_manager','employe_laverie'";

        $sql = "SELECT u.id, u.nom, u.prenom, u.role, u.email, u.telephone, u.actif, u.last_login,
                       c.nom as residence_nom, c.id as residence_id,
                       r.nom_affichage as role_nom, r.couleur as role_couleur, r.icone as role_icone
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.statut = 'actif'
                JOIN coproprietees c ON ur.residence_id = c.id
                LEFT JOIN roles r ON u.role = r.slug
                WHERE u.role IN ($roles) AND u.actif = 1 AND c.id IN ($ph)
                ORDER BY c.nom, u.role, u.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute(array_values($residenceIds)); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Employés avec shift aujourd'hui pour une résidence (pour distribution auto)
     */
    public function getEmployesAvecShift(int $residenceId, string $date, string $section): array {
        $roles = $section === 'interieur' ? "'menage_interieur'" : ($section === 'exterieur' ? "'menage_exterieur'" : "'employe_laverie'");
        $sql = "SELECT DISTINCT u.id, u.nom, u.prenom, u.role
                FROM users u
                JOIN planning_shifts ps ON ps.user_id = u.id
                WHERE u.role IN ($roles) AND u.actif = 1
                AND ps.residence_id = ? AND ps.date_debut <= CONCAT(?, ' 23:59:59') AND ps.date_fin >= CONCAT(?, ' 00:00:00')
                ORDER BY u.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId, $date, $date]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  TÂCHES DU JOUR — LECTURE
    // ─────────────────────────────────────────────────────────────

    public function getTachesDuJour(int $residenceId, string $date, ?string $type = null, ?int $employeId = null): array {
        $sql = "SELECT t.*, l.numero_lot, l.type as lot_type,
                   CASE WHEN t.resident_id IS NOT NULL THEN CONCAT(rs.prenom, ' ', rs.nom) ELSE NULL END as resident_nom,
                   CASE WHEN t.hote_id IS NOT NULL THEN CONCAT(ht.prenom, ' ', ht.nom) ELSE NULL END as hote_nom,
                   z.nom as zone_nom, z.type_zone,
                   u.prenom as employe_prenom, u.nom as employe_nom
                FROM menage_taches_jour t
                LEFT JOIN lots l ON t.lot_id = l.id
                LEFT JOIN residents_seniors rs ON t.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON t.hote_id = ht.id
                LEFT JOIN menage_zones_exterieures z ON t.zone_exterieure_id = z.id
                LEFT JOIN users u ON t.employe_id = u.id
                WHERE t.residence_id = ? AND t.date_tache = ?";
        $params = [$residenceId, $date];
        if ($type) { $sql .= " AND t.type_tache = ?"; $params[] = $type; }
        if ($employeId) { $sql .= " AND t.employe_id = ?"; $params[] = $employeId; }
        $sql .= " ORDER BY t.type_tache, t.statut, l.numero_lot, z.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Checklist d'une tâche
     */
    public function getChecklist(int $tacheId): array {
        $sql = "SELECT * FROM menage_taches_checklist WHERE tache_id = ? ORDER BY id";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$tacheId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Tâches récentes terminées (pour dashboard)
     */
    public function getTachesRecentes(array $residenceIds, int $limit = 10): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);
        $sql = "SELECT t.*, l.numero_lot, z.nom as zone_nom,
                   u.prenom as employe_prenom, u.nom as employe_nom, c.nom as residence_nom
                FROM menage_taches_jour t
                LEFT JOIN lots l ON t.lot_id = l.id
                LEFT JOIN menage_zones_exterieures z ON t.zone_exterieure_id = z.id
                LEFT JOIN users u ON t.employe_id = u.id
                JOIN coproprietees c ON t.residence_id = c.id
                WHERE t.residence_id IN ($ph) AND t.statut = 'termine'
                ORDER BY t.updated_at DESC LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Alertes stock ménage
     */
    public function getAlertesStock(array $residenceIds, int $limit = 5): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);
        $sql = "SELECT i.*, p.nom as produit_nom, p.unite, p.section, c.nom as residence_nom
                FROM menage_inventaire i JOIN menage_produits p ON i.produit_id = p.id JOIN coproprietees c ON i.residence_id = c.id
                WHERE i.quantite_stock <= i.seuil_alerte AND i.seuil_alerte > 0 AND i.residence_id IN ($ph)
                ORDER BY (i.quantite_stock / NULLIF(i.seuil_alerte, 0)) ASC LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  ZONES EXTÉRIEURES (CRUD)
    // ─────────────────────────────────────────────────────────────

    public function getZones(int $residenceId): array {
        $sql = "SELECT * FROM menage_zones_exterieures WHERE residence_id = ? ORDER BY type_zone, priorite DESC, nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function createZone(array $d): int {
        $sql = "INSERT INTO menage_zones_exterieures (residence_id, nom, type_zone, frequence, jour_semaine, priorite, description, actif) VALUES (?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([$d['residence_id'], $d['nom'], $d['type_zone'], $d['frequence'] ?? 'quotidien', $d['jour_semaine'] ?: null, (int)($d['priorite'] ?? 0), $d['description'] ?: null, isset($d['actif']) ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }

    public function updateZone(int $id, array $d): bool {
        $sql = "UPDATE menage_zones_exterieures SET nom=?, type_zone=?, frequence=?, jour_semaine=?, priorite=?, description=?, actif=? WHERE id=?";
        return $this->db->prepare($sql)->execute([$d['nom'], $d['type_zone'], $d['frequence'] ?? 'quotidien', $d['jour_semaine'] ?: null, (int)($d['priorite'] ?? 0), $d['description'] ?: null, isset($d['actif']) ? 1 : 0, $id]);
    }

    public function deleteZone(int $id): bool {
        return $this->db->prepare("UPDATE menage_zones_exterieures SET actif = 0 WHERE id = ?")->execute([$id]);
    }

    // ─────────────────────────────────────────────────────────────
    //  DEMANDES LAVERIE (dashboard)
    // ─────────────────────────────────────────────────────────────

    public function getLaverieEnAttente(array $residenceIds, int $limit = 5): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_merge(array_values($residenceIds), [$limit]);
        $sql = "SELECT d.*, CONCAT(rs.prenom, ' ', rs.nom) as resident_nom, c.nom as residence_nom
                FROM menage_laverie_demandes d
                JOIN residents_seniors rs ON d.resident_id = rs.id
                JOIN coproprietees c ON d.residence_id = c.id
                WHERE d.statut IN ('demandee','en_cours') AND d.residence_id IN ($ph)
                ORDER BY d.date_demande ASC LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  GÉNÉRATION AUTOMATIQUE DES TÂCHES DU JOUR
    // ─────────────────────────────────────────────────────────────

    /**
     * Poids par type de lot (pour distribution équitable)
     */
    private const POIDS_LOT = ['studio' => 1.0, 't2' => 1.5, 't2_bis' => 1.5, 't3' => 2.0];

    /**
     * Vérifie si les tâches ont déjà été générées pour une date/résidence
     */
    public function tachesDejaGenerees(int $residenceId, string $date, string $type): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM menage_taches_jour WHERE residence_id = ? AND date_tache = ? AND type_tache = ? AND generated_auto = 1");
        $stmt->execute([$residenceId, $date, $type]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Générer les tâches intérieur du jour pour une résidence
     * Règles :
     * - Résident premium → tâche tous les jours
     * - Résident basique → tâche lun/mer/ven
     * - Résident aucun → pas de tâche
     * - Hôte → tâche sauf ne_pas_deranger
     * Retourne le nombre de tâches créées
     */
    public function genererTachesInterieur(int $residenceId, string $date): int {
        if ($this->tachesDejaGenerees($residenceId, $date, 'interieur')) return 0;

        $jourSemaine = (int)date('N', strtotime($date)); // 1=lundi...7=dimanche
        $joursBasique = [1, 3, 5]; // lun, mer, ven

        try {
            $this->db->beginTransaction();
            $count = 0;

            // 1. Lots occupés par des résidents
            $sql = "SELECT o.id as occupation_id, o.resident_id, o.lot_id, o.forfait_type,
                           l.type as lot_type, l.numero_lot,
                           os.slug as service_slug
                    FROM occupations_residents o
                    JOIN lots l ON o.lot_id = l.id
                    LEFT JOIN occupation_services ocs ON ocs.occupation_id = o.id
                    LEFT JOIN services os ON ocs.service_id = os.id AND os.slug LIKE 'menage%'
                    WHERE o.statut = 'actif' AND l.copropriete_id = ?
                    AND l.type IN ('studio','t2','t2_bis','t3')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            $occupations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($occupations as $occ) {
                // Déterminer le niveau de service
                $niveau = 'aucun';
                if ($occ['forfait_type'] === 'premium' || $occ['service_slug'] === 'menage_premium') {
                    $niveau = 'premium';
                } elseif ($occ['forfait_type'] === 'confort' || $occ['service_slug'] === 'menage_basique') {
                    $niveau = 'basique';
                }

                // Appliquer les règles de fréquence
                if ($niveau === 'aucun') continue;
                if ($niveau === 'basique' && !in_array($jourSemaine, $joursBasique)) continue;
                // premium → tous les jours

                $poids = self::POIDS_LOT[$occ['lot_type']] ?? 1.0;

                $this->db->prepare("INSERT INTO menage_taches_jour
                    (residence_id, date_tache, type_tache, lot_id, resident_id, niveau_service, poids, statut, generated_auto)
                    VALUES (?,?,'interieur',?,?,?,?,'a_faire',1)")
                    ->execute([$residenceId, $date, $occ['lot_id'], $occ['resident_id'], $niveau, $poids]);

                $tacheId = (int)$this->db->lastInsertId();
                $this->genererChecklistPourTache($tacheId, $occ['lot_type'], null);
                $count++;
            }

            // 2. Lots occupés par des hôtes temporaires
            $sqlH = "SELECT h.id as hote_id, h.lot_id, h.ne_pas_deranger, l.type as lot_type
                     FROM hotes_temporaires h
                     JOIN lots l ON h.lot_id = l.id
                     WHERE h.statut = 'en_cours' AND h.residence_id = ?
                     AND l.type IN ('studio','t2','t2_bis','t3')";
            $stmtH = $this->db->prepare($sqlH);
            $stmtH->execute([$residenceId]);
            $hotes = $stmtH->fetchAll(PDO::FETCH_ASSOC);

            foreach ($hotes as $hote) {
                $statut = $hote['ne_pas_deranger'] ? 'pas_deranger' : 'a_faire';
                $poids = self::POIDS_LOT[$hote['lot_type']] ?? 1.0;

                $this->db->prepare("INSERT INTO menage_taches_jour
                    (residence_id, date_tache, type_tache, lot_id, hote_id, niveau_service, poids, statut, generated_auto)
                    VALUES (?,?,'interieur',?,?,'premium',?,?,1)")
                    ->execute([$residenceId, $date, $hote['lot_id'], $hote['hote_id'], $poids, $statut]);

                $tacheId = (int)$this->db->lastInsertId();
                $this->genererChecklistPourTache($tacheId, $hote['lot_type'], null);
                $count++;
            }

            $this->db->commit();
            return $count;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError("Erreur genererTachesInterieur: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Générer la checklist depuis les templates pour une tâche
     */
    private function genererChecklistPourTache(int $tacheId, ?string $typeLot, ?string $typeZone): void {
        $sql = "SELECT libelle FROM menage_checklist_templates WHERE actif = 1";
        $params = [];
        if ($typeLot) { $sql .= " AND type_lot = ?"; $params[] = $typeLot; }
        elseif ($typeZone) { $sql .= " AND type_zone = ?"; $params[] = $typeZone; }
        else return;
        $sql .= " ORDER BY ordre";

        $items = $this->db->prepare($sql);
        $items->execute($params);
        $stmtIns = $this->db->prepare("INSERT INTO menage_taches_checklist (tache_id, libelle) VALUES (?,?)");
        foreach ($items->fetchAll(PDO::FETCH_COLUMN) as $libelle) {
            $stmtIns->execute([$tacheId, $libelle]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  DISTRIBUTION ÉQUITABLE
    // ─────────────────────────────────────────────────────────────

    /**
     * Distribuer les tâches intérieur entre les employés disponibles
     * Algorithme : trier les tâches par poids décroissant, assigner à l'employé avec le moins de poids
     */
    public function distribuerTachesInterieur(int $residenceId, string $date): array {
        // Récupérer employés avec shift ce jour
        $employes = $this->getEmployesAvecShift($residenceId, $date, 'interieur');
        if (empty($employes)) return ['error' => 'Aucun employé ménage intérieur avec shift ce jour'];

        // Récupérer tâches non affectées
        $stmt = $this->db->prepare("SELECT id, poids FROM menage_taches_jour
            WHERE residence_id = ? AND date_tache = ? AND type_tache = 'interieur' AND employe_id IS NULL AND statut IN ('a_faire','pas_deranger')
            ORDER BY poids DESC");
        $stmt->execute([$residenceId, $date]);
        $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($taches)) return ['error' => 'Aucune tâche à distribuer'];

        // Initialiser les compteurs par employé
        $empLoad = [];
        foreach ($employes as $e) {
            $empLoad[$e['id']] = ['poids' => 0, 'count' => 0, 'nom' => $e['prenom'] . ' ' . $e['nom']];
        }

        // Distribution : assigner chaque tâche à l'employé le moins chargé
        $stmtUpdate = $this->db->prepare("UPDATE menage_taches_jour SET employe_id = ? WHERE id = ?");
        foreach ($taches as $t) {
            // Trouver l'employé avec le moins de poids
            $minEmp = null; $minPoids = PHP_FLOAT_MAX;
            foreach ($empLoad as $empId => $data) {
                if ($data['poids'] < $minPoids) { $minPoids = $data['poids']; $minEmp = $empId; }
            }
            $stmtUpdate->execute([$minEmp, $t['id']]);
            $empLoad[$minEmp]['poids'] += (float)$t['poids'];
            $empLoad[$minEmp]['count']++;
        }

        // Créer/mettre à jour les affectations
        $stmtAff = $this->db->prepare("INSERT INTO menage_affectations (residence_id, date_affectation, employe_id, type_affectation, nb_taches, poids_total, generated_auto)
            VALUES (?,?,?,'interieur',?,?,1) ON DUPLICATE KEY UPDATE nb_taches = VALUES(nb_taches), poids_total = VALUES(poids_total)");
        foreach ($empLoad as $empId => $data) {
            if ($data['count'] > 0) {
                $stmtAff->execute([$residenceId, $date, $empId, $data['count'], $data['poids']]);
            }
        }

        return ['success' => true, 'distributed' => count($taches), 'employes' => $empLoad];
    }

    /**
     * Réaffecter une tâche à un autre employé (manager)
     */
    public function reassignerTache(int $tacheId, int $employeId): bool {
        return $this->db->prepare("UPDATE menage_taches_jour SET employe_id = ? WHERE id = ?")->execute([$employeId, $tacheId]);
    }

    // ─────────────────────────────────────────────────────────────
    //  TÂCHES — ACTIONS EMPLOYÉ
    // ─────────────────────────────────────────────────────────────

    /**
     * Mes tâches du jour (pour un employé)
     */
    public function getMesTaches(int $employeId, string $date): array {
        $sql = "SELECT t.*, l.numero_lot, l.type as lot_type, l.etage,
                   CASE WHEN t.resident_id IS NOT NULL THEN CONCAT(rs.prenom, ' ', rs.nom) ELSE NULL END as resident_nom,
                   CASE WHEN t.hote_id IS NOT NULL THEN CONCAT(ht.prenom, ' ', ht.nom) ELSE NULL END as hote_nom,
                   z.nom as zone_nom, z.type_zone, c.nom as residence_nom,
                   (SELECT COUNT(*) FROM menage_taches_checklist WHERE tache_id = t.id) as total_items,
                   (SELECT COUNT(*) FROM menage_taches_checklist WHERE tache_id = t.id AND fait = 1) as items_faits
                FROM menage_taches_jour t
                LEFT JOIN lots l ON t.lot_id = l.id
                LEFT JOIN residents_seniors rs ON t.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON t.hote_id = ht.id
                LEFT JOIN menage_zones_exterieures z ON t.zone_exterieure_id = z.id
                JOIN coproprietees c ON t.residence_id = c.id
                WHERE t.employe_id = ? AND t.date_tache = ?
                ORDER BY t.statut = 'a_faire' DESC, t.poids DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$employeId, $date]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Démarrer une tâche
     */
    public function demarrerTache(int $tacheId): bool {
        return $this->db->prepare("UPDATE menage_taches_jour SET statut = 'en_cours', heure_debut = CURTIME(), updated_at = NOW() WHERE id = ? AND statut = 'a_faire'")
            ->execute([$tacheId]);
    }

    /**
     * Cocher un item de checklist
     */
    public function cocherChecklistItem(int $itemId, bool $fait): bool {
        $heure = $fait ? date('H:i:s') : null;
        return $this->db->prepare("UPDATE menage_taches_checklist SET fait = ?, heure_fait = ? WHERE id = ?")
            ->execute([$fait ? 1 : 0, $heure, $itemId]);
    }

    /**
     * Terminer une tâche (vérifie que tous les items sont cochés)
     */
    public function terminerTache(int $tacheId): array {
        // Vérifier la checklist
        $stmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(fait) as faits FROM menage_taches_checklist WHERE tache_id = ?");
        $stmt->execute([$tacheId]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($check['total'] > 0 && $check['faits'] < $check['total']) {
            return ['success' => false, 'message' => 'Tous les éléments de la checklist doivent être cochés (' . $check['faits'] . '/' . $check['total'] . ')'];
        }

        $this->db->prepare("UPDATE menage_taches_jour SET statut = 'termine', heure_fin = CURTIME(), updated_at = NOW() WHERE id = ?")
            ->execute([$tacheId]);
        return ['success' => true];
    }

    /**
     * Marquer une tâche comme "pas déranger" (hôte)
     */
    public function marquerPasDeranger(int $tacheId): bool {
        return $this->db->prepare("UPDATE menage_taches_jour SET statut = 'pas_deranger', notes = CONCAT(COALESCE(notes,''), ' [Pas déranger signalé]'), updated_at = NOW() WHERE id = ?")
            ->execute([$tacheId]);
    }

    /**
     * Détail d'une tâche avec checklist
     */
    public function getTache(int $id): ?array {
        $sql = "SELECT t.*, l.numero_lot, l.type as lot_type, l.etage,
                   CASE WHEN t.resident_id IS NOT NULL THEN CONCAT(rs.prenom, ' ', rs.nom) ELSE NULL END as resident_nom,
                   CASE WHEN t.hote_id IS NOT NULL THEN CONCAT(ht.prenom, ' ', ht.nom) ELSE NULL END as hote_nom,
                   z.nom as zone_nom, z.type_zone, c.nom as residence_nom,
                   u.prenom as employe_prenom, u.nom as employe_nom
                FROM menage_taches_jour t
                LEFT JOIN lots l ON t.lot_id = l.id
                LEFT JOIN residents_seniors rs ON t.resident_id = rs.id
                LEFT JOIN hotes_temporaires ht ON t.hote_id = ht.id
                LEFT JOIN menage_zones_exterieures z ON t.zone_exterieure_id = z.id
                JOIN coproprietees c ON t.residence_id = c.id
                LEFT JOIN users u ON t.employe_id = u.id
                WHERE t.id = ?";
        try {
            $stmt = $this->db->prepare($sql); $stmt->execute([$id]);
            $tache = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$tache) return null;
            $tache['checklist'] = $this->getChecklist($id);
            return $tache;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    /**
     * Affectations du jour avec stats par employé
     */
    public function getAffectationsDuJour(int $residenceId, string $date, string $type = 'interieur'): array {
        $sql = "SELECT a.*, u.prenom, u.nom,
                   (SELECT COUNT(*) FROM menage_taches_jour WHERE employe_id = a.employe_id AND date_tache = ? AND type_tache = ? AND statut = 'termine') as terminees
                FROM menage_affectations a
                JOIN users u ON a.employe_id = u.id
                WHERE a.residence_id = ? AND a.date_affectation = ? AND a.type_affectation = ?
                ORDER BY a.poids_total DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$date, $type, $residenceId, $date, $type]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  MÉNAGE EXTÉRIEUR — GÉNÉRATION & GESTION
    // ─────────────────────────────────────────────────────────────

    /**
     * Générer les tâches extérieur du jour pour une résidence
     * Basé sur les zones actives et leur fréquence
     */
    public function genererTachesExterieur(int $residenceId, string $date): int {
        if ($this->tachesDejaGenerees($residenceId, $date, 'exterieur')) return 0;

        $jourSemaine = strtolower(date('l', strtotime($date)));
        $joursFr = ['monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi','thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'];
        $jourFr = $joursFr[$jourSemaine] ?? $jourSemaine;

        try {
            $this->db->beginTransaction();
            $count = 0;

            $zones = $this->getZones($residenceId);
            foreach ($zones as $z) {
                if (!$z['actif']) continue;

                // Vérifier la fréquence
                if ($z['frequence'] === 'quotidien') {
                    // OK
                } elseif ($z['frequence'] === 'hebdomadaire' || $z['frequence'] === 'bihebdomadaire') {
                    if ($z['jour_semaine']) {
                        $jours = array_map('trim', explode(',', strtolower($z['jour_semaine'])));
                        if (!in_array($jourFr, $jours)) continue;
                    } else {
                        // Par défaut lundi pour hebdo
                        if ($jourFr !== 'lundi') continue;
                    }
                } elseif ($z['frequence'] === 'mensuel') {
                    if ((int)date('d', strtotime($date)) !== 1) continue; // 1er du mois
                }

                $this->db->prepare("INSERT INTO menage_taches_jour
                    (residence_id, date_tache, type_tache, zone_exterieure_id, poids, statut, generated_auto)
                    VALUES (?,?,'exterieur',?,1.0,'a_faire',1)")
                    ->execute([$residenceId, $date, $z['id']]);

                $tacheId = (int)$this->db->lastInsertId();
                $this->genererChecklistPourTache($tacheId, null, $z['type_zone']);
                $count++;
            }

            $this->db->commit();
            return $count;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError("Erreur genererTachesExterieur: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Distribuer les tâches extérieur entre les employés
     */
    public function distribuerTachesExterieur(int $residenceId, string $date): array {
        $employes = $this->getEmployesAvecShift($residenceId, $date, 'exterieur');
        if (empty($employes)) return ['error' => 'Aucun employé ménage extérieur avec shift ce jour'];

        $stmt = $this->db->prepare("SELECT id, poids FROM menage_taches_jour
            WHERE residence_id = ? AND date_tache = ? AND type_tache = 'exterieur' AND employe_id IS NULL AND statut = 'a_faire'
            ORDER BY poids DESC");
        $stmt->execute([$residenceId, $date]);
        $taches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($taches)) return ['error' => 'Aucune tâche à distribuer'];

        $empLoad = [];
        foreach ($employes as $e) { $empLoad[$e['id']] = ['poids' => 0, 'count' => 0, 'nom' => $e['prenom'] . ' ' . $e['nom']]; }

        $stmtUp = $this->db->prepare("UPDATE menage_taches_jour SET employe_id = ? WHERE id = ?");
        foreach ($taches as $t) {
            $minEmp = null; $minPoids = PHP_FLOAT_MAX;
            foreach ($empLoad as $empId => $data) { if ($data['poids'] < $minPoids) { $minPoids = $data['poids']; $minEmp = $empId; } }
            $stmtUp->execute([$minEmp, $t['id']]);
            $empLoad[$minEmp]['poids'] += (float)$t['poids'];
            $empLoad[$minEmp]['count']++;
        }

        $stmtAff = $this->db->prepare("INSERT INTO menage_affectations (residence_id, date_affectation, employe_id, type_affectation, nb_taches, poids_total, generated_auto)
            VALUES (?,?,?,'exterieur',?,?,1) ON DUPLICATE KEY UPDATE nb_taches = VALUES(nb_taches), poids_total = VALUES(poids_total)");
        foreach ($empLoad as $empId => $data) {
            if ($data['count'] > 0) $stmtAff->execute([$residenceId, $date, $empId, $data['count'], $data['poids']]);
        }

        return ['success' => true, 'distributed' => count($taches), 'employes' => $empLoad];
    }

    // ─────────────────────────────────────────────────────────────
    //  LAVERIE — DEMANDES & GESTION
    // ─────────────────────────────────────────────────────────────

    /**
     * Tarifs laverie d'une résidence
     */
    public function getLaverieTarifs(int $residenceId): array {
        $sql = "SELECT * FROM menage_laverie_tarifs WHERE residence_id = ? AND actif = 1 ORDER BY type_linge";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Résidents de la résidence (pour sélection demande)
     */
    public function getResidentsResidence(int $residenceId): array {
        $sql = "SELECT rs.id, rs.civilite, rs.prenom, rs.nom, o.forfait_type,
                       l.numero_lot,
                       (SELECT COUNT(*) FROM occupation_services ocs JOIN services s ON ocs.service_id = s.id WHERE ocs.occupation_id = o.id AND s.slug LIKE 'laverie%') as has_laverie
                FROM residents_seniors rs
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON o.lot_id = l.id
                WHERE rs.actif = 1 AND l.copropriete_id = ?
                ORDER BY rs.nom, rs.prenom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Créer une demande laverie
     */
    public function creerDemandeLaverie(array $d): int {
        $sql = "INSERT INTO menage_laverie_demandes
                (residence_id, resident_id, date_demande, type_linge, quantite, service_inclus, prix_unitaire, montant_total, statut, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?)";
        $qte = (int)($d['quantite'] ?? 1);
        $prix = (float)($d['prix_unitaire'] ?? 0);
        $serviceInclus = (int)($d['service_inclus'] ?? 0);
        $montant = $serviceInclus ? 0 : $prix * $qte;

        $this->db->prepare($sql)->execute([
            $d['residence_id'], $d['resident_id'], $d['date_demande'] ?? date('Y-m-d'),
            $d['type_linge'], $qte, $serviceInclus, $prix, $montant,
            'demandee', $d['notes'] ?: null
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Liste des demandes laverie
     */
    public function getLaverieDemandes(array $residenceIds, ?string $statut = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $params = array_values($residenceIds);

        $sql = "SELECT d.*, CONCAT(rs.prenom, ' ', rs.nom) as resident_nom, l.numero_lot, c.nom as residence_nom,
                       u.prenom as employe_prenom, u.nom as employe_nom
                FROM menage_laverie_demandes d
                JOIN residents_seniors rs ON d.resident_id = rs.id
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON o.lot_id = l.id
                JOIN coproprietees c ON d.residence_id = c.id
                LEFT JOIN users u ON d.employe_id = u.id
                WHERE d.residence_id IN ($ph)";
        if ($statut) { $sql .= " AND d.statut = ?"; $params[] = $statut; }
        $sql .= " ORDER BY FIELD(d.statut,'demandee','en_cours','prete','livree','facturee','annulee'), d.date_demande DESC";

        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    /**
     * Changer le statut d'une demande laverie
     */
    public function updateLaverieStatut(int $id, string $statut, ?int $employeId = null): bool {
        $dateCol = match($statut) {
            'en_cours' => 'date_traitement',
            'prete' => 'date_prete',
            'livree' => 'date_livree',
            default => null
        };
        $sql = "UPDATE menage_laverie_demandes SET statut = ?";
        $params = [$statut];
        if ($dateCol) { $sql .= ", $dateCol = CURDATE()"; }
        if ($employeId) { $sql .= ", employe_id = ?"; $params[] = $employeId; }
        $sql .= " WHERE id = ?"; $params[] = $id;
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Stats laverie
     */
    public function getLaverieStats(array $residenceIds): array {
        if (empty($residenceIds)) return ['total' => 0, 'en_attente' => 0, 'en_cours' => 0, 'ca_mois' => 0];
        $ph = implode(',', array_fill(0, count($residenceIds), '?'));
        $ids = array_values($residenceIds);
        $moisDebut = date('Y-m-01');

        $sql = "SELECT COUNT(*) as total,
                   COUNT(CASE WHEN statut = 'demandee' THEN 1 END) as en_attente,
                   COUNT(CASE WHEN statut = 'en_cours' THEN 1 END) as en_cours,
                   COALESCE(SUM(CASE WHEN statut IN ('livree','facturee') AND service_inclus = 0 AND date_demande >= '$moisDebut' THEN montant_total END), 0) as ca_mois
                FROM menage_laverie_demandes WHERE residence_id IN ($ph)";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($ids); return $stmt->fetch(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return ['total'=>0,'en_attente'=>0,'en_cours'=>0,'ca_mois'=>0]; }
    }

    // ─────────────────────────────────────────────────────────────
    //  PRODUITS MÉNAGE (CRUD)
    // ─────────────────────────────────────────────────���───────────

    public function getAllProduits(?string $categorie = null, ?string $section = null, bool $actifsOnly = false): array {
        $sql = "SELECT p.*, f.nom as fournisseur_nom FROM menage_produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id WHERE 1=1";
        $params = [];
        if ($actifsOnly) $sql .= " AND p.actif = 1";
        if ($categorie) { $sql .= " AND p.categorie = ?"; $params[] = $categorie; }
        if ($section) { $sql .= " AND (p.section = ? OR p.section = 'commun')"; $params[] = $section; }
        $sql .= " ORDER BY p.section, p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getProduit(int $id): ?array {
        try { $stmt = $this->db->prepare("SELECT p.*, f.nom as fournisseur_nom FROM menage_produits p LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id WHERE p.id = ?"); $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function createProduit(array $d): int {
        $sql = "INSERT INTO menage_produits (nom, categorie, section, unite, prix_reference, fournisseur_id, marque, conditionnement, actif, notes) VALUES (?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([$d['nom'], $d['categorie'], $d['section'] ?? 'commun', $d['unite'], !empty($d['prix_reference']) ? (float)$d['prix_reference'] : null, !empty($d['fournisseur_id']) ? (int)$d['fournisseur_id'] : null, $d['marque'] ?: null, $d['conditionnement'] ?: null, isset($d['actif']) ? 1 : 0, $d['notes'] ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProduit(int $id, array $d): bool {
        $sql = "UPDATE menage_produits SET nom=?, categorie=?, section=?, unite=?, prix_reference=?, fournisseur_id=?, marque=?, conditionnement=?, actif=?, notes=?, updated_at=NOW() WHERE id=?";
        return $this->db->prepare($sql)->execute([$d['nom'], $d['categorie'], $d['section'] ?? 'commun', $d['unite'], !empty($d['prix_reference']) ? (float)$d['prix_reference'] : null, !empty($d['fournisseur_id']) ? (int)$d['fournisseur_id'] : null, $d['marque'] ?: null, $d['conditionnement'] ?: null, isset($d['actif']) ? 1 : 0, $d['notes'] ?: null, $id]);
    }

    public function deleteProduit(int $id): bool { return $this->db->prepare("UPDATE menage_produits SET actif=0 WHERE id=?")->execute([$id]); }

    public function getFournisseursList(): array {
        try { return $this->db->query("SELECT id, nom FROM fournisseurs WHERE actif=1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  INVENTAIRE MÉNAGE
    // ─────────────────────────────────────────────────────────────

    public function getInventaire(int $residenceId, ?string $section = null, bool $alertesOnly = false): array {
        $sql = "SELECT i.*, p.nom as produit_nom, p.categorie as produit_categorie, p.section, p.unite, p.marque, f.nom as fournisseur_nom
                FROM menage_inventaire i JOIN menage_produits p ON i.produit_id = p.id LEFT JOIN fournisseurs f ON p.fournisseur_id = f.id WHERE i.residence_id = ?";
        $params = [$residenceId];
        if ($section) { $sql .= " AND (p.section = ? OR p.section = 'commun')"; $params[] = $section; }
        if ($alertesOnly) $sql .= " AND i.quantite_stock <= i.seuil_alerte AND i.seuil_alerte > 0";
        $sql .= " ORDER BY p.section, p.categorie, p.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function addToInventaire(int $produitId, int $residenceId, float $seuil = 0, ?string $emplacement = null): int {
        $this->db->prepare("INSERT IGNORE INTO menage_inventaire (produit_id, residence_id, quantite_stock, seuil_alerte, emplacement) VALUES (?,?,0,?,?)")
            ->execute([$produitId, $residenceId, $seuil, $emplacement]);
        $stmt = $this->db->prepare("SELECT id FROM menage_inventaire WHERE produit_id=? AND residence_id=?");
        $stmt->execute([$produitId, $residenceId]);
        return (int)$stmt->fetchColumn();
    }

    public function mouvementStock(int $inventaireId, string $type, float $quantite, string $motif, ?int $commandeId = null, ?string $notes = null): bool {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("INSERT INTO menage_inventaire_mouvements (inventaire_id, type_mouvement, quantite, motif, commande_id, notes, user_id) VALUES (?,?,?,?,?,?,?)")
                ->execute([$inventaireId, $type, $quantite, $motif, $commandeId, $notes, $_SESSION['user_id'] ?? null]);
            $op = ($type === 'entree') ? '+' : (($type === 'sortie') ? '-' : '');
            if ($op) {
                $this->db->prepare("UPDATE menage_inventaire SET quantite_stock = GREATEST(0, quantite_stock $op ?), updated_at=NOW() WHERE id=?")->execute([$quantite, $inventaireId]);
            } else {
                $this->db->prepare("UPDATE menage_inventaire SET quantite_stock=?, updated_at=NOW() WHERE id=?")->execute([$quantite, $inventaireId]);
            }
            $this->db->commit(); return true;
        } catch (PDOException $e) { if ($this->db->inTransaction()) $this->db->rollBack(); $this->logError($e->getMessage()); return false; }
    }

    public function getMouvements(int $inventaireId, int $limit = 20): array {
        $sql = "SELECT m.*, u.prenom as user_prenom, u.nom as user_nom FROM menage_inventaire_mouvements m LEFT JOIN users u ON m.user_id=u.id WHERE m.inventaire_id=? ORDER BY m.created_at DESC LIMIT ?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$inventaireId, $limit]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    public function getProduitsHorsInventaire(int $residenceId): array {
        $sql = "SELECT id, nom, categorie, section, unite FROM menage_produits WHERE actif=1 AND id NOT IN (SELECT produit_id FROM menage_inventaire WHERE residence_id=?) ORDER BY section, nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    public function getInventaireItem(int $id): ?array {
        try { $stmt = $this->db->prepare("SELECT i.*, p.nom as produit_nom, p.unite, p.section FROM menage_inventaire i JOIN menage_produits p ON i.produit_id=p.id WHERE i.id=?"); $stmt->execute([$id]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { return null; }
    }

    // ─────────────────────────────────────────────────────────────
    //  COMMANDES FOURNISSEURS MÉNAGE
    // ─────────────────────────────────────────────────────────────

    public function generateNumeroCommande(): string {
        $a = date('Y'); $m = date('m');
        try { $c = $this->db->query("SELECT COUNT(*) FROM menage_commandes WHERE YEAR(date_commande)=$a")->fetchColumn(); } catch (PDOException $e) { $c = 0; }
        return 'MEN-' . $a . $m . '-' . str_pad($c + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getCommandes(array $residenceIds, ?string $statut = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?')); $params = array_values($residenceIds);
        $sql = "SELECT c.*, f.nom as fournisseur_nom, res.nom as residence_nom, (SELECT COUNT(*) FROM menage_commande_lignes WHERE commande_id=c.id) as nb_lignes
                FROM menage_commandes c JOIN fournisseurs f ON c.fournisseur_id=f.id JOIN coproprietees res ON c.residence_id=res.id WHERE c.residence_id IN ($ph)";
        if ($statut) { $sql .= " AND c.statut=?"; $params[] = $statut; }
        $sql .= " ORDER BY c.date_commande DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    public function getCommande(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT c.*, f.nom as fournisseur_nom, f.email as fournisseur_email, f.telephone as fournisseur_telephone, res.nom as residence_nom FROM menage_commandes c JOIN fournisseurs f ON c.fournisseur_id=f.id JOIN coproprietees res ON c.residence_id=res.id WHERE c.id=?");
            $stmt->execute([$id]); $cmd = $stmt->fetch(PDO::FETCH_ASSOC); if (!$cmd) return null;
            $stmtL = $this->db->prepare("SELECT cl.*, p.nom as produit_nom, p.unite FROM menage_commande_lignes cl JOIN menage_produits p ON cl.produit_id=p.id WHERE cl.commande_id=? ORDER BY cl.id");
            $stmtL->execute([$id]); $cmd['lignes'] = $stmtL->fetchAll(PDO::FETCH_ASSOC);
            return $cmd;
        } catch (PDOException $e) { return null; }
    }

    public function createCommande(array $data, array $lignes): int {
        $numero = $this->generateNumeroCommande();
        $totalHt = 0; $totalTva = 0;
        foreach ($lignes as $l) { $lht = ($l['quantite_commandee'] ?? 0) * ($l['prix_unitaire_ht'] ?? 0); $totalHt += $lht; $totalTva += $lht * (($l['taux_tva'] ?? 20) / 100); }
        $this->db->prepare("INSERT INTO menage_commandes (residence_id, fournisseur_id, numero_commande, date_commande, date_livraison_prevue, statut, montant_total_ht, montant_tva, montant_total_ttc, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$data['residence_id'], $data['fournisseur_id'], $numero, $data['date_commande'] ?? date('Y-m-d'), $data['date_livraison_prevue'] ?: null, $data['statut'] ?? 'brouillon', round($totalHt,2), round($totalTva,2), round($totalHt+$totalTva,2), $data['notes'] ?: null, $_SESSION['user_id'] ?? null]);
        $cmdId = (int)$this->db->lastInsertId();
        $stmtL = $this->db->prepare("INSERT INTO menage_commande_lignes (commande_id, produit_id, designation, quantite_commandee, prix_unitaire_ht, taux_tva) VALUES (?,?,?,?,?,?)");
        foreach ($lignes as $l) { if (!empty($l['produit_id']) && !empty($l['quantite_commandee'])) $stmtL->execute([$cmdId, (int)$l['produit_id'], $l['designation'] ?? '', (float)$l['quantite_commandee'], (float)($l['prix_unitaire_ht'] ?? 0), (float)($l['taux_tva'] ?? 20)]); }
        return $cmdId;
    }

    public function updateCommandeStatut(int $id, string $statut): bool {
        $sql = "UPDATE menage_commandes SET statut=?, updated_at=NOW()"; $params = [$statut];
        if ($statut === 'livree') $sql .= ", date_livraison_effective=CURDATE()";
        $sql .= " WHERE id=?"; $params[] = $id;
        return $this->db->prepare($sql)->execute($params);
    }

    public function receptionnerCommande(int $commandeId, array $quantitesRecues): bool {
        try {
            $this->db->beginTransaction();
            $cmd = $this->getCommande($commandeId); if (!$cmd) throw new Exception("Commande introuvable");
            $toutRecu = true;
            foreach ($cmd['lignes'] as $l) {
                $qte = (float)($quantitesRecues[$l['id']] ?? 0);
                $this->db->prepare("UPDATE menage_commande_lignes SET quantite_recue=? WHERE id=?")->execute([$qte, $l['id']]);
                if ($qte < $l['quantite_commandee']) $toutRecu = false;
                if ($qte > 0) { $invId = $this->addToInventaire($l['produit_id'], $cmd['residence_id']); $this->mouvementStock($invId, 'entree', $qte, 'livraison', $commandeId, "Commande $cmd[numero_commande]"); }
            }
            $this->updateCommandeStatut($commandeId, $toutRecu ? 'livree' : 'livree_partiel');
            $this->db->commit(); return true;
        } catch (Exception $e) { if ($this->db->inTransaction()) $this->db->rollBack(); $this->logError($e->getMessage()); return false; }
    }

    public function deleteCommande(int $id): bool { return $this->db->prepare("DELETE FROM menage_commandes WHERE id=? AND statut='brouillon'")->execute([$id]); }

    // ─────────────────────────────────────────────────────────────
    //  FOURNISSEURS MÉNAGE (via table partagée)
    // ─────────────────────────────────────────────────────────────

    public function getFournisseursResidence(int $residenceId): array {
        $sql = "SELECT f.*, fr.statut as lien_statut, fr.contact_local, fr.telephone_local, fr.jour_livraison,
                   (SELECT COUNT(*) FROM menage_commandes c WHERE c.fournisseur_id=f.id AND c.residence_id=?) as nb_commandes,
                   (SELECT COALESCE(SUM(c2.montant_total_ttc),0) FROM menage_commandes c2 WHERE c2.fournisseur_id=f.id AND c2.residence_id=? AND c2.statut!='annulee') as total_commandes
                FROM fournisseurs f
                JOIN rest_fournisseur_residence fr ON fr.fournisseur_id=f.id AND fr.residence_id=?
                WHERE fr.statut='actif' ORDER BY f.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residenceId, $residenceId, $residenceId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    // ─────────────────────────────────────────────────────────────
    //  COMPTABILITÉ MÉNAGE
    // ─────────────────────────────────────────────────────────────

    public function createEcriture(array $d): int {
        $sql = "INSERT INTO menage_comptabilite (residence_id, date_ecriture, type_ecriture, categorie, reference_id, reference_type, libelle, montant_ht, montant_tva, montant_ttc, compte_comptable, mois, annee, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $this->db->prepare($sql)->execute([$d['residence_id'], $d['date_ecriture'] ?? date('Y-m-d'), $d['type_ecriture'], $d['categorie'], $d['reference_id'] ?? null, $d['reference_type'] ?? null, $d['libelle'], (float)$d['montant_ht'], (float)($d['montant_tva'] ?? 0), (float)$d['montant_ttc'], $d['compte_comptable'] ?? null, (int)($d['mois'] ?? date('m')), (int)($d['annee'] ?? date('Y')), $d['notes'] ?? null]);
        return (int)$this->db->lastInsertId();
    }

    public function getEcritures(array $residenceIds, ?int $annee = null, ?int $mois = null, ?string $type = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?')); $params = array_values($residenceIds);
        $sql = "SELECT c.*, res.nom as residence_nom FROM menage_comptabilite c JOIN coproprietees res ON c.residence_id=res.id WHERE c.residence_id IN ($ph)";
        if ($annee) { $sql .= " AND c.annee=?"; $params[] = $annee; }
        if ($mois) { $sql .= " AND c.mois=?"; $params[] = $mois; }
        if ($type) { $sql .= " AND c.type_ecriture=?"; $params[] = $type; }
        $sql .= " ORDER BY c.date_ecriture DESC, c.id DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    public function getTotauxAnnuels(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return ['recettes_ht'=>0,'recettes_ttc'=>0,'depenses_ht'=>0,'depenses_ttc'=>0,'resultat_ht'=>0,'resultat_ttc'=>0];
        $ph = implode(',', array_fill(0, count($residenceIds), '?')); $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ht END),0) as recettes_ht, COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc END),0) as recettes_ttc, COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ht END),0) as depenses_ht, COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc END),0) as depenses_ttc FROM menage_comptabilite WHERE residence_id IN ($ph) AND annee=?";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); $r = $stmt->fetch(PDO::FETCH_ASSOC); $r['resultat_ht'] = $r['recettes_ht'] - $r['depenses_ht']; $r['resultat_ttc'] = $r['recettes_ttc'] - $r['depenses_ttc']; return $r; }
        catch (PDOException $e) { return ['recettes_ht'=>0,'recettes_ttc'=>0,'depenses_ht'=>0,'depenses_ttc'=>0,'resultat_ht'=>0,'resultat_ttc'=>0]; }
    }

    public function getSyntheseMensuelle(array $residenceIds, int $annee): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?')); $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT mois, SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END) as recettes_ttc, SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END) as depenses_ttc FROM menage_comptabilite WHERE residence_id IN ($ph) AND annee=? GROUP BY mois ORDER BY mois";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    public function getDepensesParFournisseur(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?')); $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT f.id as fournisseur_id, f.nom as fournisseur_nom, COUNT(c.id) as nb_commandes, COALESCE(SUM(c.montant_total_ht),0) as total_ht, COALESCE(SUM(c.montant_total_ttc),0) as total_ttc
                FROM menage_commandes c JOIN fournisseurs f ON c.fournisseur_id=f.id WHERE c.residence_id IN ($ph) AND YEAR(c.date_commande)=? AND c.statut!='annulee'";
        if ($mois) { $sql .= " AND MONTH(c.date_commande)=?"; $params[] = $mois; }
        $sql .= " GROUP BY f.id, f.nom ORDER BY total_ttc DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }

    public function getEcrituresExport(array $residenceIds, int $annee, ?int $mois = null): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_fill(0, count($residenceIds), '?')); $params = array_merge(array_values($residenceIds), [$annee]);
        $sql = "SELECT c.date_ecriture, c.compte_comptable, c.libelle, c.type_ecriture, c.montant_ht, c.montant_tva, c.montant_ttc, c.categorie, res.nom as residence_nom FROM menage_comptabilite c JOIN coproprietees res ON c.residence_id=res.id WHERE c.residence_id IN ($ph) AND c.annee=?";
        if ($mois) { $sql .= " AND c.mois=?"; $params[] = $mois; }
        $sql .= " ORDER BY c.date_ecriture, c.id";
        try { $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { return []; }
    }
}
