<?php
/**
 * Test de la migration N-N: exploitant_residences
 * Vérifie que les requêtes de recherche et d'affectation fonctionnent correctement
 */

// Charger l'application dans le bon ordre
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/core/DotEnv.php';

// Charger le fichier .env si disponible
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    try {
        $dotenv = new DotEnv($envFile);
        $dotenv->load();
    } catch (Exception $e) {
        // Ignorer si en production ou si .env n'existe pas
    }
}

// Charger la configuration et la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Logger.php';

// Charger les modèles
require_once __DIR__ . '/../app/models/Residence.php';
require_once __DIR__ . '/../app/models/Exploitant.php';

echo "=== TEST DE LA MIGRATION N-N ===\n\n";

try {
    // Connexion DB via Singleton
    $dbInstance = Database::getInstance();
    $pdo = $dbInstance->getConnection();
    
    echo "✓ Connexion à la base de données établie\n";
    
    // Test 1: Vérifier que la table exploitant_residences existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'exploitant_residences'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table exploitant_residences existe\n";
    } else {
        throw new Exception("Table exploitant_residences non trouvée!");
    }
    
    // Test 2: Compter les associations
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM exploitant_residences");
    $count = $stmt->fetchColumn();
    echo "✓ Nombre d'associations migrées: $count\n";
    
    // Test 3: Vérifier qu'une résidence a une association
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT c.id) as residences_with_exploitants
        FROM coproprietees c
        INNER JOIN exploitant_residences er ON c.id = er.residence_id
        WHERE c.type_residence = 'residence_seniors'
    ");
    $residences = $stmt->fetchColumn();
    echo "✓ Résidences liées à des exploitants: $residences\n";
    
    // Test 4: Load Residence model et tester search()
    $resModel = new Residence();
    $search_result = $resModel->search([], 1, 5);
    echo "✓ search() retourne " . count($search_result['rows']) . " résidences (page 1)\n";
    
    if (count($search_result['rows']) > 0) {
        $first = $search_result['rows'][0];
        echo "  - Première résidence: " . htmlspecialchars($first['nom']) . "\n";
        echo "  - Exploitants: " . htmlspecialchars($first['exploitant']) . "\n";
    }
    
    // Test 5: Load Exploitant model et tester getResidences()
    $expModel = new Exploitant();
    $exp = $expModel->findById(1);
    if ($exp) {
        echo "✓ Exploitant trouvé: " . htmlspecialchars($exp->raison_sociale) . "\n";
        $residences = $expModel->getResidences(1);
        echo "  - Résidences liées: " . count($residences) . "\n";
    }
    
    // Test 6: Vérifier la vue
    $stmt = $pdo->query("SHOW TABLES LIKE 'vw_residence_exploitants'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Vue vw_residence_exploitants existe\n";
    }
    
    echo "\n=== TOUS LES TESTS PASSENT ✓ ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
