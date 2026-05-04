<?php

/**
 * Classe Logger - Gestion des logs de sécurité
 * 
 * Fonctionnalités :
 * - Logging des tentatives d'accès non autorisées
 * - Logging des erreurs d'authentification
 * - Logging des actions sensibles
 * - Rotation des fichiers de log
 */
class Logger {
    
    private static $logDir = '../logs/';
    private static $maxFileSize = 5242880; // 5 MB
    
    /**
     * Logger une tentative d'accès non autorisée
     * @param string $resource Ressource accédée
     * @param string $reason Raison du refus
     */
    public static function logUnauthorizedAccess($resource, $reason = '') {
        $data = [
            'type' => 'UNAUTHORIZED_ACCESS',
            'resource' => $resource,
            'reason' => $reason,
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'role' => $_SESSION['role'] ?? 'none',
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::write('security.log', $data);
    }
    
    /**
     * Logger une tentative de connexion échouée
     * @param string $username Nom d'utilisateur
     * @param string $reason Raison de l'échec
     */
    public static function logFailedLogin($username, $reason = 'Invalid credentials') {
        $data = [
            'type' => 'FAILED_LOGIN',
            'username' => $username,
            'reason' => $reason,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::write('security.log', $data);
        
        // Bloquer l'IP après 5 tentatives en 15 minutes
        self::checkBruteForce($username);
    }
    
    /**
     * Logger une connexion réussie
     * @param int $userId ID utilisateur
     * @param string $username Nom d'utilisateur
     * @param string $role Rôle
     */
    public static function logSuccessfulLogin($userId, $username, $role) {
        $data = [
            'type' => 'SUCCESSFUL_LOGIN',
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::write('access.log', $data);
    }
    
    /**
     * Logger une action sensible
     * @param string $action Action effectuée
     * @param array $details Détails supplémentaires
     */
    public static function logSensitiveAction($action, $details = []) {
        $data = [
            'type' => 'SENSITIVE_ACTION',
            'action' => $action,
            'details' => $details,
            'user_id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'ip' => self::getClientIp(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::write('audit.log', $data);
    }
    
    /**
     * Logger une erreur CSRF
     */
    public static function logCsrfViolation() {
        $data = [
            'type' => 'CSRF_VIOLATION',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::write('security.log', $data);
    }
    
    /**
     * Logger un dépassement de rate limit
     */
    public static function logRateLimitExceeded() {
        $data = [
            'type' => 'RATE_LIMIT_EXCEEDED',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => self::getClientIp(),
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'request_count' => $_SESSION['request_count'] ?? 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::write('security.log', $data);
    }
    
    /**
     * Vérifier les tentatives de brute force
     * @param string $username Nom d'utilisateur
     */
    private static function checkBruteForce($username) {
        $ip = self::getClientIp();
        $cacheFile = self::$logDir . 'bruteforce_' . md5($ip . $username) . '.cache';
        
        $attempts = [];
        if (file_exists($cacheFile)) {
            $attempts = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        // Nettoyer les tentatives de plus de 15 minutes
        $fifteenMinutesAgo = time() - 900;
        $attempts = array_filter($attempts, function($timestamp) use ($fifteenMinutesAgo) {
            return $timestamp > $fifteenMinutesAgo;
        });
        
        // Ajouter la tentative actuelle
        $attempts[] = time();
        
        // Sauvegarder
        file_put_contents($cacheFile, json_encode($attempts));
        
        // Si plus de 5 tentatives, bloquer
        if (count($attempts) >= 5) {
            self::blockIp($ip, 'Brute force detected');
        }
    }
    
    /**
     * Bloquer une IP temporairement
     * @param string $ip IP à bloquer
     * @param string $reason Raison du blocage
     */
    private static function blockIp($ip, $reason) {
        $blockFile = self::$logDir . 'blocked_ips.json';
        
        $blocked = [];
        if (file_exists($blockFile)) {
            $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
        }
        
        $blocked[$ip] = [
            'reason' => $reason,
            'blocked_at' => time(),
            'expires_at' => time() + 3600 // 1 heure
        ];
        
        file_put_contents($blockFile, json_encode($blocked, JSON_PRETTY_PRINT));
        
        self::logSensitiveAction('IP_BLOCKED', ['ip' => $ip, 'reason' => $reason]);
    }
    
    /**
     * Vérifier si une IP est bloquée
     * @param string $ip IP à vérifier
     * @return bool
     */
    public static function isIpBlocked($ip = null) {
        if ($ip === null) {
            $ip = self::getClientIp();
        }
        
        $blockFile = self::$logDir . 'blocked_ips.json';
        
        if (!file_exists($blockFile)) {
            return false;
        }
        
        $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
        
        if (!isset($blocked[$ip])) {
            return false;
        }
        
        // Vérifier si le blocage a expiré
        if ($blocked[$ip]['expires_at'] < time()) {
            unset($blocked[$ip]);
            file_put_contents($blockFile, json_encode($blocked, JSON_PRETTY_PRINT));
            return false;
        }
        
        return true;
    }
    
    /**
     * Écrire dans un fichier de log
     * @param string $filename Nom du fichier
     * @param array $data Données à logger
     */
    private static function write($filename, $data) {
        // Créer le dossier logs si nécessaire
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        $filepath = self::$logDir . $filename;
        
        // Rotation si le fichier est trop gros
        if (file_exists($filepath) && filesize($filepath) > self::$maxFileSize) {
            self::rotateLog($filename);
        }
        
        // Formater le log
        $logLine = json_encode($data) . PHP_EOL;
        
        // Écrire dans le fichier
        file_put_contents($filepath, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rotation des fichiers de log
     * @param string $filename Nom du fichier
     */
    private static function rotateLog($filename) {
        $filepath = self::$logDir . $filename;
        $timestamp = date('Y-m-d_H-i-s');
        $archiveFile = self::$logDir . pathinfo($filename, PATHINFO_FILENAME) . '_' . $timestamp . '.log';
        
        rename($filepath, $archiveFile);
        
        // Compresser l'archive (si gzip disponible)
        if (function_exists('gzencode')) {
            $content = file_get_contents($archiveFile);
            file_put_contents($archiveFile . '.gz', gzencode($content, 9));
            unlink($archiveFile);
        }
    }
    
    /**
     * Obtenir l'IP réelle du client (même derrière un proxy)
     * @return string IP du client
     */
    private static function getClientIp() {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // =================================================================
    //  AUDIT TRAIL MÉTIER (Phase 11)
    // =================================================================
    //  Stocké en BDD (`logs_activite`) — distinct des logs de sécurité fichier.
    //  Utilisé par les modules à enjeu réglementaire (compta, paie, AG, sinistres).
    //  Les logs de sécurité (auth, CSRF, rate limit) restent en fichier plat.

    /**
     * Insère une entrée dans l'audit trail métier.
     *
     * Utilisation typique depuis un model :
     *   Logger::audit('ecriture_create', 'ecritures_comptables', $id, [
     *       'montant_ttc' => 120.50,
     *       'module_source' => 'jardinage',
     *   ]);
     *
     * @param string $action      Verbe métier (ex: 'ecriture_create', 'tva_declaree', 'exercice_cloture')
     * @param string|null $table  Nom de la table impactée (peut être null pour actions transverses)
     * @param int|null    $recordId  ID de la ligne impactée
     * @param array       $details Données contextuelles (sérialisées en JSON)
     * @param int|null    $userId  Si null, lit depuis $_SESSION['user_id']
     * @return bool       true si insertion OK
     */
    public static function audit(string $action, ?string $table = null, ?int $recordId = null, array $details = [], ?int $userId = null): bool {
        try {
            $userId = $userId ?? ($_SESSION['user_id'] ?? null);
            $ip     = self::getClientIp();
            $ua     = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $detailsJson = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "INSERT INTO logs_activite (user_id, action, table_name, record_id, details, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                mb_substr($action, 0, 100),
                $table ? mb_substr($table, 0, 50) : null,
                $recordId,
                $detailsJson,
                $ip,
                $ua,
            ]);
            return true;
        } catch (Throwable $e) {
            // L'audit ne doit jamais bloquer une action métier — log silencieux dans security.log
            self::write('security.log', [
                'type' => 'AUDIT_FAILURE',
                'action' => $action,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
            return false;
        }
    }

    /**
     * Préfixes d'actions liés au domaine comptable (Phase 11).
     * Tout ce qui ne commence pas par un de ces préfixes est exclu de la page
     * /comptabilite/auditTrail — protection contre la pollution par d'autres
     * sources qui pourraient écrire dans `logs_activite`.
     */
    public const COMPTA_ACTION_PREFIXES = [
        'ecriture_', 'exercice_', 'bulletin_', 'tva_',
        'bank_', 'salarie_', 'export_',
    ];

    /**
     * Construit la clause SQL "AND (action LIKE ? OR action LIKE ? ...)" + remplit $params.
     * Retourne string vide si la liste de préfixes est vide.
     */
    private static function buildPrefixesClause(array $prefixes, array &$params): string {
        if (empty($prefixes)) return '';
        $likes = [];
        foreach ($prefixes as $p) {
            $likes[] = 'action LIKE ?';
            $params[] = $p . '%';
        }
        return ' AND (' . implode(' OR ', $likes) . ')';
    }

    /**
     * Lecture filtrée des entrées d'audit (pour la page /comptabilite/auditTrail).
     *
     * @param array $filters  user_id, action (LIKE %), table, date_min, date_max, search (LIKE sur details),
     *                        actions_prefixes (array — restreint à un domaine, ex: COMPTA_ACTION_PREFIXES)
     * @param int   $limit    Limite de résultats (défaut 200)
     * @return array          Lignes brutes avec join username
     */
    public static function getAuditTrail(array $filters = [], int $limit = 200): array {
        try {
            $sql = "SELECT l.*, u.username, u.role
                    FROM logs_activite l
                    LEFT JOIN users u ON u.id = l.user_id
                    WHERE 1=1";
            $params = [];
            // Filtre whitelist par préfixes (utilisé par la page compta)
            if (!empty($filters['actions_prefixes']) && is_array($filters['actions_prefixes'])) {
                $sql .= self::buildPrefixesClause($filters['actions_prefixes'], $params);
            }
            if (!empty($filters['user_id']))  { $sql .= " AND l.user_id = ?";   $params[] = (int)$filters['user_id']; }
            if (!empty($filters['action']))   { $sql .= " AND l.action LIKE ?"; $params[] = '%' . $filters['action'] . '%'; }
            if (!empty($filters['table']))    { $sql .= " AND l.table_name = ?"; $params[] = $filters['table']; }
            if (!empty($filters['date_min'])) { $sql .= " AND l.created_at >= ?"; $params[] = $filters['date_min'] . ' 00:00:00'; }
            if (!empty($filters['date_max'])) { $sql .= " AND l.created_at <= ?"; $params[] = $filters['date_max'] . ' 23:59:59'; }
            if (!empty($filters['search']))   { $sql .= " AND l.details LIKE ?"; $params[] = '%' . $filters['search'] . '%'; }

            $sql .= " ORDER BY l.created_at DESC, l.id DESC LIMIT " . max(1, min(2000, $limit));

            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Liste distincte des actions présentes en BDD (pour le sélecteur de filtre).
     * @param array $prefixes  Whitelist optionnelle de préfixes (mêmes règles que getAuditTrail).
     */
    public static function getAuditActions(array $prefixes = []): array {
        try {
            $sql = "SELECT DISTINCT action FROM logs_activite WHERE 1=1";
            $params = [];
            $sql .= self::buildPrefixesClause($prefixes, $params);
            $sql .= " ORDER BY action ASC";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Liste distincte des tables présentes en BDD (pour le sélecteur de filtre).
     * @param array $prefixes  Whitelist optionnelle de préfixes d'actions.
     */
    public static function getAuditTables(array $prefixes = []): array {
        try {
            $sql = "SELECT DISTINCT table_name FROM logs_activite
                    WHERE table_name IS NOT NULL";
            $params = [];
            $sql .= self::buildPrefixesClause($prefixes, $params);
            $sql .= " ORDER BY table_name ASC";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Obtenir les derniers logs de sécurité
     * @param int $limit Nombre de lignes
     * @return array Logs
     */
    public static function getRecentSecurityLogs($limit = 50) {
        $filepath = self::$logDir . 'security.log';
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $lines = file($filepath, FILE_IGNORE_NEW_LINES);
        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, $limit);
        
        $logs = [];
        foreach ($lines as $line) {
            $logs[] = json_decode($line, true);
        }
        
        return $logs;
    }
}
