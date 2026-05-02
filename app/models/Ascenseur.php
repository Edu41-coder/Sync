<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Ascenseur (entité dédiée + journal)
 * ====================================================================
 * Une résidence = 0..N ascenseurs (1:N).
 * Le flag coproprietees.ascenseur est auto-maintenu par triggers BDD.
 */
class Ascenseur extends Model {

    public const STATUTS = ['actif', 'hors_service', 'depose'];
    public const MARQUES = ['schindler','otis','kone','thyssenkrupp','mitsubishi','orona','autre'];

    public const TYPES_JOURNAL = [
        'maintenance_preventive' => 'Maintenance préventive',
        'visite_annuelle'        => 'Visite annuelle',
        'controle_quinquennal'   => 'Contrôle quinquennal',
        'panne'                  => 'Panne',
        'intervention'           => 'Intervention',
        'autre'                  => 'Autre',
    ];

    public const CONFORMITE = ['conforme', 'non_conforme', 'avec_reserves'];

    /** Périodicités par type (en jours) — pour calculer prochaine_echeance et alertes */
    public const PERIODICITES = [
        'maintenance_preventive' => 30,    // mensuelle
        'visite_annuelle'        => 365,   // annuelle
        'controle_quinquennal'   => 1825,  // 5 ans
    ];

    // ─── ASCENSEURS (CRUD) ──────────────────────────────────────

    /** Résidences accessibles ayant ≥ 1 ascenseur (actif). */
    public function getResidencesAvecAscenseurs(int $userId, string $userRole): array {
        if ($userRole === 'admin') {
            $sql = "SELECT id, nom, ville FROM coproprietees WHERE ascenseur = 1 AND actif = 1 ORDER BY nom";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = "SELECT c.id, c.nom, c.ville
                FROM coproprietees c
                JOIN user_residence ur ON ur.residence_id = c.id AND ur.statut = 'actif'
                WHERE c.ascenseur = 1 AND c.actif = 1 AND ur.user_id = ?
                ORDER BY c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAscenseursParResidence(int $residenceId, bool $actifsSeuls = false): array {
        $sql = "SELECT * FROM ascenseurs WHERE residence_id = ?"
             . ($actifsSeuls ? " AND statut = 'actif'" : "")
             . " ORDER BY nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAscenseur(int $id): ?array {
        $sql = "SELECT a.*, c.nom AS residence_nom, c.ville AS residence_ville
                FROM ascenseurs a
                JOIN coproprietees c ON c.id = a.residence_id
                WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createAscenseur(array $data): int {
        $sql = "INSERT INTO ascenseurs
                (residence_id, nom, numero_serie, emplacement, marque, modele,
                 capacite_kg, capacite_personnes, nombre_etages, date_mise_service,
                 contrat_ascensoriste_nom, contrat_ascensoriste_tel, contrat_ascensoriste_email,
                 contrat_numero, statut, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'],
            $data['nom'],
            $data['numero_serie'] ?: null,
            $data['emplacement']  ?: null,
            $data['marque']       ?? 'autre',
            $data['modele']       ?: null,
            !empty($data['capacite_kg'])         ? (int)$data['capacite_kg']        : null,
            !empty($data['capacite_personnes'])  ? (int)$data['capacite_personnes'] : null,
            !empty($data['nombre_etages'])       ? (int)$data['nombre_etages']      : null,
            $data['date_mise_service']        ?: null,
            $data['contrat_ascensoriste_nom'] ?: null,
            $data['contrat_ascensoriste_tel'] ?: null,
            $data['contrat_ascensoriste_email'] ?: null,
            $data['contrat_numero'] ?: null,
            $data['statut'] ?? 'actif',
            $data['notes'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateAscenseur(int $id, array $data): bool {
        $sql = "UPDATE ascenseurs SET
                  nom = ?, numero_serie = ?, emplacement = ?, marque = ?, modele = ?,
                  capacite_kg = ?, capacite_personnes = ?, nombre_etages = ?, date_mise_service = ?,
                  contrat_ascensoriste_nom = ?, contrat_ascensoriste_tel = ?, contrat_ascensoriste_email = ?,
                  contrat_numero = ?, statut = ?, notes = ?
                WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['nom'],
            $data['numero_serie'] ?: null,
            $data['emplacement']  ?: null,
            $data['marque']       ?? 'autre',
            $data['modele']       ?: null,
            !empty($data['capacite_kg'])         ? (int)$data['capacite_kg']        : null,
            !empty($data['capacite_personnes'])  ? (int)$data['capacite_personnes'] : null,
            !empty($data['nombre_etages'])       ? (int)$data['nombre_etages']      : null,
            $data['date_mise_service']        ?: null,
            $data['contrat_ascensoriste_nom'] ?: null,
            $data['contrat_ascensoriste_tel'] ?: null,
            $data['contrat_ascensoriste_email'] ?: null,
            $data['contrat_numero'] ?: null,
            $data['statut'] ?? 'actif',
            $data['notes'] ?: null,
            $id,
        ]);
    }

    public function deleteAscenseur(int $id): bool {
        return $this->db->prepare("DELETE FROM ascenseurs WHERE id = ?")->execute([$id]);
    }

    // ─── JOURNAL ────────────────────────────────────────────────

    public function getJournal(int $ascenseurId, int $limit = 100): array {
        $sql = "SELECT j.*, u.prenom AS createur_prenom, u.nom AS createur_nom,
                       i.titre AS intervention_titre
                FROM ascenseur_journal j
                LEFT JOIN users u ON u.id = j.created_by
                LEFT JOIN maintenance_interventions i ON i.id = j.intervention_id
                WHERE j.ascenseur_id = ?
                ORDER BY j.date_event DESC
                LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ascenseurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findEntree(int $id): ?array {
        $sql = "SELECT j.*, a.nom AS ascenseur_nom, a.residence_id
                FROM ascenseur_journal j
                JOIN ascenseurs a ON a.id = j.ascenseur_id
                WHERE j.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createEntree(array $data): int {
        // Auto-calcule prochaine_echeance si non fournie ET type récurrent
        $proch = $data['prochaine_echeance'] ?? null;
        if (!$proch && isset(self::PERIODICITES[$data['type_entree']])) {
            $jours = self::PERIODICITES[$data['type_entree']];
            $proch = (new DateTime($data['date_event']))->modify("+$jours days")->format('Y-m-d');
        }

        $sql = "INSERT INTO ascenseur_journal
                (ascenseur_id, type_entree, date_event, organisme, technicien_intervenant,
                 numero_pv, conformite, fichier_pv, intervention_id, prochaine_echeance,
                 observations, cout, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['ascenseur_id'],
            $data['type_entree'],
            $data['date_event'],
            $data['organisme'] ?: null,
            $data['technicien_intervenant'] ?: null,
            $data['numero_pv'] ?: null,
            $data['conformite'] ?: null,
            $data['fichier_pv'] ?: null,
            !empty($data['intervention_id']) ? (int)$data['intervention_id'] : null,
            $proch,
            $data['observations'] ?: null,
            !empty($data['cout']) ? (float)$data['cout'] : null,
            $data['created_by'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateEntree(int $id, array $data): bool {
        // Auto-recalcule prochaine_echeance si non fournie ET type récurrent ET date_event change
        $proch = $data['prochaine_echeance'] ?? null;
        if (!$proch && isset(self::PERIODICITES[$data['type_entree']])) {
            $jours = self::PERIODICITES[$data['type_entree']];
            $proch = (new DateTime($data['date_event']))->modify("+$jours days")->format('Y-m-d');
        }

        // Si nouveau fichier_pv fourni, on l'utilise. Sinon on garde l'ancien (NULL = pas de changement).
        $sql = "UPDATE ascenseur_journal SET
                  type_entree = ?, date_event = ?, organisme = ?, technicien_intervenant = ?,
                  numero_pv = ?, conformite = ?, intervention_id = ?, prochaine_echeance = ?,
                  observations = ?, cout = ?";
        $params = [
            $data['type_entree'],
            $data['date_event'],
            $data['organisme'] ?: null,
            $data['technicien_intervenant'] ?: null,
            $data['numero_pv'] ?: null,
            $data['conformite'] ?: null,
            !empty($data['intervention_id']) ? (int)$data['intervention_id'] : null,
            $proch,
            $data['observations'] ?: null,
            !empty($data['cout']) ? (float)$data['cout'] : null,
        ];
        if (array_key_exists('fichier_pv', $data) && $data['fichier_pv'] !== null) {
            $sql .= ", fichier_pv = ?";
            $params[] = $data['fichier_pv'];
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        return $this->db->prepare($sql)->execute($params);
    }

    public function deleteEntree(int $id): ?array {
        $row = $this->findEntree($id);
        if (!$row) return null;
        $this->db->prepare("DELETE FROM ascenseur_journal WHERE id = ?")->execute([$id]);
        return $row;
    }

    // ─── ALERTES & ÉCHÉANCES ────────────────────────────────────

    /**
     * Pour chaque ascenseur d'une résidence, retourne la dernière entrée
     * de chaque type récurrent + la prochaine échéance calculée.
     */
    public function getEcheancesParAscenseur(int $residenceId): array {
        $sql = "SELECT
                    a.id, a.nom, a.statut,
                    j.type_entree, j.date_event, j.prochaine_echeance, j.conformite
                FROM ascenseurs a
                LEFT JOIN ascenseur_journal j ON j.ascenseur_id = a.id
                  AND j.id = (
                      SELECT j2.id FROM ascenseur_journal j2
                      WHERE j2.ascenseur_id = a.id AND j2.type_entree = j.type_entree
                      ORDER BY j2.date_event DESC LIMIT 1
                  )
                WHERE a.residence_id = ? AND a.statut = 'actif'
                ORDER BY a.nom, j.type_entree";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alertes globales sur tous les ascenseurs accessibles.
     */
    public function getAlertes(int $userId, string $userRole): array {
        $resIds = $this->getResidenceIdsAccessibles($userId, $userRole);
        if (empty($resIds)) return [];
        $ph = implode(',', array_fill(0, count($resIds), '?'));

        // Dernière entrée par type pour chaque ascenseur actif
        $sql = "SELECT a.id AS ascenseur_id, a.nom AS ascenseur_nom, a.residence_id,
                       c.nom AS residence_nom,
                       j.type_entree, j.date_event, j.prochaine_echeance, j.conformite
                FROM ascenseurs a
                JOIN coproprietees c ON c.id = a.residence_id
                LEFT JOIN ascenseur_journal j ON j.ascenseur_id = a.id
                  AND j.id = (
                      SELECT j2.id FROM ascenseur_journal j2
                      WHERE j2.ascenseur_id = a.id AND j2.type_entree = j.type_entree
                      ORDER BY j2.date_event DESC LIMIT 1
                  )
                WHERE a.statut = 'actif' AND a.residence_id IN ($ph)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($resIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = new DateTime();
        $alertes = [];

        // Index : pour chaque ascenseur, on a éventuellement plusieurs lignes (1 par type)
        $byAsc = [];
        foreach ($rows as $r) {
            $byAsc[$r['ascenseur_id']]['nom'] = $r['ascenseur_nom'];
            $byAsc[$r['ascenseur_id']]['residence'] = $r['residence_nom'];
            if (!empty($r['type_entree'])) {
                $byAsc[$r['ascenseur_id']]['types'][$r['type_entree']] = $r;
            } else {
                $byAsc[$r['ascenseur_id']]['types'] = [];
            }
        }

        foreach ($byAsc as $info) {
            $label = $info['nom'] . ' (' . $info['residence'] . ')';

            // Visite annuelle : doit exister + échéance non dépassée
            $va = $info['types']['visite_annuelle'] ?? null;
            if (!$va) {
                $alertes[] = ['niveau'=>'warning', 'msg'=>"$label : aucune visite annuelle enregistrée."];
            } elseif ($va['prochaine_echeance']) {
                $diff = (int)$today->diff(new DateTime($va['prochaine_echeance']))->format('%r%a');
                if ($diff < 0) $alertes[] = ['niveau'=>'danger', 'msg'=>"$label : visite annuelle EXPIRÉE depuis " . abs($diff) . " jours."];
                elseif ($diff <= 30) $alertes[] = ['niveau'=>'warning', 'msg'=>"$label : visite annuelle dans $diff jours."];
            }

            // Contrôle quinquennal
            $cq = $info['types']['controle_quinquennal'] ?? null;
            if (!$cq) {
                $alertes[] = ['niveau'=>'warning', 'msg'=>"$label : aucun contrôle quinquennal enregistré."];
            } elseif ($cq['prochaine_echeance']) {
                $diff = (int)$today->diff(new DateTime($cq['prochaine_echeance']))->format('%r%a');
                if ($diff < 0) $alertes[] = ['niveau'=>'danger', 'msg'=>"$label : contrôle quinquennal EXPIRÉ depuis " . abs($diff) . " jours."];
                elseif ($diff <= 180) $alertes[] = ['niveau'=>'info', 'msg'=>"$label : contrôle quinquennal dans $diff jours (planifier)."];
            }

            // Maintenance préventive (>45 jours sans entrée)
            $mp = $info['types']['maintenance_preventive'] ?? null;
            if ($mp && $mp['date_event']) {
                $diff = (int)(new DateTime())->diff(new DateTime($mp['date_event']))->format('%r%a');
                if ($diff > 45) $alertes[] = ['niveau'=>'warning', 'msg'=>"$label : pas de maintenance préventive depuis $diff jours (idéal mensuel)."];
            }

            // Conformité non OK sur dernière visite annuelle ou contrôle
            foreach (['visite_annuelle', 'controle_quinquennal'] as $t) {
                $e = $info['types'][$t] ?? null;
                if ($e && in_array($e['conformite'], ['non_conforme', 'avec_reserves'], true)) {
                    $alertes[] = ['niveau'=>'danger', 'msg'=>"$label : dernière " . str_replace('_', ' ', $t) . " " . str_replace('_', ' ', $e['conformite']) . "."];
                }
            }
        }

        return $alertes;
    }

    private function getResidenceIdsAccessibles(int $userId, string $userRole): array {
        if ($userRole === 'admin') {
            return $this->db->query("SELECT id FROM coproprietees WHERE ascenseur = 1 AND actif = 1")->fetchAll(PDO::FETCH_COLUMN);
        }
        $stmt = $this->db->prepare("
            SELECT c.id FROM coproprietees c
            JOIN user_residence ur ON ur.residence_id = c.id AND ur.statut = 'actif'
            WHERE c.ascenseur = 1 AND c.actif = 1 AND ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getStats(int $ascenseurId): array {
        $stats = ['nb_entrees' => 0, 'derniere_pv' => null, 'cout_total_an' => 0];
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ascenseur_journal WHERE ascenseur_id = ?");
        $stmt->execute([$ascenseurId]);
        $stats['nb_entrees'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT MAX(date_event) FROM ascenseur_journal WHERE ascenseur_id = ?");
        $stmt->execute([$ascenseurId]);
        $stats['derniere_pv'] = $stmt->fetchColumn() ?: null;

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(cout),0) FROM ascenseur_journal WHERE ascenseur_id = ? AND date_event >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
        $stmt->execute([$ascenseurId]);
        $stats['cout_total_an'] = (float)$stmt->fetchColumn();
        return $stats;
    }
}
