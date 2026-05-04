<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Assemblée Générale
 * ====================================================================
 * Gestion des assemblées générales de copropriété (AGO/AGE) :
 *   - CRUD AG + workflow planifiee → convoquee → tenue / annulee
 *   - Résolutions avec ordre + votes pondérés (tantièmes optionnels)
 *   - Liaison chantiers (chantier.ag_id pour les travaux > 5000 € HT)
 *   - Convocation + PV stockés dans uploads/ag/{ag_id}/ (privé)
 */
class Assemblee extends Model {

    public const TYPES   = ['ordinaire', 'extraordinaire'];
    public const MODES   = ['presentiel', 'visio', 'mixte'];
    public const STATUTS = ['planifiee', 'convoquee', 'tenue', 'annulee'];
    public const RESULTATS_VOTE = ['adopte', 'rejete', 'reporte'];

    // ─── ASSEMBLÉES ──────────────────────────────────────────────

    /** Liste AG d'une ou plusieurs résidences avec stats (résolutions, chantiers liés) */
    public function getAGs(array $residenceIds, array $filtres = []): array {
        if (empty($residenceIds)) return [];
        $ph = implode(',', array_map('intval', $residenceIds));

        $sql = "SELECT a.*,
                       c.nom AS residence_nom,
                       (SELECT COUNT(*) FROM votes_ag v WHERE v.ag_id = a.id) AS nb_resolutions,
                       (SELECT COUNT(*) FROM chantiers ch WHERE ch.ag_id = a.id) AS nb_chantiers
                FROM assemblees_generales a
                JOIN coproprietees c ON c.id = a.copropriete_id
                WHERE a.copropriete_id IN ($ph)";
        $params = [];

        if (!empty($filtres['statut'])) { $sql .= " AND a.statut = ?"; $params[] = $filtres['statut']; }
        if (!empty($filtres['type']))   { $sql .= " AND a.type   = ?"; $params[] = $filtres['type']; }
        if (!empty($filtres['annee'])) {
            $sql .= " AND YEAR(a.date_ag) = ?";
            $params[] = (int)$filtres['annee'];
        }
        $sql .= " ORDER BY a.date_ag DESC, a.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAG(int $id): ?array {
        $sql = "SELECT a.*,
                       c.nom AS residence_nom, c.ville AS residence_ville,
                       up.prenom AS pres_prenom, up.nom AS pres_nom,
                       us.prenom AS sec_prenom,  us.nom AS sec_nom,
                       uc.prenom AS createur_prenom, uc.nom AS createur_nom
                FROM assemblees_generales a
                JOIN coproprietees c ON c.id = a.copropriete_id
                LEFT JOIN users up ON up.id = a.president_seance_id
                LEFT JOIN users us ON us.id = a.secretaire_id
                LEFT JOIN users uc ON uc.id = a.created_by
                WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createAG(array $data): int {
        $sql = "INSERT INTO assemblees_generales
                  (copropriete_id, type, date_ag, lieu, mode, ordre_du_jour, statut, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'planifiee', ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['copropriete_id'], $data['type'], $data['date_ag'],
            $data['lieu'] ?: null, $data['mode'] ?? 'presentiel',
            $data['ordre_du_jour'] ?: null, (int)$data['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Update champs éditables (hors workflow + uploads) */
    public function updateAG(int $id, array $data): bool {
        $sql = "UPDATE assemblees_generales
                   SET type = ?, date_ag = ?, lieu = ?, mode = ?, ordre_du_jour = ?,
                       president_seance_id = ?, secretaire_id = ?, notes_internes = ?,
                       quorum_requis = ?, quorum_present = ?, votants_total = ?,
                       quorum_atteint = ?, proces_verbal = ?
                 WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['type'], $data['date_ag'], $data['lieu'] ?: null,
            $data['mode'] ?? 'presentiel', $data['ordre_du_jour'] ?: null,
            !empty($data['president_seance_id']) ? (int)$data['president_seance_id'] : null,
            !empty($data['secretaire_id'])       ? (int)$data['secretaire_id']       : null,
            $data['notes_internes'] ?: null,
            isset($data['quorum_requis'])  && $data['quorum_requis']  !== '' ? (int)$data['quorum_requis']  : null,
            isset($data['quorum_present']) && $data['quorum_present'] !== '' ? (int)$data['quorum_present'] : null,
            isset($data['votants_total'])  && $data['votants_total']  !== '' ? (int)$data['votants_total']  : null,
            !empty($data['quorum_atteint']) ? 1 : 0,
            $data['proces_verbal'] ?: null,
            $id,
        ]);
    }

    public function deleteAG(int $id): ?array {
        $row = $this->findAG($id);
        if (!$row) return null;
        $this->db->prepare("DELETE FROM assemblees_generales WHERE id = ?")->execute([$id]);
        return $row;
    }

    // ─── WORKFLOW STATUTS ────────────────────────────────────────

    /** Passer en 'convoquee' (enregistre la date d'envoi + chemin convocation si fourni) */
    public function convoquer(int $id, ?string $documentConvocation = null): bool {
        $sql = "UPDATE assemblees_generales
                   SET statut = 'convoquee', convocation_envoyee_le = NOW()"
             . ($documentConvocation ? ", document_convocation = ?" : "")
             . " WHERE id = ? AND statut IN ('planifiee')";
        $params = $documentConvocation ? [$documentConvocation, $id] : [$id];
        return $this->db->prepare($sql)->execute($params);
    }

    /** Passer en 'tenue' (post-séance, avec PV + quorum) */
    public function tenir(int $id, array $data): bool {
        $sql = "UPDATE assemblees_generales
                   SET statut = 'tenue',
                       proces_verbal = ?, document_pv = COALESCE(?, document_pv),
                       quorum_atteint = ?, quorum_present = ?, votants_total = ?,
                       president_seance_id = COALESCE(?, president_seance_id),
                       secretaire_id = COALESCE(?, secretaire_id)
                 WHERE id = ? AND statut IN ('convoquee','planifiee')";
        return $this->db->prepare($sql)->execute([
            $data['proces_verbal'] ?: null,
            $data['document_pv'] ?? null,
            !empty($data['quorum_atteint']) ? 1 : 0,
            isset($data['quorum_present']) && $data['quorum_present'] !== '' ? (int)$data['quorum_present'] : null,
            isset($data['votants_total'])  && $data['votants_total']  !== '' ? (int)$data['votants_total']  : null,
            !empty($data['president_seance_id']) ? (int)$data['president_seance_id'] : null,
            !empty($data['secretaire_id'])       ? (int)$data['secretaire_id']       : null,
            $id,
        ]);
    }

    public function annuler(int $id): bool {
        return $this->db->prepare("UPDATE assemblees_generales SET statut = 'annulee' WHERE id = ?")
                        ->execute([$id]);
    }

    /** Mettre à jour le chemin d'un document après upload */
    public function setDocument(int $id, string $champ, string $chemin): bool {
        if (!in_array($champ, ['document_convocation', 'document_pv'], true)) return false;
        return $this->db->prepare("UPDATE assemblees_generales SET $champ = ? WHERE id = ?")
                        ->execute([$chemin, $id]);
    }

    // ─── RÉSOLUTIONS / VOTES ─────────────────────────────────────

    public function getResolutions(int $agId): array {
        $stmt = $this->db->prepare("SELECT * FROM votes_ag WHERE ag_id = ? ORDER BY ordre, id");
        $stmt->execute([$agId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findResolution(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM votes_ag WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function nextOrdre(int $agId): int {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(ordre), 0) + 1 FROM votes_ag WHERE ag_id = ?");
        $stmt->execute([$agId]);
        return (int)$stmt->fetchColumn();
    }

    public function createResolution(array $data): int {
        $resultat = $data['resultat']
            ?? ($this->hasVotes($data) ? $this->calculerResultat($data) : 'reporte');
        $sql = "INSERT INTO votes_ag (ag_id, resolution, description, ordre,
                                      votes_pour, votes_contre, abstentions,
                                      tantiemes_pour, tantiemes_contre, resultat)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['ag_id'], $data['resolution'], $data['description'] ?: null,
            !empty($data['ordre']) ? (int)$data['ordre'] : $this->nextOrdre((int)$data['ag_id']),
            (int)($data['votes_pour']      ?? 0),
            (int)($data['votes_contre']    ?? 0),
            (int)($data['abstentions']     ?? 0),
            (int)($data['tantiemes_pour']  ?? 0),
            (int)($data['tantiemes_contre']?? 0),
            $resultat,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Vrai si au moins un vote/tantième non nul */
    private function hasVotes(array $data): bool {
        return ((int)($data['votes_pour'] ?? 0) + (int)($data['votes_contre'] ?? 0)
              + (int)($data['tantiemes_pour'] ?? 0) + (int)($data['tantiemes_contre'] ?? 0)) > 0;
    }

    public function updateResolution(int $id, array $data): bool {
        $resultat = !empty($data['resultat'])
            ? $data['resultat']
            : ($this->hasVotes($data) ? $this->calculerResultat($data) : 'reporte');
        $sql = "UPDATE votes_ag
                   SET resolution = ?, description = ?, ordre = ?,
                       votes_pour = ?, votes_contre = ?, abstentions = ?,
                       tantiemes_pour = ?, tantiemes_contre = ?, resultat = ?
                 WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['resolution'], $data['description'] ?: null,
            !empty($data['ordre']) ? (int)$data['ordre'] : 1,
            (int)($data['votes_pour']      ?? 0),
            (int)($data['votes_contre']    ?? 0),
            (int)($data['abstentions']     ?? 0),
            (int)($data['tantiemes_pour']  ?? 0),
            (int)($data['tantiemes_contre']?? 0),
            $resultat,
            $id,
        ]);
    }

    public function deleteResolution(int $id): bool {
        return $this->db->prepare("DELETE FROM votes_ag WHERE id = ?")->execute([$id]);
    }

    /**
     * Calcule automatiquement le résultat d'une résolution selon les votes.
     * Règle simple : adopté si tantièmes_pour > tantièmes_contre, rejeté sinon.
     * Si pas de tantièmes saisis → fallback sur votes_pour vs votes_contre.
     */
    public function calculerResultat(array $r): string {
        $tp = (int)($r['tantiemes_pour'] ?? 0);
        $tc = (int)($r['tantiemes_contre'] ?? 0);
        if ($tp > 0 || $tc > 0) {
            return $tp > $tc ? 'adopte' : 'rejete';
        }
        $vp = (int)($r['votes_pour'] ?? 0);
        $vc = (int)($r['votes_contre'] ?? 0);
        return $vp > $vc ? 'adopte' : 'rejete';
    }

    // ─── INTÉGRATION CHANTIERS ───────────────────────────────────

    /** Liste des chantiers liés à une AG (résolution travaux) */
    public function getChantiersLies(int $agId): array {
        $sql = "SELECT id, titre, montant_estime, montant_engage, statut, phase, residence_id
                FROM chantiers WHERE ag_id = ? ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$agId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Chantiers en attente d'AG (necessite_ag=1, ag_id NULL) pour la résidence */
    public function getChantiersEnAttente(int $residenceId): array {
        $sql = "SELECT id, titre, montant_estime FROM chantiers
                WHERE residence_id = ? AND necessite_ag = 1 AND ag_id IS NULL
                  AND statut NOT IN ('annule','clos')
                ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Stats dashboard : AG planifiées + tenues cette année */
    public function getStats(array $residenceIds): array {
        if (empty($residenceIds)) return ['planifiees' => 0, 'tenues_annee' => 0, 'prochaine' => null];
        $ph = implode(',', array_map('intval', $residenceIds));

        $planifiees = (int)$this->db->query("SELECT COUNT(*) FROM assemblees_generales
                                              WHERE copropriete_id IN ($ph) AND statut IN ('planifiee','convoquee')
                                                AND date_ag >= NOW()")->fetchColumn();
        $tenues = (int)$this->db->query("SELECT COUNT(*) FROM assemblees_generales
                                          WHERE copropriete_id IN ($ph) AND statut = 'tenue'
                                            AND YEAR(date_ag) = YEAR(NOW())")->fetchColumn();
        $prochaine = $this->db->query("SELECT a.id, a.date_ag, a.type, c.nom AS residence_nom
                                        FROM assemblees_generales a
                                        JOIN coproprietees c ON c.id = a.copropriete_id
                                        WHERE a.copropriete_id IN ($ph)
                                          AND a.statut IN ('planifiee','convoquee')
                                          AND a.date_ag >= NOW()
                                        ORDER BY a.date_ag ASC LIMIT 1")
                              ->fetch(PDO::FETCH_ASSOC) ?: null;

        return ['planifiees' => $planifiees, 'tenues_annee' => $tenues, 'prochaine' => $prochaine];
    }

    // ─── ACCÈS PROPRIÉTAIRE ──────────────────────────────────────

    /**
     * Résidences accessibles à un propriétaire (où il a au moins un contrat actif).
     */
    public function getResidencesProprietaire(int $proprietaireId): array {
        $sql = "SELECT DISTINCT c.id, c.nom, c.ville
                FROM coproprietees c
                JOIN lots l ON l.copropriete_id = c.id
                JOIN contrats_gestion cg ON cg.lot_id = l.id AND cg.statut = 'actif'
                WHERE cg.coproprietaire_id = ?
                ORDER BY c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$proprietaireId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * AG visibles par un propriétaire : seules les AG `convoquee`/`tenue`/`annulee`
     * des résidences où il a un contrat actif.
     * Les AG `planifiee` sont masquées (pas encore officielles).
     */
    public function getAGsProprietaire(int $proprietaireId, array $filtres = []): array {
        $residences = $this->getResidencesProprietaire($proprietaireId);
        if (empty($residences)) return [];
        $ph = implode(',', array_map('intval', array_column($residences, 'id')));

        $sql = "SELECT a.*,
                       c.nom AS residence_nom, c.ville AS residence_ville,
                       (SELECT COUNT(*) FROM votes_ag v WHERE v.ag_id = a.id) AS nb_resolutions
                FROM assemblees_generales a
                JOIN coproprietees c ON c.id = a.copropriete_id
                WHERE a.copropriete_id IN ($ph)
                  AND a.statut IN ('convoquee','tenue','annulee')";
        $params = [];

        if (!empty($filtres['statut']) && in_array($filtres['statut'], ['convoquee','tenue','annulee'], true)) {
            $sql .= " AND a.statut = ?"; $params[] = $filtres['statut'];
        }
        if (!empty($filtres['residence_id'])) {
            $sql .= " AND a.copropriete_id = ?"; $params[] = (int)$filtres['residence_id'];
        }
        $sql .= " ORDER BY a.date_ag DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Vrai si une AG est accessible au propriétaire */
    public function agAccessibleProprietaire(int $agId, int $proprietaireId): bool {
        $residenceIds = array_column($this->getResidencesProprietaire($proprietaireId), 'id');
        if (empty($residenceIds)) return false;
        $ph = implode(',', array_map('intval', $residenceIds));
        $sql = "SELECT 1 FROM assemblees_generales
                WHERE id = ? AND copropriete_id IN ($ph)
                  AND statut IN ('convoquee','tenue','annulee') LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$agId]);
        return (bool)$stmt->fetchColumn();
    }

    /** Stats dashboard propriétaire : prochaine AG + nb à venir */
    public function getStatsProprietaire(int $proprietaireId): array {
        $residenceIds = array_column($this->getResidencesProprietaire($proprietaireId), 'id');
        if (empty($residenceIds)) return ['a_venir' => 0, 'prochaine' => null];
        $ph = implode(',', array_map('intval', $residenceIds));

        $aVenir = (int)$this->db->query("SELECT COUNT(*) FROM assemblees_generales
                                          WHERE copropriete_id IN ($ph)
                                            AND statut = 'convoquee' AND date_ag >= NOW()")
                                 ->fetchColumn();
        $prochaine = $this->db->query("SELECT a.id, a.date_ag, a.type, a.lieu, c.nom AS residence_nom
                                        FROM assemblees_generales a
                                        JOIN coproprietees c ON c.id = a.copropriete_id
                                        WHERE a.copropriete_id IN ($ph)
                                          AND a.statut = 'convoquee'
                                          AND a.date_ag >= NOW()
                                        ORDER BY a.date_ag ASC LIMIT 1")
                              ->fetch(PDO::FETCH_ASSOC) ?: null;
        return ['a_venir' => $aVenir, 'prochaine' => $prochaine];
    }

    /** Présidents/secrétaires candidats : direction + admin + propriétaires de la résidence */
    public function getPresidencesCandidats(int $residenceId): array {
        $sql = "SELECT DISTINCT u.id, u.prenom, u.nom, u.role
                FROM users u
                LEFT JOIN user_residence ur ON ur.user_id = u.id AND ur.residence_id = ? AND ur.statut = 'actif'
                LEFT JOIN coproprietaires cop ON cop.user_id = u.id
                LEFT JOIN contrats_gestion cg ON cg.coproprietaire_id = cop.id AND cg.statut = 'actif'
                LEFT JOIN lots l ON l.id = cg.lot_id AND l.copropriete_id = ?
                WHERE u.actif = 1
                  AND (u.role = 'admin' OR ur.id IS NOT NULL OR l.id IS NOT NULL)
                ORDER BY u.nom, u.prenom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId, $residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
