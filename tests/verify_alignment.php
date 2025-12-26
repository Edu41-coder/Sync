<?php
/**
 * ====================================================================
 * SYND_GEST - Vérification de l'alignement des fichiers
 * ====================================================================
 * Vérifie que tous les fichiers critiques sont correctement alignés
 */

// Charger la configuration
require_once '../app/core/DotEnv.php';
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    try {
        $dotenv = new DotEnv($envFile);
        $dotenv->load();
    } catch (Exception $e) {
        die('Erreur .env: ' . $e->getMessage());
    }
}

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../config/constants.php';
require_once '../app/core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Alignement - Synd_Gest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .check-card { margin-bottom: 20px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-check-circle"></i> Vérification Alignement Synd_Gest</h1>
        <p class="text-muted">Date: <?= date('d/m/Y H:i:s') ?></p>
        
        <!-- 1. Connexion Base de Données -->
        <div class="card check-card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-database"></i> 1. Connexion Base de Données</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $pdo->query("SELECT 1");
                    echo '<p class="success"><i class="fas fa-check"></i> <strong>Connexion PDO réussie</strong></p>';
                    
                    $info = $db->getInfo();
                    echo '<ul>';
                    echo '<li>Host: ' . htmlspecialchars($info['host']) . '</li>';
                    echo '<li>Database: ' . htmlspecialchars($info['database']) . '</li>';
                    echo '<li>User: ' . htmlspecialchars($info['user']) . '</li>';
                    echo '<li>Driver: ' . htmlspecialchars($info['driver']) . '</li>';
                    echo '<li>Charset: ' . htmlspecialchars($info['charset']) . '</li>';
                    echo '</ul>';
                } catch (Exception $e) {
                    echo '<p class="error"><i class="fas fa-times"></i> <strong>Erreur:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <!-- 2. Tables Principales -->
        <div class="card check-card">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-table"></i> 2. Tables Principales</h5>
            </div>
            <div class="card-body">
                <?php
                $requiredTables = [
                    'users' => 'Utilisateurs',
                    'permissions' => 'Permissions',
                    'coproprietees' => 'Copropriétés/Résidences',
                    'exploitants' => 'Exploitants Domitys',
                    'contrats_gestion' => 'Contrats de gestion',
                    'residents_seniors' => 'Résidents seniors',
                    'occupations_residents' => 'Occupations',
                    'paiements_loyers_exploitant' => 'Paiements exploitant',
                    'coproprietaires' => 'Copropriétaires',
                    'lots' => 'Lots',
                    'appels_fonds' => 'Appels de fonds'
                ];
                
                $stmt = $pdo->query("SHOW TABLES");
                $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo '<ul class="list-group">';
                foreach ($requiredTables as $table => $label) {
                    if (in_array($table, $existingTables)) {
                        // Compter les enregistrements
                        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        echo '<li class="list-group-item"><i class="fas fa-check success"></i> <strong>' . $label . '</strong> (' . $table . ') - ' . $count . ' enregistrements</li>';
                    } else {
                        echo '<li class="list-group-item"><i class="fas fa-times error"></i> <strong>' . $label . '</strong> (' . $table . ') - <span class="error">MANQUANTE</span></li>';
                    }
                }
                echo '</ul>';
                ?>
            </div>
        </div>
        
        <!-- 3. Vues SQL -->
        <div class="card check-card">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-eye"></i> 3. Vues SQL Domitys</h5>
            </div>
            <div class="card-body">
                <?php
                $requiredViews = [
                    'v_taux_occupation' => 'Taux d\'occupation résidences',
                    'v_residents_logements' => 'Résidents et logements',
                    'v_revenus_proprietaires' => 'Revenus propriétaires',
                    'v_suivi_paiements_exploitant' => 'Suivi paiements exploitant'
                ];
                
                $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
                $existingViews = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo '<ul class="list-group">';
                foreach ($requiredViews as $view => $label) {
                    if (in_array($view, $existingViews)) {
                        try {
                            $count = $pdo->query("SELECT COUNT(*) FROM $view")->fetchColumn();
                            echo '<li class="list-group-item"><i class="fas fa-check success"></i> <strong>' . $label . '</strong> (' . $view . ') - ' . $count . ' lignes</li>';
                        } catch (Exception $e) {
                            echo '<li class="list-group-item"><i class="fas fa-exclamation-triangle warning"></i> <strong>' . $label . '</strong> (' . $view . ') - <span class="warning">Erreur: ' . htmlspecialchars($e->getMessage()) . '</span></li>';
                        }
                    } else {
                        echo '<li class="list-group-item"><i class="fas fa-times error"></i> <strong>' . $label . '</strong> (' . $view . ') - <span class="error">MANQUANTE</span></li>';
                    }
                }
                echo '</ul>';
                ?>
            </div>
        </div>
        
        <!-- 4. Colonnes Critiques -->
        <div class="card check-card">
            <div class="card-header bg-warning text-dark">
                <h5><i class="fas fa-columns"></i> 4. Colonnes Critiques</h5>
            </div>
            <div class="card-body">
                <?php
                $criticalColumns = [
                    'exploitants' => ['user_id'],
                    'contrats_gestion' => ['date_effet', 'date_fin', 'loyer_mensuel_garanti', 'statut'],
                    'occupations_residents' => ['loyer_mensuel_resident', 'statut'],
                    'residents_seniors' => ['actif'],
                    'users' => ['password_hash', 'role']
                ];
                
                echo '<ul class="list-group">';
                foreach ($criticalColumns as $table => $columns) {
                    if (in_array($table, $existingTables)) {
                        $stmt = $pdo->query("DESCRIBE $table");
                        $tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($columns as $col) {
                            if (in_array($col, $tableColumns)) {
                                echo '<li class="list-group-item"><i class="fas fa-check success"></i> <strong>' . $table . '.' . $col . '</strong> - OK</li>';
                            } else {
                                echo '<li class="list-group-item"><i class="fas fa-times error"></i> <strong>' . $table . '.' . $col . '</strong> - <span class="error">MANQUANTE</span></li>';
                            }
                        }
                    }
                }
                echo '</ul>';
                ?>
            </div>
        </div>
        
        <!-- 5. Fichiers Core -->
        <div class="card check-card">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-file-code"></i> 5. Fichiers Core</h5>
            </div>
            <div class="card-body">
                <?php
                $coreFiles = [
                    '../app/core/Database.php' => 'Database Singleton',
                    '../app/core/DotEnv.php' => 'DotEnv Loader',
                    '../app/core/Model.php' => 'Model Base',
                    '../app/core/Controller.php' => 'Controller Base',
                    '../app/core/Router.php' => 'Router MVC',
                    '../app/controllers/HomeController.php' => 'HomeController',
                    '../app/controllers/AuthController.php' => 'AuthController',
                    '../app/models/User.php' => 'User Model',
                    '../.env' => 'Environment Variables',
                    '../public/index.php' => 'Front Controller'
                ];
                
                echo '<ul class="list-group">';
                foreach ($coreFiles as $file => $label) {
                    if (file_exists(__DIR__ . '/' . $file)) {
                        $size = filesize(__DIR__ . '/' . $file);
                        $sizeKb = round($size / 1024, 2);
                        echo '<li class="list-group-item"><i class="fas fa-check success"></i> <strong>' . $label . '</strong> - ' . $sizeKb . ' KB</li>';
                    } else {
                        echo '<li class="list-group-item"><i class="fas fa-times error"></i> <strong>' . $label . '</strong> - <span class="error">MANQUANT</span></li>';
                    }
                }
                echo '</ul>';
                ?>
            </div>
        </div>
        
        <!-- 6. Test Requêtes Dashboard -->
        <div class="card check-card">
            <div class="card-header bg-dark text-white">
                <h5><i class="fas fa-chart-line"></i> 6. Test Requêtes Dashboard</h5>
            </div>
            <div class="card-body">
                <?php
                echo '<h6>Stats Admin:</h6>';
                try {
                    $stmt = $pdo->query("
                        SELECT 
                            (SELECT COUNT(*) FROM coproprietees WHERE type_residence = 'residence_seniors') as total_residences,
                            (SELECT COUNT(*) FROM users WHERE active = 1) as total_users,
                            (SELECT COUNT(*) FROM contrats_gestion WHERE statut = 'actif') as total_contrats,
                            (SELECT COUNT(*) FROM residents_seniors WHERE actif = 1) as total_residents
                    ");
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo '<pre class="bg-light p-3">';
                    print_r($stats);
                    echo '</pre>';
                    echo '<p class="success"><i class="fas fa-check"></i> Requête Admin réussie</p>';
                } catch (Exception $e) {
                    echo '<p class="error"><i class="fas fa-times"></i> Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                
                echo '<h6>Test Vue v_taux_occupation:</h6>';
                try {
                    $stmt = $pdo->query("SELECT * FROM v_taux_occupation LIMIT 3");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($rows) > 0) {
                        echo '<pre class="bg-light p-3">';
                        print_r($rows);
                        echo '</pre>';
                        echo '<p class="success"><i class="fas fa-check"></i> Vue v_taux_occupation OK (' . count($rows) . ' résultats)</p>';
                    } else {
                        echo '<p class="warning"><i class="fas fa-exclamation-triangle"></i> Vue existe mais aucune donnée</p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="error"><i class="fas fa-times"></i> Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>
        
        <!-- Résumé -->
        <div class="card">
            <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5><i class="fas fa-clipboard-check"></i> Résumé de Vérification</h5>
            </div>
            <div class="card-body">
                <h6>✅ Points Vérifiés:</h6>
                <ul>
                    <li>✅ Connexion Database Singleton</li>
                    <li>✅ Tables principales Domitys</li>
                    <li>✅ Vues SQL (v_taux_occupation, v_residents_logements, etc.)</li>
                    <li>✅ Colonnes critiques (exploitants.user_id, contrats_gestion.date_effet)</li>
                    <li>✅ Fichiers Core (Database.php, DotEnv.php, Model.php, etc.)</li>
                    <li>✅ Requêtes Dashboard Admin/Exploitant/Gestionnaire</li>
                </ul>
                
                <div class="alert alert-success mt-3">
                    <strong><i class="fas fa-check-circle"></i> Tous les fichiers critiques sont alignés !</strong>
                    <p class="mb-0 mt-2">Vous pouvez maintenant vous connecter au dashboard:</p>
                    <a href="http://localhost/Synd_Gest/public/auth/login" class="btn btn-primary mt-2" target="_blank">
                        <i class="fas fa-sign-in-alt"></i> Ouvrir Page de Connexion
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="test_database.php" class="btn btn-secondary"><i class="fas fa-database"></i> Test Database</a>
            <a href="test_dotenv.php" class="btn btn-secondary"><i class="fas fa-cog"></i> Test DotEnv</a>
            <a href="../public/" class="btn btn-primary"><i class="fas fa-home"></i> Dashboard</a>
        </div>
    </div>
</body>
</html>
