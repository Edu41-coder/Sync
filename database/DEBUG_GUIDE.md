# 🔧 Guide de débogage - Pages create/edit et désactivation

## Problèmes identifiés

### 1. Pages create/edit ne se chargent pas
### 2. Bouton de désactivation ne fonctionne pas

## 🔍 Diagnostic

### Étape 1 : Vérifier si la migration SQL a été effectuée

**Exécuter le test :**
```bash
cd c:\xampp\htdocs\Synd_Gest\tests
php test_user_columns.php
```

**OU via navigateur :**
```
http://localhost/Synd_Gest/tests/test_user_columns.php
```

### Résultats possibles :

#### ✅ Si migration OK :
```
✅ Migration réussie ! Toutes les colonnes françaises sont présentes.
```
→ Passer à l'étape 2

#### ❌ Si migration NON effectuée :
```
❌ Migration NON effectuée ! Les colonnes sont encore en anglais.
```
→ **SOLUTION : Exécuter la migration SQL**

## 🚀 Solution : Exécuter la migration SQL

### Option A : Via ligne de commande MySQL

```bash
# 1. Ouvrir terminal/cmd
cd c:\xampp\htdocs\Synd_Gest

# 2. Se connecter à MySQL
mysql -u root -p

# 3. Dans MySQL, exécuter :
USE synd_gest;
SOURCE database/rename_user_columns_to_french.sql;

# 4. Vérifier
DESCRIBE users;
```

### Option B : Via phpMyAdmin (PLUS FACILE)

1. Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
2. Sélectionner la base `synd_gest` dans le menu de gauche
3. Cliquer sur l'onglet **"SQL"** en haut
4. Copier-coller le contenu complet du fichier `database/rename_user_columns_to_french.sql`
5. Cliquer sur **"Exécuter"**
6. Vérifier : cliquer sur la table `users` → onglet **"Structure"**
   - Vous devriez voir : `prenom`, `nom`, `telephone`, `actif`
   - Vous ne devriez PAS voir : `first_name`, `last_name`, `phone`, `active`

### Option C : Via XAMPP Control Panel + Terminal

```bash
# 1. Dans XAMPP Control Panel, cliquer sur "Shell"
# 2. Taper :
cd htdocs/Synd_Gest
mysql -u root synd_gest < database/rename_user_columns_to_french.sql
```

## ✅ Après la migration

### Tester :

1. **Page de création** :
   ```
   http://localhost/Synd_Gest/public/admin/users/create
   ```
   → Devrait afficher le formulaire complet

2. **Page d'édition** (remplacer 7 par un ID existant) :
   ```
   http://localhost/Synd_Gest/public/admin/users/edit/7
   ```
   → Devrait afficher le formulaire pré-rempli

3. **Désactivation** :
   - Aller sur : `http://localhost/Synd_Gest/public/admin/users`
   - Cliquer sur le bouton jaune (⚠️) à côté d'un utilisateur
   - Confirmer → L'utilisateur devrait être désactivé

## 🔍 Si les problèmes persistent après migration

### Vérifier les logs d'erreurs :

1. **Logs PHP** : `c:\xampp\apache\logs\error.log`
2. **Logs application** : `c:\xampp\htdocs\Synd_Gest\logs\`

### Console navigateur :
1. Ouvrir la page qui ne fonctionne pas
2. Appuyer sur **F12**
3. Onglet **Console** → Vérifier les erreurs JavaScript
4. Onglet **Network** → Vérifier les requêtes qui échouent (en rouge)

## 📋 Checklist finale

- [ ] Migration SQL exécutée (`test_user_columns.php` affiche ✅)
- [ ] Page `/admin/users/create` se charge
- [ ] Page `/admin/users/edit/X` se charge  
- [ ] Bouton de désactivation fonctionne
- [ ] Aucune erreur dans la console navigateur
- [ ] Aucune erreur dans les logs PHP

## 🆘 Besoin d'aide ?

Si après avoir exécuté la migration le problème persiste :
1. Partager le résultat de `test_user_columns.php`
2. Partager les erreurs de la console navigateur (F12)
3. Partager le contenu des logs PHP si disponible
