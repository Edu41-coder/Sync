# Configuration avec Variables d'Environnement (.env)

## 📋 Vue d'ensemble

Synd_Gest utilise des **variables d'environnement** pour stocker les configurations sensibles (identifiants DB, clés API, etc.) au lieu de les coder en dur dans les fichiers PHP.

**Avantages** :
- ✅ **Sécurité** : Les identifiants ne sont pas dans Git
- ✅ **Flexibilité** : Configurations différentes par environnement
- ✅ **Simplicité** : Un seul fichier à modifier

---

## 🚀 Installation Locale (XAMPP)

### Étape 1 : Copier le fichier exemple

```bash
# Depuis la racine du projet
cp .env.example .env
```

### Étape 2 : Éditer `.env` avec vos valeurs

```bash
# Ouvrir avec un éditeur de texte
notepad .env
```

### Étape 3 : Configurer la base de données

```env
# Base de données XAMPP
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synd_gest
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

### Étape 4 : Tester

```bash
# Dans le navigateur
http://localhost/Synd_Gest/tests/test_dotenv.php
```

---

## 📝 Variables Disponibles

### Environnement
```env
ENVIRONMENT=development   # ou production
DEBUG=true               # Afficher les erreurs détaillées
```

### Base de données
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synd_gest
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

### Application
```env
APP_NAME=Synd_Gest
APP_URL=http://localhost/Synd_Gest/public
SECRET_KEY=your_secret_key_here
```

### Session
```env
SESSION_LIFETIME=7200
SESSION_NAME=synd_gest_session
```

### Email
```env
MAIL_HOST=localhost
MAIL_PORT=25
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@syndgest.fr
```

---

## 🔒 Sécurité

### ⚠️ IMPORTANT

**Ne JAMAIS commiter le fichier `.env` sur Git !**

Le fichier `.env` est **automatiquement ignoré** par `.gitignore` :

```gitignore
# Variables d'environnement
.env
.env.local
.env.*.local
```

### Pour partager la configuration

1. Modifier `.env.example` avec les **clés** (pas les valeurs)
2. Commiter `.env.example` sur Git
3. Chaque développeur copie `.env.example` → `.env`
4. Chaque développeur met ses propres valeurs

---

## 💻 Utilisation dans le Code

### Lire une variable

```php
// Dans n'importe quel fichier PHP
$dbHost = DotEnv::get('DB_HOST', 'localhost'); // Avec valeur par défaut
$appName = DotEnv::get('APP_NAME'); // Sans valeur par défaut
```

### Variable requise

```php
// Lance une exception si la variable n'existe pas
$secretKey = DotEnv::getRequired('SECRET_KEY');
```

### Variable booléenne

```php
$debug = DotEnv::getBool('DEBUG', false);
if ($debug) {
    // Mode debug activé
}
```

### Variable entière

```php
$port = DotEnv::getInt('DB_PORT', 3306);
```

### Vérifier l'existence

```php
if (DotEnv::has('MAIL_HOST')) {
    // Configuration email disponible
}
```

---

## 🌍 Environnements

### Développement Local (XAMPP)

Fichier `.env` à la racine :
```env
ENVIRONMENT=development
DEBUG=true
DB_HOST=localhost
DB_USER=root
DB_PASS=
```

### Production (Heroku)

Les variables sont configurées via Heroku CLI :

```bash
# Définir une variable
heroku config:set DB_HOST=mysql.example.com

# Lister toutes les variables
heroku config

# Supprimer une variable
heroku config:unset DB_PASS
```

Ou via le Dashboard Heroku : Settings → Config Vars

---

## 🧪 Tests

### Test DotEnv

```bash
http://localhost/Synd_Gest/tests/test_dotenv.php
```

Vérifie :
- ✅ Chargement du fichier .env
- ✅ Lecture des variables
- ✅ Types (bool, int)
- ✅ Connexion à la base de données

### Test Database

```bash
http://localhost/Synd_Gest/tests/test_database.php
```

Vérifie :
- ✅ Singleton Database
- ✅ Connexion PDO
- ✅ Requêtes SQL

---

## 🔧 Dépannage

### Erreur : "File .env does not exist"

**Solution** : Copier `.env.example` en `.env`

```bash
cp .env.example .env
```

### Erreur : "Environment variable DB_NAME is required but not set"

**Solution** : Ajouter la variable manquante dans `.env`

```env
DB_NAME=synd_gest
```

### Les variables ne sont pas chargées

**Vérifier** :
1. Le fichier `.env` existe à la racine
2. Le fichier est lisible (permissions)
3. Format correct : `KEY=VALUE` (pas d'espaces autour de `=`)
4. DotEnv est bien chargé dans `public/index.php`

---

## 📚 Documentation

### Classe DotEnv

**Fichier** : `app/core/DotEnv.php`

**Méthodes** :
- `load()` : Charger le fichier .env
- `get($key, $default)` : Lire une variable
- `has($key)` : Vérifier l'existence
- `getRequired($key)` : Variable requise
- `getBool($key, $default)` : Variable booléenne
- `getInt($key, $default)` : Variable entière

---

## ✅ Checklist Installation

- [ ] Copier `.env.example` → `.env`
- [ ] Éditer les valeurs dans `.env`
- [ ] Tester avec `test_dotenv.php`
- [ ] Vérifier que `.env` est dans `.gitignore`
- [ ] Ne JAMAIS commiter `.env` !

---

**Date** : 30 novembre 2025  
**Version** : 1.0
