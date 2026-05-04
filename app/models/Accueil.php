<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Accueil
 * ====================================================================
 * Helpers communs au module Accueil :
 *   - résidences accessibles (filtre par user_residence)
 *   - résidents par résidence
 *   - notes texte libre sur résidents (CRUD)
 *   - stats dashboard (s'enrichit au fil des phases)
 */
class Accueil extends Model {

    /** Résidences accessibles au user connecté (admin = toutes, staff = via user_residence) */
    public function getResidencesAccessibles(int $userId, string $userRole): array {
        if ($userRole === 'admin') {
            $sql = "SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = "SELECT c.id, c.nom, c.ville
                FROM coproprietees c
                JOIN user_residence ur ON ur.residence_id = c.id AND ur.statut = 'actif'
                WHERE c.actif = 1 AND ur.user_id = ?
                ORDER BY c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResidenceIdsAccessibles(int $userId, string $userRole): array {
        return array_column($this->getResidencesAccessibles($userId, $userRole), 'id');
    }

    // ─── RÉSIDENTS (vue accueil) ────────────────────────────────

    /**
     * Résidents actuellement hébergés dans une résidence (occupations actives).
     */
    public function getResidentsParResidence(int $residenceId): array {
        $sql = "SELECT DISTINCT
                    rs.id, rs.civilite, rs.prenom, rs.nom,
                    rs.date_naissance, rs.age, rs.telephone_mobile, rs.email,
                    rs.niveau_autonomie, rs.regime_alimentaire,
                    rs.urgence_nom, rs.urgence_telephone, rs.urgence_lien,
                    GROUP_CONCAT(DISTINCT CONCAT(l.numero_lot, ' (', l.type, ')') ORDER BY l.numero_lot SEPARATOR ', ') AS lots,
                    rs.user_id,
                    (SELECT COUNT(*) FROM resident_notes_accueil WHERE resident_id = rs.id) AS nb_notes,
                    (SELECT MAX(created_at) FROM resident_notes_accueil WHERE resident_id = rs.id) AS derniere_note
                FROM residents_seniors rs
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON l.id = o.lot_id
                WHERE l.copropriete_id = ? AND rs.actif = 1
                GROUP BY rs.id
                ORDER BY rs.nom, rs.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie qu'un résident est bien dans une résidence accessible au user.
     */
    public function residentEstAccessible(int $residentId, array $residencesIds): bool {
        if (empty($residencesIds)) return false;
        $ph = implode(',', array_map('intval', $residencesIds));
        $sql = "SELECT 1 FROM residents_seniors rs
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON l.id = o.lot_id
                WHERE rs.id = ? AND l.copropriete_id IN ($ph)
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residentId]);
        return (bool)$stmt->fetchColumn();
    }

    public function findResident(int $id): ?array {
        $sql = "SELECT rs.*, u.username, u.email AS user_email
                FROM residents_seniors rs
                LEFT JOIN users u ON u.id = rs.user_id
                WHERE rs.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ─── NOTES TEXTE LIBRE ──────────────────────────────────────

    public function getNotes(int $residentId): array {
        $sql = "SELECT n.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom, u.role AS auteur_role
                FROM resident_notes_accueil n
                LEFT JOIN users u ON u.id = n.created_by
                WHERE n.resident_id = ?
                ORDER BY n.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findNote(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM resident_notes_accueil WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createNote(int $residentId, string $contenu, int $userId): int {
        $sql = "INSERT INTO resident_notes_accueil (resident_id, contenu, created_by) VALUES (?, ?, ?)";
        $this->db->prepare($sql)->execute([$residentId, $contenu, $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteNote(int $id): bool {
        return $this->db->prepare("DELETE FROM resident_notes_accueil WHERE id = ?")->execute([$id]);
    }

    // ─── SALLES COMMUNES ────────────────────────────────────────

    public const TYPES_EQUIPEMENT = ['mobilite', 'informatique', 'loisirs', 'medical', 'autre'];
    public const STATUTS_EQUIPEMENT = ['disponible', 'prete', 'hors_service', 'maintenance'];

    public function getSalles(int $residenceId, bool $actifsSeuls = false): array {
        $sql = "SELECT * FROM accueil_salles WHERE residence_id = ?"
             . ($actifsSeuls ? " AND actif = 1" : "")
             . " ORDER BY nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findSalle(int $id): ?array {
        $sql = "SELECT s.*, c.nom AS residence_nom FROM accueil_salles s
                JOIN coproprietees c ON c.id = s.residence_id WHERE s.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createSalle(array $data): int {
        $sql = "INSERT INTO accueil_salles (residence_id, nom, description, capacite_personnes, equipements_inclus, photo, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['residence_id'], $data['nom'], $data['description'] ?: null,
            !empty($data['capacite_personnes']) ? (int)$data['capacite_personnes'] : null,
            $data['equipements_inclus'] ?: null, $data['photo'] ?: null,
            !empty($data['actif']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateSalle(int $id, array $data): bool {
        $sql = "UPDATE accueil_salles SET nom = ?, description = ?, capacite_personnes = ?,
                equipements_inclus = ?, actif = ?";
        $params = [
            $data['nom'], $data['description'] ?: null,
            !empty($data['capacite_personnes']) ? (int)$data['capacite_personnes'] : null,
            $data['equipements_inclus'] ?: null,
            !empty($data['actif']) ? 1 : 0,
        ];
        if (array_key_exists('photo', $data) && $data['photo'] !== null) {
            $sql .= ", photo = ?";
            $params[] = $data['photo'];
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        return $this->db->prepare($sql)->execute($params);
    }

    public function deleteSalle(int $id): ?array {
        $row = $this->findSalle($id);
        if (!$row) return null;
        $this->db->prepare("DELETE FROM accueil_salles WHERE id = ?")->execute([$id]);
        return $row;
    }

    // ─── ÉQUIPEMENTS PRÊTABLES ──────────────────────────────────

    public function getEquipements(int $residenceId, bool $actifsSeuls = false): array {
        $sql = "SELECT * FROM accueil_equipements WHERE residence_id = ?"
             . ($actifsSeuls ? " AND actif = 1" : "")
             . " ORDER BY type, nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findEquipement(int $id): ?array {
        $sql = "SELECT e.*, c.nom AS residence_nom FROM accueil_equipements e
                JOIN coproprietees c ON c.id = e.residence_id WHERE e.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createEquipement(array $data): int {
        $sql = "INSERT INTO accueil_equipements (residence_id, nom, type, numero_serie, statut, notes, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['residence_id'], $data['nom'],
            $data['type'] ?? 'autre',
            $data['numero_serie'] ?: null,
            $data['statut'] ?? 'disponible',
            $data['notes'] ?: null,
            !empty($data['actif']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateEquipement(int $id, array $data): bool {
        $sql = "UPDATE accueil_equipements SET nom = ?, type = ?, numero_serie = ?, statut = ?, notes = ?, actif = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['nom'],
            $data['type'] ?? 'autre',
            $data['numero_serie'] ?: null,
            $data['statut'] ?? 'disponible',
            $data['notes'] ?: null,
            !empty($data['actif']) ? 1 : 0,
            $id,
        ]);
    }

    public function deleteEquipement(int $id): bool {
        return $this->db->prepare("DELETE FROM accueil_equipements WHERE id = ?")->execute([$id]);
    }

    // ─── RÉSERVATIONS ────────────────────────────────────────────

    public const TYPES_RESERVATION = ['salle', 'equipement', 'service_personnel'];
    public const TYPES_SERVICE_PERSONNEL = ['coiffeur', 'pedicure', 'manucure', 'esthetique', 'taxi', 'autre'];
    public const STATUTS_RESERVATION = ['en_attente', 'confirmee', 'refusee', 'annulee', 'realisee'];

    /**
     * Liste des réservations filtrées par résidence et critères optionnels.
     */
    public function getReservations(int $residenceId, array $filtres = []): array {
        $sql = "SELECT r.*,
                       s.nom AS salle_nom,
                       e.nom AS equipement_nom, e.type AS equipement_type,
                       rs.prenom AS resident_prenom, rs.nom AS resident_nom,
                       h.prenom AS hote_prenom, h.nom AS hote_nom,
                       u.prenom AS valide_par_prenom, u.nom AS valide_par_nom,
                       uc.prenom AS createur_prenom, uc.nom AS createur_nom
                FROM accueil_reservations r
                LEFT JOIN accueil_salles s        ON s.id = r.salle_id
                LEFT JOIN accueil_equipements e   ON e.id = r.equipement_id
                LEFT JOIN residents_seniors rs    ON rs.id = r.resident_id
                LEFT JOIN hotes_temporaires h     ON h.id = r.hote_id
                LEFT JOIN users u                 ON u.id = r.valide_par_id
                LEFT JOIN users uc                ON uc.id = r.created_by
                WHERE r.residence_id = ?";
        $params = [$residenceId];

        if (!empty($filtres['statut'])) {
            $sql .= " AND r.statut = ?";
            $params[] = $filtres['statut'];
        }
        if (!empty($filtres['type'])) {
            $sql .= " AND r.type_reservation = ?";
            $params[] = $filtres['type'];
        }
        if (!empty($filtres['date_min'])) {
            $sql .= " AND r.date_debut >= ?";
            $params[] = $filtres['date_min'];
        }
        $sql .= " ORDER BY r.date_debut DESC, r.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findReservation(int $id): ?array {
        $sql = "SELECT r.*,
                       s.nom AS salle_nom, s.capacite_personnes AS salle_capacite,
                       e.nom AS equipement_nom, e.type AS equipement_type,
                       rs.prenom AS resident_prenom, rs.nom AS resident_nom, rs.telephone_mobile AS resident_tel,
                       h.prenom AS hote_prenom, h.nom AS hote_nom, h.telephone AS hote_tel,
                       u.prenom AS valide_par_prenom, u.nom AS valide_par_nom,
                       uc.prenom AS createur_prenom, uc.nom AS createur_nom,
                       c.nom AS residence_nom
                FROM accueil_reservations r
                LEFT JOIN accueil_salles s        ON s.id = r.salle_id
                LEFT JOIN accueil_equipements e   ON e.id = r.equipement_id
                LEFT JOIN residents_seniors rs    ON rs.id = r.resident_id
                LEFT JOIN hotes_temporaires h     ON h.id = r.hote_id
                LEFT JOIN users u                 ON u.id = r.valide_par_id
                LEFT JOIN users uc                ON uc.id = r.created_by
                LEFT JOIN coproprietees c         ON c.id = r.residence_id
                WHERE r.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Vérifie qu'une cible (salle/équipement) est libre sur la période demandée.
     * Ignore la réservation $excludeId (utile lors d'une édition).
     * Réservations à comparer : statuts 'en_attente' + 'confirmee'.
     */
    public function checkChevauchement(string $type, int $cibleId, string $debut, string $fin, ?int $excludeId = null): bool {
        $champ = $type === 'salle' ? 'salle_id' : 'equipement_id';
        $sql = "SELECT 1 FROM accueil_reservations
                WHERE $champ = ?
                  AND statut IN ('en_attente','confirmee')
                  AND date_debut < ? AND date_fin > ?";
        $params = [$cibleId, $fin, $debut];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public function createReservation(array $data): int {
        $sql = "INSERT INTO accueil_reservations
                  (residence_id, type_reservation, salle_id, equipement_id, type_service,
                   resident_id, hote_id, titre, description, date_debut, date_fin,
                   statut, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente', ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['residence_id'],
            $data['type_reservation'],
            !empty($data['salle_id'])      ? (int)$data['salle_id']      : null,
            !empty($data['equipement_id']) ? (int)$data['equipement_id'] : null,
            !empty($data['type_service'])  ? $data['type_service']       : null,
            !empty($data['resident_id'])   ? (int)$data['resident_id']   : null,
            !empty($data['hote_id'])       ? (int)$data['hote_id']       : null,
            $data['titre'], $data['description'] ?: null,
            $data['date_debut'], $data['date_fin'],
            $data['notes'] ?: null,
            (int)$data['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateReservation(int $id, array $data): bool {
        $sql = "UPDATE accueil_reservations
                   SET titre = ?, description = ?, date_debut = ?, date_fin = ?, notes = ?,
                       salle_id = ?, equipement_id = ?, type_service = ?,
                       resident_id = ?, hote_id = ?
                 WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['titre'], $data['description'] ?: null,
            $data['date_debut'], $data['date_fin'], $data['notes'] ?: null,
            !empty($data['salle_id'])      ? (int)$data['salle_id']      : null,
            !empty($data['equipement_id']) ? (int)$data['equipement_id'] : null,
            !empty($data['type_service'])  ? $data['type_service']       : null,
            !empty($data['resident_id'])   ? (int)$data['resident_id']   : null,
            !empty($data['hote_id'])       ? (int)$data['hote_id']       : null,
            $id,
        ]);
    }

    public function validerReservation(int $id, int $userId): bool {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE accueil_reservations
                                SET statut = 'confirmee', valide_par_id = ?, valide_le = NOW(), motif_refus = NULL
                                WHERE id = ?")
                     ->execute([$userId, $id]);

            // Si équipement : passer le statut de l'équipement à 'prete'
            $r = $this->findReservation($id);
            if ($r && $r['type_reservation'] === 'equipement' && $r['equipement_id']) {
                $this->db->prepare("UPDATE accueil_equipements SET statut = 'prete' WHERE id = ?")
                         ->execute([(int)$r['equipement_id']]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function refuserReservation(int $id, int $userId, string $motif): bool {
        return $this->db->prepare("UPDATE accueil_reservations
                                    SET statut = 'refusee', valide_par_id = ?, valide_le = NOW(), motif_refus = ?
                                    WHERE id = ?")
                        ->execute([$userId, $motif, $id]);
    }

    public function annulerReservation(int $id): bool {
        $r = $this->findReservation($id);
        if ($r && $r['type_reservation'] === 'equipement' && $r['equipement_id'] && $r['statut'] === 'confirmee') {
            $this->db->prepare("UPDATE accueil_equipements SET statut = 'disponible' WHERE id = ?")
                     ->execute([(int)$r['equipement_id']]);
        }
        return $this->db->prepare("UPDATE accueil_reservations SET statut = 'annulee' WHERE id = ?")
                        ->execute([$id]);
    }

    public function realiserReservation(int $id): bool {
        $r = $this->findReservation($id);
        if ($r && $r['type_reservation'] === 'equipement' && $r['equipement_id']) {
            $this->db->prepare("UPDATE accueil_equipements SET statut = 'disponible' WHERE id = ?")
                     ->execute([(int)$r['equipement_id']]);
        }
        return $this->db->prepare("UPDATE accueil_reservations SET statut = 'realisee' WHERE id = ?")
                        ->execute([$id]);
    }

    public function deleteReservation(int $id): bool {
        return $this->db->prepare("DELETE FROM accueil_reservations WHERE id = ?")->execute([$id]);
    }

    /** Compte les réservations en attente pour le badge dashboard */
    public function countReservationsEnAttente(array $residencesIds): int {
        if (empty($residencesIds)) return 0;
        $ph = implode(',', array_map('intval', $residencesIds));
        return (int)$this->db->query("SELECT COUNT(*) FROM accueil_reservations
                                       WHERE residence_id IN ($ph) AND statut = 'en_attente'")
                              ->fetchColumn();
    }

    // ─── ANIMATIONS (planning_shifts catégorie 14) ──────────────

    public const ANIMATION_CATEGORY_ID = 14;
    public const STATUTS_INSCRIPTION = ['inscrit', 'present', 'absent', 'annule'];

    /**
     * Animations d'une résidence (shifts catégorie animation), filtrables par période.
     */
    public function getAnimations(int $residenceId, ?string $dateMin = null, ?string $dateMax = null): array {
        $sql = "SELECT s.*,
                       u.prenom AS animateur_prenom, u.nom AS animateur_nom, u.role AS animateur_role,
                       (SELECT COUNT(*) FROM accueil_animation_inscriptions ai WHERE ai.shift_id = s.id AND ai.statut != 'annule') AS nb_inscrits
                FROM planning_shifts s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.residence_id = ? AND s.category_id = ?";
        $params = [$residenceId, self::ANIMATION_CATEGORY_ID];

        if ($dateMin) { $sql .= " AND s.date_fin >= ?";   $params[] = $dateMin; }
        if ($dateMax) { $sql .= " AND s.date_debut <= ?"; $params[] = $dateMax; }
        $sql .= " ORDER BY s.date_debut DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAnimation(int $shiftId): ?array {
        $sql = "SELECT s.*,
                       u.prenom AS animateur_prenom, u.nom AS animateur_nom, u.role AS animateur_role, u.email AS animateur_email,
                       c.nom AS residence_nom
                FROM planning_shifts s
                LEFT JOIN users u ON u.id = s.user_id
                LEFT JOIN coproprietees c ON c.id = s.residence_id
                WHERE s.id = ? AND s.category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$shiftId, self::ANIMATION_CATEGORY_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createAnimation(array $data): int {
        $sql = "INSERT INTO planning_shifts
                  (user_id, residence_id, category_id, titre, description,
                   date_debut, date_fin, type_shift, statut, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'travail', 'planifie', ?)";
        $this->db->prepare($sql)->execute([
            !empty($data['user_id']) ? (int)$data['user_id'] : null,
            (int)$data['residence_id'],
            self::ANIMATION_CATEGORY_ID,
            $data['titre'],
            $data['description'] ?: null,
            $data['date_debut'], $data['date_fin'],
            $data['notes'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateAnimation(int $shiftId, array $data): bool {
        $sql = "UPDATE planning_shifts
                   SET user_id = ?, titre = ?, description = ?, date_debut = ?, date_fin = ?, notes = ?
                 WHERE id = ? AND category_id = ?";
        return $this->db->prepare($sql)->execute([
            !empty($data['user_id']) ? (int)$data['user_id'] : null,
            $data['titre'], $data['description'] ?: null,
            $data['date_debut'], $data['date_fin'], $data['notes'] ?: null,
            $shiftId, self::ANIMATION_CATEGORY_ID,
        ]);
    }

    public function deleteAnimation(int $shiftId): bool {
        return $this->db->prepare("DELETE FROM planning_shifts WHERE id = ? AND category_id = ?")
                        ->execute([$shiftId, self::ANIMATION_CATEGORY_ID]);
    }

    /** Animateurs candidats : staff de la résidence (rôles staff résidence + accueil + admin) */
    public function getAnimateursCandidats(int $residenceId): array {
        $sql = "SELECT DISTINCT u.id, u.prenom, u.nom, u.role
                FROM users u
                LEFT JOIN user_residence ur ON ur.user_id = u.id AND ur.residence_id = ? AND ur.statut = 'actif'
                WHERE u.actif = 1
                  AND (u.role = 'admin' OR ur.id IS NOT NULL)
                ORDER BY u.nom, u.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── INSCRIPTIONS ANIMATIONS ───────────────────────────────

    public function getInscriptions(int $shiftId): array {
        $sql = "SELECT ai.*,
                       rs.civilite, rs.prenom AS resident_prenom, rs.nom AS resident_nom,
                       rs.niveau_autonomie, rs.allergies, rs.regime_alimentaire,
                       u.prenom AS inscrit_par_prenom, u.nom AS inscrit_par_nom
                FROM accueil_animation_inscriptions ai
                JOIN residents_seniors rs ON rs.id = ai.resident_id
                LEFT JOIN users u ON u.id = ai.inscrit_par_id
                WHERE ai.shift_id = ?
                ORDER BY ai.statut, rs.nom, rs.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$shiftId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findInscription(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM accueil_animation_inscriptions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function isResidentInscrit(int $shiftId, int $residentId): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM accueil_animation_inscriptions
                                     WHERE shift_id = ? AND resident_id = ? AND statut != 'annule' LIMIT 1");
        $stmt->execute([$shiftId, $residentId]);
        return (bool)$stmt->fetchColumn();
    }

    public function inscrire(int $shiftId, int $residentId, int $userId, ?string $notes = null): int {
        // Si une inscription annulée existe déjà → la réactiver
        $stmt = $this->db->prepare("SELECT id FROM accueil_animation_inscriptions
                                     WHERE shift_id = ? AND resident_id = ? LIMIT 1");
        $stmt->execute([$shiftId, $residentId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $this->db->prepare("UPDATE accueil_animation_inscriptions
                                SET statut = 'inscrit', inscrit_par_id = ?, inscrit_le = NOW(), notes = ?
                                WHERE id = ?")
                     ->execute([$userId, $notes, $existing]);
            return (int)$existing;
        }

        $sql = "INSERT INTO accueil_animation_inscriptions (shift_id, resident_id, statut, inscrit_par_id, notes)
                VALUES (?, ?, 'inscrit', ?, ?)";
        $this->db->prepare($sql)->execute([$shiftId, $residentId, $userId, $notes]);
        return (int)$this->db->lastInsertId();
    }

    public function setStatutInscription(int $id, string $statut): bool {
        if (!in_array($statut, self::STATUTS_INSCRIPTION, true)) return false;
        return $this->db->prepare("UPDATE accueil_animation_inscriptions SET statut = ? WHERE id = ?")
                        ->execute([$statut, $id]);
    }

    public function deleteInscription(int $id): bool {
        return $this->db->prepare("DELETE FROM accueil_animation_inscriptions WHERE id = ?")->execute([$id]);
    }

    /** Compte les animations à venir dans les 7 jours pour le dashboard */
    public function countAnimationsAVenir(array $residencesIds, int $jours = 7): int {
        if (empty($residencesIds)) return 0;
        $ph = implode(',', array_map('intval', $residencesIds));
        return (int)$this->db->query("
            SELECT COUNT(*) FROM planning_shifts
            WHERE residence_id IN ($ph) AND category_id = " . self::ANIMATION_CATEGORY_ID . "
              AND date_debut >= NOW() AND date_debut <= DATE_ADD(NOW(), INTERVAL $jours DAY)
        ")->fetchColumn();
    }

    // ─── DASHBOARD STATS ────────────────────────────────────────

    /**
     * Stats globales pour le dashboard accueil. S'enrichira aux phases suivantes.
     */
    public function getDashboardStats(array $residencesIds): array {
        if (empty($residencesIds)) {
            return ['nb_residents' => 0, 'nb_hotes_presents' => 0, 'nb_notes_recentes' => 0, 'nb_reservations_attente' => 0, 'nb_animations_semaine' => 0];
        }
        $ph = implode(',', array_map('intval', $residencesIds));

        // Résidents hébergés actuellement
        $nbResidents = (int)$this->db->query("
            SELECT COUNT(DISTINCT rs.id)
            FROM residents_seniors rs
            JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
            JOIN lots l ON l.id = o.lot_id
            WHERE l.copropriete_id IN ($ph) AND rs.actif = 1
        ")->fetchColumn();

        // Hôtes temporaires présents (entre date_arrivee et date_depart_prevue, statut en_cours)
        $nbHotes = (int)$this->db->query("
            SELECT COUNT(*) FROM hotes_temporaires
            WHERE residence_id IN ($ph)
              AND date_arrivee <= CURDATE() AND date_depart_prevue >= CURDATE()
              AND statut = 'en_cours'
        ")->fetchColumn();

        // Notes créées dans les 7 derniers jours
        $nbNotes = (int)$this->db->query("
            SELECT COUNT(*) FROM resident_notes_accueil n
            JOIN residents_seniors rs ON rs.id = n.resident_id
            JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
            JOIN lots l ON l.id = o.lot_id
            WHERE l.copropriete_id IN ($ph)
              AND n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetchColumn();

        // Réservations en attente de validation
        $nbReservations = (int)$this->db->query("
            SELECT COUNT(*) FROM accueil_reservations
            WHERE residence_id IN ($ph) AND statut = 'en_attente'
        ")->fetchColumn();

        // Animations à venir dans les 7 prochains jours
        $nbAnimations = (int)$this->db->query("
            SELECT COUNT(*) FROM planning_shifts
            WHERE residence_id IN ($ph) AND category_id = " . self::ANIMATION_CATEGORY_ID . "
              AND date_debut >= NOW() AND date_debut <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        ")->fetchColumn();

        return [
            'nb_residents' => $nbResidents,
            'nb_hotes_presents' => $nbHotes,
            'nb_notes_recentes' => $nbNotes,
            'nb_reservations_attente' => $nbReservations,
            'nb_animations_semaine' => $nbAnimations,
        ];
    }

    // ─── PLANNING (TUI Calendar — double-vue) ───────────────────

    public const CAL_ANIMATION   = 'animation';
    public const CAL_RES_SALLE   = 'res_salle';
    public const CAL_RES_EQUIP   = 'res_equipement';
    public const CAL_RES_SERVICE = 'res_service';
    public const CAL_STAFF       = 'staff';
    public const CAL_HOTE        = 'hote';

    /** Vue résidents : animations + réservations confirmées */
    public function getCalendarEventsResidents(int $residenceId, string $debut, string $fin): array {
        $events = [];

        // Animations (catégorie 14)
        $sql = "SELECT s.id, s.titre, s.description, s.date_debut, s.date_fin,
                       u.prenom AS animateur_prenom, u.nom AS animateur_nom,
                       (SELECT COUNT(*) FROM accueil_animation_inscriptions ai WHERE ai.shift_id = s.id AND ai.statut != 'annule') AS nb_inscrits
                FROM planning_shifts s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.residence_id = ? AND s.category_id = ?
                  AND s.date_debut <= ? AND s.date_fin >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId, self::ANIMATION_CATEGORY_ID, $fin, $debut]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $animateur = $row['animateur_prenom'] ? trim($row['animateur_prenom'] . ' ' . $row['animateur_nom']) : '— non assigné —';
            $events[] = [
                'id'       => 'anim_' . $row['id'],
                'rawId'    => (int)$row['id'],
                'calendarId' => self::CAL_ANIMATION,
                'category' => 'time',
                'title'    => '🎵 ' . $row['titre'] . ' (' . (int)$row['nb_inscrits'] . ')',
                'body'     => 'Animateur : ' . $animateur,
                'start'    => $row['date_debut'],
                'end'      => $row['date_fin'],
                'isReadOnly' => false,
                'raw'      => ['type' => 'animation', 'nb_inscrits' => (int)$row['nb_inscrits']],
            ];
        }

        // Réservations confirmées (3 types)
        $sql = "SELECT r.id, r.titre, r.type_reservation, r.salle_id, r.equipement_id, r.type_service,
                       r.date_debut, r.date_fin,
                       s.nom AS salle_nom, e.nom AS equipement_nom,
                       rs.prenom AS res_prenom, rs.nom AS res_nom,
                       h.prenom AS hote_prenom, h.nom AS hote_nom
                FROM accueil_reservations r
                LEFT JOIN accueil_salles s        ON s.id = r.salle_id
                LEFT JOIN accueil_equipements e   ON e.id = r.equipement_id
                LEFT JOIN residents_seniors rs    ON rs.id = r.resident_id
                LEFT JOIN hotes_temporaires h     ON h.id = r.hote_id
                WHERE r.residence_id = ?
                  AND r.statut = 'confirmee'
                  AND r.date_debut <= ? AND r.date_fin >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId, $fin, $debut]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $iconMap = ['salle' => '🏠', 'equipement' => '🛠️', 'service_personnel' => '👤'];
            $calMap  = ['salle' => self::CAL_RES_SALLE, 'equipement' => self::CAL_RES_EQUIP, 'service_personnel' => self::CAL_RES_SERVICE];
            $cible = '';
            if ($row['type_reservation'] === 'salle')      $cible = $row['salle_nom'] ?? '';
            elseif ($row['type_reservation'] === 'equipement') $cible = $row['equipement_nom'] ?? '';
            else $cible = ucfirst($row['type_service'] ?? '');

            $demandeur = $row['res_prenom']
                ? trim($row['res_prenom'] . ' ' . $row['res_nom'])
                : ($row['hote_prenom'] ? trim($row['hote_prenom'] . ' ' . $row['hote_nom']) . ' (hôte)' : '');

            $events[] = [
                'id'       => 'res_' . $row['id'],
                'rawId'    => (int)$row['id'],
                'calendarId' => $calMap[$row['type_reservation']],
                'category' => 'time',
                'title'    => $iconMap[$row['type_reservation']] . ' ' . $row['titre'] . ($cible ? ' — ' . $cible : ''),
                'body'     => 'Pour ' . $demandeur,
                'start'    => $row['date_debut'],
                'end'      => $row['date_fin'],
                'isReadOnly' => true,
                'raw'      => ['type' => 'reservation', 'sous_type' => $row['type_reservation']],
            ];
        }

        return $events;
    }

    /** Vue staff : shifts staff de la résidence (toutes catégories sauf animation pour éviter doublon) */
    public function getCalendarEventsStaff(int $residenceId, string $debut, string $fin): array {
        $sql = "SELECT s.id, s.titre, s.description, s.date_debut, s.date_fin, s.statut, s.type_shift,
                       u.prenom, u.nom, u.role,
                       c.nom AS cat_nom, c.couleur AS cat_couleur, c.icone AS cat_icone
                FROM planning_shifts s
                LEFT JOIN users u ON u.id = s.user_id
                LEFT JOIN planning_categories c ON c.id = s.category_id
                WHERE s.residence_id = ? AND s.category_id != ?
                  AND s.date_debut <= ? AND s.date_fin >= ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId, self::ANIMATION_CATEGORY_ID, $fin, $debut]);

        $events = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $employe = $row['prenom'] ? trim($row['prenom'] . ' ' . $row['nom']) : '— non assigné —';
            $events[] = [
                'id'       => 'staff_' . $row['id'],
                'rawId'    => (int)$row['id'],
                'calendarId' => self::CAL_STAFF,
                'category' => 'time',
                'title'    => '👷 ' . ($row['titre'] ?: ($row['cat_nom'] ?? '')) . ' — ' . $employe,
                'body'     => trim(($row['cat_nom'] ?? '') . ' · ' . ($row['statut'] ?? '')),
                'start'    => $row['date_debut'],
                'end'      => $row['date_fin'],
                'isReadOnly' => true,
                'raw'      => ['type' => 'staff_shift', 'role' => $row['role'] ?? null],
            ];
        }
        return $events;
    }

    /** Hôtes temporaires présents sur la période (pour calendrier ou bandeau) */
    public function getCalendarEventsHotes(int $residenceId, string $debut, string $fin): array {
        $sql = "SELECT id, prenom, nom, lot_id, date_arrivee, date_depart_prevue, statut
                FROM hotes_temporaires
                WHERE residence_id = ?
                  AND date_arrivee <= ? AND date_depart_prevue >= ?
                  AND statut IN ('reserve','en_cours')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId, $fin, $debut]);
        $events = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $events[] = [
                'id'       => 'hote_' . $row['id'],
                'rawId'    => (int)$row['id'],
                'calendarId' => self::CAL_HOTE,
                'category' => 'allday',
                'title'    => '🧳 ' . trim($row['prenom'] . ' ' . $row['nom']) . ' (' . $row['statut'] . ')',
                'start'    => $row['date_arrivee'],
                'end'      => $row['date_depart_prevue'],
                'isReadOnly' => true,
                'raw'      => ['type' => 'hote'],
            ];
        }
        return $events;
    }

    /** Drag & drop d'une animation : déplacer date_debut + date_fin */
    public function moveAnimation(int $shiftId, string $debut, string $fin): bool {
        return $this->db->prepare("UPDATE planning_shifts
                                    SET date_debut = ?, date_fin = ?
                                    WHERE id = ? AND category_id = ?")
                        ->execute([$debut, $fin, $shiftId, self::ANIMATION_CATEGORY_ID]);
    }

    // ─── HELPERS RÉSERVATIONS (cibles disponibles) ──────────────

    // ─── ÉQUIPE & MESSAGERIE GROUPÉE ────────────────────────────

    /** Membres de l'équipe Accueil affectés à une résidence (incl. direction) */
    public function getEquipeAccueil(int $residenceId): array {
        $sql = "SELECT u.id, u.username, u.prenom, u.nom, u.email, u.telephone, u.role, u.actif, u.photo_profil
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.residence_id = ? AND ur.statut = 'actif'
                WHERE u.role IN ('accueil_manager','accueil_employe','directeur_residence')
                  AND u.actif = 1
                ORDER BY FIELD(u.role,'directeur_residence','accueil_manager','accueil_employe'), u.nom, u.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Tous les destinataires possibles pour un message groupé : résidents + staff + propriétaires */
    public function getDestinatairesPossibles(int $residenceId): array {
        $out = ['residents' => [], 'staff' => [], 'proprietaires' => []];

        // Résidents (locataire_permanent) avec un compte user
        $sql = "SELECT DISTINCT u.id, u.prenom, u.nom, u.email
                FROM residents_seniors rs
                JOIN occupations_residents o ON o.resident_id = rs.id AND o.statut = 'actif'
                JOIN lots l ON l.id = o.lot_id
                JOIN users u ON u.id = rs.user_id
                WHERE l.copropriete_id = ? AND u.actif = 1
                ORDER BY u.nom, u.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        $out['residents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Staff de la résidence
        $sql = "SELECT u.id, u.prenom, u.nom, u.role, u.email
                FROM users u
                JOIN user_residence ur ON ur.user_id = u.id AND ur.residence_id = ? AND ur.statut = 'actif'
                WHERE u.actif = 1
                ORDER BY u.role, u.nom, u.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        $out['staff'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Propriétaires des lots de la résidence
        $sql = "SELECT DISTINCT u.id, u.prenom, u.nom, u.email
                FROM coproprietaires cop
                JOIN users u ON u.id = cop.user_id
                JOIN contrats_gestion cg ON cg.coproprietaire_id = cop.id AND cg.statut = 'actif'
                JOIN lots l ON l.id = cg.lot_id
                WHERE l.copropriete_id = ? AND u.actif = 1
                ORDER BY u.nom, u.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        $out['proprietaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $out;
    }

    /** Hôtes temporaires actuellement présents dans la résidence (pour formulaire réservation) */
    public function getHotesActuels(int $residenceId): array {
        $sql = "SELECT id, prenom, nom, lot_id
                FROM hotes_temporaires
                WHERE residence_id = ?
                  AND date_arrivee <= CURDATE() AND date_depart_prevue >= CURDATE()
                  AND statut = 'en_cours'
                ORDER BY nom, prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
