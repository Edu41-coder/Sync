<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Diagnostic - Synd_Gest</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #ffc107; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .icon { font-size: 24px; margin-right: 10px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic Synd_Gest</h1>
    
    <?php
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../app/core/Database.php';
    
    // Test 1: Connexion BDD
    echo '<div class="test">';
    echo '<h2>Test 1: Connexion à la base de données</h2>';
    try {
        $db = Database::getInstance()->getConnection();
        echo '<div class="success"><span class="icon">✅</span> Connexion réussie !</div>';
    } catch (Exception $e) {
        echo '<div class="error"><span class="icon">❌</span> Erreur: ' . $e->getMessage() . '</div>';
        exit;
    }
    echo '</div>';
    
    // Test 2: Structure table users
    echo '<div class="test">';
    echo '<h2>Test 2: Structure de la table users</h2>';
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredCols = ['prenom', 'nom', 'telephone', 'actif'];
    $allPresent = true;
    foreach ($requiredCols as $col) {
        if (in_array($col, $columns)) {
            echo "<div class='success'><span class='icon'>✅</span> Colonne '$col' présente</div>";
        } else {
            echo "<div class='error'><span class='icon'>❌</span> Colonne '$col' manquante</div>";
            $allPresent = false;
        }
    }
    echo '</div>';
    
    // Test 3: Requête SELECT
    echo '<div class="test">';
    echo '<h2>Test 3: Lecture des utilisateurs</h2>';
    try {
        $stmt = $db->query("SELECT id, username, prenom, nom, email, actif FROM users LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo '<div class="success"><span class="icon">✅</span> Requête réussie</div>';
            echo '<pre>' . print_r($user, true) . '</pre>';
        } else {
            echo '<div class="warning"><span class="icon">⚠️</span> Aucun utilisateur trouvé</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error"><span class="icon">❌</span> Erreur: ' . $e->getMessage() . '</div>';
    }
    echo '</div>';
    
    // Test 4: Modèle User
    echo '<div class="test">';
    echo '<h2>Test 4: Chargement du modèle User</h2>';
    try {
        require_once __DIR__ . '/../app/core/Model.php';
        require_once __DIR__ . '/../app/models/User.php';
        $userModel = new User();
        echo '<div class="success"><span class="icon">✅</span> Modèle User chargé</div>';
        
        // Test find
        $userData = $userModel->find(1);
        if ($userData) {
            echo '<div class="success"><span class="icon">✅</span> Méthode find() fonctionne</div>';
            echo '<pre>' . print_r($userData, true) . '</pre>';
        }
    } catch (Exception $e) {
        echo '<div class="error"><span class="icon">❌</span> Erreur: ' . $e->getMessage() . '</div>';
    }
    echo '</div>';
    
    // Test 5: Routes AdminController
    echo '<div class="test">';
    echo '<h2>Test 5: Contrôleur Admin</h2>';
    $adminFile = __DIR__ . '/../app/controllers/AdminController.php';
    if (file_exists($adminFile)) {
        echo '<div class="success"><span class="icon">✅</span> AdminController.php existe</div>';
        
        // Vérifier les méthodes
        $content = file_get_contents($adminFile);
        $methods = ['create', 'store', 'edit', 'update', 'toggle'];
        foreach ($methods as $method) {
            if (strpos($content, "public function $method") !== false) {
                echo "<div class='success'><span class='icon'>✅</span> Méthode '$method' trouvée</div>";
            } else {
                echo "<div class='error'><span class='icon'>❌</span> Méthode '$method' manquante</div>";
            }
        }
    } else {
        echo '<div class="error"><span class="icon">❌</span> AdminController.php introuvable</div>';
    }
    echo '</div>';
    
    // Test 6: Vues
    echo '<div class="test">';
    echo '<h2>Test 6: Fichiers de vues</h2>';
    $views = [
        'create' => __DIR__ . '/../app/views/admin/users/create.php',
        'edit' => __DIR__ . '/../app/views/admin/users/edit.php',
        'index' => __DIR__ . '/../app/views/admin/users/index.php'
    ];
    foreach ($views as $name => $path) {
        if (file_exists($path)) {
            echo "<div class='success'><span class='icon'>✅</span> Vue '$name' existe</div>";
        } else {
            echo "<div class='error'><span class='icon'>❌</span> Vue '$name' manquante</div>";
        }
    }
    echo '</div>';
    
    // Test 7: URL Routing
    echo '<div class="test">';
    echo '<h2>Test 7: Configuration URLs</h2>';
    echo '<p><strong>BASE_URL:</strong> ' . BASE_URL . '</p>';
    echo '<p><strong>Tester les URLs:</strong></p>';
    echo '<ul>';
    echo '<li><a href="' . BASE_URL . '/admin/users" target="_blank">Liste des utilisateurs</a></li>';
    echo '<li><a href="' . BASE_URL . '/admin/users/create" target="_blank">Créer un utilisateur</a></li>';
    echo '<li><a href="' . BASE_URL . '/admin/users/edit/1" target="_blank">Éditer utilisateur #1</a></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div class="test">';
    echo '<h2>✅ Diagnostic terminé</h2>';
    echo '<p>Si tous les tests sont verts, le système devrait fonctionner.</p>';
    echo '<p>Si vous rencontrez toujours des problèmes, vérifiez:</p>';
    echo '<ul>';
    echo '<li>Que vous êtes connecté en tant qu\'admin</li>';
    echo '<li>Les logs Apache/PHP pour les erreurs</li>';
    echo '<li>La console du navigateur (F12) pour les erreurs JavaScript</li>';
    echo '</ul>';
    echo '</div>';
    ?>
</body>
</html>
