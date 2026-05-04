<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle Rapprochement bancaire (Phase 10)
 * ====================================================================
 *
 * Importe un relevé bancaire CSV (format français standard) et permet de
 * rapprocher les opérations bancaires avec les écritures comptables
 * (`ecritures_comptables`).
 *
 * ⚠️ PILOTE VITRINE : le matching est heuristique (score basé sur date +
 * montant + similarité libellé). Aucun rapprochement automatique n'est
 * persisté sans validation manuelle.
 *
 * Format CSV attendu (flexible) :
 *   - Séparateur : ; (France) ou , (export US)
 *   - Encodage : UTF-8 (avec ou sans BOM) ou ISO-8859-1
 *   - Colonnes obligatoires : date, libellé, montant (ou débit + crédit séparés)
 *   - Format date : DD/MM/YYYY ou YYYY-MM-DD
 */

class RapprochementBancaire extends Model {

    /** Score minimum pour considérer un match comme suggéré. */
    public const MATCH_SCORE_SUGGEST = 40;

    /** Score minimum pour un match auto pré-sélectionné (toujours validé manuellement). */
    public const MATCH_SCORE_AUTO    = 70;

    /** Tolérance maximale en jours entre opération bancaire et écriture comptable. */
    public const MATCH_DAYS_TOLERANCE = 14;

    /**
     * Parse un CSV bancaire et retourne la liste des opérations.
     *
     * @param string $filePath  Chemin absolu du fichier CSV
     * @return array            Liste de [date_operation, libelle, montant, reference?]
     * @throws RuntimeException si le fichier est illisible ou mal formé
     */
    public function parseCsv(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("Fichier CSV introuvable ou non lisible : $filePath");
        }

        $content = file_get_contents($filePath);
        if ($content === false || $content === '') {
            throw new RuntimeException("Fichier CSV vide.");
        }

        // Strip BOM UTF-8 + détection encodage
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        } elseif (!mb_check_encoding($content, 'UTF-8')) {
            // Probable ISO-8859-1
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        // Séparateur : compter ; vs , sur les 5 premières lignes
        $sample = substr($content, 0, 2000);
        $delim  = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';

        // Découpage lignes (gère \r\n, \r, \n)
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        if (count($lines) < 2) {
            throw new RuntimeException("CSV trop court : au moins une ligne d'en-tête + une opération attendues.");
        }

        // Parse en-tête
        $headerRaw = str_getcsv((string)array_shift($lines), $delim, '"', "\\");
        $header    = array_map(function($h) {
            return $this->normalizeHeader((string)$h);
        }, $headerRaw);

        $idxDate    = $this->findHeaderIdx($header, ['date', 'date_op', 'date_operation', 'date_op.', 'date op']);
        $idxLib     = $this->findHeaderIdx($header, ['libelle', 'libellé', 'description', 'detail', 'objet', 'communication']);
        $idxMontant = $this->findHeaderIdx($header, ['montant', 'somme', 'amount']);
        $idxDebit   = $this->findHeaderIdx($header, ['debit', 'débit']);
        $idxCredit  = $this->findHeaderIdx($header, ['credit', 'crédit']);
        $idxRef     = $this->findHeaderIdx($header, ['reference', 'référence', 'ref', 'numero']);

        if ($idxDate === null || $idxLib === null) {
            throw new RuntimeException("CSV invalide : colonnes 'date' et 'libellé' obligatoires. En-tête détectée : " . implode(', ', $header));
        }
        if ($idxMontant === null && ($idxDebit === null || $idxCredit === null)) {
            throw new RuntimeException("CSV invalide : il faut soit une colonne 'montant', soit deux colonnes 'débit' + 'crédit'.");
        }

        $operations = [];
        $lineNum = 1;
        foreach ($lines as $line) {
            $lineNum++;
            $line = trim($line);
            if ($line === '') continue;

            $row = str_getcsv($line, $delim, '"', "\\");
            if (count($row) < 2) continue;

            $dateRaw = $row[$idxDate] ?? '';
            $date    = $this->parseDate(trim($dateRaw));
            if ($date === null) continue; // ignore les lignes avec date invalide

            $libelle = trim((string)($row[$idxLib] ?? ''));
            if ($libelle === '') continue;

            $montant = 0.0;
            if ($idxMontant !== null) {
                $montant = $this->parseMontant((string)($row[$idxMontant] ?? '0'));
            } else {
                $debit  = $this->parseMontant((string)($row[$idxDebit]  ?? '0'));
                $credit = $this->parseMontant((string)($row[$idxCredit] ?? '0'));
                // Convention : crédit positif (entrée), débit négatif (sortie)
                $montant = abs($credit) - abs($debit);
            }

            // Ignorer les lignes à montant 0
            if ($montant == 0.0) continue;

            $reference = $idxRef !== null ? trim((string)($row[$idxRef] ?? '')) : null;

            $operations[] = [
                'date_operation' => $date,
                'libelle'        => mb_substr($libelle, 0, 500),
                'montant'        => round($montant, 2),
                'reference'      => $reference !== '' ? mb_substr((string)$reference, 0, 100) : null,
            ];
        }

        if (empty($operations)) {
            throw new RuntimeException("Aucune opération valide trouvée dans le CSV.");
        }

        return $operations;
    }

    /**
     * Crée un import + insère toutes les opérations en une transaction.
     */
    public function createImport(int $residenceId, string $nomFichier, ?string $cheminStockage, array $operations, ?int $userId = null, ?string $notes = null): int {
        if (empty($operations)) {
            throw new InvalidArgumentException("Aucune opération à importer.");
        }

        $dates = array_column($operations, 'date_operation');
        sort($dates);
        $periodeDebut = $dates[0] ?? null;
        $periodeFin   = end($dates) ?: null;

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO bank_imports
                (residence_id, nom_fichier, chemin_stockage, nb_operations, periode_debut, periode_fin, notes, imported_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $residenceId,
                mb_substr($nomFichier, 0, 255),
                $cheminStockage,
                count($operations),
                $periodeDebut,
                $periodeFin,
                $notes,
                $userId,
            ]);
            $importId = (int)$this->db->lastInsertId();

            $stmtOp = $this->db->prepare("INSERT INTO bank_operations
                (import_id, residence_id, date_operation, libelle, montant, reference, statut)
                VALUES (?, ?, ?, ?, ?, ?, 'non_rapprochee')");
            foreach ($operations as $op) {
                $stmtOp->execute([
                    $importId,
                    $residenceId,
                    $op['date_operation'],
                    $op['libelle'],
                    $op['montant'],
                    $op['reference'] ?? null,
                ]);
            }

            $this->db->commit();
            Logger::audit('bank_import_create', 'bank_imports', $importId, [
                'residence_id' => $residenceId,
                'fichier'      => mb_substr($nomFichier, 0, 100),
                'nb_operations' => count($operations),
                'periode'      => ($periodeDebut ?? '?') . ' → ' . ($periodeFin ?? '?'),
            ], $userId);
            return $importId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Liste les imports d'une résidence.
     */
    public function listImports(array $residenceIds): array {
        if (empty($residenceIds)) return [];

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $stmt = $this->db->prepare("SELECT i.*, c.nom AS residence_nom, u.username AS imported_by_username,
                (SELECT COUNT(*) FROM bank_operations WHERE import_id = i.id AND statut='rapprochee') AS nb_rapp
                FROM bank_imports i
                LEFT JOIN coproprietees c ON c.id = i.residence_id
                LEFT JOIN users u ON u.id = i.imported_by
                WHERE i.residence_id IN ($resPh)
                ORDER BY i.imported_at DESC");
        $stmt->execute(array_map('intval', $residenceIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un import par ID avec détails.
     */
    public function findImport(int $id): ?array {
        $stmt = $this->db->prepare("SELECT i.*, c.nom AS residence_nom, u.username AS imported_by_username
                FROM bank_imports i
                LEFT JOIN coproprietees c ON c.id = i.residence_id
                LEFT JOIN users u ON u.id = i.imported_by
                WHERE i.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Liste les opérations d'un import avec leur écriture rapprochée.
     */
    public function listOperations(int $importId, ?string $statut = null): array {
        $sql = "SELECT bo.*, e.libelle AS ecriture_libelle, e.date_ecriture AS ecriture_date,
                       e.montant_ttc AS ecriture_montant, e.module_source AS ecriture_module
                FROM bank_operations bo
                LEFT JOIN ecritures_comptables e ON e.id = bo.ecriture_id
                WHERE bo.import_id = ?";
        $params = [$importId];
        if ($statut && in_array($statut, ['non_rapprochee','rapprochee','ignoree'], true)) {
            $sql .= " AND bo.statut = ?";
            $params[] = $statut;
        }
        $sql .= " ORDER BY bo.date_operation ASC, bo.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Suggère des écritures candidates pour matching avec une opération bancaire.
     *
     * Score (0-100) basé sur :
     *   - Date proximité : ±0-3j = 40, ±4-7j = 25, ±8-14j = 10, +14j = 0
     *   - Montant exact (centime près) = 40, ±1€ = 25, ±5% = 10
     *   - Libellé : similar_text() % normalisé sur 20
     *
     * @return array Liste d'écritures avec score, triée DESC
     */
    public function suggererMatches(int $operationId, int $maxResults = 5): array {
        $stmt = $this->db->prepare("SELECT * FROM bank_operations WHERE id = ?");
        $stmt->execute([$operationId]);
        $op = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$op) return [];

        // Inverser le signe : crédit bancaire = recette comptable, débit bancaire = dépense
        $type = (float)$op['montant'] > 0 ? 'recette' : 'depense';
        $absMontant = abs((float)$op['montant']);

        // Cherche les écritures de la même résidence sur ±14 jours, type cohérent, non déjà rapprochées
        $stmt = $this->db->prepare("SELECT e.id, e.date_ecriture, e.libelle, e.montant_ttc, e.type_ecriture, e.module_source
                FROM ecritures_comptables e
                LEFT JOIN bank_operations bo ON bo.ecriture_id = e.id AND bo.statut = 'rapprochee'
                WHERE e.residence_id = ?
                  AND e.type_ecriture = ?
                  AND e.statut != 'brouillon'
                  AND ABS(DATEDIFF(e.date_ecriture, ?)) <= ?
                  AND bo.id IS NULL
                ORDER BY ABS(DATEDIFF(e.date_ecriture, ?)) ASC, ABS(e.montant_ttc - ?) ASC
                LIMIT 30");
        $stmt->execute([
            $op['residence_id'],
            $type,
            $op['date_operation'],
            self::MATCH_DAYS_TOLERANCE,
            $op['date_operation'],
            $absMontant,
        ]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Score chaque candidat
        $scored = [];
        foreach ($candidates as $c) {
            $score = $this->calculerScore($op, $c);
            if ($score >= self::MATCH_SCORE_SUGGEST) {
                $c['score'] = $score;
                $c['delta_jours'] = (int)abs((strtotime($c['date_ecriture']) - strtotime($op['date_operation'])) / 86400);
                $c['delta_montant'] = round((float)$c['montant_ttc'] - $absMontant, 2);
                $scored[] = $c;
            }
        }
        // Tri par score DESC
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $maxResults);
    }

    /**
     * Score un couple (opération bancaire, écriture comptable) — voir docblock suggererMatches().
     */
    private function calculerScore(array $op, array $ecriture): int {
        $score = 0;

        // Score date
        $deltaJours = abs((strtotime($ecriture['date_ecriture']) - strtotime($op['date_operation'])) / 86400);
        if ($deltaJours <= 3)        $score += 40;
        elseif ($deltaJours <= 7)    $score += 25;
        elseif ($deltaJours <= 14)   $score += 10;

        // Score montant
        $absMont = abs((float)$op['montant']);
        $deltaMont = abs((float)$ecriture['montant_ttc'] - $absMont);
        if ($deltaMont < 0.01)       $score += 40;
        elseif ($deltaMont <= 1.0)   $score += 25;
        elseif ($absMont > 0 && $deltaMont / $absMont <= 0.05) $score += 10;

        // Score libellé (similarité)
        $libOp  = mb_strtolower((string)$op['libelle']);
        $libEcr = mb_strtolower((string)$ecriture['libelle']);
        if ($libOp !== '' && $libEcr !== '') {
            similar_text($libOp, $libEcr, $percent);
            $score += min(20, (int)round($percent / 5));
        }

        return min(100, $score);
    }

    /**
     * Rapproche manuellement une opération bancaire avec une écriture.
     */
    public function rapprocher(int $operationId, int $ecritureId, int $userId, ?int $score = null): bool {
        try {
            $this->db->beginTransaction();

            // Vérification : l'opération existe et n'est pas déjà rapprochée
            $stmt = $this->db->prepare("SELECT id, residence_id, statut FROM bank_operations WHERE id = ?");
            $stmt->execute([$operationId]);
            $op = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$op) {
                throw new RuntimeException("Opération introuvable.");
            }
            if ($op['statut'] === 'rapprochee') {
                throw new RuntimeException("Opération déjà rapprochée.");
            }

            // L'écriture appartient bien à la même résidence
            $stmt = $this->db->prepare("SELECT id FROM ecritures_comptables WHERE id = ? AND residence_id = ?");
            $stmt->execute([$ecritureId, $op['residence_id']]);
            if (!$stmt->fetch()) {
                throw new RuntimeException("Écriture non trouvée pour cette résidence.");
            }

            // L'écriture n'est pas déjà rapprochée à une autre opération
            $stmt = $this->db->prepare("SELECT id FROM bank_operations WHERE ecriture_id = ? AND statut = 'rapprochee' AND id != ?");
            $stmt->execute([$ecritureId, $operationId]);
            if ($stmt->fetch()) {
                throw new RuntimeException("Cette écriture est déjà rapprochée à une autre opération.");
            }

            // Mise à jour
            $stmt = $this->db->prepare("UPDATE bank_operations
                SET statut = 'rapprochee', ecriture_id = ?, matched_by = ?, matched_at = NOW(), matched_score = ?
                WHERE id = ?");
            $stmt->execute([$ecritureId, $userId, $score, $operationId]);

            // Mise à jour compteur sur import
            $stmt = $this->db->prepare("UPDATE bank_imports SET nb_rapprochees = (
                SELECT COUNT(*) FROM bank_operations WHERE import_id = bank_imports.id AND statut = 'rapprochee'
            ) WHERE id = (SELECT import_id FROM bank_operations WHERE id = ?)");
            $stmt->execute([$operationId]);

            $this->db->commit();
            Logger::audit('bank_rapprocher', 'bank_operations', $operationId, [
                'ecriture_id' => $ecritureId,
                'score'       => $score,
            ], $userId);
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Annule un rapprochement (statut → non_rapprochee, FK ecriture_id → NULL).
     */
    public function defaire(int $operationId): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE bank_operations
                SET statut = 'non_rapprochee', ecriture_id = NULL, matched_by = NULL, matched_at = NULL, matched_score = NULL
                WHERE id = ? AND statut = 'rapprochee'");
            $stmt->execute([$operationId]);
            $changed = $stmt->rowCount() > 0;

            $stmt = $this->db->prepare("UPDATE bank_imports SET nb_rapprochees = (
                SELECT COUNT(*) FROM bank_operations WHERE import_id = bank_imports.id AND statut = 'rapprochee'
            ) WHERE id = (SELECT import_id FROM bank_operations WHERE id = ?)");
            $stmt->execute([$operationId]);

            $this->db->commit();
            if ($changed) {
                Logger::audit('bank_defaire', 'bank_operations', $operationId, []);
            }
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Marque une opération comme volontairement ignorée (frais bancaires hors compta, etc.).
     */
    public function ignorer(int $operationId): bool {
        $stmt = $this->db->prepare("UPDATE bank_operations
            SET statut = 'ignoree', ecriture_id = NULL, matched_by = NULL, matched_at = NULL, matched_score = NULL
            WHERE id = ? AND statut = 'non_rapprochee'");
        $stmt->execute([$operationId]);
        $changed = $stmt->rowCount() > 0;
        if ($changed) {
            Logger::audit('bank_ignorer', 'bank_operations', $operationId, []);
        }
        return $changed;
    }

    /**
     * Supprime un import + ses opérations (et défait les rapprochements).
     */
    public function deleteImport(int $importId): bool {
        $stmt = $this->db->prepare("DELETE FROM bank_imports WHERE id = ?");
        $stmt->execute([$importId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            Logger::audit('bank_import_delete', 'bank_imports', $importId, []);
        }
        return $deleted;
    }

    /**
     * Stats globales d'un import.
     */
    public function getStatsImport(int $importId): array {
        $stmt = $this->db->prepare("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN statut='rapprochee' THEN 1 ELSE 0 END) AS rapp,
            SUM(CASE WHEN statut='non_rapprochee' THEN 1 ELSE 0 END) AS non_rapp,
            SUM(CASE WHEN statut='ignoree' THEN 1 ELSE 0 END) AS ignorees,
            SUM(CASE WHEN montant > 0 THEN montant ELSE 0 END) AS total_credits,
            SUM(CASE WHEN montant < 0 THEN ABS(montant) ELSE 0 END) AS total_debits
            FROM bank_operations WHERE import_id = ?");
        $stmt->execute([$importId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total'         => (int)($row['total']         ?? 0),
            'rapprochees'   => (int)($row['rapp']          ?? 0),
            'non_rapp'      => (int)($row['non_rapp']      ?? 0),
            'ignorees'      => (int)($row['ignorees']      ?? 0),
            'total_credits' => (float)($row['total_credits'] ?? 0),
            'total_debits'  => (float)($row['total_debits']  ?? 0),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    //  Helpers privés
    // ─────────────────────────────────────────────────────────────────

    /**
     * Normalise un nom de colonne CSV : lowercase + trim + remove accents.
     */
    private function normalizeHeader(string $h): string {
        $h = mb_strtolower(trim($h));
        $h = strtr($h, ['é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ä'=>'a','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c']);
        return preg_replace('/\s+/', ' ', $h);
    }

    /**
     * Cherche l'index d'une colonne dans le header.
     */
    private function findHeaderIdx(array $header, array $aliases): ?int {
        foreach ($header as $idx => $h) {
            foreach ($aliases as $a) {
                if ($h === $a || str_contains($h, $a)) return (int)$idx;
            }
        }
        return null;
    }

    /**
     * Parse une date au format DD/MM/YYYY ou YYYY-MM-DD.
     */
    private function parseDate(string $raw): ?string {
        if ($raw === '') return null;
        // Format ISO
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
            return substr($raw, 0, 10);
        }
        // Format français DD/MM/YYYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $raw, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        // Format compact DD-MM-YYYY
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})/', $raw, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        return null;
    }

    /**
     * Parse un montant français : "1 234,56" → 1234.56, "-50,00" → -50.0
     */
    private function parseMontant(string $raw): float {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-') return 0.0;
        // Retire espaces et symbole €
        $clean = str_replace([' ', "\xc2\xa0", '€', "'"], '', $raw);
        // Remplace virgule par point
        $clean = str_replace(',', '.', $clean);
        return is_numeric($clean) ? (float)$clean : 0.0;
    }
}
