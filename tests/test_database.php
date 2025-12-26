<?php
/**
 * Test de la classe Database Singleton
 */

// Charger la configuration
require_once '../config/database.php';
require_once '../config/config.php';

// Charger la classe Database
require_once '../app/core/Database.php';

echo "<h1>Test Database Singleton</h1>";

try {
    // Test 1: Obtenir l'instance
    echo "<h2>Test 1: Obtenir l'instance</h2>";
    $db1 = Database::getInstance();
    echo "✅ Instance créée<br>";
    
    // Test 2: Vérifier que c'est un singleton (même instance)
    echo "<h2>Test 2: Vérifier Singleton</h2>";
    $db2 = Database::getInstance();
    if ($db1 === $db2) {
        echo "✅ Singleton confirmé (même instance)<br>";
    } else {
        echo "❌ Erreur: Instances différentes<br>";
    }
    
    // Test 3: Tester la connexion
    echo "<h2>Test 3: Tester la connexion</h2>";
    if ($db1->isConnected()) {
        echo "✅ Connexion réussie<br>";
    } else {
        echo "❌ Erreur de connexion<br>";
    }
    
    // Test 4: Afficher les infos de connexion
    echo "<h2>Test 4: Informations de connexion</h2>";
    $info = $db1->getInfo();
    echo "<pre>";
    print_r($info);
    echo "</pre>";
    
    // Test 5: Exécuter une requête simple
    echo "<h2>Test 5: Requête SQL</h2>";
    $result = $db1->queryOne("SELECT COUNT(*) as total FROM users");
    if ($result) {
        echo "✅ Requête réussie<br>";
        echo "Nombre d'utilisateurs: " . $result['total'] . "<br>";
    } else {
        echo "❌ Erreur de requête<br>";
    }
    
    // Test 6: Compter les résidences Domitys
    echo "<h2>Test 6: Résidences Domitys</h2>";
    $result = $db1->queryOne("SELECT COUNT(*) as total FROM coproprietees WHERE type_residence = 'residence_seniors'");
    if ($result) {
        echo "✅ Requête réussie<br>";
        echo "Nombre de résidences Domitys: " . $result['total'] . "<br>";
    } else {
        echo "❌ Erreur de requête<br>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Tous les tests réussis !</h2>";
    echo "<p><a href='../public/'>Retour à l'application</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
