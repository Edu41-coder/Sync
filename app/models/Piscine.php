<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Piscine (journal de bord)
 * ====================================================================
 * Suivi piscines collectives résidences seniors :
 *   - analyses chimiques (pH, chlore, alcalinité, stabilisant)
 *   - contrôles ARS (PV, conformité réglementaire)
 *   - hivernage / mise en service saisonniers
 *
 * Conditionné à coproprietees.piscine = 1.
 */
class Piscine extends Model {

    public const TYPES = ['analyse', 'controle_ars', 'hivernage', 'mise_en_service', 'vidange', 'autre'];
    public const TYPE_LABELS = [
        'analyse'         => 'Analyse chimique',
        'controle_ars'    => 'Contrôle ARS',
        'hivernage'       => 'Hivernage',
        'mise_en_service' => 'Mise en service',
        'vidange'         => 'Vidange',
        'autre'           => 'Autre',
    ];

    // Plages réglementaires (santé publique)
    public const PH_MIN = 7.0;
    public const PH_MAX = 7.6;
    public const CHLORE_LIBRE_MIN = 1.0;
    public const CHLORE_LIBRE_MAX = 3.0;

    /**
     * Liste des résidences avec piscine accessibles au user.
     */
    public function getResidencesAvecPiscine(int $userId, string $userRole): array {
        if ($userRole === 'admin') {
            $sql = "SELECT id, nom, ville FROM coproprietees WHERE piscine = 1 AND actif = 1 ORDER BY nom";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        $sql = "SELECT c.id, c.nom, c.ville
                FROM coproprietees c
                JOIN user_residence ur ON ur.residence_id = c.id AND ur.statut = 'actif'
                WHERE c.piscine = 1 AND c.actif = 1 AND ur.user_id = ?
                ORDER BY c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Journal complet d'une résidence (avec filtres optionnels).
     */
    public function getJournal(int $residenceId, array $filtres = [], int $limit = 100): array {
        $sql = "SELECT j.*, u.prenom AS mesure_par_prenom, u.nom AS mesure_par_nom
                FROM piscine_journal j
                LEFT JOIN users u ON u.id = j.mesure_par_user_id
                WHERE j.residence_id = ?";
        $params = [$residenceId];

        if (!empty($filtres['type_entree'])) {
            $sql .= " AND j.type_entree = ?";
            $params[] = $filtres['type_entree'];
        }
        if (!empty($filtres['date_debut'])) {
            $sql .= " AND j.date_mesure >= ?";
            $params[] = $filtres['date_debut'];
        }
        if (!empty($filtres['date_fin'])) {
            $sql .= " AND j.date_mesure <= ?";
            $params[] = $filtres['date_fin'];
        }

        $sql .= " ORDER BY j.date_mesure DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findEntree(int $id): ?array {
        $sql = "SELECT j.*, u.prenom AS mesure_par_prenom, u.nom AS mesure_par_nom,
                       c.nom AS residence_nom
                FROM piscine_journal j
                LEFT JOIN users u ON u.id = j.mesure_par_user_id
                JOIN coproprietees c ON c.id = j.residence_id
                WHERE j.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createEntree(array $data): int {
        $sql = "INSERT INTO piscine_journal
                (residence_id, type_entree, date_mesure, ph, chlore_libre_mg_l, chlore_total_mg_l,
                 temperature, alcalinite_mg_l, stabilisant_mg_l, produit_utilise, quantite_produit_kg,
                 numero_pv, conformite_ars, fichier_pv, notes, mesure_par_user_id, intervention_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'],
            $data['type_entree'],
            $data['date_mesure'],
            $data['ph']                ?? null,
            $data['chlore_libre_mg_l'] ?? null,
            $data['chlore_total_mg_l'] ?? null,
            $data['temperature']       ?? null,
            $data['alcalinite_mg_l']   ?? null,
            $data['stabilisant_mg_l']  ?? null,
            $data['produit_utilise']   ?? null,
            $data['quantite_produit_kg'] ?? null,
            $data['numero_pv']         ?? null,
            $data['conformite_ars']    ?? null,
            $data['fichier_pv']        ?? null,
            $data['notes']             ?? null,
            $data['mesure_par_user_id'] ?? null,
            $data['intervention_id']   ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateEntree(int $id, array $data): bool {
        // Si nouveau fichier_pv fourni → on l'utilise. Sinon on garde l'ancien.
        $sql = "UPDATE piscine_journal SET
                  type_entree = ?, date_mesure = ?, ph = ?, chlore_libre_mg_l = ?, chlore_total_mg_l = ?,
                  temperature = ?, alcalinite_mg_l = ?, stabilisant_mg_l = ?,
                  produit_utilise = ?, quantite_produit_kg = ?,
                  numero_pv = ?, conformite_ars = ?, notes = ?";
        $params = [
            $data['type_entree'],
            $data['date_mesure'],
            $data['ph']                ?? null,
            $data['chlore_libre_mg_l'] ?? null,
            $data['chlore_total_mg_l'] ?? null,
            $data['temperature']       ?? null,
            $data['alcalinite_mg_l']   ?? null,
            $data['stabilisant_mg_l']  ?? null,
            $data['produit_utilise']   ?? null,
            $data['quantite_produit_kg'] ?? null,
            $data['numero_pv']         ?? null,
            $data['conformite_ars']    ?? null,
            $data['notes']             ?? null,
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
        $this->db->prepare("DELETE FROM piscine_journal WHERE id = ?")->execute([$id]);
        return $row;
    }

    /**
     * Dernière analyse pour KPI dashboard.
     */
    public function getDerniereAnalyse(int $residenceId): ?array {
        $sql = "SELECT * FROM piscine_journal
                WHERE residence_id = ? AND type_entree = 'analyse'
                ORDER BY date_mesure DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getDernierControleArs(int $residenceId): ?array {
        $sql = "SELECT * FROM piscine_journal
                WHERE residence_id = ? AND type_entree = 'controle_ars'
                ORDER BY date_mesure DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * État saisonnier : ouvert (dernière mise_en_service plus récente que dernier hivernage).
     */
    public function getEtatSaisonnier(int $residenceId): array {
        $sql = "SELECT type_entree, date_mesure FROM piscine_journal
                WHERE residence_id = ? AND type_entree IN ('hivernage', 'mise_en_service')
                ORDER BY date_mesure DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residenceId]);
        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$last) return ['etat' => 'inconnu', 'date' => null];
        return [
            'etat' => $last['type_entree'] === 'mise_en_service' ? 'ouverte' : 'hivernage',
            'date' => $last['date_mesure'],
        ];
    }

    /**
     * Vérifie si une mesure est dans les normes (pour badges UI).
     */
    public static function checkPh(?float $ph): string {
        if ($ph === null) return 'inconnu';
        if ($ph < self::PH_MIN || $ph > self::PH_MAX) return 'hors_norme';
        return 'normal';
    }

    public static function checkChlore(?float $chlore): string {
        if ($chlore === null) return 'inconnu';
        if ($chlore < self::CHLORE_LIBRE_MIN) return 'critique';
        if ($chlore > self::CHLORE_LIBRE_MAX) return 'hors_norme';
        return 'normal';
    }

    /**
     * Alertes pour le dashboard.
     */
    public function getAlertes(int $residenceId): array {
        $alertes = [];
        $derniere = $this->getDerniereAnalyse($residenceId);

        // Pas d'analyse depuis > 3 jours (saison ouverture)
        $etat = $this->getEtatSaisonnier($residenceId);
        if ($etat['etat'] === 'ouverte') {
            if (!$derniere) {
                $alertes[] = ['niveau' => 'danger', 'msg' => 'Aucune analyse chimique enregistrée alors que la piscine est ouverte.'];
            } else {
                $diff = (time() - strtotime($derniere['date_mesure'])) / 86400;
                if ($diff > 3) {
                    $alertes[] = ['niveau' => 'warning', 'msg' => 'Dernière analyse chimique il y a ' . round($diff) . ' jours (idéal : quotidien).'];
                }
            }

            // Dernier contrôle ARS > 30 jours
            $ars = $this->getDernierControleArs($residenceId);
            if (!$ars) {
                $alertes[] = ['niveau' => 'warning', 'msg' => 'Aucun contrôle ARS enregistré.'];
            } else {
                $diff = (time() - strtotime($ars['date_mesure'])) / 86400;
                if ($diff > 30) {
                    $alertes[] = ['niveau' => 'warning', 'msg' => 'Dernier contrôle ARS il y a ' . round($diff) . ' jours (réglementaire mensuel).'];
                }
                if ($ars['conformite_ars'] === 'non_conforme') {
                    $alertes[] = ['niveau' => 'danger', 'msg' => 'Dernier contrôle ARS NON CONFORME — action requise.'];
                }
            }
        }

        // Dernière analyse hors norme
        if ($derniere) {
            if (self::checkPh((float)$derniere['ph']) === 'hors_norme') {
                $alertes[] = ['niveau' => 'warning', 'msg' => 'Dernier pH hors norme (' . $derniere['ph'] . ' — idéal 7.0-7.6).'];
            }
            $chloreCheck = self::checkChlore((float)$derniere['chlore_libre_mg_l']);
            if ($chloreCheck === 'critique') {
                $alertes[] = ['niveau' => 'danger', 'msg' => 'Chlore libre critique (' . $derniere['chlore_libre_mg_l'] . ' mg/L — risque sanitaire).'];
            } elseif ($chloreCheck === 'hors_norme') {
                $alertes[] = ['niveau' => 'warning', 'msg' => 'Chlore libre élevé (' . $derniere['chlore_libre_mg_l'] . ' mg/L).'];
            }
        }

        // Hivernage à programmer (octobre, si encore ouverte)
        $mois = (int)date('n');
        if ($mois === 10 && $etat['etat'] === 'ouverte') {
            $alertes[] = ['niveau' => 'info', 'msg' => 'Période d\'hivernage : pensez à enregistrer la mise en repos de la piscine.'];
        }
        if ($mois >= 4 && $mois <= 5 && $etat['etat'] === 'hivernage') {
            $alertes[] = ['niveau' => 'info', 'msg' => 'Période de remise en service : pensez à enregistrer la réouverture.'];
        }

        return $alertes;
    }

    /**
     * Stats : nb analyses sur 30 derniers jours, etc.
     */
    public function getStats(int $residenceId): array {
        $stats = ['analyses_30j' => 0, 'controles_ars_an' => 0, 'derniere_mesure' => null];
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM piscine_journal WHERE residence_id = ? AND type_entree = 'analyse' AND date_mesure >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$residenceId]);
        $stats['analyses_30j'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM piscine_journal WHERE residence_id = ? AND type_entree = 'controle_ars' AND date_mesure >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
        $stmt->execute([$residenceId]);
        $stats['controles_ars_an'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT MAX(date_mesure) FROM piscine_journal WHERE residence_id = ?");
        $stmt->execute([$residenceId]);
        $stats['derniere_mesure'] = $stmt->fetchColumn() ?: null;
        return $stats;
    }
}
