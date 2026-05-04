# Module Propriétaires, Contrats & Fiscalité

## Périmètre
Controllers : `CoproprietaireController`, `ContratController`, `FiscaliteController`
Vues : `app/views/coproprietaires/`
Accès admin : `requireRole(['admin', 'comptable'])`
Accès proprio : `requireRole(['proprietaire'])` + filtrer par `user_id` session

## Profil Propriétaire (`coproprietaires`)

### Règles
- Lié à un `user` via `user_id` (rôle `proprietaire`)
- Un propriétaire peut posséder **plusieurs lots** dans **plusieurs résidences**
- L'espace propriétaire ne montre que SES données (filtrer par `coproprietaires.user_id = $_SESSION['user_id']`)

### À vérifier lors du dev
- [ ] Toutes les requêtes dans l'espace proprio filtrent par `user_id` session
- [ ] Un admin peut voir tous les propriétaires, un proprio ne voit que lui-même

## Contrats de gestion (`contrats_gestion`)

### Règles critiques
- **1 contrat actif max par lot** (vérifier avant création)
- Champs : `lot_id`, `proprietaire_id`, `exploitant_id`, `loyer_garanti`, `date_debut`, `date_fin`, `dispositif_fiscal`, `statut`
- `statut` : `actif` / `termine` / `suspendu`
- Loyer garanti Domitys = 850€ (valeur type)
- Vérification doublon : même lot + même proprio + dates qui se chevauchent

### Vérifications avant création
```php
// Contrat actif existant sur ce lot ?
$contratActif = Contrat::getActifByLot($lot_id); // doit être NULL

// Doublon proprio + lot ?
$doublon = Contrat::checkDoublon($lot_id, $proprietaire_id); // doit être FALSE
```

### Dispositifs fiscaux supportés
- `lmnp_reel` — LMNP Réel
- `lmnp_micro` — LMNP Micro-BIC
- `censi_bouvard` — Censi-Bouvard
- `nu_proprietaire` — Nue-propriété

### À vérifier lors du dev
- [ ] Vérification unicité contrat actif par lot avant INSERT
- [ ] Clôture automatique de l'ancien contrat si nouveau contrat créé
- [ ] Dispositif fiscal affiché correctement selon valeur ENUM

## Fiscalité (`revenus_fiscaux_proprietaires`)

### Règles
- 1 ligne par propriétaire par année fiscale
- Champs : `proprietaire_id`, `annee`, `revenus_bruts`, `charges_deductibles`, `amortissements`, `revenu_net_imposable`
- Calcul : `revenu_net_imposable = revenus_bruts - charges_deductibles - amortissements`

### À vérifier lors du dev
- [ ] Unicité (proprietaire_id + annee) — pas de doublon
- [ ] Calcul net imposable recalculé à chaque modification
- [ ] Export PDF/CSV si prévu

## Comptabilité propriétaire
- Suivi des loyers reçus vs loyer garanti
- Visualisation par lot et par résidence
- Tables : `ecritures_comptables`, `comptes_comptables`, `exercices_comptables`

## Calendrier propriétaire (déjà implémenté)
Controller : `CoproprietaireController::calendrier()` + `calendarAjax()`
Vue : `app/views/coproprietaires/calendrier.php`

### Règles
- Calendrier personnel du propriétaire (rendez-vous, échéances, AG, visites, etc.)
- Filtré par `proprietaire_id` — chaque propriétaire ne voit que SON calendrier
- TUI Calendar v1.15.3 (cohérent avec module Planning staff)
- Catégories d'événements via `Coproprietaire::getPlanningCategories()`
- Liaison possible avec contrats actifs (`getContratsActifs`)

### À vérifier lors du dev
- [ ] Filtre `proprietaire_id` strict sur tous les endpoints AJAX
- [ ] CSRF token sur `calendarAjax` (POST/UPDATE/DELETE)
- [ ] Format JSON cohérent avec TUI Calendar (id, calendarId, title, start, end)

## Documents / GED propriétaire (à implémenter)
Controller à créer : `CoproprietaireDocumentController`
Vues à créer : `app/views/coproprietaires/documents/`
Table existante à utiliser/étendre : `documents`

### Concept
Espace de stockage personnel pour le propriétaire — il peut :
- Créer des **dossiers** (arborescence libre)
- Uploader **tous types de fichiers** : PDF, images (JPG, PNG, WEBP), vidéos (MP4, MOV), bureautique (DOCX, XLSX), archives (ZIP)
- Organiser ses documents (contrats, factures, photos de biens, vidéos d'inspection, AG, etc.)

### Limites de stockage
- **Quota par propriétaire : 1 GB** (1024 MB)
- **Taille max par fichier : 50 MB**
- Affichage du quota utilisé / disponible dans l'interface
- Upload bloqué si quota dépassé (vérification PHP + JS avant envoi)

### Types de fichiers autorisés (MIME whitelist)
```
Documents : application/pdf, application/msword, .docx, .xlsx, .ods, .odt
Images    : image/jpeg, image/png, image/webp, image/gif
Vidéos    : video/mp4, video/quicktime, video/webm
Archives  : application/zip
```
**Refuser** : exécutables (`.exe`, `.sh`, `.bat`), scripts (`.php`, `.js`, `.html`), tout ce qui n'est pas dans la whitelist.

### Structure stockage
```
uploads/coproprietaires/{user_id}/
  {dossier_id_ou_nom}/
    {timestamp_uuid}_{nom_original_sanitize}.{ext}
```
- Nom de fichier sanitizé (regex `/[^a-zA-Z0-9._-]/`)
- Préfixe `timestamp_uuid` pour éviter les collisions
- Nom original conservé en BDD pour affichage

### Schéma BDD à prévoir
```sql
-- Table dossiers
CREATE TABLE coproprietaire_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietaire_id INT NOT NULL,
    parent_id INT NULL, -- arborescence (NULL = racine)
    nom VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES coproprietaire_dossiers(id) ON DELETE CASCADE
);

-- Table fichiers (ou réutiliser/étendre `documents`)
CREATE TABLE coproprietaire_fichiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietaire_id INT NOT NULL,
    dossier_id INT NULL,
    nom_original VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100),
    taille_octets BIGINT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE,
    FOREIGN KEY (dossier_id) REFERENCES coproprietaire_dossiers(id) ON DELETE SET NULL
);
```

### À vérifier lors du dev
- [ ] Quota 1 GB vérifié AVANT et APRÈS upload (race conditions)
- [ ] MIME type vérifié côté serveur (jamais faire confiance au client)
- [ ] Extension de fichier validée + cohérente avec MIME
- [ ] Fichiers stockés HORS de `public/` (sécurité — accès via controller avec auth)
- [ ] Téléchargement via controller : `requireAuth()` + vérif `proprietaire_id == session`
- [ ] Suppression dossier → confirmation + suppression cascade des fichiers physiques
- [ ] Permissions Apache : `daemon:daemon` sur `uploads/coproprietaires/`
- [ ] CSRF sur tous les POST (upload, création dossier, suppression)
- [ ] Affichage : icône selon type de fichier (PDF, image, vidéo, etc.)
- [ ] Prévisualisation : images en lightbox, vidéos en lecteur HTML5, PDF iframe

## Assemblées Générales (espace propriétaire) ✅

Le propriétaire accède à ses AG en lecture seule via `/coproprietaire/assemblees`. Détails complets : voir @.claude/modules/ag.md

### Endpoints
| URL | Méthode | Description |
|-----|---------|-------------|
| `GET /coproprietaire/assemblees` | `assemblees()` | Liste AG des résidences avec contrats actifs |
| `GET /coproprietaire/assembleeShow/{id}` | `assembleeShow($id)` | Détail AG (OdJ, résolutions, PV, chantiers votés) |
| `GET /coproprietaire/assembleeDownload/{id}/{convocation\|pv}` | `assembleeDownload($id, $type)` | Téléchargement PDF avec ownership vérifié |

### Règles
- Seules les AG `convoquee`, `tenue`, `annulee` sont visibles (les `planifiee` en cours de préparation sont masquées)
- Filtrage strict via contrats actifs (`contrats_gestion.statut='actif'`) — ownership vérifié sur tous les endpoints
- Bandeau "Prochaine AG" sur dashboard `monEspace` si une AG `convoquee` est à venir
- Lien "AG" dans la navbar propriétaire (icône gavel, entre Calendrier et Mes Documents)
- Les AG apparaissent en **violet** dans le calendrier propriétaire (catégorie `ag`, click → fiche détail)

## Intégration Messagerie
Le propriétaire a accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible depuis l'espace propriétaire vers admin/direction
- [ ] Notifications messagerie visibles dans le dashboard propriétaire
- [x] Convocations AG envoyées via `/accueil/messageGroupe?ag_id=X` par admin/direction (voir @.claude/modules/ag.md § Convocation via messagerie)

## Checklist générale module Propriétaires
- [ ] Isolation des données : proprio ne voit que ses lots/contrats
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST
- [ ] Vérification unicité contrat actif avant création
- [ ] Calcul fiscal correct (revenus - charges - amortissements)
- [ ] Messages flash après chaque action
- [ ] DataTable sur listes contrats et lots