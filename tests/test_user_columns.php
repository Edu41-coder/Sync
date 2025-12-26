<?php
/**
 * Test de vérification des colonnes de la table users
 */

require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== Test de la structure de la table users ===\n\n";
    
    // Vérifier la structure
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colonnes actuelles :\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== Vérification des colonnes françaises ===\n";
    $requiredColumns = ['prenom', 'nom', 'telephone', 'actif'];
    $oldColumns = ['first_name', 'last_name', 'phone', 'active'];
    
    $hasNewColumns = true;
    $hasOldColumns = false;
    
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $dbCol) {
            if ($dbCol['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "✅ Colonne '$col' trouvée\n";
        } else {
            echo "❌ Colonne '$col' manquante\n";
            $hasNewColumns = false;
        }
    }
    
    echo "\n=== Vérification des anciennes colonnes (ne devraient pas exister) ===\n";
    foreach ($oldColumns as $col) {
        $found = false;
        foreach ($columns as $dbCol) {
            if ($dbCol['Field'] === $col) {
                $found = true;
                $hasOldColumns = true;
                break;
            }
        }
        if ($found) {
            echo "⚠️  Ancienne colonne '$col' encore présente (devrait être supprimée)\n";
        } else {
            echo "✅ Ancienne colonne '$col' n'existe plus (correct)\n";
        }
    }
    
    echo "\n=== Résultat ===\n";
    if ($hasNewColumns && !$hasOldColumns) {
        echo "✅ Migration réussie ! Toutes les colonnes françaises sont présentes.\n";
    } else if (!$hasNewColumns && $hasOldColumns) {
        echo "❌ Migration NON effectuée ! Les colonnes sont encore en anglais.\n";
        echo "\n⚡ Action requise : Exécuter le script SQL de migration :\n";
        echo "   mysql -u root -p synd_gest < database/rename_user_columns_to_french.sql\n";
    } else {
        echo "⚠️  État incohérent ! Certaines colonnes sont en français, d'autres en anglais.\n";
    }
    
    echo "\n=== Test de requête SELECT ===\n";
    try {
        $stmt = $db->query("SELECT id, username, prenom, nom, email, actif FROM users LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "✅ Requête avec colonnes françaises réussie !\n";
            echo "   Exemple : {$user['prenom']} {$user['nom']} ({$user['username']})\n";
        } else {
            echo "⚠️  Aucun utilisateur dans la base\n";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur avec colonnes françaises : " . $e->getMessage() . "\n";
        echo "\nTentative avec anciennes colonnes...\n";
        try {
            $stmt = $db->query("SELECT id, username, first_name, last_name, email, active FROM users LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                echo "✅ Requête avec colonnes anglaises réussie\n";
                echo "⚡ La base n'a PAS été migrée ! Exécuter le script SQL.\n";
            }
        } catch (PDOException $e2) {
            echo "❌ Erreur aussi avec anciennes colonnes : " . $e2->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
