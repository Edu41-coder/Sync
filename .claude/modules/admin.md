# Module Admin — Users, Résidences, Lots

## Périmètre
Controllers : `AdminController`, `UserController`
Vues : `app/views/admin/`, `app/views/residences/`, `app/views/lots/`
Accès : `requireRole(['admin', 'directeur_residence', 'exploitant'])`

## Gestion des Users

### Règles
- Mot de passe stocké en double : `password_hash` (bcrypt) + `password_plain` (visible admin)
- Changement de rôle interdit si profil lié (`proprietaire`, `locataire_permanent`, `exploitant`)
- Statut admin verrouillé — impossible de désactiver le compte admin
- Désactivation → terminer automatiquement les occupations actives du résident lié

### À vérifier lors du dev
- [ ] Création user : hash + plain sauvegardés simultanément
- [ ] Formulaire rôle désactivé si profil lié (attribut `disabled` + vérification PHP côté serveur)
- [ ] Statut et rôle admin non modifiables (vérification `if ($user->username === 'admin')`)
- [ ] Liaison `user_residence` créée pour staff/direction

## Gestion des Résidences (`coproprietees`)

### Règles
- `type_residence = 'residence_seniors'` uniquement
- Création → Domitys (`exploitant id=1`) lié automatiquement à 100% via `exploitant_residences`
- Géocodage : JS au `blur` sur le champ adresse + fallback PHP (`curl api-adresse.data.gouv.fr`)
- Ville normalisée : `ucfirst(mb_strtolower($ville))`
- **Suppression hard** si vierge (0 lot, 0 user) — **soft delete** (`actif=0`) sinon

### À vérifier lors du dev
- [ ] `exploitant_residences` créé automatiquement à la création de résidence
- [ ] Géocodage JS fonctionnel + fallback PHP testé
- [ ] Ville normalisée avant INSERT/UPDATE
- [ ] Vérification lots + users avant suppression (hard vs soft)
- [ ] Résidences inactives (`actif=0`) exclues des listes par défaut

## Gestion des Lots

### Règles
- `type` ENUM : `studio`, `t2`, `t2_bis`, `t3`, `parking`, `cave`
- `terrasse` ENUM : `non`, `oui`, `loggia`
- Un lot ne peut avoir qu'**1 occupation active** à la fois
- Un lot ne peut avoir qu'**1 contrat actif** à la fois

### À vérifier lors du dev
- [ ] ENUM respectés (pas de valeur hors liste)
- [ ] Vérification disponibilité lot avant création d'occupation
- [ ] Vérification unicité contrat actif avant création de contrat

## Carte Leaflet (`carteResidence`)
- Utilise `latitude`/`longitude` de la table `coproprietees`
- Marqueurs cliquables avec info-bulle résidence + lots
- Données passées en JSON depuis le controller

## Intégration Messagerie
Tous les rôles admin/direction ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible depuis les fiches user / résidence

## Checklist générale module Admin
- [ ] CSRF vérifié sur tous les POST
- [ ] `htmlspecialchars()` sur toutes les sorties
- [ ] Messages flash après chaque action (success/error)
- [ ] Retour propre si ID inexistant (404 ou redirect)
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
