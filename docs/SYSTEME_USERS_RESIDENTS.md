# 📋 Système de Gestion des Utilisateurs et Résidents Seniors

**Date:** 16 décembre 2025  
**Version:** 2.0 - Système anti-duplication

---

## 🎯 Objectif

Éviter la duplication d'utilisateurs dans le système, notamment lors de la gestion des résidents seniors qui ont à la fois un compte utilisateur (`users`) et un profil résident (`residents_seniors`).

---

## 📊 Structure des Données

### Table `users`
- **Clé primaire:** `id` (user_id)
- **Rôles possibles:** `admin`, `gestionnaire`, `exploitant`, `proprietaire`, `resident`
- **Champs uniques:** `email`, `username`

### Table `residents_seniors`
- **Clé primaire:** `id` (resident_senior_id)
- **Clé étrangère:** `user_id` → `users.id`
- **Relation:** 1 user peut avoir 0 ou 1 profil résident

---

## ✅ Création d'Utilisateurs

### 1. Création d'un utilisateur NON-RESIDENT
**Localisation:** `AdminController::storeUser()` (ligne 503)

**Processus:**
1. Validation des données (nom, prénom, email, username, password)
2. ✅ **Vérification unicité email** (`emailExists()`)
3. ✅ **Vérification unicité username** (`usernameExists()`)
4. Création dans `users` avec `role` choisi
5. Attribution automatique d'un `user_id`

**Résultat:**
- ✅ 1 enregistrement dans `users` uniquement
- ✅ Email unique garanti

### 2. Création d'un RÉSIDENT SENIOR
**Localisation:** `ResidentController::handleCreate()` + `ResidentSenior::createWithUser()`

**Processus:**
1. Création automatique du compte user avec `role='resident'`
2. Génération auto : `username`, `email` (format: prenom.nom@resident.syndgest.fr)
3. Attribution d'un `user_id`
4. Création simultanée du profil dans `residents_seniors`
5. Attribution d'un `resident_senior_id`

**Transaction:**
```sql
BEGIN TRANSACTION;
  INSERT INTO users (...) VALUES (...); -- user_id généré
  INSERT INTO residents_seniors (user_id, ...) VALUES (...); -- resident_senior_id généré
COMMIT;
```

**Résultat:**
- ✅ 1 enregistrement dans `users` (role='resident')
- ✅ 1 enregistrement dans `residents_seniors` (lié au user_id)
- ✅ Relation 1:1 garantie

---

## 🔒 Édition d'Utilisateurs - RÈGLES STRICTES

### Règle 1 : Un NON-RESIDENT ne peut PAS devenir RESIDENT
**Fichier:** `AdminController::updateUser()` (ligne 654)

**Protection:**
```php
if ($oldRole !== 'resident' && $role === 'resident') {
    setFlash('error', "Il n'est pas possible de changer le statut vers resident_senior...", 12000);
    redirect('admin/users/edit/' . $id);
    return;
}
```

**Message affiché:** 12 secondes  
**Raison:** Pour créer un résident senior, il faut créer un nouveau compte avec profil complet dès le départ

**Card de conseil:** Affichée en sidebar (bordure bleue)

---

### Règle 2 : Un RESIDENT avec profil ne peut PAS changer de rôle
**Fichier:** `AdminController::updateUser()` (ligne 661)

**Vérification:**
```php
$hasResidentProfile = // Requête SQL pour vérifier residents_seniors
if ($oldRole === 'resident' && $role !== 'resident' && $hasResidentProfile) {
    setFlash('error', "Il n'est pas possible de changer le statut autre que resident_senior...", 12000);
    redirect('admin/users/edit/' . $id);
    return;
}
```

**Message affiché:** 12 secondes  
**Raison:** Le profil résident contient des données complexes (santé, contacts urgence, etc.)

**Card de conseil:** Affichée en sidebar (bordure jaune warning)

---

### ⚠️ CAS PARTICULIER : Résident ET Propriétaire

Si une personne est à la fois **résident senior** ET **propriétaire**, il faut créer **2 comptes distincts** :

1. **Compte Résident**
   - Role: `resident`
   - Email: `jean.dupont@resident.syndgest.fr`
   - Profil dans `residents_seniors`

2. **Compte Propriétaire**
   - Role: `proprietaire`
   - Email: `jean.dupont@gmail.com` (email personnel)
   - Profil dans `coproprietaires` (si nécessaire)

**Justification:** Séparation des rôles, des permissions et des données métier

---

## 🗑️ Suppression d'Utilisateurs

### Méthode Unique : TOGGLE ACTIF/INACTIF
**Fichier:** `AdminController::toggleUser()` (ligne 815)

**Processus:**
1. ❌ Interdiction de modifier soi-même
2. Vérification CSRF token
3. **Bascule du statut** (`actif = 1` ↔ `actif = 0`)
4. Log de l'action dans `security_logs`

**SQL:**
```sql
UPDATE users SET actif = [0 ou 1], updated_at = NOW() WHERE id = ?
```

### ✅ Interface Simplifiée
- ✅ **1 seul bouton** : Toggle (icône ban/check)
- ✅ **Clair et réversible** : Activer ↔ Désactiver
- ❌ **Pas de bouton "Supprimer"** (évite la confusion)

### ✅ Avantages du Système
- ✅ Conservation de l'historique
- ✅ Réactivation en 1 clic
- ✅ Intégrité référentielle préservée
- ✅ Audit trail complet
- ✅ Interface intuitive

### ❌ Pas de suppression physique
- Les enregistrements restent dans la base
- Le profil `residents_seniors` reste lié
- Les relations avec `lots`, `occupations` restent intactes
- **Avantage:** Aucune perte de données, conformité RGPD

---

## 🔐 Validation - Email Unique

### Méthode : `User::emailExists()`
**Fichier:** `User.php` (ligne 221)

**Utilisation:**
```php
// Création
if ($userModel->emailExists($email)) {
    $errors[] = "Cet email est déjà utilisé";
}

// Édition (exclure l'user actuel)
if ($userModel->emailExists($email, $id)) {
    $errors[] = "Cet email est déjà utilisé";
}
```

**Requête SQL:**
```sql
SELECT id FROM users WHERE email = ? [AND id != ?]
```

✅ **Garantie d'unicité** à tous les niveaux (création, édition)

---

## 🎨 Interface Utilisateur

### Flash Messages - Durée Configurable
**Fichier:** `Controller::setFlash()` (ligne 67)

**Signature:**
```php
setFlash($type, $message, $duration = 5000)
```

**Exemples:**
```php
// Message standard (5 secondes)
$this->setFlash('success', 'Utilisateur créé');

// Message long (12 secondes)
$this->setFlash('error', 'Changement de rôle impossible...', 12000);
```

**Affichage:**
- Auto-dismiss après durée spécifiée
- Fermeture manuelle possible (bouton X)
- JavaScript Bootstrap Alert

---

### Cards de Conseils dans Formulaire Édition
**Fichier:** `admin/users/edit.php` (ligne 268)

#### Card pour RESIDENT (bordure jaune)
```html
<div class="card shadow border-warning">
    <div class="card-header bg-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Restriction Résident Senior
    </div>
    <div class="card-body">
        Un résident senior ne peut PAS changer de rôle.
        Créez un nouveau compte pour un autre statut.
    </div>
</div>
```

#### Card pour NON-RESIDENT (bordure bleue)
```html
<div class="card shadow border-info">
    <div class="card-header bg-info">
        <i class="fas fa-user-shield"></i>
        Restriction de Rôle
    </div>
    <div class="card-body">
        Cet utilisateur ne peut PAS devenir résident senior.
        Créez un nouveau compte dès le départ.
    </div>
</div>
```

---

## 📝 Logs et Sécurité

### Actions Loguées
**Fichier:** `Logger::logSensitiveAction()`

1. **USER_CREATED** - Création d'utilisateur
2. **USER_UPDATED** - Modification d'utilisateur
3. **USER_DELETED** - Désactivation d'utilisateur
4. **USER_TOGGLE_ACTIVE** - Changement statut actif/inactif
5. **USER_CREATE_ERROR** - Erreur création
6. **USER_UPDATE_ERROR** - Erreur modification

**Format:**
```json
{
    "action": "USER_UPDATED",
    "username": "jdupont",
    "user_id": 42,
    "updated_by": 1,
    "timestamp": "2025-12-16 14:30:00",
    "ip": "192.168.1.100"
}
```

---

## ✨ Améliorations Implémentées

### ✅ Validations
- [x] Email unique (création + édition)
- [x] Username unique (création + édition)
- [x] Protection changement rôle resident
- [x] Vérification profil resident existant
- [x] CSRF token sur toutes mutations

### ✅ UX
- [x] Flash messages avec durée configurable
- [x] Cards de conseils contextuelles
- [x] Messages explicites (12 secondes)
- [x] Breadcrumb navigation
- [x] Icons Font Awesome

### ✅ Sécurité
- [x] Soft delete (pas de perte de données)
- [x] Logs exhaustifs
- [x] Interdiction auto-modification
- [x] Validation server-side complète

---

## 🔄 Workflow Complet

### Scénario 1 : Créer un Propriétaire
1. Admin → Utilisateurs → Créer
2. Remplir nom, prénom, email, username, password
3. ✅ Email vérifié unique
4. Sélectionner role: `proprietaire`
5. Enregistrer → `user_id=10` créé
6. ✅ 1 seul enregistrement dans `users`

### Scénario 2 : Créer un Résident Senior
1. Admin ou Exploitant → Résidents → Créer
2. Remplir infos résident (nom, prénom, date naissance, etc.)
3. ✅ Transaction atomique:
   - User créé avec role='resident' (`user_id=11`)
   - Profil résident créé (`resident_id=5`, `user_id=11`)
4. ✅ 1 user + 1 profil résident liés

### Scénario 3 : Modifier un Gestionnaire en Exploitant
1. Admin → Utilisateurs → Modifier (ID 10)
2. User actuel: role='gestionnaire'
3. Changer role → 'exploitant'
4. ✅ Modification autorisée (ni resident source ni destination)
5. Enregistrer → Role mis à jour

### Scénario 4 : Tenter de Changer Gestionnaire en Resident ❌
1. Admin → Utilisateurs → Modifier (ID 10)
2. User actuel: role='gestionnaire'
3. Changer role → 'resident'
4. ❌ **BLOQUÉ:** "Il n'est pas possible de changer vers resident_senior..."
5. Flash message 12 secondes
6. Card bleue visible: "Créez un nouveau compte"

### Scénario 5 : Tenter de Changer Resident en Propriétaire ❌
1. Admin → Utilisateurs → Modifier (ID 11)
2. User actuel: role='resident' + profil dans residents_seniors
3. Changer role → 'proprietaire'
4. ❌ **BLOQUÉ:** "Il n'est pas possible de changer autre que resident_senior..."
5. Flash message 12 secondes
6. Card jaune visible: "Créez un nouveau compte"

### Scénario 6 : Supprimer un Utilisateur
1. Admin → Utilisateurs → Liste
2. Clic sur icône poubelle (user_id=10)
3. Confirmation modal
4. ✅ Soft delete: `actif=0`
5. User désactivé mais données conservées
6. Log: `USER_DELETED` avec détails

---

## 🛠️ Fichiers Modifiés

1. **`AdminController.php`**
   - `updateUser()` : Protections changement rôle (lignes 654-670)

2. **`Controller.php`**
   - `setFlash()` : Ajout paramètre `$duration` (ligne 67)

3. **`partials/flash.php`**
   - Auto-dismiss avec durée configurable
   - JavaScript Bootstrap Alert

4. **`admin/users/edit.php`**
   - Cards de conseils contextuelles (lignes 268-320)
   - Affichage conditionnel selon rôle

---

## 📚 Documentation Technique

### Méthodes Clés

#### `User::emailExists($email, $excludeId = null)`
Vérifie l'unicité de l'email dans la table users.

**Paramètres:**
- `$email` : Email à vérifier
- `$excludeId` : (optionnel) ID user à exclure (pour édition)

**Retour:** `bool`

---

#### `ResidentSenior::createWithUser($data)`
Crée un résident avec son compte user en transaction atomique.

**Paramètres:**
- `$data['civilite']` : M/Mme
- `$data['nom']` : Nom
- `$data['prenom']` : Prénom
- `$data['date_naissance']` : Date au format YYYY-MM-DD
- ... (autres champs résidents)

**Retour:**
```php
[
    'success' => true,
    'user_id' => 11,
    'resident_id' => 5,
    'username' => 'jdupont',
    'password' => 'resident123'
]
```

---

## 🎓 Bonnes Pratiques

### ✅ À FAIRE
- Créer les résidents via le module Résidents
- Vérifier l'email avant création
- Utiliser des transactions pour créations multiples
- Logger toutes les actions sensibles
- Afficher des messages clairs et longs (12s)

### ❌ À ÉVITER
- Changer le rôle d'un resident existant
- Créer manuellement un resident dans users puis residents_seniors
- Supprimer physiquement des users (toujours soft delete)
- Réutiliser des emails existants
- Dupliquer les comptes

---

## 🔮 Évolutions Futures

### Propositions
1. **Fusion de comptes** : Outil admin pour fusionner 2 users dupliqués
2. **Historique de rôles** : Table `user_role_history` pour tracer les changements
3. **Alertes duplication** : Détection automatique de potentiels doublons (nom + prénom similaires)
4. **Export/Import** : Gestion en masse des utilisateurs
5. **Multi-résidence** : Permettre à un résident d'avoir plusieurs occupations

---

## ✅ Checklist de Validation

- [x] Email unique garanti à la création
- [x] Email unique garanti à l'édition
- [x] Protection changement NON-RESIDENT → RESIDENT
- [x] Protection changement RESIDENT → autre rôle
- [x] Vérification profil residents_seniors existant
- [x] Flash messages durée configurable (12 secondes)
- [x] Cards de conseils dans formulaire édition
- [x] Toggle actif/inactif pour gestion utilisateurs
- [x] Bouton "Supprimer" retiré (évite confusion)
- [x] Interface simplifiée et intuitive
- [x] Logs de toutes actions utilisateurs
- [x] CSRF protection sur toutes mutations
- [x] Interface claire et explicative

---

**Système validé et prêt pour production** ✅

**Dernière mise à jour:** 16 décembre 2025
