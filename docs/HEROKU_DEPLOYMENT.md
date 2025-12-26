# 🚀 Déploiement sur Heroku - Guide Complet

## 📋 Prérequis

1. Compte Heroku: https://signup.heroku.com/
2. Heroku CLI installé: https://devcenter.heroku.com/articles/heroku-cli
3. Git installé

## 🔧 Étape 1: Préparer l'application

### 1.1 Vérifier les fichiers requis

✅ `Procfile` - Définit comment démarrer l'app  
✅ `composer.json` - Dépendances PHP  
✅ `.env.example` - Variables d'environnement  
✅ Configuration flexible (local/production)  

### 1.2 Initialiser Git (si ce n'est pas déjà fait)

```bash
cd c:\xampp\htdocs\Synd_Gest
git init
git add .
git commit -m "Initial commit - Synd_Gest v1.0"
```

## 🌐 Étape 2: Créer l'application Heroku

### 2.1 Se connecter à Heroku

```bash
heroku login
```

### 2.2 Créer l'application

```bash
heroku create synd-gest-app
# Ou avec un nom spécifique:
# heroku create votre-nom-app
```

### 2.3 Vérifier la création

```bash
heroku apps:info
```

## 🗄️ Étape 3: Configurer la base de données MySQL

### 3.1 Ajouter ClearDB (MySQL sur Heroku)

```bash
# Plan gratuit (5MB)
heroku addons:create cleardb:ignite

# Plan payant avec plus d'espace:
# heroku addons:create cleardb:punch (1GB - $9.99/mois)
```

### 3.2 Récupérer l'URL de la base de données

```bash
heroku config:get CLEARDB_DATABASE_URL
```

### 3.3 Définir DATABASE_URL

```bash
heroku config:set DATABASE_URL=$(heroku config:get CLEARDB_DATABASE_URL)
```

### 3.4 Importer la base de données

```bash
# Récupérer les infos de connexion
heroku config:get CLEARDB_DATABASE_URL
# Format: mysql://username:password@hostname/database_name

# Se connecter et importer
mysql -h hostname -u username -p database_name < database/schema.sql
```

## 📧 Étape 4: Configurer l'envoi d'emails (SendGrid)

### 4.1 Ajouter SendGrid

```bash
# Plan gratuit (12,000 emails/mois)
heroku addons:create sendgrid:starter
```

### 4.2 Configurer les variables

```bash
heroku config:set SENDGRID_USERNAME=apikey
heroku config:set SENDGRID_PASSWORD=$(heroku config:get SENDGRID_API_KEY)
```

## 🔐 Étape 5: Configurer les variables d'environnement

### 5.1 Clé secrète de sécurité

```bash
# Générer une clé aléatoire sécurisée
heroku config:set SECRET_KEY=$(openssl rand -hex 32)
```

### 5.2 Autres configurations

```bash
heroku config:set ENVIRONMENT=production
heroku config:set DEBUG=false
heroku config:set APP_NAME="Synd_Gest"
heroku config:set ADMIN_EMAIL=admin@votredomaine.com
heroku config:set MAX_UPLOAD_SIZE=10
```

### 5.3 Vérifier toutes les variables

```bash
heroku config
```

## 📤 Étape 6: Déployer l'application

### 6.1 Ajouter le remote Heroku

```bash
git remote add heroku https://git.heroku.com/votre-app-name.git
```

### 6.2 Déployer

```bash
git push heroku main
# Ou si votre branche principale s'appelle master:
# git push heroku master
```

### 6.3 Vérifier le déploiement

```bash
heroku logs --tail
```

## 🌍 Étape 7: Ouvrir l'application

```bash
heroku open
```

Votre application devrait être accessible à: `https://votre-app-name.herokuapp.com`

## 🔍 Étape 8: Vérification et tests

### 8.1 Vérifier les logs

```bash
# Logs en temps réel
heroku logs --tail

# Logs spécifiques
heroku logs --source app
```

### 8.2 Accéder à la console MySQL

```bash
heroku run bash
# Puis dans le bash Heroku:
mysql -h hostname -u username -p database_name
```

### 8.3 Redémarrer l'application

```bash
heroku restart
```

## 🛠️ Commandes utiles pour la maintenance

### Mettre à jour l'application

```bash
git add .
git commit -m "Description des changements"
git push heroku main
```

### Scaler l'application

```bash
# Voir les dynos actuels
heroku ps

# Augmenter le nombre de dynos
heroku ps:scale web=2
```

### Voir les addons installés

```bash
heroku addons
```

### Gérer les variables d'environnement

```bash
# Lister toutes les variables
heroku config

# Ajouter/Modifier une variable
heroku config:set KEY=value

# Supprimer une variable
heroku config:unset KEY
```

### Créer une sauvegarde de la base de données

```bash
# Via mysqldump
heroku run bash
mysqldump -h hostname -u username -p database_name > backup.sql
```

## 🔒 Étape 9: Sécurité en production

### 9.1 Forcer HTTPS

Décommenter dans `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 9.2 Configurer un domaine personnalisé

```bash
# Ajouter un domaine
heroku domains:add www.votredomaine.com

# Voir les domaines
heroku domains
```

### 9.3 Activer SSL automatique

```bash
heroku certs:auto:enable
```

## 📊 Étape 10: Monitoring

### 10.1 Dashboard Heroku

Accéder à: https://dashboard.heroku.com/apps/votre-app-name

### 10.2 Métriques

```bash
heroku logs --tail
heroku ps
heroku releases
```

## ⚠️ Limitations du plan gratuit Heroku

- **Dyno**: L'application "dort" après 30 minutes d'inactivité
- **Base de données**: ClearDB gratuit = 5MB seulement
- **Emails**: SendGrid gratuit = 12,000 emails/mois
- **Domaine**: Sous-domaine Heroku uniquement

### Solutions:

1. **Upgrade vers un plan payant** (recommandé pour production)
2. **Utiliser un service externe** pour la base de données (AWS RDS, etc.)
3. **Mettre en place un ping** pour éviter que le dyno ne dorme

## 🆘 Dépannage

### L'application ne démarre pas

```bash
heroku logs --tail
heroku restart
```

### Erreur de connexion à la base de données

```bash
# Vérifier l'URL
heroku config:get DATABASE_URL

# Reconfigurer
heroku config:set DATABASE_URL=mysql://...
```

### Problème de permissions sur les fichiers

```bash
heroku run bash
chmod -R 755 uploads/
```

## 📚 Ressources utiles

- Documentation Heroku PHP: https://devcenter.heroku.com/articles/getting-started-with-php
- ClearDB MySQL: https://devcenter.heroku.com/articles/cleardb
- SendGrid: https://devcenter.heroku.com/articles/sendgrid
- Heroku CLI: https://devcenter.heroku.com/articles/heroku-cli

## 🎉 C'est fait !

Votre application Synd_Gest est maintenant en ligne sur Heroku !

**Compte admin par défaut:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT**: Changez immédiatement le mot de passe admin en production !

---

Pour toute question: admin@syndgest.fr
