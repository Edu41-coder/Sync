# ✅ Checklist de Test - Gestion des Utilisateurs

## 📋 Phase 1 : Tests Création

### Test 1 : Accès à la liste des utilisateurs
- [ ] Naviguer vers http://localhost/Synd_Gest/public/admin/users
- [ ] Vérifier que la page s'affiche sans erreur
- [ ] Vérifier les 6 cartes de statistiques (Total, Admin, Gestionnaire, Exploitant, Propriétaire, Résident)
- [ ] Vérifier que la liste des utilisateurs s'affiche

### Test 2 : Filtres et Recherche
- [ ] Tester le filtre "Rôle" : sélectionner "Admin" → seuls les admins s'affichent
- [ ] Tester le filtre "Statut" : sélectionner "Actif" → seuls les actifs s'affichent
- [ ] Tester la recherche : taper un nom → résultats filtrés en temps réel
- [ ] Effacer les filtres → tous les utilisateurs réapparaissent

### Test 3 : Création d'un utilisateur valide
- [ ] Cliquer sur "Nouvel Utilisateur"
- [ ] Vérifier que le formulaire s'affiche (breadcrumbs corrects)
- [ ] Remplir tous les champs :
  - Prénom : Test
  - Nom : Utilisateur
  - Email : test.user@example.com
  - Username : tuser (auto-généré)
  - Mot de passe : Password123
  - Confirmer : Password123
  - Rôle : Gestionnaire
  - Actif : ☑️ Coché
- [ ] Vérifier que l'indicateur de force du mot de passe s'affiche
- [ ] Cliquer sur "Créer l'utilisateur"
- [ ] Vérifier le message flash de succès
- [ ] Vérifier que l'utilisateur apparaît dans la liste

### Test 4 : Validation - Email invalide
- [ ] Aller sur "Nouvel Utilisateur"
- [ ] Remplir avec un email invalide : "test@" (sans domaine)
- [ ] Tenter de soumettre
- [ ] Vérifier le message d'erreur "Email invalide"

### Test 5 : Validation - Email en double
- [ ] Créer un utilisateur avec email : duplicate@test.com
- [ ] Tenter de créer un autre utilisateur avec le même email
- [ ] Vérifier le message d'erreur "Cet email est déjà utilisé"

### Test 6 : Validation - Mot de passe trop court
- [ ] Formulaire de création
- [ ] Saisir un mot de passe de 5 caractères
- [ ] Tenter de soumettre
- [ ] Vérifier le message "Le mot de passe doit contenir au moins 8 caractères"

### Test 7 : Validation - Mots de passe ne correspondent pas
- [ ] Mot de passe : Password123
- [ ] Confirmation : Password456 (différent)
- [ ] Tenter de soumettre
- [ ] Vérifier l'alerte JavaScript "Les mots de passe ne correspondent pas"

### Test 8 : Toggle Password Visibility
- [ ] Cliquer sur l'icône 👁️ à côté du champ mot de passe
- [ ] Vérifier que le texte devient visible
- [ ] Icône change en 👁️‍🗨️
- [ ] Cliquer à nouveau → texte masqué

---

## 📝 Phase 2 : Tests Édition

### Test 9 : Modifier un utilisateur
- [ ] Dans la liste, cliquer sur le bouton "Modifier" (icône crayon) d'un utilisateur
- [ ] Vérifier que le formulaire se pré-remplit avec les données
- [ ] Modifier le prénom : "TestModifié"
- [ ] Laisser le mot de passe vide (ne pas changer)
- [ ] Cliquer sur "Enregistrer les modifications"
- [ ] Vérifier le message de succès
- [ ] Vérifier que le nom a changé dans la liste

### Test 10 : Modifier le mot de passe
- [ ] Éditer un utilisateur
- [ ] Remplir nouveau mot de passe : NewPassword456
- [ ] Confirmer : NewPassword456
- [ ] Enregistrer
- [ ] Vérifier le succès
- [ ] (Test manuel) Se déconnecter et reconnecter avec le nouveau mot de passe

### Test 11 : Modifier le rôle
- [ ] Éditer un utilisateur "Gestionnaire"
- [ ] Changer le rôle vers "Propriétaire"
- [ ] Enregistrer
- [ ] Vérifier que le badge change de couleur (vert pour Propriétaire)

### Test 12 : Informations système affichées
- [ ] Éditer un utilisateur
- [ ] Vérifier que "Créé le" s'affiche avec une date
- [ ] Vérifier que "Dernière modification" s'affiche
- [ ] Si l'utilisateur s'est connecté : "Dernière connexion" affichée

---

## 🔄 Phase 3 : Tests Actions

### Test 13 : Activer/Désactiver un utilisateur
- [ ] Cliquer sur le bouton "Désactiver" (icône 🚫) d'un utilisateur actif
- [ ] Vérifier que le modal de confirmation s'ouvre
- [ ] Message : "Voulez-vous vraiment désactiver l'utilisateur X ?"
- [ ] Cliquer sur "Confirmer"
- [ ] Vérifier le message de succès
- [ ] Vérifier que le badge passe de "Actif" vert à "Inactif" gris
- [ ] Cliquer à nouveau (icône ✅) pour réactiver
- [ ] Vérifier le retour au statut "Actif"

### Test 14 : Supprimer un utilisateur
- [ ] Cliquer sur le bouton "Supprimer" (icône 🗑️) d'un utilisateur
- [ ] Vérifier que le modal rouge s'ouvre
- [ ] Message d'alerte danger visible
- [ ] Cliquer sur "Supprimer"
- [ ] Vérifier le succès
- [ ] Vérifier que l'utilisateur est maintenant "Inactif" (soft delete)

### Test 15 : Protection auto-modification
- [ ] En étant connecté comme admin, aller sur la liste
- [ ] Repérer votre propre ligne (celle avec votre username)
- [ ] Vérifier que les boutons toggle/delete sont désactivés (icône 🔒)
- [ ] Tooltip au survol : "Vous ne pouvez pas modifier votre propre compte"

---

## 🎨 Phase 4 : Tests Responsive

### Test 16 : Mobile (375px - iPhone SE)
- [ ] Ouvrir DevTools → Mode responsive → 375x667
- [ ] Vérifier que les 6 cartes stats s'empilent verticalement
- [ ] Vérifier que le bouton "Nouvel Utilisateur" reste visible
- [ ] Vérifier que la table est scrollable horizontalement
- [ ] Les avatars circles restent visibles
- [ ] Les boutons d'action sont cliquables (assez grands)

### Test 17 : Tablet (768px - iPad)
- [ ] Mode responsive → 768x1024
- [ ] Vérifier que les cartes stats sont 2 par ligne
- [ ] La table reste lisible sans scroll horizontal

### Test 18 : Formulaire mobile
- [ ] 375px → Créer un utilisateur
- [ ] Vérifier que les champs s'empilent (col-12 col-md-6)
- [ ] Toggle password fonctionne sur mobile
- [ ] Boutons "Retour" et "Créer" visibles

---

## 🔐 Phase 5 : Tests Sécurité

### Test 19 : CSRF Token
- [ ] Ouvrir DevTools → Network
- [ ] Soumettre un formulaire de création
- [ ] Inspecter la requête POST
- [ ] Vérifier la présence du champ `csrf_token`
- [ ] Vérifier qu'il n'est pas vide

### Test 20 : Validation côté serveur
- [ ] Utiliser Postman ou curl pour soumettre des données invalides
- [ ] Exemple : POST sans email
- [ ] Vérifier que le serveur retourne une erreur

### Test 21 : Logs système
- [ ] Créer un utilisateur
- [ ] Aller sur Admin → Logs de sécurité
- [ ] Vérifier qu'une entrée "user_created" existe
- [ ] Modifier un utilisateur
- [ ] Vérifier l'entrée "user_updated"

---

## 🎯 Phase 6 : Tests UI/UX

### Test 22 : Tooltips
- [ ] Survoler les boutons d'action (Modifier, Désactiver, Supprimer)
- [ ] Vérifier que les tooltips Bootstrap s'affichent
- [ ] Texte cohérent ("Modifier", "Désactiver", etc.)

### Test 23 : Badges colorés
- [ ] Vérifier les couleurs des rôles :
  - Admin : Rouge (danger)
  - Gestionnaire : Bleu (info)
  - Exploitant : Jaune (warning)
  - Propriétaire : Vert (success)
  - Résident : Gris (secondary)
- [ ] Vérifier les icônes Font Awesome correspondantes

### Test 24 : Avatar Circles
- [ ] Vérifier que les initiales sont correctes (1ère lettre prénom + nom)
- [ ] Couleur de fond : Gradient violet (thème de l'app)
- [ ] Texte en blanc, gras

### Test 25 : Modals
- [ ] Toggle Status Modal : header jaune, titre "Confirmation"
- [ ] Delete Modal : header rouge, message d'alerte danger
- [ ] Boutons centrés dans le footer
- [ ] Fermeture au clic sur "Annuler" ou [X]

---

## 📊 Résultats Attendus

### ✅ Fonctionnalités OK si :
- Tous les formulaires soumettent sans erreur 500
- Les validations empêchent les données invalides
- Les messages flash s'affichent correctement
- Les filtres et recherche fonctionnent en temps réel
- Les modals de confirmation apparaissent
- Aucune erreur JavaScript dans la console
- Responsive fonctionne sur 3 breakpoints (375px, 768px, 1024px+)
- CSRF tokens présents sur tous les formulaires POST
- Logs créés dans la base pour chaque action

### ❌ Bugs à corriger si :
- Erreur 500 au chargement de la page
- "Undefined index" dans les flash messages
- Email en double accepté
- Mot de passe < 8 caractères accepté
- Pas de message flash après soumission
- Table non responsive (déborde de l'écran mobile)
- Modals ne s'ouvrent pas
- Tooltips ne s'affichent pas
- CSRF token manquant → erreur "Token CSRF invalide"

---

## 🚀 Commandes Utiles

### Vider les logs pour tests propres
```sql
TRUNCATE TABLE security_logs;
```

### Compter les utilisateurs par rôle
```sql
SELECT role, COUNT(*) as total FROM users GROUP BY role;
```

### Voir les derniers logs
```sql
SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 10;
```

### Réactiver tous les utilisateurs
```sql
UPDATE users SET actif = 1;
```

---

## 📝 Notes de Test

**Date** : _____________  
**Testeur** : _____________  
**Environnement** : [ ] Local | [ ] Dev | [ ] Prod  
**Navigateur** : [ ] Chrome | [ ] Firefox | [ ] Safari | [ ] Edge  

**Résumé des bugs trouvés** :
1. _________________________________
2. _________________________________
3. _________________________________

**Fonctionnalités validées** : ______ / 25

**Statut final** : [ ] ✅ Prêt pour prod | [ ] ⚠️ Bugs mineurs | [ ] ❌ Bugs bloquants
