<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Chantier (travaux planifiés)
 * ====================================================================
 * Workflow 9 phases : diagnostic → cahier_charges → devis → decision →
 *                     commande → execution → reception → garantie → cloture
 *
 * Garanties auto-créées à la phase reception (3 lignes : parfait achèvement,
 * biennale, décennale).
 * Quote-part propriétaires calculée à partir de chantier_lots_impactes.
 * Lien AG via assemblees_generales.ag_id (auto-flag si > 5000 € HT).
 */
class Chantier extends Model {

    public const SEUIL_AG_HT = 5000.00;

    public const PHASES = ['diagnostic','cahier_charges','devis','decision','commande','execution','reception','garantie','cloture'];
    public const PHASES_LABELS = [
        'diagnostic'     => 'Diagnostic',
        'cahier_charges' => 'Cahier des charges',
        'devis'          => 'Devis',
        'decision'       => 'Décision',
        'commande'       => 'Commande',
        'execution'      => 'Exécution',
        'reception'      => 'Réception',
        'garantie'       => 'Garantie',
        'cloture'        => 'Clôture',
    ];

    public const STATUTS   = ['actif','suspendu','termine','annule'];
    public const PRIORITES = ['basse','normale','haute','urgente'];
    public const CATEGORIES = ['gros_oeuvre','second_oeuvre','plomberie','electricite','chauffage','peinture','toiture','facade','ascenseur','piscine','mise_aux_normes','amenagement','autre'];

    public const TYPES_DOC = ['devis_signe','plan','photo_avant','photo_apres','photo_chantier','pv_reception','facture','garantie','attestation','autre'];

    // Garanties (durées légales en années)
    public const DUREES_GARANTIES = [
        'parfait_achevement' => 1,
        'biennale'           => 2,
        'decennale'          => 10,
    ];

    // ─── CHANTIERS ──────────────────────────────────────────────

    /**
     * Liste filtrée des chantiers selon rôle/résidences accessibles.
     */
    public function getChantiers(int $userId, string $userRole, array $residencesIds, array $filtres = []): array {
        $sql = "SELECT c.*, s.nom AS specialite_nom, s.couleur AS specialite_couleur, s.icone AS specialite_icone,
                       co.nom AS residence_nom,
                       ag.date_ag, ag.statut AS ag_statut
                FROM chantiers c
                LEFT JOIN specialites s ON s.id = c.specialite_id
                JOIN coproprietees co ON co.id = c.residence_id
                LEFT JOIN assemblees_generales ag ON ag.id = c.ag_id
                WHERE 1=1";
        $params = [];

        if ($userRole !== 'admin' && !empty($residencesIds)) {
            $ph = implode(',', array_fill(0, count($residencesIds), '?'));
            $sql .= " AND c.residence_id IN ($ph)";
            array_push($params, ...$residencesIds);
        }

        if (!empty($filtres['phase']))         { $sql .= " AND c.phase = ?";         $params[] = $filtres['phase']; }
        if (!empty($filtres['statut']))        { $sql .= " AND c.statut = ?";        $params[] = $filtres['statut']; }
        if (!empty($filtres['residence_id'])) { $sql .= " AND c.residence_id = ?";   $params[] = (int)$filtres['residence_id']; }
        if (!empty($filtres['categorie']))    { $sql .= " AND c.categorie = ?";      $params[] = $filtres['categorie']; }

        $sql .= " ORDER BY FIELD(c.priorite,'urgente','haute','normale','basse'), c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findChantier(int $id): ?array {
        $sql = "SELECT c.*, s.nom AS specialite_nom, s.couleur AS specialite_couleur, s.icone AS specialite_icone,
                       co.nom AS residence_nom, co.ville AS residence_ville,
                       ag.date_ag, ag.statut AS ag_statut, ag.type AS ag_type,
                       u.prenom AS createur_prenom, u.nom AS createur_nom
                FROM chantiers c
                LEFT JOIN specialites s ON s.id = c.specialite_id
                JOIN coproprietees co ON co.id = c.residence_id
                LEFT JOIN assemblees_generales ag ON ag.id = c.ag_id
                LEFT JOIN users u ON u.id = c.created_by
                WHERE c.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createChantier(array $data): int {
        // Auto-flag necessite_ag selon seuil (sauf si forcé manuellement)
        $necessiteAg = !empty($data['necessite_ag_force']) ? (int)$data['necessite_ag_force'] : 0;
        if (!isset($data['necessite_ag_force']) || $data['necessite_ag_force'] === '') {
            $necessiteAg = (!empty($data['montant_estime']) && (float)$data['montant_estime'] > self::SEUIL_AG_HT) ? 1 : 0;
        }

        $sql = "INSERT INTO chantiers
                (residence_id, specialite_id, titre, description, categorie, phase, statut, priorite,
                 necessite_ag, ag_id, date_debut_prevue, date_fin_prevue, montant_estime, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'],
            !empty($data['specialite_id']) ? (int)$data['specialite_id'] : null,
            $data['titre'],
            $data['description'] ?: null,
            $data['categorie'] ?? 'autre',
            $data['phase']     ?? 'diagnostic',
            $data['statut']    ?? 'actif',
            $data['priorite']  ?? 'normale',
            $necessiteAg,
            !empty($data['ag_id']) ? (int)$data['ag_id'] : null,
            $data['date_debut_prevue'] ?: null,
            $data['date_fin_prevue']   ?: null,
            !empty($data['montant_estime']) ? (float)$data['montant_estime'] : null,
            $data['notes'] ?: null,
            $data['created_by'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateChantier(int $id, array $data): bool {
        // Recalcul auto necessite_ag si changement de montant (sauf si force manuelle dans data)
        $necessiteAg = $data['necessite_ag_force'] ?? null;
        if ($necessiteAg === null || $necessiteAg === '') {
            $necessiteAg = (!empty($data['montant_estime']) && (float)$data['montant_estime'] > self::SEUIL_AG_HT) ? 1 : 0;
        } else {
            $necessiteAg = (int)$necessiteAg;
        }

        $sql = "UPDATE chantiers SET
                  residence_id = ?, specialite_id = ?, titre = ?, description = ?, categorie = ?,
                  phase = ?, statut = ?, priorite = ?, necessite_ag = ?, ag_id = ?,
                  date_debut_prevue = ?, date_fin_prevue = ?, date_debut_reelle = ?, date_fin_reelle = ?,
                  montant_estime = ?, montant_engage = ?, montant_paye = ?, notes = ?
                WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['residence_id'],
            !empty($data['specialite_id']) ? (int)$data['specialite_id'] : null,
            $data['titre'],
            $data['description'] ?: null,
            $data['categorie'] ?? 'autre',
            $data['phase']     ?? 'diagnostic',
            $data['statut']    ?? 'actif',
            $data['priorite']  ?? 'normale',
            $necessiteAg,
            !empty($data['ag_id']) ? (int)$data['ag_id'] : null,
            $data['date_debut_prevue'] ?: null,
            $data['date_fin_prevue']   ?: null,
            $data['date_debut_reelle'] ?: null,
            $data['date_fin_reelle']   ?: null,
            !empty($data['montant_estime']) ? (float)$data['montant_estime'] : null,
            !empty($data['montant_engage']) ? (float)$data['montant_engage'] : 0,
            !empty($data['montant_paye'])   ? (float)$data['montant_paye']   : 0,
            $data['notes'] ?: null,
            $id,
        ]);
    }

    public function deleteChantier(int $id): bool {
        return $this->db->prepare("DELETE FROM chantiers WHERE id = ?")->execute([$id]);
    }

    public function transitionPhase(int $id, string $nouvellePhase): bool {
        if (!in_array($nouvellePhase, self::PHASES, true)) return false;
        $extra = '';
        if ($nouvellePhase === 'execution') $extra = ", date_debut_reelle = COALESCE(date_debut_reelle, CURDATE())";
        if ($nouvellePhase === 'cloture')   $extra = ", date_fin_reelle = COALESCE(date_fin_reelle, CURDATE()), statut = 'termine'";
        $sql = "UPDATE chantiers SET phase = ?$extra WHERE id = ?";
        return $this->db->prepare($sql)->execute([$nouvellePhase, $id]);
    }

    // ─── DEVIS ──────────────────────────────────────────────────

    public function getDevis(int $chantierId): array {
        $sql = "SELECT d.*, f.nom AS fournisseur_nom, f.email AS fournisseur_email
                FROM chantier_devis d
                JOIN fournisseurs f ON f.id = d.fournisseur_id
                WHERE d.chantier_id = ?
                ORDER BY d.statut = 'retenu' DESC, d.montant_ht ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$chantierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDevis(int $id): ?array {
        $sql = "SELECT d.*, f.nom AS fournisseur_nom, c.id AS chantier_id, c.titre AS chantier_titre
                FROM chantier_devis d
                JOIN fournisseurs f ON f.id = d.fournisseur_id
                JOIN chantiers c ON c.id = d.chantier_id
                WHERE d.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createDevis(array $data): int {
        $tva = (float)($data['tva_pourcentage'] ?? 20.00);
        $montantHt = (float)$data['montant_ht'];
        $montantTtc = round($montantHt * (1 + $tva / 100), 2);

        $sql = "INSERT INTO chantier_devis
                (chantier_id, fournisseur_id, reference, date_devis, date_validite, montant_ht,
                 tva_pourcentage, montant_ttc, delai_execution_jours, fichier_pdf, statut, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['chantier_id'],
            (int)$data['fournisseur_id'],
            $data['reference'] ?: null,
            $data['date_devis'],
            $data['date_validite'] ?: null,
            $montantHt,
            $tva,
            $montantTtc,
            !empty($data['delai_execution_jours']) ? (int)$data['delai_execution_jours'] : null,
            $data['fichier_pdf'] ?: null,
            $data['statut'] ?? 'recu',
            $data['notes'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function setDevisStatut(int $id, string $statut): bool {
        if (!in_array($statut, ['recu','analyse','retenu','refuse'], true)) return false;
        return $this->db->prepare("UPDATE chantier_devis SET statut = ? WHERE id = ?")->execute([$statut, $id]);
    }

    /**
     * Retient un devis : marque ce devis comme `retenu`, refuse les autres,
     * et met à jour montant_engage du chantier.
     */
    public function retenirDevis(int $devisId): bool {
        $devis = $this->findDevis($devisId);
        if (!$devis) return false;

        $this->db->beginTransaction();
        try {
            // Refuser les autres devis du même chantier
            $this->db->prepare("UPDATE chantier_devis SET statut = 'refuse' WHERE chantier_id = ? AND id != ?")
                ->execute([(int)$devis['chantier_id'], $devisId]);
            // Retenir celui-ci
            $this->db->prepare("UPDATE chantier_devis SET statut = 'retenu' WHERE id = ?")->execute([$devisId]);
            // Mettre à jour montant_engage du chantier
            $this->db->prepare("UPDATE chantiers SET montant_engage = ? WHERE id = ?")
                ->execute([(float)$devis['montant_ttc'], (int)$devis['chantier_id']]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteDevis(int $id): ?array {
        $devis = $this->findDevis($id);
        if (!$devis) return null;
        $this->db->prepare("DELETE FROM chantier_devis WHERE id = ?")->execute([$id]);
        return $devis;
    }

    // ─── JALONS ─────────────────────────────────────────────────

    public function getJalons(int $chantierId): array {
        $stmt = $this->db->prepare("SELECT * FROM chantier_jalons WHERE chantier_id = ? ORDER BY ordre, date_prevue");
        $stmt->execute([$chantierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createJalon(array $data): int {
        $sql = "INSERT INTO chantier_jalons (chantier_id, nom, description, date_prevue, date_realisee, pourcentage_avancement, ordre, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['chantier_id'],
            $data['nom'],
            $data['description'] ?: null,
            $data['date_prevue'] ?: null,
            $data['date_realisee'] ?: null,
            (int)($data['pourcentage_avancement'] ?? 0),
            (int)($data['ordre'] ?? 0),
            $data['notes'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateJalon(int $id, array $data): bool {
        $sql = "UPDATE chantier_jalons SET nom = ?, description = ?, date_prevue = ?, date_realisee = ?, pourcentage_avancement = ?, ordre = ?, notes = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['nom'],
            $data['description'] ?: null,
            $data['date_prevue'] ?: null,
            $data['date_realisee'] ?: null,
            (int)($data['pourcentage_avancement'] ?? 0),
            (int)($data['ordre'] ?? 0),
            $data['notes'] ?: null,
            $id,
        ]);
    }

    public function deleteJalon(int $id): bool {
        return $this->db->prepare("DELETE FROM chantier_jalons WHERE id = ?")->execute([$id]);
    }

    // ─── DOCUMENTS ──────────────────────────────────────────────

    public function getDocuments(int $chantierId, ?string $type = null): array {
        $sql = "SELECT d.*, u.prenom AS uploader_prenom, u.nom AS uploader_nom
                FROM chantier_documents d
                LEFT JOIN users u ON u.id = d.uploaded_by
                WHERE d.chantier_id = ?";
        $params = [$chantierId];
        if ($type) { $sql .= " AND d.type = ?"; $params[] = $type; }
        $sql .= " ORDER BY d.uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDocument(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM chantier_documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createDocument(array $data): int {
        $sql = "INSERT INTO chantier_documents (chantier_id, type, nom_fichier, chemin_stockage, mime_type, taille_octets, description, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            (int)$data['chantier_id'],
            $data['type'],
            $data['nom_fichier'],
            $data['chemin_stockage'],
            $data['mime_type'] ?? null,
            (int)($data['taille_octets'] ?? 0),
            $data['description'] ?: null,
            $data['uploaded_by'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteDocument(int $id): ?array {
        $doc = $this->findDocument($id);
        if (!$doc) return null;
        $this->db->prepare("DELETE FROM chantier_documents WHERE id = ?")->execute([$id]);
        return $doc;
    }

    // ─── RÉCEPTIONS + GARANTIES AUTO ────────────────────────────

    public function getReceptions(int $chantierId): array {
        $sql = "SELECT r.*, u.prenom AS signataire_prenom, u.nom AS signataire_nom
                FROM chantier_receptions r
                LEFT JOIN users u ON u.id = r.signe_par_id
                WHERE r.chantier_id = ?
                ORDER BY r.date_reception DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$chantierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une réception. Si pas de réserves OU pas de réception existante :
     * crée AUSSI les 3 garanties (parfait achèvement / biennale / décennale)
     * à partir de la date de réception.
     */
    public function createReception(array $data): int {
        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO chantier_receptions (chantier_id, date_reception, avec_reserves, reserves_description, pv_pdf, signe_par_id)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->prepare($sql)->execute([
                (int)$data['chantier_id'],
                $data['date_reception'],
                !empty($data['avec_reserves']) ? 1 : 0,
                $data['reserves_description'] ?: null,
                $data['pv_pdf'] ?: null,
                $data['signe_par_id'] ?: null,
            ]);
            $receptionId = (int)$this->db->lastInsertId();

            // Auto-création des garanties (sauf si déjà présentes)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM chantier_garanties WHERE chantier_id = ?");
            $stmt->execute([(int)$data['chantier_id']]);
            if ((int)$stmt->fetchColumn() === 0) {
                $this->creerGarantiesAuto((int)$data['chantier_id'], $data['date_reception'], $data['fournisseur_id'] ?? null);
            }

            // Transition phase → garantie
            $this->db->prepare("UPDATE chantiers SET phase = 'garantie' WHERE id = ?")->execute([(int)$data['chantier_id']]);

            $this->db->commit();
            return $receptionId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function leverReserves(int $receptionId, string $dateLevee): bool {
        return $this->db->prepare("UPDATE chantier_receptions SET reserves_levees = ? WHERE id = ?")
            ->execute([$dateLevee, $receptionId]);
    }

    private function creerGarantiesAuto(int $chantierId, string $dateDebut, ?int $fournisseurId = null): void {
        $sql = "INSERT INTO chantier_garanties (chantier_id, type, date_debut, date_fin, fournisseur_id, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        foreach (self::DUREES_GARANTIES as $type => $duree) {
            $dateFin = (new DateTime($dateDebut))->modify("+$duree years")->format('Y-m-d');
            $stmt->execute([$chantierId, $type, $dateDebut, $dateFin, $fournisseurId, 'Auto-créée à la réception']);
        }
    }

    // ─── GARANTIES ──────────────────────────────────────────────

    public function getGaranties(int $chantierId): array {
        $sql = "SELECT g.*, f.nom AS fournisseur_nom
                FROM chantier_garanties g
                LEFT JOIN fournisseurs f ON f.id = g.fournisseur_id
                WHERE g.chantier_id = ?
                ORDER BY g.date_fin";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$chantierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── LOTS IMPACTÉS / QUOTE-PART PROPRIÉTAIRES ──────────────

    public function getLotsImpactes(int $chantierId): array {
        $sql = "SELECT li.lot_id, li.quote_part_pourcentage,
                       l.numero_lot, l.type AS lot_type,
                       cg.coproprietaire_id AS proprietaire_id,
                       cop.nom AS proprietaire_nom, cop.prenom AS proprietaire_prenom
                FROM chantier_lots_impactes li
                JOIN lots l ON l.id = li.lot_id
                LEFT JOIN contrats_gestion cg ON cg.lot_id = l.id AND cg.statut = 'actif'
                LEFT JOIN coproprietaires cop ON cop.id = cg.coproprietaire_id
                WHERE li.chantier_id = ?
                ORDER BY l.numero_lot";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$chantierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remplace les lots impactés par la nouvelle liste.
     * @param array $lots [['lot_id' => N, 'quote_part_pourcentage' => 12.5], ...]
     */
    public function setLotsImpactes(int $chantierId, array $lots): bool {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM chantier_lots_impactes WHERE chantier_id = ?")->execute([$chantierId]);
            $stmt = $this->db->prepare("INSERT INTO chantier_lots_impactes (chantier_id, lot_id, quote_part_pourcentage) VALUES (?, ?, ?)");
            foreach ($lots as $l) {
                if (empty($l['lot_id'])) continue;
                $stmt->execute([$chantierId, (int)$l['lot_id'], (float)($l['quote_part_pourcentage'] ?? 0)]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Calcul agrégé : somme par propriétaire de la quote-part × montant_estime.
     */
    public function getQuotePartProprietaires(int $chantierId): array {
        $sql = "SELECT
                    cg.coproprietaire_id AS proprietaire_id,
                    cop.nom AS proprietaire_nom,
                    cop.prenom AS proprietaire_prenom,
                    SUM(li.quote_part_pourcentage) AS quote_part_totale,
                    GROUP_CONCAT(l.numero_lot ORDER BY l.numero_lot SEPARATOR ', ') AS lots
                FROM chantier_lots_impactes li
                JOIN lots l ON l.id = li.lot_id
                JOIN contrats_gestion cg ON cg.lot_id = l.id AND cg.statut = 'actif'
                JOIN coproprietaires cop ON cop.id = cg.coproprietaire_id
                WHERE li.chantier_id = ?
                GROUP BY cg.coproprietaire_id
                ORDER BY quote_part_totale DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$chantierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── HELPERS ────────────────────────────────────────────────

    /** Lots disponibles d'une résidence (pour sélecteur lots impactés) */
    public function getLotsResidence(int $residenceId): array {
        $stmt = $this->db->prepare("SELECT id, numero_lot, type FROM lots WHERE copropriete_id = ? ORDER BY numero_lot");
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** AGs disponibles d'une résidence (pour sélecteur AG) */
    public function getAGsResidence(int $residenceId): array {
        $stmt = $this->db->prepare("SELECT id, type, date_ag, statut FROM assemblees_generales WHERE copropriete_id = ? ORDER BY date_ag DESC LIMIT 20");
        $stmt->execute([$residenceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Stats dashboard chantiers */
    public function getStats(array $residencesIds, string $userRole): array {
        $sql = "SELECT
                  COUNT(*) AS total,
                  SUM(CASE WHEN phase NOT IN ('cloture','garantie') AND statut = 'actif' THEN 1 ELSE 0 END) AS en_cours,
                  SUM(CASE WHEN phase = 'garantie' THEN 1 ELSE 0 END) AS en_garantie,
                  SUM(CASE WHEN priorite = 'urgente' AND statut = 'actif' THEN 1 ELSE 0 END) AS urgentes,
                  SUM(CASE WHEN necessite_ag = 1 AND ag_id IS NULL THEN 1 ELSE 0 END) AS attente_ag,
                  COALESCE(SUM(montant_estime), 0) AS budget_total
                FROM chantiers WHERE 1=1";
        $params = [];
        if ($userRole !== 'admin' && !empty($residencesIds)) {
            $ph = implode(',', array_fill(0, count($residencesIds), '?'));
            $sql .= " AND residence_id IN ($ph)";
            array_push($params, ...$residencesIds);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'en_cours'=>0,'en_garantie'=>0,'urgentes'=>0,'attente_ag'=>0,'budget_total'=>0];
    }
}
