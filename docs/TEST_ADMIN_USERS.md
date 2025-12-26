# 🧪 Guide de Test - Gestion des Utilisateurs Admin

## 📋 Objectif
Tester complètement le module de gestion des utilisateurs dans l'espace admin.

---

## 🔐 Prérequis

### 1. Connexion Admin
- URL : `http://localhost/Synd_Gest/public/auth/login`
- Utilisateur admin : Voir `database/TEST_USERS_CREDENTIALS.md`

### 2. Accès à la page principale
- URL : `http://localhost/Synd_Gest/public/admin/users`
- Devrait afficher :
  - ✅ Breadcrumb : Accueil > Administration > Gestion des utilisateurs
  - ✅ Titre avec icône : "Gestion des Utilisateurs"
  - ✅ 6 cartes statistiques colorées (Total, Admins, Gestionnaires, Exploitants, Propriétaires, Résidents)
  - ✅ Bouton "Nouvel Utilisateur"
  - ✅ Filtres : Rôle, Statut, Recherche
  - ✅ Table avec colonnes : #, Nom complet, Email, Username, Rôle, Statut, Dernière connexion, Actions

---

## ✅ Tests à Effectuer

### Test 1 : Affichage de la Liste
**Objectif** : Vérifier que tous les utilisateurs s'affichent correctement.

**Étapes** :
1. Se connecter en tant qu'admin
2. Aller sur `/admin/users`
3. Vérifier :
   - [ ] Les stats dans les cartes correspondent au nombre réel d'utilisateurs
   - [ ] Tous les utilisateurs apparaissent dans la table
   - [ ] Les badges de rôle ont les bonnes couleurs :
     - 🔴 Admin = rouge (danger)
     - 🔵 Gestionnaire = bleu (info)
     - 🟡 Exploitant = jaune (warning)
     - 🟢 Propriétaire = vert (success)
     - ⚫ Résident = gris (secondary)
   - [ ] Les statuts affichent correctement "Actif" (vert) ou "Inactif" (gris)
   - [ ] Les avatars circulaires affichent les initiales (ex: "JD" pour Jean Dupont)
   - [ ] La dernière connexion s'affiche au format "DD/MM/YYYY HH:MM"

**Résultat attendu** : Table complète et responsive.

---

### Test 2 : Filtrage par Rôle
**Objectif** : Vérifier que le filtre par rôle fonctionne en temps réel.

**Étapes** :
1. Sur `/admin/users`
2. Dans le dropdown "Rôle", sélectionner "Admin"
3. Vérifier :
   - [ ] Seuls les utilisateurs avec rôle "Admin" s'affichent
   - [ ] Les autres lignes sont cachées
4. Sélectionner "Gestionnaire"
5. Vérifier :
   - [ ] Seuls les gestionnaires apparaissent
6. Sélectionner "Tous les rôles"
7. Vérifier :
   - [ ] Tous les utilisateurs réapparaissent

**Résultat attendu** : Filtrage instantané sans rechargement de page.

---

### Test 3 : Filtrage par Statut
**Objectif** : Filtrer les utilisateurs actifs/inactifs.

**Étapes** :
1. Dans le dropdown "Statut", sélectionner "Actif"
2. Vérifier :
   - [ ] Seuls les utilisateurs avec badge vert "Actif" s'affichent
3. Sélectionner "Inactif"
4. Vérifier :
   - [ ] Seuls les utilisateurs avec badge gris "Inactif" s'affichent
5. Sélectionner "Tous les statuts"

**Résultat attendu** : Filtrage correct par statut.

---

### Test 4 : Recherche Textuelle
**Objectif** : Rechercher un utilisateur par nom, email ou username.

**Étapes** :
1. Dans le champ "Recherche", taper un nom partiel (ex: "Jean")
2. Vérifier :
   - [ ] Les utilisateurs contenant "Jean" s'affichent
   - [ ] Les autres sont cachés
3. Taper un email (ex: "admin@")
4. Vérifier :
   - [ ] Les utilisateurs avec cet email s'affichent
5. Effacer la recherche
6. Vérifier :
   - [ ] Tous les utilisateurs réapparaissent

**Résultat attendu** : Recherche en temps réel fonctionnelle.

---

### Test 5 : Créer un Nouvel Utilisateur
**Objectif** : Tester le formulaire de création.

**Étapes** :
1. Cliquer sur "Nouvel Utilisateur"
2. Vérifier :
   - [ ] Redirection vers `/admin/users/create`
   - [ ] Formulaire avec tous les champs :
     - Nom
     - Prénom
     - Email
     - Username
     - Password
     - Confirmation password
     - Rôle (dropdown avec 6 rôles)
     - Statut actif (checkbox)
3. Remplir le formulaire avec des données valides :
   ```
   Nom: Testeur
   Prénom: Jean
   Email: jean.testeur@test.com
   Username: jtesteur
   Password: Test1234!
   Confirmation: Test1234!
   Rôle: Gestionnaire
   Statut: Coché (actif)
   ```
4. Cliquer "Créer"
5. Vérifier :
   - [ ] Redirection vers `/admin/users`
   - [ ] Message flash vert : "Utilisateur créé avec succès"
   - [ ] Le nouvel utilisateur apparaît dans la table
   - [ ] Ses informations sont correctes

**Résultat attendu** : Création réussie avec validation.

---

### Test 6 : Validation du Formulaire de Création
**Objectif** : Vérifier que les validations fonctionnent.

**Étapes** :
1. Aller sur `/admin/users/create`
2. **Test champs vides** :
   - Ne remplir aucun champ
   - Cliquer "Créer"
   - Vérifier : [ ] Messages d'erreur pour tous les champs requis
3. **Test email invalide** :
   - Remplir tous les champs mais mettre email : "invalide"
   - Vérifier : [ ] Erreur "Email invalide"
4. **Test passwords différents** :
   - Password : "Test1234!"
   - Confirmation : "Different123!"
   - Vérifier : [ ] Erreur "Les mots de passe ne correspondent pas"
5. **Test password faible** :
   - Password : "123"
   - Vérifier : [ ] Erreur "Le mot de passe doit contenir au moins 8 caractères"
6. **Test username existant** :
   - Username : "admin" (déjà existant)
   - Vérifier : [ ] Erreur "Nom d'utilisateur déjà utilisé"
7. **Test email existant** :
   - Email d'un utilisateur existant
   - Vérifier : [ ] Erreur "Email déjà utilisé"

**Résultat attendu** : Toutes les validations empêchent la création.

---

### Test 7 : Modifier un Utilisateur
**Objectif** : Éditer les informations d'un utilisateur.

**Étapes** :
1. Sur `/admin/users`, cliquer sur le bouton "Modifier" (icône crayon) d'un utilisateur
2. Vérifier :
   - [ ] Redirection vers `/admin/users/edit/{id}`
   - [ ] Formulaire pré-rempli avec les données actuelles
   - [ ] Les champs password sont vides (optionnels)
3. Modifier des informations (ex: changer le nom)
4. Laisser les passwords vides
5. Cliquer "Mettre à jour"
6. Vérifier :
   - [ ] Redirection vers `/admin/users`
   - [ ] Message flash : "Utilisateur mis à jour avec succès"
   - [ ] Les modifications apparaissent dans la table
   - [ ] Le password n'a PAS changé (connexion toujours possible)

**Résultat attendu** : Modification réussie sans changer le password.

---

### Test 8 : Changer le Mot de Passe
**Objectif** : Modifier uniquement le password d'un utilisateur.

**Étapes** :
1. Éditer un utilisateur
2. Remplir les champs password :
   ```
   Nouveau mot de passe: NewPassword123!
   Confirmation: NewPassword123!
   ```
3. Cliquer "Mettre à jour"
4. Se déconnecter
5. Essayer de se connecter avec l'ancien password
6. Vérifier : [ ] Connexion échoue
7. Essayer avec le nouveau password
8. Vérifier : [ ] Connexion réussit

**Résultat attendu** : Password changé avec succès.

---

### Test 9 : Activer/Désactiver un Utilisateur
**Objectif** : Basculer le statut actif/inactif.

**Étapes** :
1. Sur `/admin/users`, trouver un utilisateur **actif** (badge vert)
2. Cliquer sur le bouton "Désactiver" (icône ban)
3. Vérifier :
   - [ ] Modal de confirmation s'affiche : "Voulez-vous vraiment désactiver l'utilisateur {username} ?"
4. Cliquer "Confirmer"
5. Vérifier :
   - [ ] Modal se ferme
   - [ ] Message flash : "Utilisateur désactivé avec succès"
   - [ ] Le badge passe à gris "Inactif"
6. Se déconnecter
7. Essayer de se connecter avec cet utilisateur désactivé
8. Vérifier : [ ] Connexion refusée avec message d'erreur
9. Se reconnecter en admin
10. Cliquer sur le bouton "Activer" (icône check)
11. Vérifier :
    - [ ] Modal : "Voulez-vous vraiment activer..."
    - [ ] Après confirmation : badge redevient vert "Actif"
12. Tester la connexion avec cet utilisateur
13. Vérifier : [ ] Connexion réussit

**Résultat attendu** : Toggle actif/inactif fonctionnel avec impact sur la connexion.

---

### Test 10 : Supprimer un Utilisateur
**Objectif** : Soft delete (désactivation permanente).

**Étapes** :
1. Sur `/admin/users`, cliquer sur le bouton "Supprimer" (icône poubelle rouge)
2. Vérifier :
   - [ ] Modal de confirmation rouge s'affiche
   - [ ] Alerte danger : "Attention ! Cette action va désactiver l'utilisateur."
   - [ ] Message : "Voulez-vous vraiment supprimer l'utilisateur {username} ?"
3. Cliquer "Supprimer"
4. Vérifier :
   - [ ] Modal se ferme
   - [ ] Message flash : "Utilisateur désactivé avec succès"
   - [ ] L'utilisateur passe à statut "Inactif" (pas supprimé de la base)
   - [ ] Il reste visible dans la liste avec badge gris
5. Essayer de se connecter avec cet utilisateur
6. Vérifier : [ ] Connexion impossible

**Résultat attendu** : Soft delete (désactivation) au lieu de suppression physique.

---

### Test 11 : Protection Contre Auto-Modification
**Objectif** : Empêcher un admin de modifier son propre compte.

**Étapes** :
1. Sur `/admin/users`, trouver votre propre compte (celui avec lequel vous êtes connecté)
2. Vérifier :
   - [ ] Les boutons "Désactiver" et "Supprimer" sont remplacés par un bouton grisé avec icône cadenas
   - [ ] Tooltip : "Vous ne pouvez pas modifier votre propre compte"
   - [ ] Le bouton "Modifier" (crayon) est toujours disponible
3. Cliquer sur "Modifier"
4. Modifier votre email
5. Cliquer "Mettre à jour"
6. Vérifier : [ ] Modification autorisée
7. Essayer d'accéder directement à l'URL `/admin/users/toggle/{votre_id}` via POST (avec curl ou Postman)
8. Vérifier : [ ] Erreur : "Vous ne pouvez pas modifier votre propre statut"

**Résultat attendu** : Protection côté client ET serveur.

---

### Test 12 : Responsive Mobile
**Objectif** : Vérifier l'affichage sur petits écrans.

**Étapes** :
1. Ouvrir DevTools (F12)
2. Activer mode mobile (Ctrl+Shift+M)
3. Tester à 375px (iPhone SE)
4. Vérifier :
   - [ ] Les cartes stats s'empilent (col-12)
   - [ ] Les boutons s'empilent verticalement
   - [ ] La table est scrollable horizontalement
   - [ ] Les filtres s'empilent (col-12)
   - [ ] Les modals sont bien dimensionnés
   - [ ] Les boutons d'action sont cliquables (taille suffisante)
5. Tester à 768px (iPad)
6. Vérifier :
   - [ ] Les cartes passent en 2 colonnes (col-md-6)
   - [ ] Les filtres en 3 colonnes
7. Tester à 1024px (Desktop)
8. Vérifier :
   - [ ] Les cartes en 6 colonnes (une par type)
   - [ ] Table pleine largeur
   - [ ] Tous les boutons en ligne

**Résultat attendu** : Design responsive sans débordement.

---

### Test 13 : Tooltips et Accessibilité
**Objectif** : Vérifier les tooltips sur les boutons.

**Étapes** :
1. Sur `/admin/users`, survoler le bouton "Modifier" (crayon)
2. Vérifier : [ ] Tooltip "Modifier" apparaît
3. Survoler "Désactiver" (ban)
4. Vérifier : [ ] Tooltip "Désactiver" ou "Activer" selon le statut
5. Survoler "Supprimer" (poubelle)
6. Vérifier : [ ] Tooltip "Supprimer"
7. Survoler le cadenas (sur votre propre compte)
8. Vérifier : [ ] Tooltip "Vous ne pouvez pas modifier votre propre compte"

**Résultat attendu** : Tous les tooltips fonctionnent.

---

### Test 14 : Sécurité CSRF
**Objectif** : Vérifier la protection CSRF.

**Étapes** :
1. Ouvrir DevTools > Network
2. Créer un utilisateur
3. Inspecter la requête POST
4. Vérifier : [ ] Présence du champ `csrf_token` dans le formulaire
5. Copier l'URL de création : `/admin/users/store`
6. Dans un nouvel onglet, faire un POST direct avec curl :
   ```bash
   curl -X POST http://localhost/Synd_Gest/public/admin/users/store -d "nom=Hack"
   ```
7. Vérifier : [ ] Erreur : "Token CSRF invalide"

**Résultat attendu** : Requêtes sans token CSRF refusées.

---

### Test 15 : Logs d'Activité
**Objectif** : Vérifier que les actions sont loggées.

**Étapes** :
1. Créer un utilisateur
2. Vérifier : [ ] Fichier `logs/security.log` contient : "Utilisateur créé: {username}"
3. Modifier un utilisateur
4. Vérifier : [ ] Log : "Utilisateur mis à jour: {username}"
5. Désactiver un utilisateur
6. Vérifier : [ ] Log : "Utilisateur désactivé: {username}"
7. Activer un utilisateur
8. Vérifier : [ ] Log : "Utilisateur activé: {username}"

**Résultat attendu** : Toutes les actions critiques sont loggées.

---

## 🎯 Checklist Finale

Avant de passer au module suivant (Gestion des Exploitants), vérifier :

- [ ] ✅ Tous les tests passent
- [ ] ✅ Aucune erreur PHP dans les logs
- [ ] ✅ Aucune erreur JavaScript dans la console
- [ ] ✅ Design responsive sur tous les écrans
- [ ] ✅ Tous les boutons fonctionnent
- [ ] ✅ Validations côté client et serveur
- [ ] ✅ Protection CSRF active
- [ ] ✅ Messages flash affichés correctement
- [ ] ✅ Logs d'activité fonctionnels
- [ ] ✅ Filtres et recherche en temps réel
- [ ] ✅ Modals de confirmation stylés
- [ ] ✅ Tooltips accessibles
- [ ] ✅ Protection auto-modification

---

## 🐛 Bugs Connus à Vérifier

1. **Transformation nom/prenom** :
   - La base utilise `first_name`/`last_name`
   - La vue attend `nom`/`prenom`
   - Solution : Transformation dans le contrôleur ✅

2. **Transformation actif/active** :
   - La base utilise `active`
   - La vue attend `actif`
   - Solution : Transformation dans le contrôleur ✅

3. **Filtres JavaScript** :
   - Vérifier que les filtres fonctionnent avec les vrais noms de colonnes

---

## 📊 Résultats Attendus

À la fin de ces tests, vous devriez avoir :
- ✅ Un module de gestion des utilisateurs 100% fonctionnel
- ✅ Toutes les opérations CRUD opérationnelles
- ✅ Sécurité CSRF et protection anti-spam
- ✅ Interface responsive et accessible
- ✅ Logs complets de toutes les actions
- ✅ Validation robuste côté client et serveur

---

**Date de création** : 30 novembre 2025  
**Version** : 1.0  
**Auteur** : GitHub Copilot
