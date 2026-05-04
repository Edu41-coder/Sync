<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle Ecriture (comptabilité unifiée)
 * ====================================================================
 *
 * Source de vérité unique pour toutes les écritures comptables des modules.
 *
 * Les modules métier (jardinage, ménage, restauration, maintenance, paie, etc.)
 * ne créent plus d'écritures dans des tables dédiées : ils appellent
 * Ecriture::create() qui pousse dans `ecritures_comptables`.
 *
 * Architecture introduite par la migration 025 (Phase 0 du module Comptabilité).
 *
 * Usage typique depuis un autre model :
 *
 *   $eModel = new Ecriture();
 *   $eModel->create([
 *       'residence_id'           => $residenceId,
 *       'module_source'          => 'jardinage',
 *       'categorie'              => 'achat_fournisseur',
 *       'date_ecriture'          => '2026-05-04',
 *       'type_ecriture'          => 'depense',
 *       'montant_ht'             => 100.00,
 *       'taux_tva'               => 20.00,
 *       'montant_tva'            => 20.00,
 *       'montant_ttc'            => 120.00,
 *       'compte_comptable_id'    => 23,        // FK vers comptes_comptables (601, 602, etc.)
 *       'reference_externe_type' => 'commande_fournisseur',
 *       'reference_externe_id'   => 42,
 *       'imputation_type'        => 'espace_jardin',
 *       'imputation_id'          => 7,
 *       'libelle'                => 'Achat engrais NPK',
 *       'auto_genere'            => 1,
 *       'created_by'             => $userId,
 *   ]);
 */

class Ecriture extends Model {

    /**
     * Modules sources autorisés (alignés avec l'ENUM SQL).
     */
    public const MODULES = [
        'jardinage'      => 'Jardinage',
        'menage'         => 'Ménage',
        'restauration'   => 'Restauration',
        'maintenance'    => 'Maintenance',
        'loyer_proprio'  => 'Loyers propriétaires',
        'loyer_resident' => 'Loyers résidents',
        'services'       => 'Services résidents',
        'hote'           => 'Hôtes temporaires',
        'rh_paie'        => 'RH / Paie',
        'admin'          => 'Charges administratives',
        'sinistre'       => 'Sinistres',
        'autre'          => 'Autre',
    ];

    /**
     * Couleurs Bootstrap par module pour le dashboard.
     */
    public const MODULE_COLORS = [
        'jardinage'      => 'success',
        'menage'         => 'info',
        'restauration'   => 'warning',
        'maintenance'    => 'orange',
        'loyer_proprio'  => 'primary',
        'loyer_resident' => 'primary',
        'services'       => 'teal',
        'hote'           => 'secondary',
        'rh_paie'        => 'danger',
        'admin'          => 'dark',
        'sinistre'       => 'red',
        'autre'          => 'muted',
    ];

    /**
     * Valeurs autorisées pour `type_ecriture` et `statut`.
     */
    public const TYPES = ['recette', 'depense'];
    public const STATUTS = ['brouillon', 'validee', 'cloturee'];

    // ─────────────────────────────────────────────────────────────────
    //  CRÉATION
    // ─────────────────────────────────────────────────────────────────

    /**
     * Crée une nouvelle écriture comptable.
     *
     * @param array $data  Voir docblock du fichier pour la structure.
     * @return int  ID de l'écriture créée.
     * @throws InvalidArgumentException si données invalides.
     * @throws RuntimeException si insertion échoue.
     */
    public function create(array $data): int {
        $this->validateInput($data);

        // Calcul auto du montant TTC si non fourni
        if (!isset($data['montant_ttc']) || $data['montant_ttc'] === null) {
            $ht  = (float)($data['montant_ht'] ?? 0);
            $tva = (float)($data['montant_tva'] ?? 0);
            $data['montant_ttc'] = round($ht + $tva, 2);
        }

        // Lien automatique vers exercice ouvert si applicable
        if (empty($data['exercice_id']) && !empty($data['residence_id']) && !empty($data['date_ecriture'])) {
            $data['exercice_id'] = $this->findExerciceForDate(
                (int)$data['residence_id'],
                $data['date_ecriture']
            );
        }

        $sql = "INSERT INTO ecritures_comptables (
                    residence_id, exercice_id, module_source, categorie,
                    date_ecriture, type_ecriture,
                    montant_ht, taux_tva, montant_tva, montant_ttc,
                    compte_comptable_id,
                    reference_externe_type, reference_externe_id,
                    imputation_type, imputation_id,
                    libelle, notes, piece_justificative,
                    auto_genere, statut, created_by
                ) VALUES (?,?,?,?, ?,?, ?,?,?,?, ?, ?,?, ?,?, ?,?,?, ?,?,?)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                (int)$data['residence_id'],
                $data['exercice_id'] ?? null,
                $data['module_source'],
                $data['categorie'],
                $data['date_ecriture'],
                $data['type_ecriture'],
                (float)$data['montant_ht'],
                isset($data['taux_tva']) ? (float)$data['taux_tva'] : null,
                (float)($data['montant_tva'] ?? 0),
                (float)$data['montant_ttc'],
                $data['compte_comptable_id'] ?? null,
                $data['reference_externe_type'] ?? null,
                $data['reference_externe_id'] ?? null,
                $data['imputation_type'] ?? null,
                $data['imputation_id'] ?? null,
                trim((string)$data['libelle']),
                $data['notes'] ?? null,
                $data['piece_justificative'] ?? null,
                !empty($data['auto_genere']) ? 1 : 0,
                $data['statut'] ?? 'validee',
                $data['created_by'] ?? null,
            ]);
            $newId = (int)$this->db->lastInsertId();
            Logger::audit('ecriture_create', 'ecritures_comptables', $newId, [
                'module_source' => $data['module_source'],
                'type_ecriture' => $data['type_ecriture'],
                'montant_ttc'   => (float)$data['montant_ttc'],
                'libelle'       => mb_substr(trim((string)$data['libelle']), 0, 80),
                'residence_id'  => (int)$data['residence_id'],
                'auto_genere'   => !empty($data['auto_genere']) ? 1 : 0,
            ]);
            return $newId;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $data);
            throw new RuntimeException("Erreur création écriture : " . $e->getMessage());
        }
    }

    /**
     * Met à jour une écriture (uniquement si non clôturée).
     * Utilisé pour corrections par le comptable.
     */
    public function update(int $id, array $data): bool {
        $existing = $this->findById($id);
        if (!$existing) return false;
        if ($existing['statut'] === 'cloturee') {
            throw new RuntimeException("Écriture clôturée — non modifiable. Créer une contre-passation à la place.");
        }

        $sql = "UPDATE ecritures_comptables SET
                    date_ecriture = ?, type_ecriture = ?,
                    categorie = ?, libelle = ?, notes = ?,
                    montant_ht = ?, taux_tva = ?, montant_tva = ?, montant_ttc = ?,
                    compte_comptable_id = ?, statut = ?
                WHERE id = ? AND statut != 'cloturee'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['date_ecriture'] ?? $existing['date_ecriture'],
                $data['type_ecriture'] ?? $existing['type_ecriture'],
                $data['categorie']     ?? $existing['categorie'],
                trim((string)($data['libelle'] ?? $existing['libelle'])),
                $data['notes']         ?? $existing['notes'],
                isset($data['montant_ht'])  ? (float)$data['montant_ht']  : (float)$existing['montant_ht'],
                isset($data['taux_tva'])    ? (float)$data['taux_tva']    : ($existing['taux_tva'] !== null ? (float)$existing['taux_tva'] : null),
                isset($data['montant_tva']) ? (float)$data['montant_tva'] : (float)$existing['montant_tva'],
                isset($data['montant_ttc']) ? (float)$data['montant_ttc'] : (float)$existing['montant_ttc'],
                $data['compte_comptable_id'] ?? $existing['compte_comptable_id'],
                $data['statut']        ?? $existing['statut'],
                $id,
            ]);
            $changed = $stmt->rowCount() > 0;
            if ($changed) {
                Logger::audit('ecriture_update', 'ecritures_comptables', $id, [
                    'libelle_avant' => mb_substr((string)$existing['libelle'], 0, 80),
                    'montant_ttc_avant' => (float)$existing['montant_ttc'],
                    'montant_ttc_apres' => isset($data['montant_ttc']) ? (float)$data['montant_ttc'] : (float)$existing['montant_ttc'],
                    'statut_apres' => $data['statut'] ?? $existing['statut'],
                ]);
            }
            return $changed;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return false;
        }
    }

    /**
     * Suppression réelle uniquement pour brouillons. Sinon contre-passation obligatoire.
     * Méthode renommée (pas `delete`) pour ne pas écraser Model::delete($id).
     */
    public function deleteEcriture(int $id): bool {
        try {
            // Capturer un snapshot avant suppression pour l'audit
            $snapshot = $this->findById($id);
            $stmt = $this->db->prepare("DELETE FROM ecritures_comptables WHERE id = ? AND statut = 'brouillon'");
            $stmt->execute([$id]);
            $deleted = $stmt->rowCount() > 0;
            if ($deleted && $snapshot) {
                Logger::audit('ecriture_delete', 'ecritures_comptables', $id, [
                    'libelle' => mb_substr((string)$snapshot['libelle'], 0, 80),
                    'montant_ttc' => (float)$snapshot['montant_ttc'],
                    'module_source' => $snapshot['module_source'],
                ]);
            }
            return $deleted;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), '', [$id]);
            return false;
        }
    }

    /**
     * Crée une écriture de contre-passation (annule logiquement une écriture validée/clôturée).
     * Conserve la traçabilité comptable (PCG art. 410-1).
     */
    public function contrePasser(int $sourceId, int $userId, string $motif = ''): ?int {
        $source = $this->findById($sourceId);
        if (!$source) return null;

        // Inverse type recette/dépense
        $newType = $source['type_ecriture'] === 'recette' ? 'depense' : 'recette';

        $newId = $this->create([
            'residence_id'         => $source['residence_id'],
            'module_source'        => $source['module_source'],
            'categorie'            => $source['categorie'],
            'date_ecriture'        => date('Y-m-d'),
            'type_ecriture'        => $newType,
            'montant_ht'           => $source['montant_ht'],
            'taux_tva'             => $source['taux_tva'],
            'montant_tva'          => $source['montant_tva'],
            'montant_ttc'          => $source['montant_ttc'],
            'compte_comptable_id'  => $source['compte_comptable_id'],
            'reference_externe_type' => 'contre_passation',
            'reference_externe_id' => $sourceId,
            'libelle'              => 'CP — ' . $source['libelle'] . ($motif ? ' (' . $motif . ')' : ''),
            'auto_genere'          => 0,
            'created_by'           => $userId,
        ]);

        Logger::audit('ecriture_contre_passation', 'ecritures_comptables', $sourceId, [
            'source_id'      => $sourceId,
            'contre_passation_id' => $newId,
            'motif'          => $motif !== '' ? $motif : null,
            'montant_ttc'    => (float)$source['montant_ttc'],
        ], $userId);

        return $newId;
    }

    // ─────────────────────────────────────────────────────────────────
    //  LECTURE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Méthode renommée (pas `find`) pour ne pas écraser Model::find($id).
     * Retourne le row complet en array associatif (Model::find renvoie un objet via $this->table).
     */
    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ecritures_comptables WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), '', [$id]);
            return null;
        }
    }

    /**
     * Liste filtrée pour le dashboard ou la table détail.
     *
     * @param array $filters Clés possibles : residence_ids (array), modules (array),
     *                       date_min, date_max, type_ecriture, categorie, compte_comptable_id,
     *                       reference_externe_type/id, search (libelle), limit
     */
    public function findFiltered(array $filters): array {
        $sql = "SELECT e.*, c.nom AS residence_nom,
                       cc.numero_compte, cc.libelle AS compte_libelle
                FROM ecritures_comptables e
                LEFT JOIN coproprietees c ON c.id = e.residence_id
                LEFT JOIN comptes_comptables cc ON cc.id = e.compte_comptable_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['residence_ids']) && is_array($filters['residence_ids'])) {
            $ph = implode(',', array_fill(0, count($filters['residence_ids']), '?'));
            $sql .= " AND e.residence_id IN ($ph)";
            $params = array_merge($params, array_map('intval', $filters['residence_ids']));
        }
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $ph = implode(',', array_fill(0, count($filters['modules']), '?'));
            $sql .= " AND e.module_source IN ($ph)";
            $params = array_merge($params, $filters['modules']);
        }
        if (!empty($filters['date_min'])) { $sql .= " AND e.date_ecriture >= ?"; $params[] = $filters['date_min']; }
        if (!empty($filters['date_max'])) { $sql .= " AND e.date_ecriture <= ?"; $params[] = $filters['date_max']; }
        if (!empty($filters['type_ecriture']) && in_array($filters['type_ecriture'], self::TYPES, true)) {
            $sql .= " AND e.type_ecriture = ?"; $params[] = $filters['type_ecriture'];
        }
        if (!empty($filters['categorie'])) { $sql .= " AND e.categorie = ?"; $params[] = $filters['categorie']; }
        if (!empty($filters['compte_comptable_id'])) { $sql .= " AND e.compte_comptable_id = ?"; $params[] = (int)$filters['compte_comptable_id']; }
        if (!empty($filters['reference_externe_type'])) {
            $sql .= " AND e.reference_externe_type = ?"; $params[] = $filters['reference_externe_type'];
            if (!empty($filters['reference_externe_id'])) {
                $sql .= " AND e.reference_externe_id = ?"; $params[] = (int)$filters['reference_externe_id'];
            }
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (e.libelle LIKE ? OR e.notes LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY e.date_ecriture DESC, e.id DESC";
        if (!empty($filters['limit'])) { $sql .= " LIMIT " . (int)$filters['limit']; }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  AGRÉGATIONS DASHBOARD
    // ─────────────────────────────────────────────────────────────────

    /**
     * Totaux globaux pour une période + résidences.
     * Retourne ['recettes_ttc', 'depenses_ttc', 'resultat', 'nb_ecritures'].
     */
    public function getTotaux(array $residenceIds, string $dateMin, string $dateMax, ?array $modules = null): array {
        if (empty($residenceIds)) return ['recettes_ttc' => 0, 'depenses_ttc' => 0, 'resultat' => 0, 'nb_ecritures' => 0];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT
                    SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END) AS recettes_ttc,
                    SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END) AS depenses_ttc,
                    COUNT(*) AS nb_ecritures
                FROM ecritures_comptables
                WHERE residence_id IN ($resPh)
                  AND date_ecriture BETWEEN ? AND ?
                  AND statut != 'brouillon'";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);
        if ($modules) {
            $modPh = implode(',', array_fill(0, count($modules), '?'));
            $sql .= " AND module_source IN ($modPh)";
            $params = array_merge($params, $modules);
        }
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $r = (float)($row['recettes_ttc'] ?? 0);
            $d = (float)($row['depenses_ttc'] ?? 0);
            return [
                'recettes_ttc' => $r,
                'depenses_ttc' => $d,
                'resultat'     => round($r - $d, 2),
                'nb_ecritures' => (int)($row['nb_ecritures'] ?? 0),
            ];
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); return ['recettes_ttc' => 0, 'depenses_ttc' => 0, 'resultat' => 0, 'nb_ecritures' => 0]; }
    }

    /**
     * Synthèse mensuelle pour graphique : 12 mois × (recettes, dépenses).
     */
    public function getSyntheseMensuelle(array $residenceIds, int $annee, ?array $modules = null): array {
        if (empty($residenceIds)) return [];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT MONTH(date_ecriture) AS mois,
                       SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END) AS recettes_ttc,
                       SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END) AS depenses_ttc
                FROM ecritures_comptables
                WHERE residence_id IN ($resPh) AND YEAR(date_ecriture) = ?
                  AND statut != 'brouillon'";
        $params = array_merge(array_map('intval', $residenceIds), [$annee]);
        if ($modules) {
            $modPh = implode(',', array_fill(0, count($modules), '?'));
            $sql .= " AND module_source IN ($modPh)";
            $params = array_merge($params, $modules);
        }
        $sql .= " GROUP BY MONTH(date_ecriture) ORDER BY mois";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); return []; }
    }

    /**
     * Ventilation par module sur une période.
     */
    public function getVentilationParModule(array $residenceIds, string $dateMin, string $dateMax): array {
        if (empty($residenceIds)) return [];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT module_source,
                       SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END) AS recettes,
                       SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END) AS depenses,
                       COUNT(*) AS nb
                FROM ecritures_comptables
                WHERE residence_id IN ($resPh)
                  AND date_ecriture BETWEEN ? AND ?
                  AND statut != 'brouillon'
                GROUP BY module_source
                ORDER BY SUM(montant_ttc) DESC";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); return []; }
    }

    /**
     * Ventilation par résidence (pour drill-down admin).
     */
    public function getVentilationParResidence(array $residenceIds, string $dateMin, string $dateMax, ?array $modules = null): array {
        if (empty($residenceIds)) return [];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT e.residence_id, c.nom AS residence_nom,
                       SUM(CASE WHEN e.type_ecriture='recette' THEN e.montant_ttc ELSE 0 END) AS recettes,
                       SUM(CASE WHEN e.type_ecriture='depense' THEN e.montant_ttc ELSE 0 END) AS depenses
                FROM ecritures_comptables e
                JOIN coproprietees c ON c.id = e.residence_id
                WHERE e.residence_id IN ($resPh)
                  AND e.date_ecriture BETWEEN ? AND ?
                  AND e.statut != 'brouillon'";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);
        if ($modules) {
            $modPh = implode(',', array_fill(0, count($modules), '?'));
            $sql .= " AND e.module_source IN ($modPh)";
            $params = array_merge($params, $modules);
        }
        $sql .= " GROUP BY e.residence_id, c.nom ORDER BY SUM(e.montant_ttc) DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); return []; }
    }

    /**
     * Lecture spécifique pour exports comptables (FEC, CSV Excel, Cegid).
     *
     * Retourne toutes les colonnes nécessaires aux formats normalisés :
     * - numéro et libellé compte comptable (FK comptes_comptables)
     * - nom résidence (pour journal/auxiliaire)
     * - exercice associé (annee + statut)
     * - dates brutes (date_ecriture conservée en YYYY-MM-DD pour formatage côté export)
     *
     * Exclut les brouillons par défaut (FEC = écritures validées/clôturées seulement,
     * obligation art. L.47 A-I LPF + art. A.47 A-1).
     */
    public function getEcrituresPourExport(array $filters): array {
        $sql = "SELECT e.id, e.residence_id, e.exercice_id, e.module_source, e.categorie,
                       e.date_ecriture, e.type_ecriture,
                       e.montant_ht, e.taux_tva, e.montant_tva, e.montant_ttc,
                       e.compte_comptable_id, e.reference_externe_type, e.reference_externe_id,
                       e.libelle, e.notes, e.piece_justificative, e.statut, e.created_at,
                       cc.numero_compte, cc.libelle AS compte_libelle, cc.type AS compte_type,
                       c.nom AS residence_nom,
                       ex.annee AS exercice_annee, ex.statut AS exercice_statut
                FROM ecritures_comptables e
                LEFT JOIN comptes_comptables cc ON cc.id = e.compte_comptable_id
                LEFT JOIN coproprietees c ON c.id = e.residence_id
                LEFT JOIN exercices_comptables ex ON ex.id = e.exercice_id
                WHERE e.statut != 'brouillon'";
        $params = [];

        if (!empty($filters['residence_ids']) && is_array($filters['residence_ids'])) {
            $ph = implode(',', array_fill(0, count($filters['residence_ids']), '?'));
            $sql .= " AND e.residence_id IN ($ph)";
            $params = array_merge($params, array_map('intval', $filters['residence_ids']));
        }
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $ph = implode(',', array_fill(0, count($filters['modules']), '?'));
            $sql .= " AND e.module_source IN ($ph)";
            $params = array_merge($params, $filters['modules']);
        }
        if (!empty($filters['date_min'])) { $sql .= " AND e.date_ecriture >= ?"; $params[] = $filters['date_min']; }
        if (!empty($filters['date_max'])) { $sql .= " AND e.date_ecriture <= ?"; $params[] = $filters['date_max']; }
        if (!empty($filters['exercice_id'])) { $sql .= " AND e.exercice_id = ?"; $params[] = (int)$filters['exercice_id']; }

        $sql .= " ORDER BY e.date_ecriture ASC, e.id ASC";

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
     * Détail TVA pour préparation déclaration CA3/CA12.
     *
     * Retourne un tableau structuré avec ventilation par taux ET par sens
     * (collectée vs déductible) ET totaux globaux. Plus granulaire que
     * getRecapTva() qui ne donnait qu'un tableau plat par (taux × type).
     *
     * Structure retournée :
     *   [
     *     'ca_ht'              => ['20' => 1000, '10' => 500, '5.5' => 0, '2.1' => 0, 'exonere' => 0],
     *     'tva_collectee'      => ['20' => 200,  '10' => 50,  '5.5' => 0, '2.1' => 0, 'total' => 250],
     *     'tva_deductible'     => ['biens_services' => 100, 'immobilisations' => 0, 'total' => 100],
     *     'tva_a_payer'        => 150,  // si > 0
     *     'credit_a_reporter'  => 0,    // si tva_collectee < tva_deductible
     *     'nb_ecritures'       => 12,
     *     'achats_ht_total'    => 500,  // dépenses HT (informatif)
     *   ]
     *
     * Note règle PCG : pas de gestion fine immobilisations (compte 21x) en pilote
     * — toutes les dépenses sont classées en "biens et services" (ligne 19 CA3).
     */
    public function getDetailTva(array $residenceIds, string $dateMin, string $dateMax): array {
        $defaults = [
            'ca_ht'              => ['20' => 0.0, '10' => 0.0, '5.5' => 0.0, '2.1' => 0.0, 'exonere' => 0.0],
            'tva_collectee'      => ['20' => 0.0, '10' => 0.0, '5.5' => 0.0, '2.1' => 0.0, 'total' => 0.0],
            'tva_deductible'     => ['biens_services' => 0.0, 'immobilisations' => 0.0, 'total' => 0.0],
            'tva_a_payer'        => 0.0,
            'credit_a_reporter'  => 0.0,
            'nb_ecritures'       => 0,
            'achats_ht_total'    => 0.0,
        ];
        if (empty($residenceIds)) return $defaults;

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        // Récupère chaque écriture pour pouvoir inférer le taux TVA quand il est NULL
        // (legacy : les controllers modules n'ont pas toujours rempli taux_tva avant Phase 6)
        $sql = "SELECT id, taux_tva, type_ecriture, montant_ht, montant_tva
                FROM ecritures_comptables
                WHERE residence_id IN ($resPh)
                  AND date_ecriture BETWEEN ? AND ?
                  AND statut != 'brouillon'";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return $defaults;
        }

        $result = $defaults;
        foreach ($rows as $row) {
            $taux  = $row['taux_tva'];
            $type  = $row['type_ecriture'];
            $ht    = (float)$row['montant_ht'];
            $tva   = (float)$row['montant_tva'];

            $result['nb_ecritures']++;

            // Inférence du taux quand il est NULL mais TVA > 0 (cas legacy)
            // Ratio TVA/HT arrondi au demi-pourcent puis snap sur 20 / 10 / 5.5 / 2.1
            if ($taux === null && $tva > 0 && $ht > 0) {
                $ratio = ($tva / $ht) * 100.0;
                if (abs($ratio - 20.0) < 0.5)      { $taux = 20.0; }
                elseif (abs($ratio - 10.0) < 0.5)  { $taux = 10.0; }
                elseif (abs($ratio - 5.5) < 0.3)   { $taux = 5.5; }
                elseif (abs($ratio - 2.1) < 0.2)   { $taux = 2.1; }
            }

            // Bucket par taux (chaîne pour préserver "5.5"). NULL ou 0 = exonéré.
            if ($taux === null || (float)$taux === 0.0) {
                $bucket = 'exonere';
            } else {
                $bucket = rtrim(rtrim(number_format((float)$taux, 2, '.', ''), '0'), '.');
                if (!in_array($bucket, ['20', '10', '5.5', '2.1'], true)) {
                    $bucket = 'exonere';
                }
            }

            if ($type === 'recette') {
                $result['ca_ht'][$bucket] += round($ht, 2);
                if ($bucket !== 'exonere') {
                    $result['tva_collectee'][$bucket] += round($tva, 2);
                }
            } else { // depense
                $result['achats_ht_total']                  += round($ht, 2);
                $result['tva_deductible']['biens_services'] += round($tva, 2);
            }
        }

        // Totaux et solde
        $result['tva_collectee']['total']  = round(array_sum(array_diff_key($result['tva_collectee'], ['total' => 0])), 2);
        $result['tva_deductible']['total'] = round($result['tva_deductible']['biens_services'] + $result['tva_deductible']['immobilisations'], 2);

        $solde = round($result['tva_collectee']['total'] - $result['tva_deductible']['total'], 2);
        if ($solde >= 0) {
            $result['tva_a_payer']       = $solde;
            $result['credit_a_reporter'] = 0.0;
        } else {
            $result['tva_a_payer']       = 0.0;
            $result['credit_a_reporter'] = abs($solde);
        }

        return $result;
    }

    /**
     * TVA collectée vs déductible par taux pour la période (sert à CA3/CA12).
     */
    public function getRecapTva(array $residenceIds, string $dateMin, string $dateMax): array {
        if (empty($residenceIds)) return [];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT taux_tva, type_ecriture,
                       SUM(montant_ht) AS total_ht,
                       SUM(montant_tva) AS total_tva
                FROM ecritures_comptables
                WHERE residence_id IN ($resPh)
                  AND date_ecriture BETWEEN ? AND ?
                  AND taux_tva IS NOT NULL
                  AND statut != 'brouillon'
                GROUP BY taux_tva, type_ecriture
                ORDER BY taux_tva, type_ecriture";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); return []; }
    }

    // ─────────────────────────────────────────────────────────────────
    //  BALANCE / GRAND LIVRE / BILAN / SIG (Phase 9)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Balance comptable : agrégation par compte sur la période, avec totaux
     * débit/crédit/solde et libellé du compte. Convention :
     *   - recette = crédit (comptes 7xx)
     *   - dépense = débit  (comptes 6xx)
     * Les écritures sans compte affecté sont rangées dans un compte virtuel "TBD".
     */
    public function getBalance(array $residenceIds, string $dateMin, string $dateMax): array {
        if (empty($residenceIds)) return [];

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT
                    COALESCE(cc.id, 0) AS compte_id,
                    COALESCE(cc.numero_compte, 'TBD') AS numero_compte,
                    COALESCE(cc.libelle, '(Compte non affecté)') AS libelle,
                    COALESCE(cc.type, 'autre') AS type,
                    COUNT(*) AS nb,
                    SUM(CASE WHEN e.type_ecriture='depense' THEN e.montant_ttc ELSE 0 END) AS total_debit,
                    SUM(CASE WHEN e.type_ecriture='recette' THEN e.montant_ttc ELSE 0 END) AS total_credit
                FROM ecritures_comptables e
                LEFT JOIN comptes_comptables cc ON cc.id = e.compte_comptable_id
                WHERE e.residence_id IN ($resPh)
                  AND e.date_ecriture BETWEEN ? AND ?
                  AND e.statut != 'brouillon'
                GROUP BY cc.id, cc.numero_compte, cc.libelle, cc.type
                ORDER BY cc.numero_compte ASC";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $debit  = (float)$r['total_debit'];
                $credit = (float)$r['total_credit'];
                // Solde signé : positif = solde créditeur, négatif = solde débiteur
                $r['solde'] = round($credit - $debit, 2);
                $r['total_debit']  = round($debit, 2);
                $r['total_credit'] = round($credit, 2);
            }
            return $rows;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }

    /**
     * Grand livre d'un compte : toutes les écritures détaillées d'un compte donné
     * sur la période, avec solde progressif.
     */
    public function getGrandLivre(array $residenceIds, ?int $compteId, string $dateMin, string $dateMax): array {
        if (empty($residenceIds)) return [];

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT e.id, e.date_ecriture, e.type_ecriture, e.libelle, e.module_source,
                       e.montant_ht, e.montant_tva, e.montant_ttc,
                       e.piece_justificative, e.statut,
                       c.nom AS residence_nom,
                       cc.numero_compte, cc.libelle AS compte_libelle
                FROM ecritures_comptables e
                LEFT JOIN comptes_comptables cc ON cc.id = e.compte_comptable_id
                LEFT JOIN coproprietees c ON c.id = e.residence_id
                WHERE e.residence_id IN ($resPh)
                  AND e.date_ecriture BETWEEN ? AND ?
                  AND e.statut != 'brouillon'";
        $params = array_merge(array_map('intval', $residenceIds), [$dateMin, $dateMax]);

        if ($compteId !== null) {
            if ($compteId === 0) {
                $sql .= " AND e.compte_comptable_id IS NULL";
            } else {
                $sql .= " AND e.compte_comptable_id = ?";
                $params[] = $compteId;
            }
        }

        $sql .= " ORDER BY e.date_ecriture ASC, e.id ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Solde progressif
            $solde = 0.0;
            foreach ($rows as &$r) {
                $debit  = $r['type_ecriture'] === 'depense' ? (float)$r['montant_ttc'] : 0.0;
                $credit = $r['type_ecriture'] === 'recette' ? (float)$r['montant_ttc'] : 0.0;
                $solde += $credit - $debit;
                $r['debit']             = round($debit, 2);
                $r['credit']            = round($credit, 2);
                $r['solde_progressif']  = round($solde, 2);
            }
            return $rows;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }

    /**
     * Bilan simplifié actif/passif sur un exercice donné.
     * Vue agrégée par grandes masses (pour pilote — pas de compteur amortissement).
     */
    public function getBilan(array $residenceIds, string $dateMin, string $dateMax): array {
        $balance = $this->getBalance($residenceIds, $dateMin, $dateMax);
        if (empty($balance)) {
            return ['actif' => [], 'passif' => [], 'total_actif' => 0.0, 'total_passif' => 0.0, 'resultat_net' => 0.0];
        }

        $actif = ['immobilisations' => 0.0, 'creances_tiers' => 0.0, 'tva_deductible' => 0.0, 'tresorerie' => 0.0];
        $passif = ['capitaux_propres' => 0.0, 'dettes_fournisseurs' => 0.0, 'dettes_personnel' => 0.0, 'dettes_sociales_fiscales' => 0.0, 'tva_collectee' => 0.0];

        $totalCharges = 0.0;
        $totalProduits = 0.0;

        foreach ($balance as $row) {
            $num = (string)$row['numero_compte'];
            $sgn = (string)$row['type'];
            $debit = (float)$row['total_debit'];
            $credit = (float)$row['total_credit'];

            // Comptabilisation par classe PCG (1er chiffre du numéro de compte)
            $classe = $num !== 'TBD' ? substr($num, 0, 1) : '';

            if ($classe === '6') {
                // Charges → contribuent au résultat
                $totalCharges += $debit;
            } elseif ($classe === '7') {
                // Produits → contribuent au résultat
                $totalProduits += $credit;
            } elseif ($classe === '1') {
                // Capitaux (passif)
                $passif['capitaux_propres'] += ($credit - $debit);
            } elseif ($classe === '2') {
                $actif['immobilisations'] += ($debit - $credit);
            } elseif ($classe === '4') {
                // Tiers / TVA — discrimination par numéro de compte
                if (str_starts_with($num, '401')) {
                    $passif['dettes_fournisseurs'] += ($credit - $debit);
                } elseif (str_starts_with($num, '411') || str_starts_with($num, '450') || str_starts_with($num, '455')) {
                    $actif['creances_tiers'] += ($debit - $credit);
                } elseif (str_starts_with($num, '421')) {
                    $passif['dettes_personnel'] += ($credit - $debit);
                } elseif (str_starts_with($num, '43')) {
                    $passif['dettes_sociales_fiscales'] += ($credit - $debit);
                } elseif (str_starts_with($num, '44566')) {
                    $actif['tva_deductible'] += ($debit - $credit);
                } elseif (str_starts_with($num, '44571')) {
                    $passif['tva_collectee'] += ($credit - $debit);
                } elseif ($sgn === 'actif') {
                    $actif['creances_tiers'] += ($debit - $credit);
                } else {
                    $passif['dettes_fournisseurs'] += ($credit - $debit);
                }
            } elseif ($classe === '5') {
                // Trésorerie
                $actif['tresorerie'] += ($debit - $credit);
            }
        }

        $resultatNet = round($totalProduits - $totalCharges, 2);
        // Le résultat impacte les capitaux propres (signe selon bénéfice/perte)
        $passif['capitaux_propres'] += $resultatNet;

        // Arrondi final + retrait des montants nuls
        foreach ($actif  as $k => $v) $actif[$k]  = round($v, 2);
        foreach ($passif as $k => $v) $passif[$k] = round($v, 2);

        $totalActif  = array_sum($actif);
        $totalPassif = array_sum($passif);

        return [
            'actif'         => $actif,
            'passif'        => $passif,
            'total_actif'   => round($totalActif, 2),
            'total_passif'  => round($totalPassif, 2),
            'ecart'         => round($totalActif - $totalPassif, 2),
            'resultat_net'  => $resultatNet,
            'total_charges' => round($totalCharges, 2),
            'total_produits'=> round($totalProduits, 2),
        ];
    }

    /**
     * Soldes Intermédiaires de Gestion (SIG) — cascade comptable normalisée.
     *
     * Cascade :
     *   Production de l'exercice = produits classe 7 (sauf exceptionnel 758, 701)
     *   Consommations en provenance des tiers = charges externes (60x sauf 64x, 61x, 62x)
     *   Valeur ajoutée = Production - Consommations
     *   EBE = VA - charges de personnel (64x) - impôts/taxes (63x)
     *   Résultat d'exploitation = EBE (pas d'amortissement géré pilote)
     *   Résultat net = Résultat exploitation + produits exceptionnels - charges exceptionnelles
     */
    public function getSIG(array $residenceIds, string $dateMin, string $dateMax): array {
        $balance = $this->getBalance($residenceIds, $dateMin, $dateMax);
        if (empty($balance)) {
            return [
                'production' => 0.0, 'consommations' => 0.0, 'valeur_ajoutee' => 0.0,
                'charges_personnel' => 0.0, 'impots_taxes' => 0.0, 'ebe' => 0.0,
                'resultat_exploitation' => 0.0, 'produits_exceptionnels' => 0.0,
                'charges_exceptionnelles' => 0.0, 'resultat_net' => 0.0,
            ];
        }

        $production           = 0.0;
        $consommations        = 0.0;
        $chargesPersonnel     = 0.0;
        $impotsTaxes          = 0.0;
        $produitsExcept       = 0.0;
        $chargesExcept        = 0.0;

        foreach ($balance as $row) {
            $num    = (string)$row['numero_compte'];
            $debit  = (float)$row['total_debit'];
            $credit = (float)$row['total_credit'];
            if ($num === 'TBD') continue;

            // Produits exceptionnels
            if (str_starts_with($num, '701') || str_starts_with($num, '758')) {
                $produitsExcept += $credit;
            }
            // Production : autres produits classe 7
            elseif (str_starts_with($num, '7')) {
                $production += $credit;
            }
            // Charges externes (60x, 61x, 62x — hors 63 et 64)
            elseif (str_starts_with($num, '60') || str_starts_with($num, '61') || str_starts_with($num, '62')) {
                $consommations += $debit;
            }
            // Impôts et taxes (63x)
            elseif (str_starts_with($num, '63')) {
                $impotsTaxes += $debit;
            }
            // Charges de personnel (64x)
            elseif (str_starts_with($num, '64')) {
                $chargesPersonnel += $debit;
            }
            // Charges exceptionnelles (67x)
            elseif (str_starts_with($num, '67')) {
                $chargesExcept += $debit;
            }
        }

        $va    = $production - $consommations;
        $ebe   = $va - $chargesPersonnel - $impotsTaxes;
        $rExp  = $ebe; // pas d'amortissement géré
        $rNet  = $rExp + $produitsExcept - $chargesExcept;

        return [
            'production'              => round($production, 2),
            'consommations'           => round($consommations, 2),
            'valeur_ajoutee'          => round($va, 2),
            'charges_personnel'       => round($chargesPersonnel, 2),
            'impots_taxes'            => round($impotsTaxes, 2),
            'ebe'                     => round($ebe, 2),
            'resultat_exploitation'   => round($rExp, 2),
            'produits_exceptionnels'  => round($produitsExcept, 2),
            'charges_exceptionnelles' => round($chargesExcept, 2),
            'resultat_net'            => round($rNet, 2),
        ];
    }

    /**
     * Liste des comptes du PCG (utile pour les sélecteurs).
     */
    public function getComptesActifs(): array {
        try {
            $stmt = $this->db->query("SELECT id, numero_compte, libelle, type, code_module
                                      FROM comptes_comptables
                                      WHERE actif = 1
                                      ORDER BY numero_compte ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), '');
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  VALIDATION
    // ─────────────────────────────────────────────────────────────────

    private function validateInput(array $data): void {
        $required = ['residence_id', 'module_source', 'categorie', 'date_ecriture', 'type_ecriture', 'montant_ht', 'libelle'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                throw new InvalidArgumentException("Champ obligatoire manquant : $field");
            }
        }
        if (!isset(self::MODULES[$data['module_source']])) {
            throw new InvalidArgumentException("module_source invalide : " . $data['module_source']);
        }
        if (!in_array($data['type_ecriture'], self::TYPES, true)) {
            throw new InvalidArgumentException("type_ecriture invalide : " . $data['type_ecriture']);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data['date_ecriture'])) {
            throw new InvalidArgumentException("date_ecriture invalide (format attendu : YYYY-MM-DD)");
        }
        if ((float)$data['montant_ht'] < 0) {
            throw new InvalidArgumentException("montant_ht ne peut pas être négatif (utiliser type_ecriture pour signe)");
        }
    }

    /**
     * Trouve l'exercice ouvert qui couvre une date donnée pour une résidence.
     */
    private function findExerciceForDate(int $residenceId, string $date): ?int {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM exercices_comptables
                 WHERE copropriete_id = ? AND ? BETWEEN date_debut AND date_fin
                 ORDER BY statut = 'ouvert' DESC, annee DESC LIMIT 1"
            );
            $stmt->execute([$residenceId, $date]);
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), '', [$residenceId, $date]);
            return null;
        }
    }

    // logError() hérité du parent Model (signature : $message, $sql='', $params=[])
}
