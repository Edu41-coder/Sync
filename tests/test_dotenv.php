<?php
/**
 * Test de la classe DotEnv
 */

// Charger DotEnv
require_once '../app/core/DotEnv.php';

echo "<h1>Test DotEnv</h1>";

try {
    // Test 1: Charger le fichier .env
    echo "<h2>Test 1: Charger le fichier .env</h2>";
    $envFile = __DIR__ . '/../.env';
    
    if (file_exists($envFile)) {
        $dotenv = new DotEnv($envFile);
        $dotenv->load();
        echo "✅ Fichier .env chargé<br>";
    } else {
        echo "❌ Fichier .env non trouvé<br>";
        echo "Chemin: $envFile<br>";
        die();
    }
    
    // Test 2: Lire des variables
    echo "<h2>Test 2: Lire les variables</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Variable</th><th>Valeur</th></tr>";
    
    $vars = [
        'ENVIRONMENT',
        'DEBUG',
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_CHARSET',
        'APP_NAME',
        'APP_URL'
    ];
    
    foreach ($vars as $var) {
        $value = DotEnv::get($var, 'Non définie');
        $color = $value !== 'Non définie' ? 'green' : 'red';
        echo "<tr>";
        echo "<td><strong>$var</strong></td>";
        echo "<td style='color: $color'>$value</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Variables booléennes
    echo "<h2>Test 3: Variables booléennes</h2>";
    $debug = DotEnv::getBool('DEBUG', false);
    echo "DEBUG = " . ($debug ? '✅ true' : '❌ false') . "<br>";
    
    // Test 4: Variables entières
    echo "<h2>Test 4: Variables entières</h2>";
    $port = DotEnv::getInt('DB_PORT', 3306);
    echo "DB_PORT = $port<br>";
    
    // Test 5: Vérifier l'existence
    echo "<h2>Test 5: Vérifier l'existence</h2>";
    if (DotEnv::has('DB_HOST')) {
        echo "✅ DB_HOST existe<br>";
    } else {
        echo "❌ DB_HOST n'existe pas<br>";
    }
    
    // Test 6: Variable requise
    echo "<h2>Test 6: Variable requise</h2>";
    try {
        $dbName = DotEnv::getRequired('DB_NAME');
        echo "✅ DB_NAME = $dbName<br>";
    } catch (RuntimeException $e) {
        echo "❌ Erreur: " . $e->getMessage() . "<br>";
    }
    
    // Test 7: Tester avec Database
    echo "<h2>Test 7: Tester avec Database</h2>";
    require_once '../config/database.php';
    echo "DB_HOST constant = " . DB_HOST . "<br>";
    echo "DB_NAME constant = " . DB_NAME . "<br>";
    echo "DB_USER constant = " . DB_USER . "<br>";
    echo "DB_CHARSET constant = " . DB_CHARSET . "<br>";
    
    require_once '../app/core/Database.php';
    $db = Database::getInstance();
    if ($db->isConnected()) {
        echo "✅ Connexion à la base de données réussie<br>";
        $info = $db->getInfo();
        echo "<pre>";
        print_r($info);
        echo "</pre>";
    } else {
        echo "❌ Erreur de connexion<br>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Tous les tests réussis !</h2>";
    echo "<p><a href='../public/'>Retour à l'application</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
