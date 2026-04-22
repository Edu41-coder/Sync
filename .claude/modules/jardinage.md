# Module Jardinage

## État : ✅ 100 % livré

Toutes les phases sont terminées et testées. Le module couvre :
- Gestion des espaces jardin personnalisables par résidence (+ photos, tâches récurrentes)
- Catalogue produits & outils + inventaire avec traçabilité complète (+ photos)
- Planning staff (TUI Calendar, catégorie `jardinage` id 6)
- Fournisseurs (pivot par résidence) + commandes (workflow brouillon → facturée, réception auto → entrée inventaire)
- Comptabilité dédiée (MANAGER strict, coût par espace, Chart.js, export CSV)
- **Apiculture complète** : configuration réglementaire (NAPI, référent…), ruches (CRUD + photo), carnet de visite (export CSV conforme FR), historique statut (audit trail), alertes traitements saisonniers (varroa, nourrissement, etc.)
- Dashboard riche (KPI, bandeaux d'alerte, contacts rapides avec messagerie)

> ℹ️ **Renommage historique** : le module s'appelait initialement "Jardinerie" (routes `/jardinerie/*`, classe `Jardinerie`). Renommé en **Jardinage** (sémantiquement plus juste pour un service d'entretien d'espaces verts). Les tables DB `jardin_*` ont un préfixe neutre et ne changent pas.

## Périmètre (fichiers)

- Controller : [app/controllers/JardinageController.php](../../app/controllers/JardinageController.php)
- Modèle : [app/models/Jardinage.php](../../app/models/Jardinage.php)
- Vues : [app/views/jardinage/](../../app/views/jardinage/) — 18 fichiers :
  - `dashboard`, `equipe`, `planning`
  - `espaces`, `espace_taches`
  - `produits`, `produit_edit`
  - `inventaire`, `inventaire_historique`
  - `fournisseurs`, `commandes`, `commande_form`, `commande_show`
  - `comptabilite`
  - `ruches`, `ruche_show`
  - `apiculture` (config), `traitements` (calendrier)
- Uploads : `public/uploads/jardinage/{espaces,produits,ruches}/`
- Navbar : dropdown "🌱 Jardinage" dans [app/views/partials/navbar.php](../../app/views/partials/navbar.php)

## Migrations appliquées

| # | Nom | Contenu |
|---|-----|---------|
| 008 | `008_module_jardinerie.sql` | Tables initiales : `jardin_espaces`, `jardin_taches`, `jardin_produits`, `jardin_inventaire`, `jardin_inventaire_mouvements`, `jardin_ruches`, `jardin_ruches_visites`, `jardin_fournisseur_residence` + colonne `coproprietees.ruches` + seed 12 résidences (dont La Badiane) |
| 009 | `009_apiculture_config.sql` | `coproprietees_apiculture` (1:1, NAPI, référent, etc.) |
| 010 | `010_jardin_commandes.sql` | `jardin_commandes` + `jardin_commande_lignes` |
| 011 | `011_jardin_comptabilite.sql` | `jardin_comptabilite` (écritures recettes/dépenses, imputation espace) |
| 012 | `012_jardin_ruches_statut_log.sql` | `jardin_ruches_statut_log` (audit trail) + backfill statut initial |
| 013 | `013_jardin_traitements_calendrier.sql` | `jardin_traitements_calendrier` + seed 6 templates FR (varroa été/hiver, nourrissement, etc.) |

⚠️ Les noms de fichiers `*_jardinerie_*.sql` sont des artefacts historiques immuables (le nom est enregistré dans la table `migrations`, on ne les renomme pas).

## Contrôle d'accès

### Constantes de rôles

```php
private const ROLES_ALL     = ['admin', 'directeur_residence', 'jardinier_manager', 'jardinier_employe'];
private const ROLES_MANAGER = ['admin', 'directeur_residence', 'jardinier_manager'];
```

| Rôle DB | Nom affiché | Id |
|---------|-------------|----|
| `jardinier_manager` | Jardinier-Paysagiste (Chef) | 6 |
| `jardinier_employe` | Jardinier-Paysagiste | 7 |

### Permissions par section (définitives)

| Section | `admin` / `directeur_residence` | `jardinier_manager` | `jardinier_employe` |
|---------|--------------------------------|---------------------|---------------------|
| Dashboard | ✅ | ✅ | ✅ |
| Planning (lecture) | ✅ | ✅ | ✅ |
| Planning (create/move/delete) | ✅ | ✅ | ❌ |
| Espaces jardin (lecture) | ✅ | ✅ | ✅ |
| Espaces jardin (CRUD + photo) | ✅ | ✅ | ❌ |
| Tâches récurrentes par espace | ✅ | ✅ | ❌ |
| Catalogue produits & outils (+ photo) | ✅ | ✅ | ❌ (masqué navbar) |
| Inventaire (consultation) | ✅ | ✅ | ✅ |
| Inventaire (mouvements entrée/sortie/ajustement) | ✅ | ✅ | ✅ |
| Inventaire (ajout d'un produit) | ✅ | ✅ | ❌ |
| Fournisseurs (pivot CRUD) | ✅ | ✅ | ❌ |
| Commandes fournisseurs | ✅ | ✅ | ❌ |
| **Comptabilité** | ✅ | ✅ | **❌ (403 strict)** |
| Config apiculture — lecture | ✅ | ✅ | ✅ (fieldset disabled) |
| Config apiculture — édition | ✅ | ✅ | ❌ |
| Ruches — lecture / carnet | ✅ | ✅ | ✅ |
| Ruches — CRUD (créer/modifier/désactiver + photo) | ✅ | ✅ | ❌ |
| Ruches — ajouter une visite | ✅ | ✅ | ✅ |
| Ruches — export CSV carnet | ✅ | ✅ | ✅ |
| Traitements apicoles (calendrier CRUD + alertes) | ✅ | ✅ | ❌ |
| Équipe | ✅ | ✅ | ❌ |

## Sections fonctionnelles

### Dashboard (`/jardinage/index`)
- 4 KPI : espaces (dont ruchers), catalogue (produits/outils), ruches actives + visites 30j + miel annuel, alertes stock
- **Bandeaux d'alerte** : stock (manager), ruches sans visite > 30j, **traitements obligatoires cette période** (rouge)
- Cartes d'accès rapide : Espaces, Inventaire, Planning, Catalogue, Ruches (🐝 avec badge actives)
- Liste alertes stock (manager) + mouvements récents
- **Bloc "Contacter rapidement"** : jardinier_manager + direction des résidences accessibles, avec liens messagerie interne / email / tel
- Sélecteur résidence si staff affecté à plusieurs

### Espaces jardin (`/jardinage/espaces`)
- CRUD manager + modal création/édition
- 12 types (potager, parterre, pelouse, haie, arbre_fruitier, serre, verger, rocaille, bassin, compost, rucher, autre) avec icônes dédiées
- Surface m², description, photo
- **Photo upload** : modal avec preview, double-clic mini → viewer modal plein écran
- Sous-page tâches récurrentes : `/jardinage/espaces/taches/<id>` (fréquence quotidien/hebdo/…, saison, durée estimée)

### Planning (`/jardinage/planning`)
- TUI Calendar v1.15.3
- Filtré automatiquement sur les rôles `jardinier_manager` / `jardinier_employe`
- Drag & drop `move` (manager only)
- Catégorie pré-sélectionnée sur `planning_categories.jardinage` (id 6)
- Endpoint AJAX : `JardinageController::planningAjax($action)` — actions `getEvents`, `save`, `move`, `delete`

### Catalogue produits & outils (`/jardinage/produits`)
- CRUD manager-only, filtre par type (produit/outil) et catégorie, recherche
- 11 catégories (engrais, terreau, semence, plant, phytosanitaire, outillage_main/motorise, arrosage, protection, consommable, autre)
- Champs : nom, marque, type, catégorie, unité, prix HT, fournisseur, BIO, danger (mentions sécurité), **photo**, notes, actif
- **Photo upload** : création + édition dédiée + bouton supprimer photo seule

### Inventaire (`/jardinage/inventaire`)
- Sélection résidence obligatoire
- Filtre par catégorie + switch "Alertes seulement"
- Ajout produit à l'inventaire (manager) : dropdown des produits du catalogue non présents
- Mouvement stock : modal unique entrée/sortie/ajustement
  - Sortie : champ `espace_id` (imputation pour coût par espace)
  - Motifs ENUM : `livraison`, `usage`, `perte`, `casse`, `inventaire`, `autre`
  - **Transaction `FOR UPDATE` + refus stock négatif**
- Historique complet : `/jardinage/inventaire/historique/<id>`

### Fournisseurs (`/jardinage/fournisseurs`) — MANAGER
- Pivot `jardin_fournisseur_residence` (actif / inactif, contact_local, téléphone_local, jour livraison, délai)
- UI lier / délier / modifier le lien
- INSERT...ON DUPLICATE KEY UPDATE : réactive un lien "inactif"

### Commandes fournisseurs (`/jardinage/commandes`) — MANAGER
- Numéro auto `CMD-JARD-YYYY-NNNN`
- Workflow : `brouillon → envoyée → livrée_partiel → livrée → facturée` (ou `annulée`)
- Formulaire avec lignes dynamiques JS (ajout/suppr, auto-fill prix depuis catalogue, totaux TTC live)
- **Réception** : modal saisie par ligne → transaction :
  - Update `quantite_recue` par ligne
  - Mouvement d'**entrée automatique** dans `jardin_inventaire` (motif `livraison`, notes réf commande)
  - Création auto de l'entrée inventaire si le produit n'y est pas encore pour cette résidence
  - Statut bascule `livree` (si tout reçu) ou `livree_partiel`
  - Date de livraison effective
- Suppression : hard delete si brouillon, sinon statut = `annulee`

### Comptabilité (`/jardinage/comptabilite`) — **MANAGER strict**
- `requireRole(ROLES_MANAGER)` sur tous les endpoints
- 4 KPI (Recettes TTC, Dépenses TTC, Résultat, Nb écritures)
- **Graphique Chart.js** : barres recettes vs dépenses sur 12 mois
- **Coût par espace** : écritures imputées + sorties stock × prix produit groupées par espace_id
- Dépenses par fournisseur (agrégé depuis `jardin_commandes` livrées/facturées)
- **🍯 Récoltes de miel à comptabiliser** : ligne par visite `recolte` non comptabilisée, saisie prix €/kg inline → crée écriture recette auto
- Modal nouvelle écriture (catégories auto-filtrées selon type recette/dépense, calcul TTC live)
- **Export CSV** : BOM UTF-8, séparateur `;`, colonnes Date/Compte/Libellé/Type/Catégorie/Espace/HT/TVA/TTC/Résidence

### Équipe (`/jardinage/equipe`) — MANAGER
- Liste staff `jardinier_*` groupée par résidence
- Rôle (badge coloré), contact (email/tel), dernière connexion, statut

## Apiculture

### Pré-requis : `coproprietees.ruches = 1`
- Checkbox 🐝 sur formulaire création/édition résidence (admin)
- **Garde-fou serveur** : refus de décocher si `COUNT(jardin_ruches.statut = 'active') > 0`

### Config apiculture (`/jardinage/apiculture?residence_id=X`)
- Lecture `ROLES_ALL`, édition `ROLES_MANAGER` (fieldset disabled pour employe)
- 7 champs : NAPI, date déclaration préfecture, nombre max ruches, référent interne (user) OU externe (texte), type rucher (sédentaire/transhumant), distance habitations (m), notes
- Section aide-mémoire (réglementation FR)
- Table 1:1 `coproprietees_apiculture` (upsert via INSERT...ON DUPLICATE KEY UPDATE)

### Ruches (`/jardinage/ruches`)
- Liste avec sélecteur résidence (filtrée aux `ruches=1`), photo miniature double-clic → viewer, badge `⚠ X traitements` si alertes en fenêtre
- Modal create/edit (numéro, type, race, date install, statut, espace rucher, photo, notes)
- **Photo upload** (5 Mo max, JPG/PNG/WEBP, MIME + `getimagesize`)
- Détail `/jardinage/ruches/show/<id>` :
  - Infos ruche + photo plein format
  - Stats (nb visites, récoltes, miel année courante, miel total)
  - **Carnet de visite** avec tri/recherche/pagination
  - **Bloc "Traitements recommandés"** : tableau avec 3 états (fait ✓ / à faire / hors fenêtre)
  - **Bloc "Historique statut"** : timeline complète (création, transitions, motifs, auteur, date)
  - Modal nouvelle visite (type intervention, couvain, reine vue, kg miel conditionnel, produit traitement, observations)
  - Modal édition ruche (statut avec champ motif conditionnel JS)
  - **Bouton export CSV carnet** (`ROLES_ALL`) : métadonnées (NAPI, référent) + tableau visites, conforme pratique FR

### Carnet de visite — Export CSV
- URL : `/jardinage/ruches/exportCarnet/<id>`
- Nom fichier : `carnet_ruche_<numero>_<YYYY-MM-DD>.csv`
- En-tête : ruche, résidence, type, race, installation, emplacement, statut, NAPI, déclaration, référent, type rucher
- Tableau : Date, Type intervention, État couvain, Reine vue (Oui/Non), Miel (kg), Produit traitement, Observations, Apiculteur

### Historique statut ruche
- Table `jardin_ruches_statut_log` : `statut_avant` (NULL = création), `statut_apres`, `motif`, `user_id`, `changed_at`
- **Auto-logging** : `createRuche()`, `updateRuche()`, `setRucheStatut()` enregistrent automatiquement si changement
- Backfill initial : 1 ligne "statut initial" par ruche existante au moment de la migration 012
- Timeline affichée sur la page détail ruche avec icônes par statut (▶ active, 🐛 essaim, ⏸ inactive, 💀 morte)
- Champ **motif** optionnel dans les modales d'édition — affichage conditionnel JS quand le statut change

### Traitements apicoles saisonniers (`/jardinage/traitements`)
- `ROLES_MANAGER` strict
- Référentiel `jardin_traitements_calendrier` (residence_id NULL = template système, sinon spécifique à une résidence)
- **6 templates FR seedés** :
  1. Contrôle hivernal (Jan–Fév, recommandé)
  2. Visite de printemps (Mar–Avr, recommandé)
  3. Pose hausses / essaimage (Avr–Mai, optionnel)
  4. Traitement varroa été (Août–Sept, **critique**, produit: Apivar)
  5. Nourrissement automnal (Oct–Nov, **critique**, produit: Sirop 50/50)
  6. Traitement varroa hiver acide oxalique (Déc, **critique**)
- **Logique d'alerte** : pour chaque ruche `statut='active'`, si le mois courant ∈ [mois_debut, mois_fin] ET aucune visite `type='traitement'` dans cette fenêtre de l'année courante → alerte
- Affichage :
  - **Bandeau rouge dashboard** avec badges cliquables
  - **Badge rouge** `⚠ X` à côté du numéro sur la liste ruches
  - Tableau **"Traitements recommandés"** sur la page détail ruche (fait ✓ / à faire / hors fenêtre)
  - Page dédiée `/jardinage/traitements` : alertes actives + CRUD du référentiel
- Gestion des fenêtres traversant l'année (mois_debut > mois_fin, ex: nov → fév) supportée

## Routes implémentées

| URL | Méthode | Rôles |
|-----|---------|-------|
| `GET /jardinage/index` | `index()` | ROLES_ALL |
| `GET /jardinage/equipe` | `equipe()` | ROLES_MANAGER |
| `GET /jardinage/planning` | `planning()` | ROLES_ALL |
| `* /jardinage/planningAjax/<action>` | `planningAjax($action)` | ROLES_ALL (save/move/delete : MANAGER) |
| `GET /jardinage/espaces` | `espaces()` | ROLES_ALL |
| `POST /jardinage/espaces/create` | `espaces('create')` | ROLES_MANAGER |
| `POST /jardinage/espaces/update/<id>` | `espaces('update', $id)` | ROLES_MANAGER |
| `GET /jardinage/espaces/delete/<id>` | `espaces('delete', $id)` | ROLES_MANAGER |
| `GET /jardinage/espaces/photoDelete/<id>` | `espaces('photoDelete', $id)` | ROLES_MANAGER |
| `GET\|POST /jardinage/espaces/taches/<id>` | `espaces('taches', $id)` | ROLES_ALL lecture / MANAGER écriture |
| `GET /jardinage/produits` | `produits()` | ROLES_MANAGER |
| `POST /jardinage/produits/create` | `produits('create')` | ROLES_MANAGER |
| `GET /jardinage/produits/edit/<id>` | `produits('edit', $id)` | ROLES_MANAGER |
| `POST /jardinage/produits/update/<id>` | `produits('update', $id)` | ROLES_MANAGER |
| `GET /jardinage/produits/delete/<id>` | `produits('delete', $id)` | ROLES_MANAGER |
| `GET /jardinage/produits/photoDelete/<id>` | `produits('photoDelete', $id)` | ROLES_MANAGER |
| `GET /jardinage/inventaire` | `inventaire()` | ROLES_ALL |
| `POST /jardinage/inventaire/ajouter` | `inventaire('ajouter')` | ROLES_MANAGER |
| `POST /jardinage/inventaire/mouvement/<id>` | `inventaire('mouvement', $id)` | ROLES_ALL |
| `GET /jardinage/inventaire/historique/<id>` | `inventaire('historique', $id)` | ROLES_ALL |
| `GET /jardinage/fournisseurs` | `fournisseurs()` | ROLES_MANAGER |
| `POST /jardinage/fournisseurs/lier` | `fournisseurs('lier')` | ROLES_MANAGER |
| `POST /jardinage/fournisseurs/update/<id>` | `fournisseurs('update', $id)` | ROLES_MANAGER |
| `GET /jardinage/fournisseurs/delier/<id>` | `fournisseurs('delier', $id)` | ROLES_MANAGER |
| `GET /jardinage/commandes` | `commandes()` | ROLES_MANAGER |
| `GET /jardinage/commandes/create` | `commandes('create')` | ROLES_MANAGER |
| `POST /jardinage/commandes/store` | `commandes('store')` | ROLES_MANAGER |
| `GET /jardinage/commandes/show/<id>` | `commandes('show', $id)` | ROLES_MANAGER |
| `GET /jardinage/commandes/envoyer/<id>` | `commandes('envoyer', $id)` | ROLES_MANAGER |
| `POST /jardinage/commandes/receptionner/<id>` | `commandes('receptionner', $id)` | ROLES_MANAGER |
| `GET /jardinage/commandes/facturer/<id>` | `commandes('facturer', $id)` | ROLES_MANAGER |
| `GET /jardinage/commandes/delete/<id>` | `commandes('delete', $id)` | ROLES_MANAGER |
| `GET /jardinage/comptabilite` | `comptabilite()` | ROLES_MANAGER |
| `POST /jardinage/comptabilite/ecriture` | `comptabilite('ecriture')` | ROLES_MANAGER |
| `POST /jardinage/comptabilite/recolte` | `comptabilite('recolte')` | ROLES_MANAGER |
| `GET /jardinage/comptabilite/delete/<id>` | `comptabilite('delete', $id)` | ROLES_MANAGER |
| `GET /jardinage/comptabilite/export` | `comptabilite('export')` | ROLES_MANAGER |
| `GET\|POST /jardinage/apiculture[/save]` | `apiculture($action)` | ROLES_ALL lecture / MANAGER save |
| `GET /jardinage/ruches` | `ruches()` | ROLES_ALL (filtré `ruches=1`) |
| `POST /jardinage/ruches/create` | `ruches('create')` | ROLES_MANAGER |
| `POST /jardinage/ruches/update/<id>` | `ruches('update', $id)` | ROLES_MANAGER |
| `GET /jardinage/ruches/delete/<id>` | `ruches('delete', $id)` | ROLES_MANAGER |
| `GET /jardinage/ruches/photoDelete/<id>` | `ruches('photoDelete', $id)` | ROLES_MANAGER |
| `GET /jardinage/ruches/show/<id>` | `ruches('show', $id)` | ROLES_ALL |
| `GET /jardinage/ruches/exportCarnet/<id>` | `ruches('exportCarnet', $id)` | ROLES_ALL |
| `POST /jardinage/ruches/visite/<id>` | `ruches('visite', $id)` | ROLES_ALL |
| `GET /jardinage/traitements` | `traitements()` | ROLES_MANAGER |
| `POST /jardinage/traitements/create` | `traitements('create')` | ROLES_MANAGER |
| `POST /jardinage/traitements/update/<id>` | `traitements('update', $id)` | ROLES_MANAGER |
| `GET /jardinage/traitements/delete/<id>` | `traitements('delete', $id)` | ROLES_MANAGER |

## Méthodes modèle principales

`app/models/Jardinage.php` (30+ méthodes) :

- **Résidences** : `getResidencesByUser`, `getResidenceIdsByUser`, `getAllResidencesSimple`
- **Dashboard** : `getDashboardStats`, `getAlertesStock`, `getMouvementsRecents`, `getRuchesSansVisite`
- **Staff** : `getStaffByResidences`, **`getContactsRapides`** (pour bloc messagerie)
- **Espaces** : `getEspaces`, `getEspace`, `createEspace`, `updateEspace`, `deleteEspace`, `updateEspacePhoto`, `getEspacePhoto`
- **Tâches espaces** : `getTachesByEspace`, `createTache`, `deleteTache`
- **Produits** : `getAllProduits`, `getProduit`, `createProduit`, `updateProduit`, `deleteProduit`, `getFournisseursList`, `updateProduitPhoto`, `getProduitPhoto`
- **Inventaire** : `getInventaire`, `getInventaireItem`, `getProduitsHorsInventaire`, `addToInventaire`, `mouvementStock` (transaction FOR UPDATE), `getMouvements`
- **Fournisseurs pivot** : `getFournisseursResidence`, `getFournisseursDisponibles`, `getFournisseursActifsResidence`, `getLienFournisseur`, `lierFournisseurResidence`, `updateLienFournisseur`, `delierFournisseurResidence`
- **Commandes** : `getCommandes`, `getCommande`, `createCommande`, `updateCommandeStatut`, **`receptionnerCommande`** (transaction + entrée inventaire auto), `deleteOrCancelCommande`
- **Comptabilité** : `createEcriture`, `deleteEcriture`, `getEcriture`, `getEcritures`, `getTotauxAnnuels`, `getSyntheseMensuelle`, `getCoutParEspace`, `getDepensesParFournisseur`, `getRecoltesNonComptabilisees`, `getEcrituresExport`
- **Apiculture config** : `getApiculture`, `upsertApiculture`, `getApiculteursCandidats`, `userHasAccessToRuches`
- **Ruches** : `getRuchesByResidence`, `getRuche`, `createRuche`, `updateRuche`, `setRucheStatut`, `updateRuchePhoto`, `getRuchePhoto`, `getEspacesRucher`, `logStatutChange`, `getStatutHistory`
- **Visites** : `getVisitesByRuche`, `createVisite`
- **Traitements** : `getCalendrierTraitements`, `getTraitementCalendrier`, `createTraitementCalendrier`, `updateTraitementCalendrier`, `deleteTraitementCalendrier`, `getTraitementsPourRuche`, `getAlertesTraitements`, `countAlertesRuche`

## Intégration Messagerie

- Icône 📧 du navbar : accessible à tous les rôles (dont jardinage)
- **Bloc "Contacter rapidement"** sur le dashboard : cartes pour chaque manager/direction affecté aux résidences visibles, avec boutons :
  - 📧 `/message/compose?to=<user_id>` (messagerie interne avec destinataire pré-sélectionné)
  - 📮 `mailto:` (si email présent)
  - 📞 `tel:` (si téléphone présent)

## Règles métier transversales (appliquées)

- Filtrage strict par `residence_id` selon le rôle (staff voit sa résidence via `user_residence`, admin voit tout)
- Tout mouvement d'inventaire = ligne dans `jardin_inventaire_mouvements` avec `user_id` + `espace_id` (traçabilité complète)
- `jardinier_employe` masqué de la navbar pour catalogue/équipe/fournisseurs/commandes/comptabilité/traitements ET côté serveur (`requireRole`)
- Espaces jardin avec `actif = 0` affichés dans l'écran manager mais grisés
- **Comptabilité jardinage bloquée pour `jardinier_employe`** (vérification serveur stricte `ROLES_MANAGER`)
- **Apiculture conditionnée à `coproprietees.ruches = 1`** (navbar + accès pages)
- Garde-fou désactivation apiculture : refus si ruches actives
- Photo upload helper générique : `handlePhotoUpload($file, $subdir, $prefix, $id)` — validation extension + MIME + `getimagesize()` (anti-polyglot), 5 Mo max

## Schéma SQL complet

Tous les types sont les définitifs tels que créés par les migrations 008 → 013.

### `coproprietees` (colonnes ajoutées)
```sql
ALTER TABLE coproprietees
  ADD COLUMN ruches TINYINT(1) DEFAULT 0 COMMENT '1 si résidence dispose de ruches' AFTER actif;
```

### `coproprietees_apiculture` (1:1 avec coproprietees, migration 009)
```sql
CREATE TABLE coproprietees_apiculture (
    residence_id INT PRIMARY KEY,
    numero_napi VARCHAR(50) DEFAULT NULL,
    date_declaration_prefecture DATE DEFAULT NULL,
    nombre_max_ruches INT DEFAULT NULL,
    apiculteur_referent_user_id INT DEFAULT NULL,
    apiculteur_referent_externe VARCHAR(200) DEFAULT NULL,
    type_rucher ENUM('sedentaire','transhumant') DEFAULT 'sedentaire',
    distance_habitations_m INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (apiculteur_referent_user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### `jardin_espaces` / `jardin_taches` / `jardin_produits` / `jardin_inventaire` / `jardin_inventaire_mouvements`
Voir migration 008. Points clés :
- `jardin_inventaire.quantite_actuelle DECIMAL(10,3)` + `UNIQUE KEY (produit_id, residence_id)`
- `jardin_inventaire_mouvements` : `type_mouvement` ENUM, `motif` ENUM, `espace_id` FK (imputation coût par espace), `created_at`

### `jardin_fournisseur_residence` (pivot, migration 008)
Pattern identique à `rest_fournisseur_residence`.

### `jardin_commandes` / `jardin_commande_lignes` (migration 010)
```sql
CREATE TABLE jardin_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    numero_commande VARCHAR(50) NOT NULL UNIQUE,
    date_commande DATE NOT NULL,
    date_livraison_prevue DATE DEFAULT NULL,
    date_livraison_effective DATE DEFAULT NULL,
    statut ENUM('brouillon','envoyee','livree_partiel','livree','facturee','annulee') DEFAULT 'brouillon',
    montant_total_ht DECIMAL(12,2), montant_tva DECIMAL(12,2), montant_total_ttc DECIMAL(12,2),
    notes TEXT, created_by INT,
    created_at DATETIME, updated_at DATETIME,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE jardin_commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite_commandee DECIMAL(10,3) NOT NULL,
    quantite_recue DECIMAL(10,3) DEFAULT NULL,
    prix_unitaire_ht DECIMAL(8,2) NOT NULL,
    taux_tva DECIMAL(4,2) DEFAULT 20.00,
    montant_ligne_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_commandee * prix_unitaire_ht) STORED,
    ...
);
```

### `jardin_comptabilite` (migration 011)
```sql
CREATE TABLE jardin_comptabilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_ecriture DATE NOT NULL,
    type_ecriture ENUM('recette','depense') NOT NULL,
    categorie ENUM('achat_fournisseur','recolte_miel','charge_personnel','autre_recette','autre_depense'),
    reference_id INT, reference_type ENUM('commande_fournisseur','ruche_visite','manuel','autre'),
    espace_id INT DEFAULT NULL,
    libelle VARCHAR(255) NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    montant_tva DECIMAL(12,2) DEFAULT 0.00,
    montant_ttc DECIMAL(12,2) NOT NULL,
    compte_comptable VARCHAR(20), mois INT, annee INT, notes TEXT,
    created_by INT, created_at DATETIME,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### `jardin_ruches` / `jardin_ruches_visites` (migration 008)
Voir migration 008.

### `jardin_ruches_statut_log` (migration 012)
```sql
CREATE TABLE jardin_ruches_statut_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruche_id INT NOT NULL,
    statut_avant ENUM('active','essaim_capture','inactive','morte') DEFAULT NULL,
    statut_apres ENUM('active','essaim_capture','inactive','morte') NOT NULL,
    motif VARCHAR(255) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruche_id) REFERENCES jardin_ruches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### `jardin_traitements_calendrier` (migration 013)
```sql
CREATE TABLE jardin_traitements_calendrier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT DEFAULT NULL COMMENT 'NULL = template système',
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    mois_debut TINYINT NOT NULL,
    mois_fin TINYINT NOT NULL,
    priorite TINYINT NOT NULL DEFAULT 2 COMMENT '1=critique, 2=recommandé, 3=optionnel',
    produit_suggere VARCHAR(150),
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME, updated_at DATETIME,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
);
```

## Tests recommandés

### Permissions
- [ ] `jardinier_employe` → 403 sur `/jardinage/produits`, `/jardinage/equipe`, `/jardinage/comptabilite`, `/jardinage/commandes`, `/jardinage/fournisseurs`, `/jardinage/traitements`
- [ ] `jardinier_employe` → 200 sur `/jardinage/ruches/show/<id>`, peut poster `/jardinage/ruches/visite/<id>` et `/jardinage/inventaire/mouvement/<id>`
- [ ] `jardinier_employe` → 403 sur `/jardinage/ruches/create`, `/update`, `/delete`, `/photoDelete`
- [ ] Résidence avec `ruches=0` → items Ruches/Apiculture/Traitements absents du navbar pour les jardiniers de cette résidence
- [ ] Tentative décocher `ruches` alors que ruches actives → blocage avec message explicite
- [ ] Tentative sortie stock > disponible → exception "Stock insuffisant"

### Fonctionnel
- [ ] Cycle commande complet : brouillon → envoyée → réception partielle → réception complète → facturée → écriture comptable manuelle
- [ ] Vérifier que la réception crée bien les entrées inventaire (type=entree, motif=livraison)
- [ ] Créer une visite type `traitement` → l'alerte saisonnière correspondante disparaît
- [ ] Créer une visite type `recolte` avec miel > 0 → apparaît dans "Récoltes à comptabiliser"
- [ ] Changer le statut d'une ruche avec motif → entrée dans historique
- [ ] Export CSV carnet ruche ouvert dans Excel → accents OK, tableau lisible

## Améliorations éventuelles (nice-to-have)

- [ ] Resize des photos uploadées via GD/Imagick — thumbnails 300px pour accélérer le chargement
- [ ] Export PDF du carnet de visite (complément au CSV)
- [ ] Vue calendrier annuel des traitements apicoles (TUI Calendar avec bandes "fenêtres recommandées")
- [ ] Notifications email automatiques aux managers quand une alerte traitement critique apparaît
- [ ] API REST pour les alertes dashboard (en vue d'une app mobile)
- [ ] Rapprochement bancaire comptabilité (import CSV relevé bancaire → matching écritures)
