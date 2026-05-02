<?php
/**
 * ====================================================================
 * SYND_GEST - Test automatisé : requireAuth() sur tous les controllers
 * ====================================================================
 *
 * Vérifie que toutes les méthodes publiques des controllers appellent
 * $this->requireAuth() ou $this->requireRole([...]) dans leur corps.
 *
 * Usage :
 *   php tests/test_require_auth.php          (rapport synthétique)
 *   php tests/test_require_auth.php -v       (détail des méthodes whitelistées)
 *
 * Exit code :
 *   0 = toutes les méthodes sont protégées
 *   1 = au moins une violation détectée
 *
 * Comment opt-out une méthode publique légitime (ex: page de login) :
 *   1. Ajouter le FQN dans la WHITELIST ci-dessous, OU
 *   2. Ajouter @public-no-auth dans le docblock de la méthode :
 *      /**
 *       * Page d'accueil publique.
 *       * @public-no-auth
 *       *\/
 *      public function landing() { ... }
 */

declare(strict_types=1);

// =====================================================================
// CONFIGURATION
// =====================================================================

const CONTROLLERS_DIR = __DIR__ . '/../app/controllers';

/**
 * Méthodes publiques qui n'ont PAS besoin d'auth (login, callbacks, etc.).
 * Format : ClassName::methodName
 */
const WHITELIST = [
    // Authentification : la page de login DOIT être accessible non-connecté
    'AuthController::login',
    'AuthController::logout',
    'AuthController::register',
    'AuthController::forgotPassword',
    'AuthController::resetPassword',
];

/**
 * Tag de docblock pour autoriser une méthode publique sans auth.
 */
const OPTOUT_TAG = '@public-no-auth';

/**
 * Patterns acceptés dans le corps de la méthode.
 * Si l'un d'eux est trouvé, la méthode est considérée comme protégée.
 */
const REQUIRED_PATTERNS = [
    '$this->requireAuth(',
    '$this->requireRole(',
];

// =====================================================================
// COULEURS CLI
// =====================================================================

$useColor = !in_array('--no-color', $argv ?? [], true)
    && (PHP_OS_FAMILY !== 'Windows' || getenv('ANSICON') || getenv('WT_SESSION') || getenv('TERM') === 'xterm-256color');

$verbose = in_array('-v', $argv ?? [], true) || in_array('--verbose', $argv ?? [], true);

function c(string $code, string $text): string {
    global $useColor;
    return $useColor ? "\033[{$code}m{$text}\033[0m" : $text;
}
function red(string $t): string    { return c('31', $t); }
function green(string $t): string  { return c('32', $t); }
function yellow(string $t): string { return c('33', $t); }
function gray(string $t): string   { return c('90', $t); }
function bold(string $t): string   { return c('1',  $t); }

// =====================================================================
// PARSING : extraire les méthodes publiques d'un fichier controller
// =====================================================================

/**
 * Retourne la liste des méthodes publiques d'un fichier PHP.
 *
 * @return array<int, array{name:string, line:int, body:string, docblock:string}>
 */
function extractPublicMethods(string $file): array {
    $tokens = token_get_all(file_get_contents($file));
    $count  = count($tokens);
    $methods = [];

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];
        if (!is_array($tok) || $tok[0] !== T_PUBLIC) {
            continue;
        }

        // Cherche T_FUNCTION (en sautant whitespace + éventuel T_STATIC)
        $j = $i + 1;
        while ($j < $count
            && is_array($tokens[$j])
            && in_array($tokens[$j][0], [T_WHITESPACE, T_STATIC], true)
        ) {
            $j++;
        }
        if ($j >= $count || !is_array($tokens[$j]) || $tokens[$j][0] !== T_FUNCTION) {
            continue;
        }

        // Skip whitespace puis nom de la méthode (T_STRING)
        $j++;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if ($j >= $count || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) {
            continue;
        }

        $methodName = $tokens[$j][1];
        $methodLine = $tokens[$j][2];

        // Récupère le docblock juste avant T_PUBLIC (en sautant whitespace)
        $docblock = '';
        for ($k = $i - 1; $k >= 0; $k--) {
            if (is_array($tokens[$k]) && $tokens[$k][0] === T_DOC_COMMENT) {
                $docblock = $tokens[$k][1];
                break;
            }
            if (is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                continue;
            }
            break;
        }

        // Trouve { ... } correspondant (gestion profondeur d'accolades)
        $depth     = 0;
        $bodyStart = null;
        $bodyEnd   = null;
        for ($k = $j; $k < $count; $k++) {
            $t = $tokens[$k];
            if ($t === '{') {
                if ($depth === 0) {
                    $bodyStart = $k;
                }
                $depth++;
            } elseif ($t === '}') {
                $depth--;
                if ($depth === 0) {
                    $bodyEnd = $k;
                    break;
                }
            } elseif ($t === ';' && $depth === 0) {
                // méthode abstraite ou interface
                break;
            }
        }

        if ($bodyStart === null || $bodyEnd === null) {
            continue;
        }

        // Reconstruit le source du corps
        $body = '';
        for ($k = $bodyStart; $k <= $bodyEnd; $k++) {
            $body .= is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
        }

        $methods[] = [
            'name'     => $methodName,
            'line'     => $methodLine,
            'body'     => $body,
            'docblock' => $docblock,
        ];

        $i = $bodyEnd;
    }

    return $methods;
}

// =====================================================================
// VÉRIFICATION
// =====================================================================

function isAuthChecked(string $body): bool {
    foreach (REQUIRED_PATTERNS as $pattern) {
        if (str_contains($body, $pattern)) {
            return true;
        }
    }
    return false;
}

function relPath(string $file): string {
    $root = realpath(__DIR__ . '/..');
    $real = realpath($file);
    if ($root && $real && str_starts_with($real, $root)) {
        return ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
    }
    return $file;
}

// =====================================================================
// EXÉCUTION
// =====================================================================

echo "\n" . bold("🔒 Test requireAuth() — controllers Synd_Gest") . "\n";
echo str_repeat('─', 64) . "\n";

if (!is_dir(CONTROLLERS_DIR)) {
    echo red("✗ Dossier introuvable : " . CONTROLLERS_DIR) . "\n";
    exit(2);
}

$files = glob(CONTROLLERS_DIR . '/*.php');
sort($files);

$totalScanned    = 0;
$totalProtected  = 0;
$totalWhitelist  = 0;
$totalOptOut     = 0;
$violations      = [];
$skippedDetail   = [];
$perController   = [];

foreach ($files as $file) {
    $className = basename($file, '.php');
    $methods   = extractPublicMethods($file);

    $cnt = ['ok' => 0, 'whitelist' => 0, 'optout' => 0, 'violations' => []];

    foreach ($methods as $m) {
        if ($m['name'] === '__construct') {
            continue;
        }
        $fqn = "{$className}::{$m['name']}";
        $totalScanned++;

        // 1. Whitelist statique
        if (in_array($fqn, WHITELIST, true)) {
            $cnt['whitelist']++;
            $totalWhitelist++;
            $skippedDetail[] = ['fqn' => $fqn, 'reason' => 'whitelist'];
            continue;
        }

        // 2. Opt-out par tag docblock
        if ($m['docblock'] !== '' && str_contains($m['docblock'], OPTOUT_TAG)) {
            $cnt['optout']++;
            $totalOptOut++;
            $skippedDetail[] = ['fqn' => $fqn, 'reason' => OPTOUT_TAG];
            continue;
        }

        // 3. Vérification effective
        if (isAuthChecked($m['body'])) {
            $cnt['ok']++;
            $totalProtected++;
        } else {
            $cnt['violations'][] = [
                'fqn'  => $fqn,
                'file' => relPath($file),
                'line' => $m['line'],
            ];
            $violations[] = end($cnt['violations']);
        }
    }

    $perController[$className] = $cnt;
}

// =====================================================================
// AFFICHAGE
// =====================================================================

foreach ($perController as $ctrl => $cnt) {
    $total = $cnt['ok'] + $cnt['whitelist'] + $cnt['optout'] + count($cnt['violations']);
    if ($total === 0) {
        continue;
    }

    $hasViolation = count($cnt['violations']) > 0;
    $marker = $hasViolation ? red('✗') : green('✓');

    $details = [];
    if ($cnt['ok'] > 0)         $details[] = "{$cnt['ok']} OK";
    if ($cnt['whitelist'] > 0)  $details[] = gray("{$cnt['whitelist']} whitelist");
    if ($cnt['optout'] > 0)     $details[] = gray("{$cnt['optout']} opt-out");
    if ($hasViolation)          $details[] = red(count($cnt['violations']) . ' violations');

    printf(" %s %-32s %s\n", $marker, $ctrl, '(' . implode(', ', $details) . ')');

    foreach ($cnt['violations'] as $v) {
        echo "      " . red('└─ ' . $v['fqn']) . gray("  {$v['file']}:{$v['line']}") . "\n";
    }
}

echo str_repeat('─', 64) . "\n";

if ($verbose && !empty($skippedDetail)) {
    echo gray("Méthodes ignorées (whitelist / opt-out) :\n");
    foreach ($skippedDetail as $s) {
        echo gray("  · {$s['fqn']}  [{$s['reason']}]") . "\n";
    }
    echo str_repeat('─', 64) . "\n";
}

printf(
    "Total : %d scannées │ %s │ %s │ %s\n",
    $totalScanned,
    green("✓ {$totalProtected} protégées"),
    gray("○ " . ($totalWhitelist + $totalOptOut) . " ignorées"),
    count($violations) > 0
        ? red("✗ " . count($violations) . " violations")
        : green('✗ 0 violation')
);

if (empty($violations)) {
    echo "\n" . green("✅ Toutes les méthodes publiques sont protégées par requireAuth/requireRole.") . "\n\n";
    exit(0);
}

echo "\n" . red("❌ Violations détectées — corriger via l'une des options suivantes :") . "\n";
echo "   1. Ajouter en début de méthode :\n";
echo "        " . bold('$this->requireAuth();') . "  (auth simple)\n";
echo "        " . bold("\$this->requireRole(['admin', ...]);") . "  (auth + rôle)\n";
echo "   2. Ou si l'exposition publique est légitime, ajouter dans le docblock :\n";
echo "        " . bold('* ' . OPTOUT_TAG) . "\n";
echo "      (exemple : page de connexion, callback de paiement public, etc.)\n";
echo "   3. Ou ajouter le FQN à la WHITELIST en haut de ce fichier\n\n";

exit(1);
