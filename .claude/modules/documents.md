# Module Documents (GED admin/staff direction)

## État : ✅ MVP livré

GED unifiée pour la direction Domitys avec deux périmètres distincts :

- **Global Domitys** (HQ) — modèles, contrats cadre, RGPD, modèles de baux, charte graphique…
- **Par résidence** — AG, factures archivées, sinistres clos, photos résidence, documents légaux locaux…

> ⚠️ Distinct des autres GED du projet :
> - [`coproprietaire_*`](proprietaires.md) → GED **personnelle** propriétaire (1 espace par user, 1 GB)
> - [`resident_*`](residents.md) → GED **personnelle** résident senior (1 espace par user, 500 MB)
> - `admin_*` (ce module) → GED **organisationnelle** Domitys (partagée par scope, pas de quota)
> - [`sinistres_documents`](sinistres.md), `chantier_documents`, etc. → GED **liée à une entité métier** spécifique

## Périmètre (fichiers)

- Controller : [app/controllers/DocumentController.php](../../app/controllers/DocumentController.php)
- Modèle : [app/models/AdminDocument.php](../../app/models/AdminDocument.php)
- Vue : [app/views/documents/index.php](../../app/views/documents/index.php) (onglets Global / Par résidence)
- Stockage :
  - `uploads/admin/global/{dossier_id|racine}/...` (HORS `public/`)
  - `uploads/admin/residences/{residence_id}/{dossier_id|racine}/...`
- Navbar : item « Documents » top-level dans [navbar.php](../../app/views/partials/navbar.php) pour les rôles de pilotage

## Migration appliquée

| # | Nom | Contenu |
|---|-----|---------|
| 029 | `029_module_documents_admin.sql` | Tables `admin_dossiers` + `admin_fichiers` avec `residence_id` NULLABLE (NULL = global), FK CASCADE sur résidence/parent, FK SET NULL sur uploader/dossier_id |

## Schéma BDD

### `admin_dossiers`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | |
| `residence_id` | INT NULLABLE | NULL = scope global, FK `coproprietees.id` ON DELETE CASCADE |
| `parent_id` | INT NULLABLE | NULL = racine du scope, FK self ON DELETE CASCADE (arborescence libre) |
| `nom` | VARCHAR(255) | |
| `created_by` | FK users SET NULL | |
| `created_at`, `updated_at` | DATETIME | |

### `admin_fichiers`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | |
| `residence_id` | INT NULLABLE | NULL = scope global |
| `dossier_id` | FK `admin_dossiers` SET NULL | NULL = racine du scope |
| `nom_original` | VARCHAR(255) | |
| `chemin_stockage` | VARCHAR(512) | Chemin relatif sous `uploads/` |
| `mime_type`, `taille_octets` | | |
| `description` | VARCHAR(500) | Optionnel |
| `uploaded_by` | FK users SET NULL | |
| `uploaded_at` | DATETIME | |

> **Comportement clé** : la FK `dossier_id` est `ON DELETE SET NULL` → si on supprime un dossier en cascade via `deleteDossierCascade()`, les fichiers contenus sont déplacés à la racine du scope (puis supprimés explicitement par le code Python avant `DELETE` du dossier racine).

## Permissions

| Rôle | Global | Par résidence | Notes |
|------|--------|---------------|-------|
| `admin` | R/W | R/W (toutes) | Accès total |
| `directeur_residence` | **Lecture seule** | R/W (ses résidences via `user_residence`) | Voit le global Domitys, écrit sur ses résidences |
| `exploitant` | **Lecture seule** | R/W (ses résidences via `exploitant_residences`) | Idem directeur |
| `comptable` | **Lecture seule** | **Lecture seule** (ses résidences via `user_residence`) | Lecture stricte |
| Autres rôles | ❌ | ❌ | Aucun accès |

Logique appliquée par `AdminDocument::canRead()` et `AdminDocument::canWrite()`. Le filtrage des résidences accessibles passe par `getResidencesForUser()` qui rejoint sur `user_residence` (admin/dir/comptable) ou `exploitant_residences` (exploitant).

## Routes

| URL | Méthode | Rôles |
|-----|---------|-------|
| `GET /document/index?scope=global\|residence[&residence_id=Y][&dossier=X]` | `index()` | `ROLES_LECTURE` (admin, directeur, exploitant, comptable) |
| `POST /document/createDossier?scope=...` | `createDossier()` | `canWrite` |
| `POST /document/renameDossier/{id}` | `renameDossier($id)` | `canWrite` (déduit du dossier) |
| `POST /document/deleteDossier/{id}` | `deleteDossier($id)` | `canWrite` (déduit du dossier) — CASCADE |
| `POST /document/upload?scope=...` | `upload()` | `canWrite` |
| `GET /document/download/{id}` | `download($id)` | `canRead` (déduit du fichier) |
| `GET /document/preview/{id}` | `preview($id)` | `canRead` — `Content-Disposition: inline` (images/vidéos/PDF) |
| `POST /document/deleteFichier/{id}` | `deleteFichier($id)` | `canWrite` (déduit du fichier) |

## UX (vue index)

- **Onglets** en haut : « Documents globaux Domitys » et « Par résidence » (dropdown avec liste)
- **4 KPI** : nb dossiers / nb fichiers / taille totale / périmètre courant
- **Breadcrumb arborescence** quand on est dans un sous-dossier (avec lien retour racine)
- **Cartes dossiers** : icône, nom, compteurs sous-dossiers/fichiers + boutons renommer/supprimer (si `canWrite`)
- **Tableau fichiers** : icône typée par MIME, description, taille humaine, uploader, date, actions (preview si image/vidéo/PDF / download / delete)
- **Modales Bootstrap** : créer dossier · renommer dossier · upload fichier
- **DataTable** + pagination si > 10 fichiers
- **Bandeau « Lecture seule »** affiché si l'utilisateur n'a pas `canWrite` sur le scope courant

## Sécurité

- ✅ `requireAuth + requireRole(ROLES_LECTURE)` sur toutes les méthodes
- ✅ `requirePostCsrf` sur toutes les actions destructives
- ✅ Validation triple à l'upload : taille (max 50 MB) → extension (whitelist 18) → MIME réel via `finfo` (anti-polyglot)
- ✅ Stockage HORS `public/` — accès uniquement via `download()`/`preview()` qui vérifient `canRead`
- ✅ Vérif scope/dossier cohérent : un dossier de la résidence X ne peut pas être consulté depuis le scope global ou résidence Y
- ✅ Suppression cascade explicite : `deleteDossierCascade()` rassemble tous les `chemin_stockage` AVANT le `DELETE`, le controller efface ensuite les fichiers physiques (sinon FK CASCADE perdrait la trace)

## Limites & quotas

- **Pas de quota global** en MVP (la direction est responsable du remplissage)
- **50 MB par fichier** (constante `AdminDocument::TAILLE_MAX_FICHIER`)
- **18 extensions whitelistées** : pdf, doc/docx, xls/xlsx, odt/ods, jpg/jpeg/png/webp/gif, mp4/mov/webm, zip, csv, txt
- Quota par résidence à ajouter en V2 si volume devient un problème

## Tests recommandés

### Permissions
- [ ] `admin` connecté → onglets Global + toutes résidences disponibles, R/W partout
- [ ] `directeur_residence` connecté → onglet Global en lecture seule (bandeau visible), onglet « Par résidence » montre uniquement ses résidences (R/W)
- [ ] `exploitant` connecté → idem directeur mais via `exploitant_residences`
- [ ] `comptable` connecté → onglet Global en lecture seule + ses résidences en lecture seule (pas de boutons créer dossier / upload)
- [ ] `proprietaire`, `locataire_permanent`, `technicien` → 403 sur `/document/index`

### Fonctionnel
- [ ] Création dossier en racine → apparait dans la liste
- [ ] Création sous-dossier dans un dossier existant → breadcrumb fonctionne
- [ ] Upload PDF → fichier visible + téléchargement OK
- [ ] Upload .exe → rejeté
- [ ] Upload polyglot (image renommée .pdf) → rejeté par finfo
- [ ] Upload > 50 MB → rejeté
- [ ] Renommage dossier avec caractère interdit (`/` ou `\`) → rejeté
- [ ] Suppression dossier avec sous-dossiers/fichiers → CASCADE OK + fichiers physiques supprimés
- [ ] Preview d'une image → ouverture inline dans nouvel onglet
- [ ] Preview d'un PDF → ouverture inline
- [ ] Download d'un fichier → header `Content-Disposition: attachment`
- [ ] Tentative download par utilisateur sans accès au scope → 403

### Étanchéité scope
- [ ] Accès `/document/index?scope=residence&residence_id=X&dossier=Y` où Y appartient à un autre scope → flash erreur + redirect vers la racine du scope
- [ ] Tentative POST `dossier_id` d'un autre scope dans l'upload → rejeté

## Évolutions V2 (hors MVP)

| Feature | Effort | Priorité |
|---------|--------|----------|
| Quota par résidence + global Domitys | 0.5 j | 🟡 si volume devient un problème |
| Tags / catégories sur les fichiers | 0.5 j | 🟢 |
| Recherche full-text (sujet + nom fichier + description) | 1 j | 🟡 utile à fort volume |
| Versionnage : conserver les versions précédentes d'un fichier | 1.5 j | 🟢 si demande client |
| Partage temporaire (lien signé expirant) avec un tiers (notaire, expert) | 1 j | 🟢 |
| Audit trail (qui a téléchargé quoi quand) — RGPD-friendly | 0.5 j | 🟡 |
