# Module Accueil

## État : ✅ 100 % livré (6 phases)

Module dédié à l'équipe d'accueil de la résidence couvrant : gestion des résidents (notes), catalogues salles communes + équipements prêtables, réservations multi-types avec workflow validation, animations + inscriptions résidents (pointage présent/absent), planning double-vue TUI Calendar (résidents/staff/tout), équipe + messagerie groupée multi-cibles.

## Rôles dédiés

| Rôle BDD | id | Description |
|----------|----|-------------|
| `accueil_manager` | 20 | Pilotage de l'accueil : équipe, catalogues salles/équipements, validation réservations, animations, hôtes, messagerie groupée |
| `accueil_employe` | 21 | Opérations courantes : créer réservations, inscrire résidents aux animations, notes, hôtes (lecture catalogues seulement) |

> **Distinction** : `accueil_*` est strictement séparé de `employe_residence` (qui reste pour les autres opérations administratives). Le rôle `employe_residence` continuera à servir pour la gestion historique des hôtes temporaires (`HoteController`).

### Constantes contrôleur
```php
private const ROLES_ALL     = ['admin', 'directeur_residence', 'accueil_manager', 'accueil_employe'];
private const ROLES_MANAGER = ['admin', 'directeur_residence', 'accueil_manager'];
```

## Architecture BDD (migration 025)

| Table | Description |
|-------|-------------|
| `accueil_salles` | Catalogue salles communes par résidence (capacité, photo, équipements inclus) |
| `accueil_equipements` | Catalogue équipements prêtables (mobilité, info, loisirs, médical) avec statut |
| `accueil_reservations` | Réservations multi-types (salle / équipement / service_personnel) avec workflow validation |
| `accueil_animation_inscriptions` | Pivot résident ↔ shift animation (UNIQUE shift+resident) |
| `resident_notes_accueil` | Notes texte libre sur résidents (créateur + horodatage) |

### Réutilisations (pas de nouvelle table)
- **Animations** = shifts du planning staff `category_id = 14` (table `planning_shifts`)
- **Hôtes temporaires** = `hotes_temporaires` + `HoteController` existants (lien depuis le dropdown navbar)
- **Messagerie groupée** = réutilise `MessageController::send()` qui supporte déjà `destinataires[]` (vue dédiée pré-remplit la sélection)

## Périmètre (fichiers)

- Controller : [app/controllers/AccueilController.php](../../app/controllers/AccueilController.php) — 30+ endpoints
- Modèle : [app/models/Accueil.php](../../app/models/Accueil.php) — 50+ méthodes
- Vues : [app/views/accueil/](../../app/views/accueil/) — 15 fichiers :
  - `index.php` (dashboard avec 5 KPI + 7 cartes accès rapide)
  - `residents.php`, `resident_notes.php`
  - `salles.php`, `salle_form.php`
  - `equipements.php`, `equipement_form.php`
  - `reservations.php`, `reservation_form.php`, `reservation_show.php`
  - `animations.php`, `animation_form.php`, `animation_show.php`
  - `planning.php` (TUI Calendar double-vue)
  - `equipe.php`, `message_groupe.php`
- Stockage : `public/uploads/accueil/salles/` (photos publiques 5 Mo max, JPG/PNG/WEBP, MIME finfo)

## Permissions par section

| Section | accueil_employe | accueil_manager | admin / dir_residence |
|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ |
| Liste résidents + notes | ✓ | ✓ | ✓ |
| Catalogue salles (lecture) | ✓ | ✓ | ✓ |
| Catalogue salles (CRUD + photo) | ❌ | ✓ | ✓ |
| Catalogue équipements (lecture) | ✓ | ✓ | ✓ |
| Catalogue équipements (CRUD) | ❌ | ✓ | ✓ |
| Réservations (créer + voir) | ✓ | ✓ | ✓ |
| Réservations (valider/refuser/réaliser) | ❌ | ✓ | ✓ |
| Réservations (supprimer définitivement) | ❌ | ✓ | ✓ |
| Animations (lecture + inscrire résidents) | ✓ | ✓ | ✓ |
| Animations (CRUD + supprimer) | ❌ | ✓ | ✓ |
| Pointage présent/absent | ✓ | ✓ | ✓ |
| Inscriptions (supprimer) | ❌ | ✓ | ✓ |
| Planning (lecture) | ✓ | ✓ | ✓ |
| Planning (drag & drop animations) | ❌ | ✓ | ✓ |
| Hôtes temporaires | ✓ | ✓ | ✓ |
| Équipe | ❌ | ✓ | ✓ |
| Message groupé | ❌ | ✓ | ✓ |

## Routes implémentées (30+)

| URL | Méthode | Rôles |
|-----|---------|-------|
| `GET /accueil/index` | `index()` | ROLES_ALL |
| `GET /accueil/residents?residence_id=N` | `residents()` | ROLES_ALL |
| `GET /accueil/residentNotes/{id}` | `residentNotes($id)` | ROLES_ALL |
| `POST /accueil/noteCreate` | `noteCreate()` | ROLES_ALL |
| `POST /accueil/noteDelete/{id}` | `noteDelete($id)` | auteur ou MANAGER |
| `GET /accueil/salles?residence_id=N` | `salles()` | ROLES_ALL |
| `GET\|POST /accueil/salleForm/{id?}` | `salleForm($id?)` | MANAGER |
| `POST /accueil/salleDelete/{id}` | `salleDelete($id)` | MANAGER |
| `GET /accueil/equipements?residence_id=N` | `equipements()` | ROLES_ALL |
| `GET\|POST /accueil/equipementForm/{id?}` | `equipementForm($id?)` | MANAGER |
| `POST /accueil/equipementDelete/{id}` | `equipementDelete($id)` | MANAGER |
| `GET /accueil/reservations` | `reservations()` | ROLES_ALL |
| `GET\|POST /accueil/reservationForm/{id?}` | `reservationForm($id?)` | ROLES_ALL |
| `GET /accueil/reservationShow/{id}` | `reservationShow($id)` | ROLES_ALL |
| `POST /accueil/reservationValider/{id}` | `reservationValider($id)` | MANAGER |
| `POST /accueil/reservationRefuser/{id}` | `reservationRefuser($id)` | MANAGER (motif obligatoire) |
| `POST /accueil/reservationAnnuler/{id}` | `reservationAnnuler($id)` | ROLES_ALL |
| `POST /accueil/reservationRealiser/{id}` | `reservationRealiser($id)` | MANAGER |
| `POST /accueil/reservationDelete/{id}` | `reservationDelete($id)` | MANAGER |
| `GET /accueil/animations?residence_id=N&periode=futures\|toutes` | `animations()` | ROLES_ALL |
| `GET\|POST /accueil/animationForm/{id?}` | `animationForm($id?)` | MANAGER |
| `GET /accueil/animationShow/{id}` | `animationShow($id)` | ROLES_ALL |
| `POST /accueil/animationDelete/{id}` | `animationDelete($id)` | MANAGER |
| `POST /accueil/inscriptionCreate` | `inscriptionCreate()` | ROLES_ALL |
| `POST /accueil/inscriptionStatut/{id}` | `inscriptionStatut($id)` | ROLES_ALL |
| `POST /accueil/inscriptionDelete/{id}` | `inscriptionDelete($id)` | MANAGER |
| `GET /accueil/planning` | `planning()` | ROLES_ALL |
| `* /accueil/planningAjax/{action}` | `planningAjax($action)` | ROLES_ALL — actions `getEvents`, `moveAnimation` (MANAGER) |
| `GET /accueil/equipe?residence_id=N` | `equipe()` | MANAGER |
| `GET /accueil/messageGroupe?residence_id=N` | `messageGroupe()` | MANAGER (POST → `/message/send`) |

## Workflow réservations

- **Salles communes** : `en_attente` → validation accueil → `confirmee` ou `refusee` (motif obligatoire). Anti-chevauchement vérifié à la création (statuts `en_attente` + `confirmee` bloquants)
- **Équipements** : `en_attente` → validation accueil → `confirmee`. **Statut équipement bascule à `prete`** quand confirmé. Réalisation ou annulation → retour `disponible`
- **Services personnels** (coiffeur, pédicure, manucure, esthétique, taxi, autre) : `en_attente` → `confirmee`/`refusee`. Pas de tarification, pas d'anti-chevauchement
- **Annulation** : possible à tout moment par n'importe quel utilisateur (libère l'équipement si confirmée)
- **Réalisation** : marque la réservation comme honorée (manager only)
- **Suppression définitive** : manager only

## Workflow animations

- Création par MANAGER seulement (devient un shift `category_id=14` dans `planning_shifts`, animateur = staff résidence ou admin)
- Inscription d'un résident : par accueil_employe ou manager — statut **`inscrit`** immédiat (pas de validation)
- Inscription **idempotente** : si une inscription `annule` existe déjà → réactivée. Pas de doublon possible (UNIQUE shift+resident)
- Pointage présent/absent : disponible une fois l'animation commencée ou terminée (boutons verts/rouges)
- Annulation : utilisateur ou résident ferme l'inscription en `annule`
- Suppression définitive : manager only
- Suppression de l'animation : retire toutes les inscriptions (CASCADE)

## Planning double-vue (TUI Calendar v1.15.3)

- 6 calendriers colorés : animation (cyan), salle (bleu foncé), équipement (vert), service personnel (violet), staff (gris), hôte (orange `allday`)
- 3 onglets vue : **Résidents** (animations + réservations confirmées + hôtes), **Staff** (shifts toutes catégories sauf 14), **Tout** (superposition)
- Drag & drop : **uniquement pour les animations + manager** (catégorie 14 vérifiée serveur). Refus bloquant pour les autres types/rôles
- Click sur événement : redirige vers la fiche correspondante (`animationShow`, `reservationShow`, `hote/show`)
- Endpoint AJAX : `planningAjax/getEvents?residence_id=X&vue=Y&start=Z&end=W` retourne JSON unifié
- CSRF via meta `<meta name="csrf-token">` du layout

## Équipe & messagerie groupée

### Page Équipe (`/accueil/equipe` — manager only)
- Cards staff filtrées par résidence (rôles `accueil_manager`, `accueil_employe`, `directeur_residence`)
- Photo de profil ou initiales + badge rôle coloré
- Boutons rapides : message interne / email / téléphone
- Lien direct vers "Message groupé"

### Message groupé (`/accueil/messageGroupe` — manager only)
- 3 onglets de destinataires : Résidents (avec compte) / Staff / Propriétaires
- Sélection par checkbox + filtres recherche live + boutons "Tout/Aucun" par onglet
- Compteur dynamique de destinataires sélectionnés
- Champs : sujet (obligatoire), priorité (normale/haute/urgente), contenu (obligatoire)
- POST direct vers `/message/send` (réutilise infrastructure `MessageController` existante avec `type_envoi=individuel` + `destinataires[]`)
- Le message est envoyé **individuellement** à chaque destinataire (pas un thread partagé)

## Stockage fichiers

- `public/uploads/accueil/salles/` — photos publiques des salles communes (5 Mo max)
- Validation upload : extension whitelist (`.jpg`, `.jpeg`, `.png`, `.webp`) + MIME via `finfo` (anti-polyglot) + nom sanitizé (`time() + random_bytes(4) + nom_origine`)

## KPI dashboard (5)

1. **Résidences** accessibles
2. **Résidents** hébergés actuellement (occupations actives)
3. **Hôtes** présents (séjours en cours)
4. **Notes** créées dans les 7 derniers jours
5. **Réservations en attente** de validation (cliquable → filtre statut)
6. Badge "Animations cette semaine" sur la carte d'accès rapide

## Règles métier transversales

- Filtrage strict par `residence_id` selon le rôle (staff accueil voit sa résidence via `user_residence.statut='actif'`, admin voit tout)
- Notes résidents en **texte libre simple** (pas de catégorisation ni RGPD avancé)
- Catalogue salles + équipements **par résidence** (pas mutualisé)
- Anti-chevauchement appliqué uniquement sur salles + équipements (services personnels libres)
- Animations utilisent `planning_shifts` directement → visibles dans tous les modules de planning
- CSRF systématique sur tous les POST (validation, refus, annulation, suppression, drag & drop AJAX)
- `htmlspecialchars()` sur toutes les sorties

## Tests recommandés

### Permissions
- [ ] `accueil_employe` → 200 sur `/accueil/index`, `/accueil/salles` (lecture), redirect avec flash error sur `/accueil/salleForm`, `/accueil/equipementForm`, `/accueil/reservationValider/X`, `/accueil/animationDelete/X`, `/accueil/equipe`, `/accueil/messageGroupe`
- [ ] `accueil_manager` → 200 sur tous les endpoints du module
- [ ] `accueil_*` → 403 sur `/jardinage/*`, `/maintenance/*` (modules hors périmètre)
- [ ] `admin` → 200 partout

### Workflow réservations
- [ ] Créer réservation salle 14h-16h → tenter doublon 15h-17h → bloqué (anti-chevauchement)
- [ ] Valider réservation équipement → statut équipement passe à `prete`
- [ ] Annuler réservation équipement confirmée → statut équipement revient à `disponible`
- [ ] Refuser sans motif → erreur

### Workflow animations
- [ ] Inscrire résident annulé → réactivé (pas de doublon)
- [ ] Supprimer animation → toutes inscriptions retirées (CASCADE)
- [ ] Pointage présent/absent visible uniquement quand animation démarrée

### Planning
- [ ] Drag & drop animation en tant qu'employé → bloqué côté JS et serveur
- [ ] Drag & drop salle/équipement → bloqué (isReadOnly côté client)
- [ ] Vue "Tout" → fusion correcte des 6 types

### Messagerie groupée
- [ ] Sélection 3 résidents + 2 staff → 5 messages individuels créés
- [ ] Filtre recherche → masque les items hors filtre, "Tout" ne sélectionne que les visibles

## Comptes de test
| Username | Rôle | Mot de passe |
|----------|------|-------------|
| `accueil_chef` (Marie Dubois, id=90) | `accueil_manager` | `Acc1234` |
| `accueil_emp` (Pierre Lambert, id=91) | `accueil_employe` | `Acc1234` |

Les deux sont liés à la résidence 61 (La Badiane) via `user_residence`.

## Améliorations éventuelles

- [ ] Notification email automatique au demandeur quand sa réservation est validée/refusée
- [ ] Vue calendrier publique pour les résidents seniors (lecture seule + bouton inscription animation)
- [ ] Export PDF du programme d'animation hebdomadaire (affiche)
- [ ] Statistiques équipe : nb réservations traitées, taux validation/refus, temps moyen de validation
- [ ] Réservation récurrente (animations hebdomadaires automatiques)
- [ ] Notes catégorisées (santé, comportement, social, fiscal) si demande RGPD ultérieure
