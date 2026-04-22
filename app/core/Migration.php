<?php
/**
 * ====================================================================
 * SYND_GEST - Système de Migrations DB
 * ====================================================================
 * Applique les fichiers SQL du dossier database/migrations/ dans l'ordre.
 * Chaque migration n'est exécutée qu'une seule fois (trackée dans la table `migrations`).
 *
 * Fichiers attendus : NNN_description.sql (ex: 001_initial.sql, 002_add_photo_profil.sql)
 * Le préfixe numérique détermine l'ordre d'exécution.
 */

class Migration {

    private PDO $db;
    private string $migrationsPath;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->migrationsPath = realpath(__DIR__ . '/../../database/migrations');
        $this->ensureMigrationsTable();
    }

    /**
     * Créer la table migrations si elle n'existe pas
     */
    private function ensureMigrationsTable(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL DEFAULT 1,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Récupérer les migrations déjà appliquées
     */
    private function getApplied(): array {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY migration");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupérer tous les fichiers de migration disponibles
     */
    private function getAvailable(): array {
        if (!$this->migrationsPath || !is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.sql');
        $migrations = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $migrations[$name] = $file;
        }

        ksort($migrations); // tri par nom (donc par numéro)
        return $migrations;
    }

    /**
     * Récupérer les migrations en attente
     */
    public function getPending(): array {
        $applied = $this->getApplied();
        $available = $this->getAvailable();

        $pending = [];
        foreach ($available as $name => $file) {
            if (!in_array($name, $applied)) {
                $pending[$name] = $file;
            }
        }

        return $pending;
    }

    /**
     * Récupérer le prochain numéro de batch
     */
    private function getNextBatch(): int {
        $stmt = $this->db->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Exécuter toutes les migrations en attente
     *
     * @return array ['applied' => string[], 'errors' => string[]]
     */
    public function migrate(): array {
        $pending = $this->getPending();
        $results = ['applied' => [], 'errors' => []];

        if (empty($pending)) {
            return $results;
        }

        $batch = $this->getNextBatch();

        // Pas de transaction explicite : MySQL/MariaDB applique un implicit commit
        // sur chaque DDL (CREATE/ALTER/DROP TABLE...), donc beginTransaction/commit
        // n'offre aucune atomicité et fait planter commit() à tort.
        foreach ($pending as $name => $file) {
            $sql = file_get_contents($file);

            if (empty(trim($sql))) {
                $results['errors'][] = "$name — fichier vide, ignoré";
                continue;
            }

            $statements = $this->splitStatements($sql);
            $failed = null;

            foreach ($statements as $i => $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;

                try {
                    $this->db->exec($statement);
                } catch (PDOException $e) {
                    $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 120);
                    $failed = "instruction #" . $i + 1 . " [" . $preview . "…] : " . $e->getMessage();
                    break;
                }
            }

            if ($failed !== null) {
                $results['errors'][] = "$name — $failed";
                // Stop à la première erreur pour éviter d'appliquer les migrations suivantes
                break;
            }

            try {
                $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch, applied_at) VALUES (?, ?, NOW())");
                $stmt->execute([$name, $batch]);
                $results['applied'][] = $name;
            } catch (PDOException $e) {
                $results['errors'][] = "$name — SQL appliqué mais suivi impossible : " . $e->getMessage();
                break;
            }
        }

        return $results;
    }

    /**
     * Statut complet des migrations
     */
    public function getStatus(): array {
        $applied = $this->getApplied();
        $available = $this->getAvailable();

        $status = [];
        foreach ($available as $name => $file) {
            $status[] = [
                'name'    => $name,
                'applied' => in_array($name, $applied),
                'file'    => basename($file),
            ];
        }

        // Ajouter les migrations appliquées avec détails
        $stmt = $this->db->query("SELECT migration, batch, applied_at FROM migrations ORDER BY migration");
        $appliedDetails = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $appliedDetails[$row['migration']] = $row;
        }

        foreach ($status as &$s) {
            if (isset($appliedDetails[$s['name']])) {
                $s['batch'] = $appliedDetails[$s['name']]['batch'];
                $s['applied_at'] = $appliedDetails[$s['name']]['applied_at'];
            }
        }

        return $status;
    }

    /**
     * Marquer une migration comme déjà appliquée (sans l'exécuter)
     * Utile pour le schéma initial déjà en place ou des migrations appliquées hors système
     */
    public function markAsApplied(string $name): bool {
        if (!$this->isKnownMigration($name)) return false;
        try {
            $batch = $this->getNextBatch();
            $stmt = $this->db->prepare("INSERT IGNORE INTO migrations (migration, batch, applied_at) VALUES (?, ?, NOW())");
            return $stmt->execute([$name, $batch]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Démarquer une migration (supprime l'entrée de la table migrations)
     * ⚠️ N'annule PAS les changements SQL — seul le tracking est retiré.
     */
    public function unmarkAsApplied(string $name): bool {
        if (!$this->isKnownMigration($name)) return false;
        try {
            $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
            return $stmt->execute([$name]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Appliquer uniquement les migrations sélectionnées (dans l'ordre de leur nom)
     *
     * @param string[] $names Liste des noms de migrations à appliquer
     * @return array ['applied' => string[], 'errors' => string[], 'skipped' => string[]]
     */
    public function migrateSelected(array $names): array {
        $available = $this->getAvailable();
        $applied = $this->getApplied();
        $results = ['applied' => [], 'errors' => [], 'skipped' => []];

        // Filtrer + trier par ordre de nom
        $toRun = [];
        foreach ($names as $name) {
            if (!isset($available[$name])) {
                $results['errors'][] = "$name — fichier introuvable";
                continue;
            }
            if (in_array($name, $applied, true)) {
                $results['skipped'][] = $name;
                continue;
            }
            $toRun[$name] = $available[$name];
        }
        ksort($toRun);

        if (empty($toRun)) return $results;

        $batch = $this->getNextBatch();

        foreach ($toRun as $name => $file) {
            try {
                $sql = file_get_contents($file);
                if (empty(trim($sql))) {
                    $results['errors'][] = "$name — fichier vide, ignoré";
                    continue;
                }

                $this->db->beginTransaction();
                foreach ($this->splitStatements($sql) as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $this->db->exec($statement);
                    }
                }
                $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch, applied_at) VALUES (?, ?, NOW())");
                $stmt->execute([$name, $batch]);
                $this->db->commit();
                $results['applied'][] = $name;
            } catch (PDOException $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                $results['errors'][] = "$name — " . $e->getMessage();
                break;
            }
        }

        return $results;
    }

    /**
     * Vérifier qu'un nom de migration correspond à un fichier réel
     * (protection contre injection de noms arbitraires)
     */
    private function isKnownMigration(string $name): bool {
        return array_key_exists($name, $this->getAvailable());
    }

    /**
     * Découper un fichier SQL en instructions individuelles
     * Gère les DELIMITER pour les procédures/triggers
     */
    private function splitStatements(string $sql): array {
        // Supprimer les commentaires en début de ligne
        $sql = preg_replace('/^--.*$/m', '', $sql);

        // Si pas de DELIMITER custom, simple split par ;
        if (stripos($sql, 'DELIMITER') === false) {
            $parts = explode(';', $sql);
            return array_filter(array_map('trim', $parts), fn($s) => !empty($s));
        }

        // Gestion DELIMITER pour procédures stockées / triggers
        $statements = [];
        $delimiter = ';';
        $lines = explode("\n", $sql);
        $buffer = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $m)) {
                $delimiter = trim($m[1]);
                continue;
            }

            if (str_ends_with($trimmed, $delimiter)) {
                $buffer .= substr($trimmed, 0, -strlen($delimiter));
                if (!empty(trim($buffer))) {
                    $statements[] = trim($buffer);
                }
                $buffer = '';
            } else {
                $buffer .= $line . "\n";
            }
        }

        if (!empty(trim($buffer))) {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
