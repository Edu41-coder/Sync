# Module Assemblées Générales

## État : ✅ 100 % livré (4 phases)

Module de gestion des AG de copropriété (AGO/AGE) couvrant : workflow complet (planifiée → convoquée → tenue / annulée), résolutions avec votes pondérés (voix + tantièmes), upload convocation/PV, intégration chantiers (`chantiers.ag_id`), espace propriétaire en lecture, convocation par messagerie groupée pré-remplie, AG dans le calendrier propriétaire.

## Périmètre (fichiers)

- Migrations :
  - [028_assemblees_generales.sql](../../database/migrations/028_assemblees_generales.sql) — extensions BDD (convocation, quorum, mode, président/secrétaire)
  - [029_planning_proprio_categorie_ag.sql](../../database/migrations/029_planning_proprio_categorie_ag.sql) — catégorie `ag` calendrier propriétaire (violet)
- Modèle : [app/models/Assemblee.php](../../app/models/Assemblee.php) — 21 méthodes (CRUD AG + workflow + résolutions + intégrations + accès propriétaire)
- Contrôleurs :
  - [app/controllers/AssembleeController.php](../../app/controllers/AssembleeController.php) — gestion (admin/direction/comptable)
  - [app/controllers/CoproprietaireController.php](../../app/controllers/CoproprietaireController.php) — `assemblees()` / `assembleeShow()` / `assembleeDownload()` + bandeau dashboard
  - [app/controllers/AccueilController.php](../../app/controllers/AccueilController.php) — `messageGroupe()` étendu pour pré-remplissage AG
- Vues :
  - [app/views/assemblees/](../../app/views/assemblees/) — `index`, `form`, `show`, `resolution_form` (admin)
  - [app/views/coproprietaires/assemblees.php](../../app/views/coproprietaires/assemblees.php), [assemblee_show.php](../../app/views/coproprietaires/assemblee_show.php) (propriétaire lecture)
  - [app/views/accueil/message_groupe.php](../../app/views/accueil/message_groupe.php) — pré-remplissage convocation
  - [app/views/coproprietaires/calendrier.php](../../app/views/coproprietaires/calendrier.php) — AG injectées + clic redirige vers fiche
- Stockage : `uploads/ag/{ag_id}/` (privé, hors `public/`, accès via controller authentifié)

## Architecture BDD

Tables `assemblees_generales` et `votes_ag` existaient déjà mais étaient inutilisées. La migration 028 ajoute les colonnes manquantes :

### `assemblees_generales` (étendue migration 028)

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT PK | |
| `copropriete_id` | FK coproprietees | Résidence |
| `type` | ENUM('ordinaire','extraordinaire') | AGO ou AGE |
| `date_ag` | DATETIME | Date + heure de séance |
| `lieu` | VARCHAR(255) | Salon/lien visio/etc. |
| **`mode`** | ENUM('presentiel','visio','mixte') | (028) |
| `ordre_du_jour` | TEXT | OdJ détaillé |
| `proces_verbal` | TEXT | PV texte |
| **`notes_internes`** | TEXT | (028) Notes privées non visibles propriétaires |
| **`created_by`** | FK users | (028) |
| `document_convocation` | VARCHAR(512) | Chemin relatif `{ag_id}/convocation_*.pdf` |
| **`convocation_envoyee_le`** | DATETIME | (028) Date passage `planifiee→convoquee` |
| `document_pv` | VARCHAR(512) | Chemin relatif `{ag_id}/pv_*.pdf` |
| `statut` | ENUM('planifiee','convoquee','tenue','annulee') | Workflow |
| `quorum_atteint` | TINYINT | 1 si délibérations valides |
| **`quorum_requis`** | INT | (028) En tantièmes ou voix |
| **`quorum_present`** | INT | (028) |
| **`votants_total`** | INT | (028) Présents + représentés |
| **`president_seance_id`** | FK users | (028) |
| **`secretaire_id`** | FK users | (028) |
| `created_at`, `updated_at` | TIMESTAMP | |

Index : `idx_residence_statut`, `idx_date_ag`. FK ON DELETE SET NULL pour les 3 user FK.

### `votes_ag` (existante, non modifiée)

| Colonne | Type |
|---------|------|
| `ag_id` | FK assemblees_generales |
| `resolution` | VARCHAR(255) |
| `description` | TEXT |
| `ordre` | INT |
| `votes_pour`, `votes_contre`, `abstentions` | INT |
| `tantiemes_pour`, `tantiemes_contre` | INT |
| `resultat` | ENUM('adopte','rejete','reporte') NOT NULL |

⚠️ `resultat` est NOT NULL sans default — calcul auto par le modèle (fallback `reporte` si pas de votes).

### Liaison chantier (existante)
- `chantiers.necessite_ag` (TINYINT) auto-coché si `montant_estime > 5000 €`
- `chantiers.ag_id` (FK) — rattachement chantier → AG ayant voté la résolution travaux

### Catégorie calendrier (migration 029)
`planning_proprio_categories.slug='ag'` (id=7, couleur `#6610f2` violet, icône `fas fa-gavel`, `auto_genere=1`)

## Permissions

| Rôle | Lecture admin (/assemblee/*) | Gestion (CRUD + workflow) | Espace proprio (/coproprietaire/assemblees) |
|------|:---:|:---:|:---:|
| `admin` | ✅ | ✅ | — |
| `directeur_residence` | ✅ | ✅ | — |
| `exploitant` | ✅ | ✅ | — |
| `comptable` | ✅ (lecture seule) | ❌ | — |
| `proprietaire` | — | — | ✅ (lecture, AG `convoquee`/`tenue`/`annulee` uniquement) |

### Constantes contrôleur
```php
// AssembleeController
private const ROLES_LECTURE = ['admin', 'directeur_residence', 'exploitant', 'comptable'];
private const ROLES_GESTION = ['admin', 'directeur_residence', 'exploitant'];
private const UPLOAD_DIR    = '../uploads/ag/';
```

### Filtrage strict
- Admin voit toutes les résidences ; les autres rôles voient seulement leurs résidences via `user_residence` (jointure `coproprietees → user_residence`)
- Propriétaire voit seulement les résidences où il a au moins un `contrats_gestion.statut='actif'`
- Les AG `planifiee` (en cours de préparation) sont **invisibles aux propriétaires** — apparaissent dès `convoquee`

## Workflow statuts

```
planifiee ──┬─→ convoquee ──┬─→ tenue       (séance terminée + PV + votes)
            │               └─→ annulee
            └─→ annulee
```

| Action | Bouton/endpoint | Effet | Rôle |
|--------|----------------|-------|------|
| Créer | Modal `Nouvelle AG` | Statut = `planifiee` | GESTION |
| Convoquer (changer statut) | Modal `convoquerModal` + upload PDF | `→ convoquee`, set `convocation_envoyee_le=NOW()` | GESTION |
| **Envoyer convocation par messagerie** | Bouton orange `/accueil/messageGroupe?ag_id=X` | Pré-rempli (proprios + sujet + corps + priorité haute) | accueil_manager + GESTION |
| Marquer tenue | Modal `tenirModal` | `→ tenue`, saisie PV/quorum/président/secrétaire + upload PV | GESTION |
| Annuler | Bouton sidebar | `→ annulee` | GESTION |
| Supprimer définitivement | Bouton sidebar | DELETE + suppression dossier `uploads/ag/{id}/` | GESTION |

## Routes implémentées

### Côté gestion (`AssembleeController`)

| URL | Méthode | Rôles |
|-----|---------|-------|
| `GET /assemblee/index?residence_id=N&statut=X&type=Y&annee=Z` | `index()` | LECTURE |
| `GET /assemblee/show/{id}` | `show($id)` | LECTURE |
| `GET\|POST /assemblee/form/{id?}` | `form($id?)` | GESTION |
| `POST /assemblee/delete/{id}` | `delete($id)` | GESTION |
| `POST /assemblee/convoquer/{id}` | `convoquer($id)` | GESTION |
| `POST /assemblee/tenir/{id}` | `tenir($id)` | GESTION |
| `POST /assemblee/annuler/{id}` | `annuler($id)` | GESTION |
| `GET /assemblee/download/{id}/{convocation\|pv}` | `download($id, $type)` | LECTURE |
| `GET\|POST /assemblee/resolutionForm/{agId}/{id?}` | `resolutionForm($agId, $id?)` | GESTION |
| `POST /assemblee/resolutionDelete/{id}` | `resolutionDelete($id)` | GESTION |

### Côté propriétaire (`CoproprietaireController`)

| URL | Méthode | Rôle |
|-----|---------|------|
| `GET /coproprietaire/assemblees?statut=X&residence_id=N` | `assemblees()` | proprietaire |
| `GET /coproprietaire/assembleeShow/{id}` | `assembleeShow($id)` | proprietaire |
| `GET /coproprietaire/assembleeDownload/{id}/{type}` | `assembleeDownload($id, $type)` | proprietaire (ownership vérifié) |

### Intégrations

| URL | Effet |
|-----|-------|
| `GET /accueil/messageGroupe?ag_id=X` | Pré-rempli pour convocation : tab Propriétaires actif, tous cochés, sujet/corps types, priorité haute |

## Calcul automatique du résultat de vote

```php
// Assemblee::calculerResultat() — règle simple :
if (tantiemes_pour > 0 || tantiemes_contre > 0) {
    return tantiemes_pour > tantiemes_contre ? 'adopte' : 'rejete';
}
return votes_pour > votes_contre ? 'adopte' : 'rejete';
```

- Si l'admin force `resultat` (sélecteur du formulaire) → valeur conservée
- Sinon : calcul auto si au moins 1 vote/tantième saisi
- **Fallback `reporte`** si aucun vote (NOT NULL sur la colonne)

## Stockage uploads

```
uploads/ag/{ag_id}/
  ├── convocation_<timestamp>_<rand>_<nom>.{pdf|jpg|png}
  └── pv_<timestamp>_<rand>_<nom>.{pdf|jpg|png}
```

- Validation triple : extension whitelist (`pdf`, `jpg`, `jpeg`, `png`) → MIME `finfo` (anti-polyglot) → 10 Mo max
- Suppression cascade : `delete($id)` supprime le dossier entier
- Téléchargement via controller authentifié uniquement (jamais en accès direct via URL)
- Endpoint `download` distinct côté admin (`/assemblee/download/`) et propriétaire (`/coproprietaire/assembleeDownload/`) avec ownership check spécifique à chaque rôle

## Intégration chantiers

- `Chantier.necessite_ag` est auto-coché à la création si `montant_estime > 5000 € HT`
- L'admin peut lier un chantier à une AG via `chantier.ag_id` (champ existant)
- La fiche AG affiche dans la sidebar :
  - **Chantiers liés** : ceux dont `ag_id = current_ag_id`
  - **Chantiers en attente** : ceux de la résidence avec `necessite_ag=1` ET `ag_id IS NULL` (alerte orange — à rattacher à une prochaine AG)
- Côté propriétaire : la fiche AG montre les chantiers votés avec `montant_estime` + `montant_engage`

## Convocation via messagerie groupée (Phase 4)

`/accueil/messageGroupe?ag_id=X` :
1. Vérifie que l'AG appartient à une résidence accessible
2. Force `residence_id` à celle de l'AG
3. Pré-remplit :
   - **Sujet** : `Convocation Assemblée Générale [type] du [date]`
   - **Contenu** : modèle de message complet avec OdJ + invitation à télécharger la convocation depuis l'espace propriétaire
   - **Priorité** : `haute` (au lieu de `normale`)
   - **Tab actif** : Propriétaires (au lieu de Résidents)
   - **Cases cochées** : tous les propriétaires de la résidence
4. Bandeau d'alerte bleu en haut de page + breadcrumb adapté avec retour vers fiche AG
5. À la soumission → réutilise `MessageController::send()` qui crée 1 message par destinataire (pattern existant)

⚠️ Le bouton "Convoquer (changer statut)" reste distinct de "Envoyer convocation par messagerie" :
- Le premier change le statut + enregistre date d'envoi + upload PDF officiel
- Le second envoie effectivement les notifications aux propriétaires
- L'ordre recommandé : changer statut → puis envoyer messagerie

## AG dans calendrier propriétaire (Phase 4)

- Catégorie `ag` (violet) ajoutée à `planning_proprio_categories` via migration 029
- `CoproprietaireController::calGetEvents()` boucle sur `Assemblee::getAGsProprietaire()` et injecte les AG dans la période affichée :
  - Title : `🏛️ AGE — Domitys La Badiane (📨 Convoquée)`
  - Description : lieu + mode
  - Durée : 2h par défaut (ajustable selon `date_ag`)
  - `auto_genere=1` → lecture seule (pas de drag & drop, pas d'édition)
- Click sur l'événement → redirige vers `/coproprietaire/assembleeShow/{id}` (handler JS `clickSchedule` étendu pour exploiter `raw.agId`)

## Dashboard propriétaire

Bandeau bleu sur `monEspace` si une AG `convoquee` est à venir :
```
🏛️ Prochaine AG : AGE le 15/07/2027 à 18h00 — Domitys La Badiane     [Voir convocation]
```
Donné par `Assemblee::getStatsProprietaire($proprioId)` qui retourne `['a_venir' => N, 'prochaine' => {...}]`.

## Tests recommandés

### Permissions / sécurité
- [ ] `comptable` → 200 sur `/assemblee/index`, redirect avec flash sur `/assemblee/form`
- [ ] `directeur_residence` non affecté à la résidence X → ne voit pas les AG de X
- [ ] `proprietaire1` → 200 sur `/coproprietaire/assemblees`, ne voit que les AG des résidences avec contrats actifs
- [ ] AG `planifiee` → invisible côté propriétaire ; `convoquee` → visible
- [ ] Tentative `/coproprietaire/assembleeShow/{ag_d_une_autre_residence}` → redirect avec flash error

### Workflow
- [ ] Créer AG `planifiee` → modifier OdJ → convoquer (avec PDF) → vérifier `convocation_envoyee_le` rempli
- [ ] Tenir AG → quorum atteint coché + président saisi + PV uploadé → vérifier statut `tenue`
- [ ] Annuler une AG `convoquee` → statut `annulee` (visible propriétaire en rouge)
- [ ] Supprimer AG → dossier `uploads/ag/{id}/` retiré

### Résolutions
- [ ] Créer résolution avec tantièmes 750/100 → résultat auto = `adopte`
- [ ] Créer résolution avec tantièmes 100/750 → résultat auto = `rejete`
- [ ] Créer résolution sans aucun vote → résultat auto = `reporte` (fallback)
- [ ] Forcer manuellement résultat = `reporte` malgré majorité pour → respecté

### Intégration messagerie
- [ ] Cliquer "Envoyer convocation par messagerie" sur AG `convoquee` → arrive sur `/accueil/messageGroupe?ag_id=X` avec tab Propriétaires actif + cases cochées + sujet/contenu pré-remplis
- [ ] Filtrer la recherche dans messagerie → "Tout" ne sélectionne que visibles (existant)
- [ ] Soumettre → 1 message individuel par propriétaire dans `messages_destinataires`

### Intégration calendrier propriétaire
- [ ] AG `convoquee` apparaît en violet dans `/coproprietaire/calendrier`
- [ ] Click sur l'événement AG → redirige vers `/coproprietaire/assembleeShow/{id}`
- [ ] Tentative drag & drop sur AG → bloqué (`isReadOnly` car `auto_genere=1`)
- [ ] AG hors période affichée → masquée (filtre temporel `$startDate / $endDate`)

### Intégration chantiers
- [ ] Chantier > 5000 € `necessite_ag=1` sans `ag_id` → apparaît dans alerte orange "Chantiers en attente d'AG" sur fiche AG résidence
- [ ] Lier `chantier.ag_id = X` → apparaît dans "Chantiers liés" sur fiche AG côté admin ET côté propriétaire

## Améliorations éventuelles

- [ ] Génération PDF convocation/PV automatique depuis le modèle BDD (mPDF/Dompdf)
- [ ] Notification email externe (mailto:) en complément de la messagerie interne
- [ ] Workflow vote en ligne (chaque propriétaire vote depuis son espace, calcul résultat auto à clôture)
- [ ] Export Excel de toutes les résolutions adoptées sur l'année
- [ ] Procuration : un propriétaire peut donner pouvoir à un autre pour une AG donnée
- [ ] Lien depuis fiche chantier vers AG associée (actuellement seulement AG → chantiers)
