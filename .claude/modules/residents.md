# Module Résidents & Occupations

## Périmètre
Controllers : `ResidentController`, `OccupationController`, `ServiceController`
Vues : `app/views/residences/residents/`, `app/views/occupations/`
Accès : `requireRole(['admin', 'directeur_residence', 'exploitant'])`

## Profil Résident Senior (`residents_seniors`)

### Champs principaux (34 champs)
- Identité : nom, prénom, date_naissance, lieu_naissance, nationalite
- CNI : numero_cni, date_expiration_cni
- Contact : telephone, email, adresse_avant
- Urgence : contact_urgence_nom, contact_urgence_tel, contact_urgence_lien
- Santé : medecin_traitant, groupe_sanguin, allergies, traitement_medical
- Financier : caisse_retraite, numero_securite_sociale, revenus_mensuels
- Statut : actif (BOOLEAN)

### Règles
- Lié à un `user` via `user_id` (rôle `locataire_permanent`)
- Désactivation résident (`actif=0`) → **terminer automatiquement** toutes ses occupations actives
- Un résident ne peut avoir qu'**1 compte user** (1:1)

### À vérifier lors du dev
- [ ] Désactivation résident → UPDATE occupations SET date_fin = NOW() WHERE resident_id = X AND date_fin IS NULL
- [ ] Désactivation user lié simultanément
- [ ] Données sensibles (santé, CNI) accessibles uniquement aux rôles autorisés

## Occupations (`occupations_residents`)

### Règles critiques
- **1 occupant actif max par lot** — seule contrainte appliquée (trigger BDD `trg_validation_occupation`)
- **Aucune limite côté résident** : un résident peut louer **plusieurs lots dans plusieurs résidences** (logements, caves, parkings sans plafond)
- Champs : `loyer_mensuel_resident`, `forfait_type`, `montant_apl`, `montant_apa`, `date_entree`, `date_sortie`, `statut`

### Vérification avant création d'occupation
```php
// Seule règle : le lot ne doit pas être déjà occupé.
if ($occupationModel->isLotOccupied($lot_id)) {
    throw new Exception("Ce lot est déjà occupé.");
}
// Le trigger BDD double-check côté MySQL.
```

### Historique
> Avant la migration 018 (avril 2026), des contraintes applicatives limitaient le résident à 1 logement + 1 cave + 1 parking (3 occupations max). Ces règles ont été levées : un résident senior peut désormais louer librement dans le parc Domitys.

### À vérifier lors du dev
- [ ] Vérification disponibilité lot avant tout INSERT (`isLotOccupied`)
- [ ] `date_sortie` NULL et `statut='actif'` = occupation active
- [ ] Calcul loyer net = loyer - aides sociales affiché correctement
- [ ] Liste des occupations filtrée par résidence si directeur

## Services (`services` + `occupation_services`)

### Règles
- Services `inclus` : forfait mensuel global
- Services `supplémentaire` : facturation individuelle avec `prix_applique`
- `prix_applique` peut différer du prix catalogue (négociation)

### À vérifier lors du dev
- [ ] Prix appliqué ≠ prix catalogue possible (champ éditable)
- [ ] Calcul total services correct dans la vue occupation
- [ ] Ajout/suppression service sans clôturer l'occupation

## Intégration Messagerie
Le résident senior (`locataire_permanent`) a accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible depuis la fiche résident vers admin/direction

## Checklist générale module Résidents
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST
- [ ] Vérification disponibilité lot avant création d'occupation (`isLotOccupied`)
- [ ] Désactivation en cascade (résident → occupations → user)
- [ ] `htmlspecialchars()` sur données sensibles affichées
- [ ] Accès restreint aux données santé/financières

## Espace Résident Senior ✅

Espace personnel complet pour les users avec rôle `locataire_permanent`, analogue à l'espace propriétaire. URL préfixée par `/resident/...`.

### Périmètre (fichiers)

- Controllers :
  - [app/controllers/ResidentController.php](../../app/controllers/ResidentController.php) — espace résident (méthodes ajoutées section "Espace personnel")
  - [app/controllers/ResidentDocumentController.php](../../app/controllers/ResidentDocumentController.php) — GED
- Modèles :
  - [app/models/ResidentCalendar.php](../../app/models/ResidentCalendar.php) — calendrier
  - [app/models/ResidentDocument.php](../../app/models/ResidentDocument.php) — GED
  - [app/models/ResidentSenior.php](../../app/models/ResidentSenior.php) — profil (existant)
- Vues : [app/views/residents/](../../app/views/residents/)
  - `mon_espace.php`, `mes_lots.php`, `mes_occupations.php`, `mes_residences.php`
  - `calendrier.php`, `comptabilite.php`, `declaration_fiscale.php`, `profile.php`
  - `documents/index.php`
- Stockage GED : `uploads/residents/{user_id}/{dossier_id|racine}/` (hors `public/`)
- Navbar : dropdown « Mon espace » + liens Calendrier / Mes Documents / Comptabilité dans [navbar.php](../../app/views/partials/navbar.php) (visibles uniquement pour `locataire_permanent`)

### Migration appliquée

| # | Nom | Contenu |
|---|-----|---------|
| 018 | `018_espace_resident.sql` | Tables `planning_resident_categories` (6 cat. : loyer, animation, medical, famille, fiscal, autre), `planning_resident`, `resident_dossiers`, `resident_fichiers` |

Note : la catégorie `animation` a aussi été ajoutée dans `planning_categories` (id=14) pour permettre la lecture des shifts d'animation des résidences.

### Routes implémentées

| URL | Méthode | Rôles |
|-----|---------|-------|
| `GET /resident/monEspace` | `monEspace()` | locataire_permanent |
| `GET /resident/mesLots` | `mesLots()` | locataire_permanent |
| `GET /resident/mesOccupations` | `mesOccupations()` | locataire_permanent |
| `GET /resident/mesResidences` | `mesResidences()` | locataire_permanent |
| `GET /resident/calendrier` | `calendrier()` | locataire_permanent |
| `* /resident/calendarAjax/{action}` | `calendarAjax($action)` | locataire_permanent — actions `getEvents`, `save`, `move`, `delete` |
| `GET /resident/comptabilite` | `comptabilite()` | locataire_permanent |
| `POST /resident/chat` | `chat()` | locataire_permanent — assistant IA budget/fiscal (mode `budget` ou `fiscal`) |
| `GET /resident/declarationFiscale` | `declarationFiscale()` | locataire_permanent |
| `POST /resident/chatDeclaration` | `chatDeclaration()` | locataire_permanent — assistant IA fiscal avec analyse vision PDF/images |
| `GET /resident/profile` | `profile()` | locataire_permanent |
| `POST /resident/changePassword` | `changePassword()` | locataire_permanent (synchronise `password_plain`) |
| `GET /residentDocument/index/{dossierId?}` | `index()` | locataire_permanent |
| `POST /residentDocument/createDossier` | `createDossier()` | locataire_permanent |
| `POST /residentDocument/renameDossier/{id}` | `renameDossier($id)` | locataire_permanent |
| `POST /residentDocument/deleteDossier/{id}` | `deleteDossier($id)` | locataire_permanent |
| `POST /residentDocument/upload` | `upload()` | locataire_permanent |
| `GET /residentDocument/download/{id}` | `download($id)` | locataire_permanent |
| `GET /residentDocument/preview/{id}` | `preview($id)` | locataire_permanent |
| `POST /residentDocument/deleteFichier/{id}` | `deleteFichier($id)` | locataire_permanent |

Routes croisées avec autorisation étendue au `locataire_permanent` :
- `GET /lot/show/{id}` — autorisé pour le résident **uniquement sur les lots qu'il occupe** (filtrage `Occupation::getActivesByResident`)
- `GET /resident/show/{id}` — autorisé pour le résident **uniquement sur sa propre fiche** (filtrage `user_id`, RGPD art. 9)
- `GET /occupation/show/{id}` — autorisé pour le résident **uniquement sur ses propres occupations** (existait déjà)
- `GET/POST /message/*` — accessible (pas de `requireRole` sur `MessageController`, juste `requireAuth`)

### Sections fonctionnelles

#### Tableau de bord (`/resident/monEspace`)
- 4 KPI : lots actifs, résidences, loyer mensuel total, niveau d'autonomie
- 6 cartes d'accès rapide : Mes lots, Résidences Domitys, Calendrier, Mes documents, Comptabilité, Messagerie
- Tableau lots actifs avec liens vers occupation/show et lot/show

#### Mes lots / Mes occupations (`/resident/mesLots`, `/resident/mesOccupations`)
- Grid de cartes lots actifs avec détails (résidence, type, surface, étage, loyer)
- Tableau occupations actives + historique avec DataTable (tri + recherche + pagination)

#### Vitrine résidences (`/resident/mesResidences`)
- Carte Leaflet plein écran (22+ résidences géolocalisées)
- Marqueurs orange = résidences où le résident habite, bleu = autres
- Tableau filtrable + pagination, mes résidences en surbrillance

#### Calendrier (`/resident/calendrier`)
- TUI Calendar v1.15.3, 6 catégories
- Auto-génération (non éditables) :
  - Loyers mensuels au `jour_prelevement` (défaut 5) pour chaque occupation active
  - Animations résidence (lecture `planning_shifts` filtrée par catégorie `animation` + résidences du résident)
  - Rappels fiscaux avril (ouverture déclaration) et mai (date limite)
- Manuels (drag & drop, double-clic pour éditer) : RDV médicaux, famille, autres

#### GED documents (`/residentDocument/...`)
- **Quota 500 MB par résident, 50 MB par fichier**
- 15 MIME autorisés (PDF, Office, ODF, images, vidéos, ZIP), 18 extensions whitelist
- Triple validation upload : taille → quota → extension → MIME réel via `finfo` (anti-polyglot)
- Stockage hors `public/` : `uploads/residents/{user_id}/{dossier_id|racine}/{ts}_{uuid}_{nom}.{ext}`
- Arborescence de dossiers libres, prévisualisation inline images/vidéos/PDF, DataTable + pagination > 10 lignes

#### Comptabilité (`/resident/comptabilite`)
- 4 KPI : dépenses/mois, aides perçues, reste à charge mois/an
- Ventilation détaillée (loyer + charges + services - APL - APA)
- Détail par lot
- **Assistant IA budget** (Claude Sonnet, mode `budget`) : conseils sur reste à charge, aides supplémentaires (ASH), optimisation services

#### Déclaration fiscale (`/resident/declarationFiscale`)
- Estimations indicatives :
  - Crédit d'impôt services à la personne (50%, plafond 12 000 €/an, case 7DB)
  - Réduction d'impôt résidence services médicalisée (25%, plafond 10 000 €, case 7CD)
- Aide-mémoire cases formulaire 2042 (1AS pension, 7DB, 7CD)
- **Assistant IA fiscal** (mode `fiscal`) avec analyse de fichiers PDF/images depuis la GED

#### Profil (`/resident/profile`)
- Infos compte **toutes en lecture seule** (modifications via la messagerie auprès de la direction)
- Carte profil avec photo (upload/changement via endpoints `/user/uploadPhoto` réutilisés)
- Section sécurité :
  - Aide-mémoire mot de passe (toggle œil + bouton copier) — affichage `password_plain`
  - Formulaire changement mot de passe (vérif actuel, longueur min 6, confirmation)
  - `changePassword` synchronise `password_plain` après update

### Règles métier transversales

- **Filtrage strict `user_id`** sur tous les endpoints : un résident ne voit jamais les données d'un autre résident
- **RGPD art. 9** : `resident/show` exclut volontairement le rôle `proprietaire` (santé/CNI/SS) et limite le résident à sa propre fiche
- **Lot/show filtré** : un résident ne peut consulter que les lots dont il a une occupation active
- **GED quota 500 MB** vérifié AVANT et APRÈS upload (race condition)
- **Calendrier auto-events en mémoire**, non persistés (dédoublonnage automatique). Manuels seuls en BDD avec `auto_genere=0`
- **Drag & drop calendrier** : refusé sur événements auto (`auto_genere=1` filtré dans `moveEvenement`)
- **CSRF sur tous les POST** : changement mot de passe, upload GED, création/suppression dossiers, save/move/delete calendrier
- Assistant IA : contexte budget complet injecté dans le system prompt (identité, autonomie, occupations, dépenses détaillées)

### Tests recommandés

#### Permissions
- [ ] Connecté en `resident1` → 200 sur `/resident/monEspace`, `/resident/mesLots`, `/resident/calendrier`, `/resident/comptabilite`
- [ ] Connecté en `resident1` → 200 sur `/resident/show/{son_id}`, redirect avec flash erreur sur `/resident/show/{autre_id}`
- [ ] Connecté en `resident1` → 200 sur `/lot/show/{lot_avec_son_occupation}`, redirect avec flash erreur sur `/lot/show/{autre_lot}`
- [ ] Connecté en `resident1` → 403 sur `/admin/users`, `/coproprietaire/index`, etc.
- [ ] Connecté en `admin` → toutes les pages résident accessibles via la liste résidents (vue admin)

#### Fonctionnel
- [ ] Création d'un événement manuel (RDV médical) → apparaît au calendrier
- [ ] Drag & drop sur RDV manuel → sauvegardé en BDD
- [ ] Tentative drag & drop sur loyer auto-généré → bloqué (raw.autoGenere)
- [ ] Upload PDF dans la GED → quota mis à jour, fichier accessible via download
- [ ] Tentative upload .exe → rejeté (extension non autorisée)
- [ ] Changement de mot de passe → ancien mdp invalide en login, nouveau mdp dans `password_plain`
- [ ] Chat IA budget : question simple → réponse pertinente avec contexte injecté
- [ ] Chat IA fiscal : analyse d'un avis d'imposition (vision) → extraction des montants pertinents