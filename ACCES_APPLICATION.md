# 🚀 Accès à l'Application Synd_Gest

## ⚠️ IMPORTANT : Utiliser XAMPP, PAS le serveur PHP intégré

### ❌ NE PAS UTILISER
```bash
php -S localhost:8000 -t public
```
**Raison** : Le serveur PHP intégré utilise le port 8000, mais l'application est configurée pour XAMPP sur le port 80. Les chemins CSS/JS ne fonctionneront pas.

---

## ✅ UTILISATION CORRECTE

### 1. Démarrer XAMPP
1. Ouvrir **XAMPP Control Panel**
2. Cliquer sur **Start** pour **Apache**
3. Cliquer sur **Start** pour **MySQL**

### 2. Accéder à l'Application

#### URL de Base
```
http://localhost/Synd_Gest/public/
```

#### URLs Principales

**Authentification**
- Connexion : http://localhost/Synd_Gest/public/auth/login
- Déconnexion : http://localhost/Synd_Gest/public/auth/logout

**Dashboard**
- Accueil : http://localhost/Synd_Gest/public/
- Tableau de bord : http://localhost/Synd_Gest/public/home

**Administration** (rôle admin requis)
- Gestion des utilisateurs : http://localhost/Synd_Gest/public/admin/users
- Créer un utilisateur : http://localhost/Synd_Gest/public/admin/users/create
- Carte des résidences : http://localhost/Synd_Gest/public/admin/carte-residences
- Logs de sécurité : http://localhost/Synd_Gest/public/admin/logs

**Gestion** (autres modules)
- Copropriétés : http://localhost/Synd_Gest/public/coproprietes
- Lots : http://localhost/Synd_Gest/public/lots
- Copropriétaires : http://localhost/Synd_Gest/public/coproprietaires

---

## 🎨 Vérification des Styles

Avec XAMPP, vous devriez voir :
- ✅ Navbar rouge/rose avec logo
- ✅ Boutons rouges (primary) et roses (secondary)
- ✅ Cartes avec ombres et bordures roses
- ✅ Badges colorés par rôle
- ✅ Design responsive mobile
- ✅ Animations et transitions
- ✅ Icônes Font Awesome

### Si les styles ne s'appliquent toujours pas :

1. **Vider le cache du navigateur** : Ctrl + F5
2. **Vérifier la console** : F12 → Console (chercher erreurs 404 sur CSS/JS)
3. **Vérifier les fichiers** :
   ```bash
   Test-Path "c:\xampp\htdocs\Synd_Gest\public\assets\css\style.css"
   Test-Path "c:\xampp\htdocs\Synd_Gest\public\assets\css\mobile.css"
   ```

---

## 🔑 Comptes de Test

Voir le fichier : `database/TEST_USERS_CREDENTIALS.md`

**Compte Admin par défaut** :
- Username : `admin`
- Password : (voir fichier)
- Rôle : admin

---

## 🛠️ Configuration

La configuration se trouve dans : `config/config.php`

```php
// URL de base en local
define('BASE_URL', 'http://localhost/Synd_Gest/public');
```

**Ne PAS modifier** cette constante si vous utilisez XAMPP.

---

## 🐛 Problèmes Connus

### Problème : Styles ne s'appliquent pas
**Cause** : Vous utilisez `localhost:8000` au lieu de `localhost`
**Solution** : Utilisez XAMPP et accédez via `http://localhost/Synd_Gest/public/`

### Problème : Page blanche
**Cause** : Erreur PHP non affichée
**Solution** : 
1. Vérifier `logs/error.log`
2. Activer l'affichage des erreurs dans `config/config.php` :
   ```php
   define('DISPLAY_ERRORS', true);
   ```

### Problème : Redirection vers login en boucle
**Cause** : Session non démarrée ou cookies bloqués
**Solution** :
1. Vérifier que les cookies sont activés
2. Vider les cookies du site
3. Vérifier `config/constants.php` → `SESSION_NAME`

---

## 📊 Structure des URLs

L'application utilise un routeur MVC :

```
/controller/action/param

Exemples :
/admin/users          → AdminController::users()
/admin/users/create   → AdminController::create()
/admin/users/edit/5   → AdminController::edit(5)
/auth/login           → AuthController::login()
/coproprietes         → CoproprieteController::index()
```

---

## ✅ Checklist de Vérification

Avant de commencer à utiliser l'application :

- [ ] XAMPP Apache démarré
- [ ] XAMPP MySQL démarré
- [ ] Base de données `synd_gest` créée et importée
- [ ] Accès via `http://localhost/Synd_Gest/public/`
- [ ] Styles Bootstrap + thème rouge/rose visibles
- [ ] Navbar affichée correctement
- [ ] Connexion fonctionnelle

---

**Date** : 4 décembre 2025  
**Version** : 2.0
