<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle Exercice comptable (Phase 9)
 * ====================================================================
 *
 * Gestion des exercices comptables annuels par résidence (table
 * `exercices_comptables`). Fonctionnalités :
 *   - Liste filtrée
 *   - Création d'un nouvel exercice (avec contraintes annuelles)
 *   - Clôture / ré-ouverture / archivage
 *   - Recherche de l'exercice couvrant une date donnée
 *   - Stats agrégées sur un exercice (totaux écritures + résultat)
 *
 * Workflow statut :
 *   ouvert → cloture → archive
 *
 * Une fois clôturé, les écritures de l'exercice sont gelées (statut='cloturee')
 * pour empêcher toute modification rétroactive (PCG art. 410-1).
 */

class Exercice extends Model {

    public const STATUTS = [
        'ouvert'  => 'Ouvert',
        'cloture' => 'Clôturé',
        'archive' => 'Archivé',
    ];

    /**
     * Liste filtrée pour l'index admin (par résidences accessibles).
     */
    public function listFiltered(array $residenceIds, ?int $annee = null, ?string $statut = null): array {
        if (empty($residenceIds)) return [];

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT e.*, c.nom AS residence_nom,
                       (SELECT COUNT(*) FROM ecritures_comptables ec WHERE ec.exercice_id = e.id) AS nb_ecritures
                FROM exercices_comptables e
                LEFT JOIN coproprietees c ON c.id = e.copropriete_id
                WHERE e.copropriete_id IN ($resPh)";
        $params = array_map('intval', $residenceIds);

        if ($annee)  { $sql .= " AND e.annee = ?";  $params[] = (int)$annee; }
        if ($statut && array_key_exists($statut, self::STATUTS)) {
            $sql .= " AND e.statut = ?";
            $params[] = $statut;
        }

        $sql .= " ORDER BY e.copropriete_id, e.annee DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve un exercice par ID avec ses agrégats.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT e.*, c.nom AS residence_nom
                                    FROM exercices_comptables e
                                    LEFT JOIN coproprietees c ON c.id = e.copropriete_id
                                    WHERE e.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Stats agrégées sur un exercice : totaux recettes/dépenses + résultat + nb écritures.
     */
    public function getStats(int $exerciceId): array {
        $stmt = $this->db->prepare("SELECT
            COUNT(*) AS nb_ecritures,
            COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ttc ELSE 0 END), 0) AS recettes_ttc,
            COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ttc ELSE 0 END), 0) AS depenses_ttc,
            COALESCE(SUM(CASE WHEN type_ecriture='recette' THEN montant_ht ELSE 0 END), 0) AS recettes_ht,
            COALESCE(SUM(CASE WHEN type_ecriture='depense' THEN montant_ht ELSE 0 END), 0) AS depenses_ht
            FROM ecritures_comptables
            WHERE exercice_id = ? AND statut != 'brouillon'");
        $stmt->execute([$exerciceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $r = (float)($row['recettes_ttc'] ?? 0);
        $d = (float)($row['depenses_ttc'] ?? 0);
        return [
            'nb_ecritures' => (int)($row['nb_ecritures'] ?? 0),
            'recettes_ttc' => $r,
            'depenses_ttc' => $d,
            'recettes_ht'  => (float)($row['recettes_ht'] ?? 0),
            'depenses_ht'  => (float)($row['depenses_ht'] ?? 0),
            'resultat'     => round($r - $d, 2),
        ];
    }

    /**
     * Crée un nouvel exercice annuel pour une résidence.
     */
    public function create(int $residenceId, int $annee, ?string $dateDebut = null, ?string $dateFin = null, ?float $budget = 0.0, ?string $notes = null): int {
        // Vérif unicité
        $stmt = $this->db->prepare("SELECT id FROM exercices_comptables WHERE copropriete_id = ? AND annee = ?");
        $stmt->execute([$residenceId, $annee]);
        if ($stmt->fetch()) {
            throw new RuntimeException("Un exercice $annee existe déjà pour cette résidence.");
        }
        $debut = $dateDebut ?: sprintf('%d-01-01', $annee);
        $fin   = $dateFin   ?: sprintf('%d-12-31', $annee);

        $stmt = $this->db->prepare("INSERT INTO exercices_comptables
            (copropriete_id, annee, date_debut, date_fin, budget_previsionnel, statut, notes)
            VALUES (?, ?, ?, ?, ?, 'ouvert', ?)");
        $stmt->execute([$residenceId, $annee, $debut, $fin, $budget ?? 0.0, $notes]);
        $newId = (int)$this->db->lastInsertId();
        Logger::audit('exercice_create', 'exercices_comptables', $newId, [
            'residence_id' => $residenceId,
            'annee'        => $annee,
            'date_debut'   => $debut,
            'date_fin'     => $fin,
            'budget'       => (float)($budget ?? 0.0),
        ]);
        return $newId;
    }

    /**
     * Clôture un exercice : statut → cloture + gel des écritures (statut → cloturee).
     * Atomique via transaction.
     */
    public function cloturer(int $exerciceId): bool {
        $exercice = $this->findById($exerciceId);
        if (!$exercice || $exercice['statut'] !== 'ouvert') {
            throw new RuntimeException("Seul un exercice ouvert peut être clôturé.");
        }

        try {
            $this->db->beginTransaction();

            // Refuse si écritures en brouillon
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM ecritures_comptables
                                        WHERE exercice_id = ? AND statut = 'brouillon'");
            $stmt->execute([$exerciceId]);
            $nbBrouillons = (int)$stmt->fetchColumn();
            if ($nbBrouillons > 0) {
                throw new RuntimeException("$nbBrouillons écriture(s) en brouillon — les valider ou supprimer avant clôture.");
            }

            // Gel des écritures validées
            $stmt = $this->db->prepare("UPDATE ecritures_comptables
                                        SET statut = 'cloturee'
                                        WHERE exercice_id = ? AND statut = 'validee'");
            $stmt->execute([$exerciceId]);

            // Statut exercice
            $stmt = $this->db->prepare("UPDATE exercices_comptables SET statut = 'cloture' WHERE id = ?");
            $stmt->execute([$exerciceId]);

            $this->db->commit();
            Logger::audit('exercice_cloture', 'exercices_comptables', $exerciceId, [
                'residence_id' => (int)$exercice['copropriete_id'],
                'annee'        => (int)$exercice['annee'],
            ]);
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Réouvre un exercice clôturé (admin uniquement, pour correction comptable
     * exceptionnelle). Dégèle les écritures (cloturee → validee).
     */
    public function reouvrir(int $exerciceId): bool {
        $exercice = $this->findById($exerciceId);
        if (!$exercice || $exercice['statut'] !== 'cloture') {
            throw new RuntimeException("Seul un exercice clôturé peut être ré-ouvert.");
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE ecritures_comptables
                                        SET statut = 'validee'
                                        WHERE exercice_id = ? AND statut = 'cloturee'");
            $stmt->execute([$exerciceId]);
            $stmt = $this->db->prepare("UPDATE exercices_comptables SET statut = 'ouvert' WHERE id = ?");
            $stmt->execute([$exerciceId]);
            $this->db->commit();
            Logger::audit('exercice_reouverture', 'exercices_comptables', $exerciceId, [
                'residence_id' => (int)$exercice['copropriete_id'],
                'annee'        => (int)$exercice['annee'],
                'note'         => 'Action exceptionnelle réservée admin',
            ]);
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Archive un exercice clôturé (statut final, irréversible côté UI).
     */
    public function archiver(int $exerciceId): bool {
        $stmt = $this->db->prepare("UPDATE exercices_comptables SET statut = 'archive'
                                    WHERE id = ? AND statut = 'cloture'");
        $stmt->execute([$exerciceId]);
        $archived = $stmt->rowCount() > 0;
        if ($archived) {
            Logger::audit('exercice_archive', 'exercices_comptables', $exerciceId, []);
        }
        return $archived;
    }

    /**
     * Trouve l'exercice ouvert qui couvre une date donnée pour une résidence.
     * Retourne null si aucun.
     */
    public function findByResidenceDate(int $residenceId, string $date): ?array {
        $stmt = $this->db->prepare("SELECT * FROM exercices_comptables
                                    WHERE copropriete_id = ? AND ? BETWEEN date_debut AND date_fin
                                    ORDER BY statut = 'ouvert' DESC, annee DESC LIMIT 1");
        $stmt->execute([$residenceId, $date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
