# Migrations de Base de Données

## Principe

Les migrations permettent de versionner les changements de schéma de la base de données. Chaque modification (ajout de colonne, nouvelle table, modification d'index...) est un fichier SQL numéroté dans `database/migrations/`. Le système garantit que chaque migration n'est exécutée qu'une seule fois et dans l'ordre.

## Structure

```
database/
  migrations/
    001_initial_schema.sql
    002_create_declarations_fiscales.sql
    003_create_planning_proprietaire.sql
    004_votre_prochaine_migration.sql
    ...
  synd_gest.sql                 ← schéma complet (import initial)
```

## Interface d'administration

Accessible via **Administration > Migrations DB** ou directement :

```
http://localhost/Synd_Gest/public/admin/migrate
```

Cette page affiche :
- La liste de toutes les migrations (appliquées ou en attente)
- Le bouton pour appliquer les migrations en attente
- Le résultat de la dernière exécution (succès / erreurs)

> Seul le rôle **admin** a accès à cette page.

## Première utilisation

Si le projet est déjà installé avec `synd_gest.sql` :

1. Aller sur `/admin/migrate`
2. Cliquer **"Marquer le schéma initial"** — cela enregistre `001_initial_schema` comme déjà appliqué
3. Cliquer **"Appliquer"** pour exécuter les migrations suivantes

Si c'est une installation neuve :

1. Importer `database/synd_gest.sql` dans MariaDB
2. Suivre les étapes ci-dessus

## Créer une migration

### 1. Choisir le numéro

Regarder le dernier fichier dans `database/migrations/` et incrémenter :

```
003_create_planning_proprietaire.sql    ← dernier existant
004_votre_migration.sql                 ← votre nouveau fichier
```

### 2. Nommer le fichier

Format : `NNN_description_courte.sql`

- `NNN` : numéro à 3 chiffres (001, 002, ..., 042, ...)
- `description_courte` : en snake_case, décrit le changement

Exemples :
```
004_add_photo_profil_users.sql
005_create_table_notifications.sql
006_add_index_occupations_lot_id.sql
007_alter_contrats_add_tva_column.sql
```

### 3. Écrire le SQL

Le fichier contient des instructions SQL standard. Exemples :

**Ajouter une colonne :**
```sql
ALTER TABLE users ADD COLUMN photo_profil VARCHAR(500) DEFAULT NULL AFTER telephone;
```

**Créer une table :**
```sql
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu TEXT,
    lu TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Ajouter un index :**
```sql
CREATE INDEX idx_occupations_statut ON occupations_residents(statut);
```

**Modifier une colonne :**
```sql
ALTER TABLE lots MODIFY COLUMN surface DECIMAL(8,2) DEFAULT NULL;
```

**Insérer des données de référence :**
```sql
INSERT IGNORE INTO planning_categories (slug, nom, couleur, ordre_affichage, actif) VALUES
('formation', 'Formation', '#9c27b0', 14, 1);
```

### 4. Appliquer

- Aller sur `/admin/migrate`
- Cliquer **"Appliquer"**
- Vérifier le résultat (vert = OK, rouge = erreur)

## Bonnes pratiques

### Utiliser `IF NOT EXISTS` / `IF EXISTS`

```sql
-- Bien : ne plante pas si la table existe déjà
CREATE TABLE IF NOT EXISTS ma_table (...);

-- Bien : ne plante pas si la colonne existe déjà (MariaDB 10.0+)
ALTER TABLE users ADD COLUMN IF NOT EXISTS photo_profil VARCHAR(500);
```

### Utiliser `INSERT IGNORE` pour les données de référence

```sql
-- Ne plante pas si la ligne existe déjà (clé unique)
INSERT IGNORE INTO roles (slug, nom) VALUES ('nouveau_role', 'Nouveau Rôle');
```

### Une migration = un changement logique

Ne pas mélanger des changements non liés dans une même migration. Préférer :
```
004_add_photo_profil_users.sql      ← un sujet
005_create_table_notifications.sql  ← un autre sujet
```

Plutôt que :
```
004_photo_et_notifications.sql      ← deux sujets mélangés
```

### Ne jamais modifier une migration déjà appliquée

Une fois qu'une migration a été exécutée (en local ou en prod), **ne plus la modifier**. Si le résultat n'est pas celui attendu, créer une nouvelle migration corrective :

```
004_add_column_x.sql                ← déjà appliqué, erreur dedans
005_fix_column_x.sql                ← correction
```

### Tester avant d'appliquer en prod

1. Écrire la migration
2. L'appliquer en local via `/admin/migrate`
3. Vérifier que l'application fonctionne
4. Commit + push
5. Appliquer en prod

## Fonctionnement interne

### Table `migrations`

Le système crée automatiquement une table `migrations` :

```sql
CREATE TABLE migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,  -- nom du fichier (sans .sql)
    batch INT NOT NULL DEFAULT 1,            -- groupe d'exécution
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Logique d'exécution

1. Lire tous les fichiers `database/migrations/*.sql`
2. Comparer avec la table `migrations`
3. Pour chaque fichier non encore appliqué (dans l'ordre numérique) :
   - Exécuter le SQL dans une transaction
   - Enregistrer dans la table `migrations`
   - En cas d'erreur : rollback + arrêt (les migrations suivantes ne sont pas exécutées)

### Code source

- Moteur : `app/core/Migration.php`
- Route : `AdminController::migrate()`
- Vue : `app/views/admin/migrate.php`

## Dépannage

### "Erreur lors de la migration"

- Lire le message d'erreur affiché (il contient l'erreur SQL exacte)
- Corriger le fichier `.sql` si la migration n'a pas été appliquée
- Réessayer

### "La migration est marquée mais la table n'existe pas"

Si une migration a été enregistrée dans la table `migrations` mais que le SQL n'a pas réellement fonctionné :

```sql
-- Supprimer l'entrée pour pouvoir la rejouer
DELETE FROM migrations WHERE migration = '004_ma_migration';
```

Puis corriger le fichier et relancer `/admin/migrate`.

### "Je veux repartir de zéro"

```sql
DROP TABLE IF EXISTS migrations;
```

Puis aller sur `/admin/migrate`, marquer le schéma initial, et relancer.
