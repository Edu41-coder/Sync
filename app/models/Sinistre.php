<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Sinistre
 * ====================================================================
 * MVP : déclaration / suivi des sinistres + GED documents + audit trail.
 *
 * Règles d'accès (filtrage strict) :
 *   - admin               : tout
 *   - directeur_residence : sinistres de leurs résidences (user_residence)
 *   - exploitant          : sinistres de leurs résidences (exploitant_residences)
 *   - employe_residence   : sinistres de leur résidence d'affectation
 *   - technicien*         : sinistres de leur résidence d'affectation
 *   - locataire_permanent : sinistres sur leurs lots OU déclarés par eux
 *   - proprietaire        : sinistres sur leurs lots (contrats_gestion actif)
 *
 * Règles de modification :
 *   - Un sinistre n'est modifiable que tant que `statut = 'declare'`
 *   - Seuls admin / directeur_residence / exploitant peuvent modifier
 *   - Le résident ne peut PAS modifier sa propre déclaration une fois créée
 */

class Sinistre extends Model {

    protected $table = 'sinistres';

    public const ROLES_MANAGER     = ['admin', 'directeur_residence', 'exploitant'];
    public const ROLES_STAFF_DECLARANT = ['admin', 'directeur_residence', 'exploitant', 'employe_residence', 'technicien_chef', 'technicien'];
    public const STATUTS_FIGES     = ['transmis_assureur', 'expertise_en_cours', 'en_reparation', 'indemnise', 'clos', 'refuse'];

    // ─────────────────────────────────────────────────────────────
    //  ACCÈS / OWNERSHIP
    // ─────────────────────────────────────────────────────────────

    /**
     * Renvoie l'ensemble des résidences accessibles à l'utilisateur,
     * selon son rôle. Retourne [] si aucune.
     *
     * @return int[] residence_ids
     */
    public function getResidenceIdsAccessibles(int $userId, string $role): array {
        if ($role === 'admin') {
            $sql = "SELECT id FROM coproprietees WHERE actif = 1 AND type_residence = 'residence_seniors'";
            return array_map('intval', $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        if (in_array($role, ['directeur_residence', 'employe_residence', 'technicien_chef', 'technicien'], true)) {
            $sql = "SELECT ur.residence_id
                    FROM user_residence ur
                    JOIN coproprietees c ON c.id = ur.residence_id
                    WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        if ($role === 'exploitant') {
            $sql = "SELECT er.residence_id
                    FROM exploitants e
                    JOIN exploitant_residences er ON er.exploitant_id = e.id
                    JOIN coproprietees c ON c.id = er.residence_id
                    WHERE e.user_id = ? AND er.statut = 'actif' AND c.actif = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        if ($role === 'locataire_permanent') {
            $sql = "SELECT DISTINCT l.copropriete_id
                    FROM residents_seniors rs
                    JOIN occupations_residents o ON o.resident_id = rs.id
                    JOIN lots l ON l.id = o.lot_id
                    WHERE rs.user_id = ? AND o.statut = 'actif'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        if ($role === 'proprietaire') {
            $sql = "SELECT DISTINCT l.copropriete_id
                    FROM coproprietaires cp
                    JOIN contrats_gestion cg ON cg.coproprietaire_id = cp.id
                    JOIN lots l ON l.id = cg.lot_id
                    WHERE cp.user_id = ? AND cg.statut = 'actif'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }

        return [];
    }

    /**
     * Renvoie les lots accessibles pour création/visualisation.
     * Pour résident : ses lots occupés. Pour propriétaire : ses lots possédés.
     *
     * @return int[] lot_ids
     */
    public function getLotIdsAccessibles(int $userId, string $role): array {
        if ($role === 'locataire_permanent') {
            $sql = "SELECT o.lot_id
                    FROM residents_seniors rs
                    JOIN occupations_residents o ON o.resident_id = rs.id
                    WHERE rs.user_id = ? AND o.statut = 'actif'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }
        if ($role === 'proprietaire') {
            $sql = "SELECT cg.lot_id
                    FROM coproprietaires cp
                    JOIN contrats_gestion cg ON cg.coproprietaire_id = cp.id
                    WHERE cp.user_id = ? AND cg.statut = 'actif'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        }
        return [];
    }

    /**
     * Vérifie si l'utilisateur a le droit d'accéder à un sinistre.
     */
    public function userCanAccess(int $sinistreId, int $userId, string $role): bool {
        $sinistre = $this->find($sinistreId);
        if (!$sinistre) return false;

        if ($role === 'admin') return true;

        $residenceIds = $this->getResidenceIdsAccessibles($userId, $role);
        if (!in_array((int)$sinistre->residence_id, $residenceIds, true)) return false;

        // Restriction supplémentaire pour résident et propriétaire : limiter aux LOTS leur appartenant
        if (in_array($role, ['locataire_permanent', 'proprietaire'], true)) {
            $lotIds = $this->getLotIdsAccessibles($userId, $role);
            // Si le sinistre est sur un lot, le lot doit être dans la liste accessible
            if ($sinistre->lot_id !== null) {
                if (!in_array((int)$sinistre->lot_id, $lotIds, true)) {
                    // Exception : un résident peut voir les sinistres qu'il a déclarés lui-même
                    // (cas où il déclare puis déménage avant la clôture)
                    if ($role === 'locataire_permanent' && (int)$sinistre->declarant_user_id === $userId) {
                        return true;
                    }
                    return false;
                }
            } else {
                // Sinistre sur partie commune : visibilité limitée au manager.
                // Résident et propriétaire ne voient pas les parties communes en MVP.
                return false;
            }
        }

        return true;
    }

    /**
     * Un sinistre est modifiable uniquement tant que statut = 'declare',
     * et uniquement par les rôles MANAGER.
     */
    public function userCanEdit(int $sinistreId, string $role): bool {
        if (!in_array($role, self::ROLES_MANAGER, true)) return false;
        $s = $this->find($sinistreId);
        return $s && $s->statut === 'declare';
    }

    // ─────────────────────────────────────────────────────────────
    //  LISTING (avec filtrage rôle)
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste filtrée selon rôle + critères optionnels.
     *
     * @param array $filters [statut?, type?, residence_id?, gravite?, search?]
     * @return array
     */
    public function getList(int $userId, string $role, array $filters = []): array {
        $residenceIds = $this->getResidenceIdsAccessibles($userId, $role);
        if (empty($residenceIds)) return [];

        $where  = ['s.residence_id IN (' . implode(',', $residenceIds) . ')'];
        $params = [];

        // Restriction par lot pour résident/propriétaire
        if (in_array($role, ['locataire_permanent', 'proprietaire'], true)) {
            $lotIds = $this->getLotIdsAccessibles($userId, $role);
            if (empty($lotIds)) {
                // Cas locataire sans lot actif : ne montrer que les sinistres qu'il a déclarés
                if ($role === 'locataire_permanent') {
                    $where = ['s.declarant_user_id = ?'];
                    $params = [$userId];
                } else {
                    return [];
                }
            } else {
                $lotList = implode(',', array_map('intval', $lotIds));
                if ($role === 'locataire_permanent') {
                    $where[] = "(s.lot_id IN ($lotList) OR s.declarant_user_id = ?)";
                    $params[] = $userId;
                } else { // proprietaire
                    $where[] = "s.lot_id IN ($lotList)";
                }
            }
        }

        if (!empty($filters['statut']))       { $where[] = 's.statut = ?';        $params[] = $filters['statut']; }
        if (!empty($filters['type']))         { $where[] = 's.type_sinistre = ?'; $params[] = $filters['type']; }
        if (!empty($filters['gravite']))      { $where[] = 's.gravite = ?';       $params[] = $filters['gravite']; }
        if (!empty($filters['residence_id'])) { $where[] = 's.residence_id = ?';  $params[] = (int)$filters['residence_id']; }
        if (!empty($filters['search'])) {
            $where[]  = '(s.titre LIKE ? OR s.description LIKE ? OR s.numero_dossier_sinistre LIKE ?)';
            $like     = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql = "SELECT s.*,
                       c.nom AS residence_nom,
                       c.ville AS residence_ville,
                       l.numero_lot,
                       l.type AS lot_type,
                       CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS declarant_nom,
                       u.username AS declarant_username,
                       (SELECT COUNT(*) FROM sinistres_documents WHERE sinistre_id = s.id) AS nb_documents
                FROM sinistres s
                JOIN coproprietees c ON c.id = s.residence_id
                LEFT JOIN lots l     ON l.id = s.lot_id
                LEFT JOIN users u    ON u.id = s.declarant_user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY s.date_survenue DESC, s.id DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }

    /**
     * Récupère un sinistre avec toutes ses données enrichies.
     */
    public function findWithDetails(int $id): ?array {
        $sql = "SELECT s.*,
                       c.nom AS residence_nom, c.ville AS residence_ville,
                       l.numero_lot, l.type AS lot_type, l.surface AS lot_surface,
                       CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS declarant_nom,
                       u.username AS declarant_username, u.role AS declarant_role
                FROM sinistres s
                JOIN coproprietees c ON c.id = s.residence_id
                LEFT JOIN lots l     ON l.id = s.lot_id
                LEFT JOIN users u    ON u.id = s.declarant_user_id
                WHERE s.id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            return $r ?: null;
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return null; }
    }

    // ─────────────────────────────────────────────────────────────
    //  CRUD
    // ─────────────────────────────────────────────────────────────

    public function createSinistre(array $data, int $declarantUserId): int|false {
        $sql = "INSERT INTO sinistres (
                    residence_id, lot_id, lieu_partie_commune, description_lieu,
                    type_sinistre, gravite, date_survenue, date_constat,
                    declarant_user_id, titre, description,
                    assureur_nom, numero_contrat_assurance, numero_dossier_sinistre,
                    franchise, montant_estime, statut, notes
                ) VALUES (
                    :residence_id, :lot_id, :lieu_partie_commune, :description_lieu,
                    :type_sinistre, :gravite, :date_survenue, :date_constat,
                    :declarant_user_id, :titre, :description,
                    :assureur_nom, :numero_contrat_assurance, :numero_dossier_sinistre,
                    :franchise, :montant_estime, 'declare', :notes
                )";
        try {
            $this->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'residence_id'              => (int)$data['residence_id'],
                'lot_id'                    => !empty($data['lot_id']) ? (int)$data['lot_id'] : null,
                'lieu_partie_commune'       => !empty($data['lieu_partie_commune']) ? $data['lieu_partie_commune'] : null,
                'description_lieu'          => $data['description_lieu'] ?? null,
                'type_sinistre'             => $data['type_sinistre'],
                'gravite'                   => $data['gravite'] ?? 'modere',
                'date_survenue'             => $data['date_survenue'],
                'date_constat'              => $data['date_constat'] ?? null,
                'declarant_user_id'         => $declarantUserId,
                'titre'                     => $data['titre'],
                'description'               => $data['description'],
                'assureur_nom'              => $data['assureur_nom'] ?? null,
                'numero_contrat_assurance'  => $data['numero_contrat_assurance'] ?? null,
                'numero_dossier_sinistre'   => $data['numero_dossier_sinistre'] ?? null,
                'franchise'                 => !empty($data['franchise']) ? (float)$data['franchise'] : null,
                'montant_estime'            => !empty($data['montant_estime']) ? (float)$data['montant_estime'] : null,
                'notes'                     => $data['notes'] ?? null,
            ]);
            $id = (int)$this->db->lastInsertId();
            $this->logEvent($id, 'creation', null, 'declare', "Sinistre déclaré : {$data['titre']}", $declarantUserId);
            $this->commit();
            return $id;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($e->getMessage(), $sql);
            return false;
        }
    }

    public function updateSinistre(int $id, array $data, int $userId): bool {
        $current = $this->find($id);
        if (!$current) return false;
        if ($current->statut !== 'declare') return false; // figé

        $sql = "UPDATE sinistres SET
                    lot_id = :lot_id,
                    lieu_partie_commune = :lieu_partie_commune,
                    description_lieu = :description_lieu,
                    type_sinistre = :type_sinistre,
                    gravite = :gravite,
                    date_survenue = :date_survenue,
                    date_constat = :date_constat,
                    titre = :titre,
                    description = :description,
                    assureur_nom = :assureur_nom,
                    numero_contrat_assurance = :numero_contrat_assurance,
                    numero_dossier_sinistre = :numero_dossier_sinistre,
                    franchise = :franchise,
                    montant_estime = :montant_estime,
                    notes = :notes
                WHERE id = :id AND statut = 'declare'";
        try {
            $this->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id'                        => $id,
                'lot_id'                    => !empty($data['lot_id']) ? (int)$data['lot_id'] : null,
                'lieu_partie_commune'       => !empty($data['lieu_partie_commune']) ? $data['lieu_partie_commune'] : null,
                'description_lieu'          => $data['description_lieu'] ?? null,
                'type_sinistre'             => $data['type_sinistre'],
                'gravite'                   => $data['gravite'] ?? 'modere',
                'date_survenue'             => $data['date_survenue'],
                'date_constat'              => $data['date_constat'] ?? null,
                'titre'                     => $data['titre'],
                'description'               => $data['description'],
                'assureur_nom'              => $data['assureur_nom'] ?? null,
                'numero_contrat_assurance'  => $data['numero_contrat_assurance'] ?? null,
                'numero_dossier_sinistre'   => $data['numero_dossier_sinistre'] ?? null,
                'franchise'                 => !empty($data['franchise']) ? (float)$data['franchise'] : null,
                'montant_estime'            => !empty($data['montant_estime']) ? (float)$data['montant_estime'] : null,
                'notes'                     => $data['notes'] ?? null,
            ]);
            $this->logEvent($id, 'update', null, null, "Modification de la déclaration", $userId);
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($e->getMessage(), $sql);
            return false;
        }
    }

    public function changeStatut(int $id, string $newStatut, int $userId, ?string $details = null): bool {
        $valides = ['declare','transmis_assureur','expertise_en_cours','en_reparation','indemnise','clos','refuse'];
        if (!in_array($newStatut, $valides, true)) return false;

        $current = $this->find($id);
        if (!$current) return false;
        if ($current->statut === $newStatut) return true;

        // Construction SQL hors try pour rester accessible dans le catch (logging).
        $sql = "UPDATE sinistres SET statut = ?";
        $params = [$newStatut];
        if ($newStatut === 'transmis_assureur' && empty($current->date_declaration_assureur)) {
            $sql .= ", date_declaration_assureur = CURDATE()";
        }
        if (in_array($newStatut, ['clos','refuse','indemnise'], true) && empty($current->date_cloture)) {
            $sql .= ", date_cloture = NOW()";
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;

        try {
            $this->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $this->logEvent($id, 'changement_statut', $current->statut, $newStatut, $details, $userId);
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($e->getMessage(), $sql);
            return false;
        }
    }

    public function saveIndemnisation(int $id, float $montant, string $date, int $userId): bool {
        try {
            $this->beginTransaction();
            $stmt = $this->db->prepare("UPDATE sinistres SET montant_indemnise = ?, date_indemnisation = ? WHERE id = ?");
            $stmt->execute([$montant, $date, $id]);
            $this->logEvent($id, 'indemnisation', null, null,
                "Indemnisation enregistrée : " . number_format($montant, 2, ',', ' ') . " € le $date", $userId);
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function deleteSinistre(int $id): bool {
        // Hard delete autorisé uniquement à l'admin (vérifié au controller).
        // CASCADE supprime documents et logs automatiquement.
        try {
            $stmt = $this->db->prepare("DELETE FROM sinistres WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  AUDIT TRAIL
    // ─────────────────────────────────────────────────────────────

    private function logEvent(int $sinistreId, string $action, ?string $statutAvant, ?string $statutApres, ?string $details, int $userId): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sinistres_log (sinistre_id, action, statut_avant, statut_apres, details, user_id)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$sinistreId, $action, $statutAvant, $statutApres, $details, $userId]);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
        }
    }

    public function getHistory(int $sinistreId): array {
        $sql = "SELECT sl.*,
                       CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS user_nom,
                       u.username AS user_username
                FROM sinistres_log sl
                LEFT JOIN users u ON u.id = sl.user_id
                WHERE sl.sinistre_id = ?
                ORDER BY sl.created_at DESC, sl.id DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sinistreId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  GED DOCUMENTS
    // ─────────────────────────────────────────────────────────────

    public function getDocuments(int $sinistreId): array {
        $sql = "SELECT sd.*, CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS uploaded_by_nom
                FROM sinistres_documents sd
                LEFT JOIN users u ON u.id = sd.uploaded_by
                WHERE sd.sinistre_id = ?
                ORDER BY sd.uploaded_at DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sinistreId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage()); return []; }
    }

    public function findDocument(int $documentId): ?array {
        $sql = "SELECT sd.*, s.residence_id, s.lot_id, s.declarant_user_id
                FROM sinistres_documents sd
                JOIN sinistres s ON s.id = sd.sinistre_id
                WHERE sd.id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$documentId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            return $r ?: null;
        } catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function addDocument(int $sinistreId, array $doc, int $userId): int|false {
        try {
            $this->beginTransaction();
            $stmt = $this->db->prepare(
                "INSERT INTO sinistres_documents
                    (sinistre_id, type_document, nom_original, chemin_stockage, mime_type, taille_octets, description, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $sinistreId,
                $doc['type_document'] ?? 'autre',
                $doc['nom_original'],
                $doc['chemin_stockage'],
                $doc['mime_type'] ?? null,
                $doc['taille_octets'] ?? null,
                $doc['description'] ?? null,
                $userId,
            ]);
            $docId = (int)$this->db->lastInsertId();
            $this->logEvent($sinistreId, 'document_ajoute', null, null,
                "Document ajouté : " . ($doc['nom_original'] ?? '') . " (type: " . ($doc['type_document'] ?? 'autre') . ")",
                $userId);
            $this->commit();
            return $docId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($e->getMessage());
            return false;
        }
    }

    public function deleteDocument(int $documentId, int $userId): bool {
        $doc = $this->findDocument($documentId);
        if (!$doc) return false;
        try {
            $this->beginTransaction();
            $stmt = $this->db->prepare("DELETE FROM sinistres_documents WHERE id = ?");
            $stmt->execute([$documentId]);
            $this->logEvent((int)$doc['sinistre_id'], 'document_supprime', null, null,
                "Document supprimé : " . $doc['nom_original'], $userId);
            $this->commit();
            return true;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logError($e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS POUR LES VUES
    // ─────────────────────────────────────────────────────────────

    public function getResidencesPourFormulaire(int $userId, string $role): array {
        $ids = $this->getResidenceIdsAccessibles($userId, $role);
        if (empty($ids)) return [];
        $list = implode(',', array_map('intval', $ids));
        $sql = "SELECT id, nom, ville FROM coproprietees WHERE id IN ($list) AND actif = 1 ORDER BY nom";
        try { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage()); return []; }
    }

    public function getLotsPourResidence(int $residenceId, int $userId, string $role): array {
        // Pour résident : uniquement ses lots dans cette résidence
        // Pour autres rôles : tous les lots de la résidence
        if ($role === 'locataire_permanent') {
            $sql = "SELECT l.id, l.numero_lot, l.type
                    FROM lots l
                    JOIN occupations_residents o ON o.lot_id = l.id
                    JOIN residents_seniors rs ON rs.id = o.resident_id
                    WHERE l.copropriete_id = ? AND rs.user_id = ? AND o.statut = 'actif'
                    ORDER BY l.numero_lot";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId, $userId]);
        } else {
            $sql = "SELECT id, numero_lot, type FROM lots WHERE copropriete_id = ? ORDER BY numero_lot";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lots accessibles groupés par résidence : ['<res_id>' => [['id','numero_lot','type'], ...]]
     * Utilisé pour alimenter le <select> dynamique du formulaire create/edit côté JS.
     */
    public function getLotsGroupesParResidence(int $userId, string $role): array {
        $residenceIds = $this->getResidenceIdsAccessibles($userId, $role);
        if (empty($residenceIds)) return [];

        // Pour résident, restreindre aux lots qu'il occupe
        if ($role === 'locataire_permanent') {
            $sql = "SELECT l.copropriete_id AS rid, l.id, l.numero_lot, l.type
                    FROM lots l
                    JOIN occupations_residents o ON o.lot_id = l.id
                    JOIN residents_seniors rs ON rs.id = o.resident_id
                    WHERE rs.user_id = ? AND o.statut = 'actif'
                    ORDER BY l.numero_lot";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $list = implode(',', array_map('intval', $residenceIds));
            $sql = "SELECT copropriete_id AS rid, id, numero_lot, type
                    FROM lots
                    WHERE copropriete_id IN ($list)
                    ORDER BY copropriete_id, numero_lot";
            $stmt = $this->db->query($sql);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(int)$r['rid']][] = [
                'id'         => (int)$r['id'],
                'numero_lot' => $r['numero_lot'],
                'type'       => $r['type'],
            ];
        }
        return $grouped;
    }

    /**
     * Chantiers de réparation liés à un sinistre.
     * Délègue à Chantier::getChantiersBySinistre() pour rester DRY.
     * Le contrôle d'accès au sinistre lui-même se fait en amont (userCanAccess).
     */
    public function getChantiersLies(int $sinistreId): array {
        $chantierModel = new Chantier();
        return $chantierModel->getChantiersBySinistre($sinistreId);
    }

    public function getDashboardStats(int $userId, string $role): array {
        $residenceIds = $this->getResidenceIdsAccessibles($userId, $role);
        $stats = ['total' => 0, 'en_cours' => 0, 'clos' => 0, 'montant_estime_total' => 0, 'montant_indemnise_total' => 0];
        if (empty($residenceIds)) return $stats;

        $list = implode(',', array_map('intval', $residenceIds));
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut IN ('declare','transmis_assureur','expertise_en_cours','en_reparation') THEN 1 ELSE 0 END) AS en_cours,
                    SUM(CASE WHEN statut IN ('clos','indemnise','refuse') THEN 1 ELSE 0 END) AS clos,
                    COALESCE(SUM(montant_estime), 0)    AS montant_estime_total,
                    COALESCE(SUM(montant_indemnise), 0) AS montant_indemnise_total
                FROM sinistres
                WHERE residence_id IN ($list)";
        try {
            $r = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
            return array_merge($stats, array_map(fn($v) => $v ?? 0, $r));
        } catch (PDOException $e) { $this->logError($e->getMessage()); return $stats; }
    }
}
