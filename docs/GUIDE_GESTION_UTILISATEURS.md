# 👥 Guide Utilisateur - Gestion des Utilisateurs Admin

## 🎯 Objectif
Module complet de gestion des utilisateurs pour les administrateurs de Synd_Gest. Permet de créer, modifier, activer/désactiver et supprimer des comptes utilisateurs avec 5 rôles différents.

---

## 📂 Accès au Module

### Navigation
1. Se connecter en tant qu'**Admin**
2. Cliquer sur le menu **"Administration"** (icône 🛡️) dans la navbar
3. Sélectionner **"Gestion des utilisateurs"**
4. URL directe : `http://localhost/Synd_Gest/public/admin/users`

### Permissions requises
- ⚠️ **Rôle Admin uniquement** - Les autres rôles n'ont pas accès à ce module

---

## 📊 Page Principale : Liste des Utilisateurs

### Statistiques en haut (6 cartes colorées)
- **Total** : Nombre total d'utilisateurs (bleu)
- **Admins** : Super administrateurs (rouge)
- **Gestionnaires** : Gestion syndic (cyan)
- **Exploitants** : Opérateurs Domitys (jaune)
- **Propriétaires** : Investisseurs (vert)
- **Résidents** : Seniors occupants (gris)

### Filtres disponibles
1. **Rôle** : Dropdown avec les 5 rôles + "Tous"
2. **Statut** : Actif / Inactif / Tous
3. **Recherche** : Texte libre (nom, email, username)
   - Filtrage en temps réel à la frappe

### Tableau des utilisateurs
Colonnes affichées :
- **#** : ID de l'utilisateur
- **Nom complet** : Prénom + Nom avec avatar circle (initiales)
- **Email** : Cliquable (mailto:)
- **Username** : En `code` style
- **Rôle** : Badge coloré avec icône
- **Statut** : Badge Actif (vert) ou Inactif (gris)
- **Dernière connexion** : Date et heure (ou "Jamais connecté")
- **Actions** : 3 boutons (Modifier, Toggle statut, Supprimer)

### Actions par ligne
- 🖊️ **Modifier** : Ouvre le formulaire d'édition
- 🚫/✅ **Toggle** : Activer ou désactiver le compte
- 🗑️ **Supprimer** : Désactiver définitivement (soft delete)
- 🔒 **Verrouillé** : Si c'est votre propre compte (non modifiable)

---

## ➕ Créer un Utilisateur

### Accès
- Bouton **"Nouvel Utilisateur"** en haut à droite
- URL : `/admin/users/create`

### Formulaire de création
Champs obligatoires (marqués **\***) :

1. **Prénom** : Ex. "Jean"
2. **Nom** : Ex. "Dupont"
3. **Email** : Ex. "jean.dupont@example.com"
   - Doit être unique
   - Format valide vérifié
4. **Username** : Ex. "jdupont"
   - Minimum 3 caractères
   - Doit être unique
   - Auto-généré depuis prénom/nom si vide
5. **Mot de passe** : Minimum 8 caractères
   - Bouton 👁️ pour afficher/masquer
   - Indicateur de force (Très faible → Très fort)
6. **Confirmer le mot de passe** : Doit correspondre
7. **Rôle** : Dropdown des 5 rôles
8. **Actif** : Switch (coché par défaut)
   - ✅ Actif : peut se connecter
   - ❌ Inactif : ne peut pas se connecter

### Validation
- Email invalide → Erreur "Email invalide"
- Email en double → "Cet email est déjà utilisé"
- Username en double → "Ce nom d'utilisateur est déjà utilisé"
- Username < 3 car → "Minimum 3 caractères"
- Mot de passe < 8 car → "Minimum 8 caractères"
- Mots de passe différents → Alerte JavaScript

### Aide contextuelle (colonne droite)
- **Description des rôles** :
  - Admin : Accès complet
  - Gestionnaire : Copropriétés, syndic
  - Exploitant : Domitys, résidents
  - Propriétaire : Contrats, paiements, fiscalité
  - Résident : Occupation, profil
- **Règles de sécurité** :
  - Mot de passe hashé en base
  - Email et username uniques
  - Compte inactif ne peut pas se connecter

### Soumission
- Cliquer sur **"Créer l'utilisateur"**
- Message flash de succès en vert
- Redirection vers la liste
- Log créé dans `security_logs` : `user_created`

---

## ✏️ Modifier un Utilisateur

### Accès
- Cliquer sur 🖊️ dans la liste
- URL : `/admin/users/edit/{id}`

### Formulaire d'édition
Même structure que la création, mais :
- Champs pré-remplis avec les données actuelles
- **Mot de passe OPTIONNEL** :
  - Laisser vide = conserver l'actuel
  - Remplir = modifier le mot de passe
- Affichage des **Informations système** :
  - Créé le : Date de création
  - Dernière modification : Date de dernière MAJ
  - Dernière connexion : Si l'utilisateur s'est connecté

### Validation
Identique à la création, sauf :
- Email unique : sauf pour l'utilisateur actuel
- Username unique : sauf pour l'utilisateur actuel
- Mot de passe : optionnel, validé seulement si rempli

### Cas particulier
Si vous modifiez **votre propre compte** :
- ⚠️ Alerte jaune affichée
- Attention au changement de rôle (peut supprimer vos accès)

### Soumission
- Cliquer sur **"Enregistrer les modifications"**
- Message flash de succès
- Redirection vers la liste
- Log créé : `user_updated`

---

## 🔄 Activer / Désactiver un Utilisateur

### Fonction
Permet de **temporairement** bloquer l'accès d'un utilisateur sans le supprimer.

### Procédure
1. Dans la liste, cliquer sur le bouton 🚫 (désactiver) ou ✅ (activer)
2. Modal de confirmation s'ouvre :
   - Titre : "Confirmation"
   - Message : "Voulez-vous vraiment [désactiver/activer] l'utilisateur X ?"
3. Cliquer sur **"Confirmer"** ou **"Annuler"**
4. Si confirmé :
   - Badge change de couleur (Actif vert ↔ Inactif gris)
   - Message flash de succès
   - Log créé : `user_toggle_active`

### Effet
- **Compte actif** : Peut se connecter normalement
- **Compte inactif** : 
  - Ne peut plus se connecter
  - Message à la connexion : "Votre compte a été désactivé"
  - Toutes les données sont conservées

### Restrictions
- ❌ Vous ne pouvez pas désactiver votre propre compte
- Bouton désactivé (🔒) sur votre ligne

---

## 🗑️ Supprimer un Utilisateur

### Fonction
**Soft delete** : désactive définitivement l'utilisateur sans supprimer les données de la base.

### Procédure
1. Cliquer sur le bouton 🗑️ (rouge)
2. Modal rouge s'ouvre :
   - Titre : "Supprimer l'utilisateur"
   - Alerte danger : "Attention ! Cette action va désactiver l'utilisateur."
   - Message : "Voulez-vous vraiment supprimer l'utilisateur X ?"
3. Cliquer sur **"Supprimer"** ou **"Annuler"**
4. Si confirmé :
   - Compte désactivé (`actif = 0`)
   - Message flash de succès
   - Log créé : `user_deleted`

### Important
- ⚠️ **Pas de suppression physique** : Les données restent en base
- L'utilisateur apparaît comme "Inactif" dans la liste
- Peut être réactivé plus tard avec le bouton ✅
- ❌ Vous ne pouvez pas supprimer votre propre compte

---

## 🔐 Sécurité

### CSRF Protection
- Tous les formulaires incluent un token CSRF
- Validation côté serveur
- Erreur si token invalide : "Token CSRF invalide"

### Validation des données
- **Côté client** : HTML5 + JavaScript
- **Côté serveur** : PHP avec filtres
- Email : `FILTER_VALIDATE_EMAIL`
- Mot de passe : `password_hash()` avec `PASSWORD_DEFAULT`

### Logs d'audit
Toutes les actions sont loguées :
- `user_created` : Création d'utilisateur
- `user_updated` : Modification
- `user_deleted` : Suppression (soft)
- `user_toggle_active` : Changement de statut

Consultables dans **Admin → Logs de sécurité**

---

## 📱 Responsive Design

### Mobile (375px - 667px)
- Cartes stats empilées verticalement
- Table scrollable horizontalement
- Boutons d'action réduits mais cliquables (44px min)
- Avatar circles plus petits (30px)
- Formulaires : champs empilés (col-12)

### Tablet (768px - 1024px)
- Cartes stats : 2 par ligne
- Table lisible sans scroll horizontal
- Formulaires : 2 colonnes (col-md-6)

### Desktop (1024px+)
- Cartes stats : 6 en ligne
- Formulaire : colonne d'aide à droite (col-lg-4)
- Table complète visible

---

## 🎨 UI/UX

### Couleurs des rôles
- 🔴 **Admin** : Rouge (`badge-danger`) + icône `user-shield`
- 🔵 **Gestionnaire** : Bleu (`badge-info`) + icône `user-tie`
- 🟡 **Exploitant** : Jaune (`badge-warning`) + icône `building`
- 🟢 **Propriétaire** : Vert (`badge-success`) + icône `home`
- ⚪ **Résident** : Gris (`badge-secondary`) + icône `user`

### Icônes Font Awesome
- 👤 Utilisateurs
- ➕ Créer
- ✏️ Modifier
- 🚫 Désactiver
- ✅ Activer
- 🗑️ Supprimer
- 🔒 Verrouillé
- 📧 Email
- 🔑 Mot de passe
- 👁️ Afficher/Masquer

### Breadcrumbs
Toutes les pages incluent un fil d'Ariane :
- Accueil > Administration > Gestion des utilisateurs
- Accueil > Administration > Utilisateurs > Créer un utilisateur
- Accueil > Administration > Utilisateurs > Modifier un utilisateur

---

## 🛠️ Dépannage

### Erreur "Token CSRF invalide"
**Cause** : Session expirée ou formulaire ouvert depuis trop longtemps  
**Solution** : Rafraîchir la page (F5) et ressoumettre

### Email déjà utilisé
**Cause** : Un autre utilisateur a déjà cet email  
**Solution** : Utiliser un email différent ou modifier l'utilisateur existant

### "Vous ne pouvez pas modifier votre propre compte"
**Cause** : Protection pour éviter l'auto-suppression  
**Solution** : Demander à un autre admin de faire la modification

### Mot de passe oublié d'un utilisateur
**Solution** : 
1. Éditer l'utilisateur
2. Saisir un nouveau mot de passe temporaire
3. Communiquer le mot de passe à l'utilisateur
4. L'utilisateur devra le changer à la prochaine connexion

### Utilisateur ne peut pas se connecter
**Vérifications** :
1. Statut = "Actif" (badge vert) ?
2. Mot de passe correct ?
3. Vérifier les logs de sécurité : `failed_login` ?

---

## 📞 Support

Pour toute question ou bug :
1. Consulter les **Logs de sécurité** : Admin → Logs
2. Vérifier la **Console JavaScript** : F12 → Console
3. Contacter le développeur avec :
   - Navigateur utilisé
   - Action effectuée
   - Message d'erreur exact

---

**Version** : 1.0  
**Date** : 30 novembre 2025  
**Module** : Gestion des Utilisateurs - Admin
