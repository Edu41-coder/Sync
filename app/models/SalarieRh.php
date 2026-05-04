<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle SalarieRh (fiche RH d'un staff)
 * ====================================================================
 *
 * Phase 3 du module Comptabilité. Une fiche par user (relation 1:1).
 * Données préalables au calcul des bulletins de paie (Phase 4).
 */

class SalarieRh extends Model {

    public const TYPES_CONTRAT = [
        'CDI'           => 'CDI',
        'CDD'           => 'CDD',
        'Apprentissage' => 'Apprentissage',
        'Stage'         => 'Stage',
        'Interim'       => 'Intérim',
        'Intermittent'  => 'Intermittent',
        'Autre'         => 'Autre',
    ];

    public const CATEGORIES = [
        'ouvrier'        => 'Ouvrier',
        'employe'        => 'Employé',
        'agent_maitrise' => 'Agent de maîtrise',
        'cadre'          => 'Cadre',
    ];

    public const TEMPS_TRAVAIL = [
        'temps_plein'    => 'Temps plein',
        'temps_partiel'  => 'Temps partiel',
    ];

    /** Nombre d'heures mensuelles de référence pour un temps plein (35h × 52 / 12). */
    public const HEURES_MENSUELLES_REF = 151.67;

    // ─────────────────────────────────────────────────────────────────
    //  LECTURE
    // ─────────────────────────────────────────────────────────────────

    public function findByUserId(int $userId): ?array {
        try {
            $sql = "SELECT s.*, cc.nom AS convention_nom, cc.idcc AS convention_idcc, cc.code AS convention_code
                    FROM salaries_rh s
                    LEFT JOIN conventions_collectives cc ON cc.id = s.convention_collective_id
                    WHERE s.user_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) { $this->logError($e->getMessage(), '', [$userId]); return null; }
    }

    /**
     * Liste des staff salariés (avec ou sans fiche RH créée).
     * Filtres : residence_id (via user_residence), only_with_fiche, only_active_contract
     */
    public function listAllStaff(?int $residenceId = null, bool $onlyWithFiche = false, bool $onlyActive = true): array {
        $sql = "SELECT u.id AS user_id, u.username, u.prenom, u.nom, u.email, u.role, u.statut AS user_statut,
                       s.id AS fiche_id, s.numero_ss, s.date_embauche, s.date_sortie, s.type_contrat,
                       s.salaire_brut_base, s.iban,
                       cc.nom AS convention_nom, cc.code AS convention_code
                FROM users u
                LEFT JOIN salaries_rh s ON s.user_id = u.id
                LEFT JOIN conventions_collectives cc ON cc.id = s.convention_collective_id
                WHERE u.statut = 'actif'
                  AND u.role NOT IN ('admin', 'proprietaire', 'locataire_permanent')";
        $params = [];

        if ($residenceId) {
            $sql .= " AND EXISTS (SELECT 1 FROM user_residence ur WHERE ur.user_id = u.id AND ur.residence_id = ? AND ur.statut = 'actif')";
            $params[] = $residenceId;
        }
        if ($onlyWithFiche) {
            $sql .= " AND s.id IS NOT NULL";
        }
        if ($onlyActive) {
            $sql .= " AND (s.date_sortie IS NULL OR s.date_sortie >= CURDATE())";
        }
        $sql .= " ORDER BY u.nom, u.prenom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); return []; }
    }

    public function getConventions(): array {
        try {
            return $this->db->query("SELECT id, code, nom, idcc, modules_concernes FROM conventions_collectives WHERE actif = 1 ORDER BY nom")
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    // ─────────────────────────────────────────────────────────────────
    //  ÉCRITURE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Crée OU met à jour la fiche RH du user (UPSERT 1:1).
     */
    public function upsert(int $userId, array $data, ?int $createdBy = null): int {
        $existing = $this->findByUserId($userId);

        $payload = [
            'numero_ss'                  => self::nullIfEmpty($data['numero_ss'] ?? null),
            'date_embauche'              => self::nullIfEmpty($data['date_embauche'] ?? null),
            'date_sortie'                => self::nullIfEmpty($data['date_sortie'] ?? null),
            'motif_sortie'               => self::nullIfEmpty($data['motif_sortie'] ?? null),
            'type_contrat'               => $data['type_contrat'] ?? 'CDI',
            'motif_cdd'                  => self::nullIfEmpty($data['motif_cdd'] ?? null),
            'cdd_date_fin'               => self::nullIfEmpty($data['cdd_date_fin'] ?? null),
            'temps_travail'              => $data['temps_travail'] ?? 'temps_plein',
            'quotite_temps_partiel'      => self::nullIfEmpty($data['quotite_temps_partiel'] ?? null),
            'convention_collective_id'   => self::nullIfEmpty($data['convention_collective_id'] ?? null),
            'coefficient'                => self::nullIfEmpty($data['coefficient'] ?? null),
            'categorie'                  => $data['categorie'] ?? 'employe',
            'salaire_brut_base'          => self::nullIfEmpty($data['salaire_brut_base'] ?? null),
            'taux_horaire_normal'        => self::nullIfEmpty($data['taux_horaire_normal'] ?? null),
            'taux_majoration_25'         => $data['taux_majoration_25'] ?? 25.00,
            'taux_majoration_50'         => $data['taux_majoration_50'] ?? 50.00,
            'iban'                       => self::nullIfEmpty($data['iban'] ?? null),
            'bic'                        => self::nullIfEmpty($data['bic'] ?? null),
            'mutuelle_taux_salarial'     => $data['mutuelle_taux_salarial'] ?? 1.50,
            'mutuelle_taux_patronal'     => $data['mutuelle_taux_patronal'] ?? 1.50,
            'prevoyance_taux_salarial'   => $data['prevoyance_taux_salarial'] ?? 0.50,
            'prevoyance_taux_patronal'   => $data['prevoyance_taux_patronal'] ?? 0.50,
            'notes'                      => self::nullIfEmpty($data['notes'] ?? null),
        ];

        // Auto-calcul du taux horaire si salaire_brut fourni mais pas le taux
        if ($payload['salaire_brut_base'] !== null && $payload['taux_horaire_normal'] === null) {
            $payload['taux_horaire_normal'] = round((float)$payload['salaire_brut_base'] / self::HEURES_MENSUELLES_REF, 4);
        }

        if ($existing) {
            $cols = implode(', ', array_map(fn($k) => "$k = ?", array_keys($payload)));
            $sql = "UPDATE salaries_rh SET $cols WHERE user_id = ?";
            $params = array_merge(array_values($payload), [$userId]);
            try {
                $this->db->prepare($sql)->execute($params);
                // Audit RGPD : tracer les changements sensibles (salaire, IBAN, dates contrat)
                $changes = [];
                foreach (['salaire_brut_base','iban','date_embauche','date_sortie','convention_collective_id','type_contrat'] as $f) {
                    if (($existing[$f] ?? null) != ($payload[$f] ?? null)) {
                        // IBAN masqué pour ne pas leaker la valeur en clair dans le log
                        if ($f === 'iban') {
                            $changes[$f] = ['avant' => self::maskIban($existing[$f] ?? ''), 'apres' => self::maskIban($payload[$f] ?? '')];
                        } else {
                            $changes[$f] = ['avant' => $existing[$f] ?? null, 'apres' => $payload[$f] ?? null];
                        }
                    }
                }
                if (!empty($changes)) {
                    Logger::audit('salarie_rh_update', 'salaries_rh', (int)$existing['id'], [
                        'user_id' => $userId,
                        'changes' => $changes,
                    ]);
                }
                return (int)$existing['id'];
            } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); throw new RuntimeException("Erreur mise à jour : " . $e->getMessage()); }
        } else {
            $cols = array_merge(['user_id', 'created_by'], array_keys($payload));
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO salaries_rh (" . implode(',', $cols) . ") VALUES ($placeholders)";
            $params = array_merge([$userId, $createdBy], array_values($payload));
            try {
                $this->db->prepare($sql)->execute($params);
                $newId = (int)$this->db->lastInsertId();
                Logger::audit('salarie_rh_create', 'salaries_rh', $newId, [
                    'user_id'        => $userId,
                    'type_contrat'   => $payload['type_contrat'],
                    'salaire_brut'   => $payload['salaire_brut_base'],
                    'convention_id'  => $payload['convention_collective_id'],
                ], $createdBy);
                return $newId;
            } catch (PDOException $e) { $this->logError($e->getMessage(), $sql, $params); throw new RuntimeException("Erreur création : " . $e->getMessage()); }
        }
    }

    /**
     * Le salarié peut modifier UNIQUEMENT son IBAN/BIC (le reste géré par admin/comptable).
     */
    public function updateRib(int $userId, ?string $iban, ?string $bic): bool {
        $existing = $this->findByUserId($userId);
        if (!$existing) {
            // Créer une fiche minimale pour pouvoir y stocker le RIB
            $this->upsert($userId, ['iban' => $iban, 'bic' => $bic], $userId);
            return true;
        }
        try {
            $stmt = $this->db->prepare("UPDATE salaries_rh SET iban = ?, bic = ? WHERE user_id = ?");
            $ok = $stmt->execute([self::nullIfEmpty($iban), self::nullIfEmpty($bic), $userId]);
            if ($ok && (($existing['iban'] ?? '') !== (string)$iban)) {
                Logger::audit('salarie_rib_update', 'salaries_rh', (int)$existing['id'], [
                    'user_id'    => $userId,
                    'iban_avant' => self::maskIban($existing['iban'] ?? ''),
                    'iban_apres' => self::maskIban((string)$iban),
                ]);
            }
            return $ok;
        } catch (PDOException $e) { $this->logError($e->getMessage(), '', [$userId]); return false; }
    }

    /** Masque un IBAN pour les logs : conserve les 4 premiers et 4 derniers caractères. */
    private static function maskIban(string $iban): string {
        $iban = trim($iban);
        $len = strlen($iban);
        if ($len === 0) return '';
        if ($len <= 8) return str_repeat('*', $len);
        return substr($iban, 0, 4) . str_repeat('*', $len - 8) . substr($iban, -4);
    }

    public function deleteByUserId(int $userId): bool {
        try { return $this->db->prepare("DELETE FROM salaries_rh WHERE user_id = ?")->execute([$userId]); }
        catch (PDOException $e) { return false; }
    }

    // ─────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────

    private static function nullIfEmpty($v) {
        if ($v === null || $v === '') return null;
        if (is_string($v) && trim($v) === '') return null;
        return $v;
    }

    /**
     * Validation IBAN basique (longueur + format FR).
     */
    public static function validateIban(?string $iban): bool {
        if ($iban === null || $iban === '') return true; // facultatif
        $iban = strtoupper(str_replace(' ', '', $iban));
        return (bool)preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/', $iban);
    }

    /**
     * Validation numéro SS français (15 chiffres).
     */
    public static function validateNumeroSs(?string $ns): bool {
        if ($ns === null || $ns === '') return true; // facultatif
        $clean = preg_replace('/\s+/', '', $ns);
        return (bool)preg_match('/^[12]\d{14}$/', $clean);
    }
}
