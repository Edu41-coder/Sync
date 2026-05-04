# Module Sinistres

## État : ✅ MVP + intégration Maintenance livrée

Couvre la déclaration et le suivi des sinistres (dégâts des eaux, incendie, vol, bris de glace, catastrophes naturelles, vandalisme, chutes résidents, pannes équipement). Intègre une GED dédiée pour constats, photos, expertises, courriers assureur, un audit trail complet, **et une intégration bidirectionnelle avec le module Maintenance Technique** (chantiers de réparation déclenchés par un sinistre).

> **Hors scope actuel** : indemnisations multiples (acomptes), intervenants pivot (assureur/expert/courtier en tables), alertes délais légaux, intégration comptabilité (écriture auto). Voir section "Évolutions futures" en fin de doc.

## Périmètre (fichiers)

- Controller : [app/controllers/SinistreController.php](../../app/controllers/SinistreController.php)
- Modèle : [app/models/Sinistre.php](../../app/models/Sinistre.php)
- Vues : [app/views/sinistres/](../../app/views/sinistres/) — `index.php`, `show.php`, `create.php`, `edit.php`, `_form_fields.php` (partial)
- Migration : [database/migrations/026_module_sinistres.sql](../../database/migrations/026_module_sinistres.sql)
- Stockage GED : `uploads/sinistres/{sinistre_id}/` (HORS `public/`, RGPD)
- Navbar : item « Sinistres » top-level dans [navbar.php](../../app/views/partials/navbar.php) pour tous les rôles concernés
- Dashboards : carte d'accès rapide ajoutée dans [resident/mon_espace.php](../../app/views/residents/mon_espace.php) et [coproprietaire/espace.php](../../app/views/coproprietaires/espace.php)

## Migrations appliquées

| # | Nom | Contenu |
|---|-----|---------|
| 026 | `026_module_sinistres.sql` | Drop legacy table `sinistres` (schéma 001 générique copro, jamais utilisée) + drop FK `documents.sinistre_id`. Création des 3 tables MVP avec FK + CHECK XOR sur le lieu. |
| 027 | `027_chantiers_sinistre_link.sql` | `ALTER TABLE chantiers ADD COLUMN sinistre_id INT NULLABLE` + FK `fk_chantiers_sinistre` ON DELETE SET NULL → matérialise la relation 1→N (un sinistre peut générer N chantiers de réparation). |

## Schéma BDD

### `sinistres` (table principale)

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | |
| `residence_id` | FK coproprietees | Toujours rattaché à une résidence |
| `lot_id` | FK lots NULLABLE | Sinistre sur un lot précis (mutuellement exclusif avec `lieu_partie_commune`) |
| `lieu_partie_commune` | ENUM 11 valeurs | parking/ascenseur/hall/couloir/cage_escalier/jardin/salle_commune/local_technique/toiture/facade/autre |
| `description_lieu` | VARCHAR(255) | Précision libre (ex: "ascenseur côté A") |
| `type_sinistre` | ENUM 9 valeurs | degat_eaux, incendie, vol_cambriolage, bris_glace, catastrophe_naturelle, vandalisme, chute_resident, panne_equipement, autre |
| `gravite` | ENUM | mineur, modere, majeur, catastrophe |
| `date_survenue` | DATETIME | Quand ça s'est passé |
| `date_constat` | DATETIME | Quand on l'a constaté |
| `date_declaration_assureur` | DATE | Date envoi déclaration (auto-renseignée au passage de statut `transmis_assureur`) |
| `date_cloture` | DATETIME | Auto-renseignée aux statuts `clos`/`refuse`/`indemnise` |
| `declarant_user_id` | FK users NULLABLE | ON DELETE SET NULL pour conserver l'historique |
| `titre`, `description` | VARCHAR(150), TEXT | |
| `assureur_nom`, `numero_contrat_assurance`, `numero_dossier_sinistre` | VARCHAR | Texte libre en MVP, table `assureurs` en V2 |
| `franchise`, `montant_estime`, `montant_indemnise` | DECIMAL(10,2) | |
| `date_indemnisation` | DATE | |
| `statut` | ENUM 7 valeurs | declare, transmis_assureur, expertise_en_cours, en_reparation, indemnise, clos, refuse |
| `notes` | TEXT | Notes internes (non visibles côté résident/proprio) |
| `created_at`, `updated_at` | DATETIME | |

**Contrainte critique** : `chk_sinistres_lieu_xor` — XOR entre `lot_id` et `lieu_partie_commune` (l'un ou l'autre, jamais les deux, jamais aucun).

### `sinistres_documents` (GED)

| Colonne | Description |
|---------|-------------|
| `id`, `sinistre_id` (CASCADE) | |
| `type_document` | ENUM : photo_avant, photo_apres, constat_amiable, devis, facture, rapport_expertise, courrier_assureur, autre |
| `nom_original`, `chemin_stockage` | Nom affiché + chemin relatif sous `uploads/` |
| `mime_type`, `taille_octets` | |
| `description` | Optionnelle |
| `uploaded_by` (SET NULL) | |

### `sinistres_log` (audit trail)

| Colonne | Description |
|---------|-------------|
| `id`, `sinistre_id` (CASCADE) | |
| `action` | ENUM : creation, changement_statut, update, indemnisation, cloture, document_ajoute, document_supprime |
| `statut_avant`, `statut_apres` | Capturés sur changement_statut |
| `details` | Texte libre |
| `user_id` (SET NULL) | |
| `created_at` | |

## Workflow

```
declare ──┬──> transmis_assureur ──> expertise_en_cours ──> en_reparation ──┬──> indemnise ──> clos
          │                                                                  │
          └──> refuse                                                        └──> clos (sans indemnisation)
```

Le passage à `transmis_assureur` enregistre automatiquement la `date_declaration_assureur`. Les statuts terminaux (`clos`, `refuse`, `indemnise`) renseignent automatiquement `date_cloture`.

## Règles de modification (figées par statut)

- **Modification du contenu** (titre, description, lieu, type, dates, infos assureur) : possible **uniquement tant que `statut = 'declare'`**, par `admin`/`directeur_residence`/`exploitant` (`Sinistre::ROLES_MANAGER`).
- **Une fois transmis à l'assureur**, le contenu est figé. Restent éditables : changements de statut, indemnisation, ajout/suppression de documents.
- **Un résident ne peut PAS modifier sa propre déclaration** une fois créée. Il peut uniquement ajouter des documents complémentaires.

## Permissions

| Rôle | Liste | Détail | Créer | Modifier | Statut/Indem. | Upload doc | Supprimer |
|------|-------|--------|-------|----------|---------------|------------|-----------|
| `admin` | tout | tout | ✅ | ✅ (si declare) | ✅ | ✅ | ✅ |
| `directeur_residence` | leurs résidences | leurs résidences | ✅ | ✅ (si declare) | ✅ | ✅ | ❌ |
| `exploitant` | leurs résidences | leurs résidences | ✅ | ✅ (si declare) | ✅ | ✅ | ❌ |
| `employe_residence` | leur résidence | leur résidence | ✅ | ❌ | ❌ | ✅ | ❌ |
| `technicien_chef` / `technicien` | leur résidence | leur résidence | ✅ | ❌ | ❌ | ✅ | ❌ |
| `locataire_permanent` | sinistres sur ses lots OU déclarés par lui | idem | ✅ (sur son lot uniquement, jamais en partie commune) | ❌ | ❌ | ✅ (ses sinistres) | ❌ |
| `proprietaire` | sinistres sur ses lots possédés | idem | ❌ | ❌ | ❌ | ❌ | ❌ |

> Le résident et le propriétaire **ne voient PAS les sinistres en parties communes** en MVP (visibilité limitée au manager). Évolution V2 si besoin business.

## Routes

| URL | Méthode | Rôles |
|-----|---------|-------|
| `GET /sinistre/index` | `index()` | tous (filtré rôle) |
| `GET /sinistre/show/{id}` | `show($id)` | tous (avec ownership check) |
| `GET /sinistre/create` | `create()` | déclarants (admin/direction/staff/résident) |
| `POST /sinistre/store` | `store()` | déclarants + CSRF |
| `GET /sinistre/edit/{id}` | `edit($id)` | manager + statut='declare' |
| `POST /sinistre/update/{id}` | `update($id)` | manager + statut='declare' + CSRF |
| `POST /sinistre/changeStatut/{id}` | `changeStatut($id)` | manager + CSRF |
| `POST /sinistre/saveIndemnisation/{id}` | `saveIndemnisation($id)` | manager + CSRF |
| `POST /sinistre/document/upload/{id}` | `document('upload', $id)` | sauf propriétaire + CSRF |
| `GET /sinistre/document/download/{docId}` | `document('download', $docId)` | tous accès lecture |
| `POST /sinistre/document/delete/{docId}` | `document('delete', $docId)` | manager OU uploader + CSRF |
| `POST /sinistre/delete/{id}` | `delete($id)` | admin uniquement + CSRF |

## Stockage fichiers

```
uploads/sinistres/{sinistre_id}/{timestamp}_{uuid8}_{nom_sanitize}.{ext}
```

- **Hors `public/`** : accès uniquement via controller authentifié + ownership check (`userCanAccess`)
- **Whitelist MIME** : PDF, JPG/PNG/WEBP/GIF, MP4/MOV, DOC/DOCX, XLS/XLSX
- **Whitelist extensions** : pdf, jpg, jpeg, png, webp, gif, mp4, mov, doc, docx, xls, xlsx
- **Triple validation** : taille (max 50 MB) → extension → MIME réel via `finfo` (anti-polyglot)
- **Streaming download** via `readfile()` avec headers `Content-Disposition: attachment` et `Cache-Control: no-cache`

## Méthodes modèle principales

`app/models/Sinistre.php` :

- **Accès / ownership** :
  - `getResidenceIdsAccessibles($userId, $role)` — résidences visibles selon rôle
  - `getLotIdsAccessibles($userId, $role)` — lots accessibles (résident: ses occupations actives, proprio: ses contrats actifs)
  - `userCanAccess($sinistreId, $userId, $role)` — vérif lecture
  - `userCanEdit($sinistreId, $role)` — vérif modification (manager + statut='declare')

- **CRUD** :
  - `getList($userId, $role, $filters)` — listing filtré avec recherche/tri
  - `findWithDetails($id)` — détail enrichi (résidence, lot, déclarant)
  - `createSinistre($data, $declarantUserId)` — création + log auto
  - `updateSinistre($id, $data, $userId)` — refuse si statut != 'declare'
  - `changeStatut($id, $newStatut, $userId, $details)` — transitions + auto-renseigne dates
  - `saveIndemnisation($id, $montant, $date, $userId)`
  - `deleteSinistre($id)` — CASCADE sur documents et logs

- **Audit & GED** :
  - `getHistory($sinistreId)` — timeline d'événements
  - `getDocuments($sinistreId)`, `findDocument($docId)`, `addDocument()`, `deleteDocument()`

- **Helpers vues** :
  - `getResidencesPourFormulaire($userId, $role)` — pour le `<select>`
  - `getLotsGroupesParResidence($userId, $role)` — pour le `<select>` dynamique JS du formulaire
  - `getDashboardStats($userId, $role)` — KPI : total, en_cours, clos, montants estimé/indemnisé

## Sécurité (audit checklist)

- [x] `requireAuth + requireRole` sur toutes les méthodes publiques (vérifié par `tests/test_require_auth.php`)
- [x] `requirePostCsrf` sur toutes les actions destructives (delete, upload, change status, etc.)
- [x] Filtrage strict par rôle dans le modèle (`getResidenceIdsAccessibles`)
- [x] Ownership check à 2 niveaux : résidences + lots (pour résident/propriétaire)
- [x] Constraint XOR DB-level pour empêcher données incohérentes
- [x] Triple validation upload (extension + finfo MIME + taille)
- [x] Stockage GED hors `public/` + streaming via controller
- [x] `htmlspecialchars()` systématique sur toutes les données affichées
- [x] Audit trail immutable (table append-only, jamais d'UPDATE)
- [x] FK `ON DELETE CASCADE` sur sinistres → documents + logs (cohérence)
- [x] FK `ON DELETE SET NULL` sur user → declarant_user_id, uploaded_by, log.user_id (préserve l'historique)

## Tests recommandés

### Permissions
- [ ] `locataire_permanent` peut créer sur son lot, mais redirect erreur sur partie commune ou sur lot d'un autre résident
- [ ] `locataire_permanent` voit uniquement les sinistres sur ses lots (ou qu'il a déclarés)
- [ ] `proprietaire` voit uniquement les sinistres sur ses lots possédés (contrat actif)
- [ ] `proprietaire` ne voit PAS les sinistres en partie commune
- [ ] `technicien` peut déclarer mais pas modifier ni changer statut
- [ ] `admin` peut tout, `directeur_residence` ne voit que ses résidences

### Workflow
- [ ] Création → statut `declare` automatique + log `creation`
- [ ] Modification autorisée au statut `declare`, refusée après transmission
- [ ] Passage `transmis_assureur` → `date_declaration_assureur` auto-renseignée
- [ ] Passage `clos`/`refuse`/`indemnise` → `date_cloture` auto-renseignée
- [ ] Saisie indemnisation → `montant_indemnise` + `date_indemnisation` + log `indemnisation`

### Contraintes
- [ ] Tentative INSERT avec `lot_id` ET `lieu_partie_commune` → rejetée (XOR)
- [ ] Tentative INSERT sans `lot_id` ni `lieu_partie_commune` → rejetée (XOR)
- [ ] Suppression sinistre → CASCADE supprime documents + logs + dossier physique
- [ ] Suppression user déclarant → `declarant_user_id` passe à NULL, sinistre conservé

### GED
- [ ] Upload PDF → 200, écriture en base + fichier physique présent
- [ ] Upload .exe → rejeté avec flash erreur (extension non whitelisted)
- [ ] Upload polyglot (image avec MIME PDF) → rejeté par finfo
- [ ] Upload > 50 MB → rejeté
- [ ] Download par utilisateur sans accès → 403
- [ ] Suppression doc par non-uploader non-manager → bloquée

## Intégration avec module Maintenance Technique (chantiers)

Un sinistre peut déclencher 0, 1 ou N chantiers de réparation. La relation est matérialisée par la FK `chantiers.sinistre_id` (NULLABLE, ON DELETE SET NULL).

### Boucle complète couverte
```
Déclaration sinistre
   ↓
Transmission assureur
   ↓
Expertise
   ↓ (manager clique "Créer un chantier de réparation")
Chantier maintenance pré-rempli (titre, description, résidence, montant_estime)
   ↓ (workflow chantier : devis → décision → commande → exécution)
Réception chantier + paiement
   ↓
Saisie indemnisation côté sinistre
   ↓
Bilan financier visible : Indemnisation reçue vs Coût réel chantiers
```

### Points d'intégration UI

**Sur la fiche sinistre** ([app/views/sinistres/show.php](../../app/views/sinistres/show.php)) :
- Section « Chantiers de réparation » avec liste des chantiers liés (titre, spécialité, phase, statut, montants engagé/payé)
- Bouton « Créer un chantier de réparation » (manager only) → redirige vers `/chantier/form?sinistre_id={id}` avec pré-remplissage automatique
- **Bilan financier** : affiche indemnisation vs coût total payé → excédent ou déficit
- Tfoot avec totaux engagé/payé sur tous les chantiers liés

**Sur la fiche chantier** ([app/views/chantiers/show.php](../../app/views/chantiers/show.php)) :
- Badge rouge `🛡 Sinistre #X` à côté du titre, cliquable vers la fiche sinistre

**Sur le formulaire chantier** ([app/views/chantiers/form.php](../../app/views/chantiers/form.php)) :
- Bandeau d'avertissement « Chantier issu d'un sinistre » avec lien retour
- Pré-remplissage : titre = "Réparation : {titre sinistre}", description includes infos sinistre, residence_id verrouillé sur la résidence du sinistre, montant_estime initialisé depuis le sinistre
- Hidden input `sinistre_id` persiste le lien à la création
- En mode édition : bandeau bleu d'info si chantier déjà lié à un sinistre

**Sur la liste des chantiers** ([app/views/chantiers/index.php](../../app/views/chantiers/index.php)) :
- Nouvelle colonne « Origine » : badge rouge si chantier issu d'un sinistre (cliquable), `Maintenance` sinon

### Méthodes modèle ajoutées

`Chantier::getChantiersBySinistre(int $sinistreId): array` — liste des chantiers liés à un sinistre, avec spécialité jointe.

`Sinistre::getChantiersLies(int $sinistreId): array` — proxy DRY délégant à `Chantier::getChantiersBySinistre()`.

`Chantier::findChantier()` et `Chantier::getChantiers()` enrichis : LEFT JOIN sur `sinistres` pour ramener `sinistre_titre`, `sinistre_type`, `sinistre_id_lie` (alias pour distinguer du `sinistre_id` direct).

`Chantier::createChantier()` : accepte et persiste `data['sinistre_id']`. `updateChantier()` ne touche PAS `sinistre_id` (préserve la valeur, évite les écrasements accidentels par formulaire incomplet).

### Contrôle d'accès

Le bouton « Créer un chantier de réparation » est conditionné à `$canManage` côté sinistre. Côté chantier, le pré-remplissage `?sinistre_id=X` vérifie `Sinistre::userCanAccess()` avant d'injecter quoi que ce soit dans le formulaire — un user n'ayant pas accès au sinistre n'obtient simplement pas le pré-remplissage.

## Évolutions futures (hors scope actuel)

| Feature | Effort | Priorité |
|---------|--------|----------|
| Indemnisations multiples (table `sinistres_indemnisations` avec acomptes) | 1 j | 🟡 moyenne |
| Tables `assureurs` + `experts` + `courtiers` (intervenants pivots réutilisables) | 1.5 j | 🟡 moyenne |
| Alertes délais légaux (bandeau rouge dashboard) | 0.5 j | 🟡 moyenne |
| Intégration compta : écriture auto `recette` lors saisie indemnisation | 0.5 j | 🟢 basse |
| Export PDF déclaration (annexes assureur) | 1 j | 🟢 basse |
| Statistiques (saison, type, résidence) + détection patterns récurrents | 1.5 j | 🟢 basse |

## Intégration Messagerie

Tous les rôles ayant accès aux sinistres ont accès à la messagerie (voir [messagerie.md](messagerie.md)). Les notifications de changement de statut → bénéficiaires (déclarant + propriétaire concerné) sont prévues en V2.

## Checklist générale module Sinistres

- [x] **Toutes les listes** ont tri colonnes + recherche + pagination (`DataTableWithPagination` sur `index.php`)
- [x] CSRF sur tous les POST (store, update, changeStatut, saveIndemnisation, upload, delete, document delete)
- [x] Filtrage par résidence + lot selon rôle
- [x] `htmlspecialchars()` sur toutes les données affichées (titre, description, notes, n° dossier...)
- [x] DataTable sur la liste (15 lignes/page, exclu colonne actions du tri)
- [x] Modal upload + indemnisation + changement statut
- [x] Audit trail consultable depuis la fiche détail (timeline)
- [x] Test `tests/test_require_auth.php` passe (0 violation)
