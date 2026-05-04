# Module Comptabilité

## ✅ État d'avancement — 12 phases sur 12 livrées (~71 j de dev)

| Phase | Description | État | Jours |
|---|---|---|---|
| **0** | Fondations : schéma unifié + Model Ecriture + doc | ✅ 2026-05-04 | 3 |
| **1** | Refonte modules existants → push vers `ecritures_comptables` | ✅ 2026-05-04 | 8 |
| **2** | Dashboard central + visualisations | ✅ 2026-05-04 | 10 |
| **3** | RH salariés (table + UI multi-conventions) | ✅ 2026-05-04 | 5 |
| **4** | Bulletins de paie (vitrine pilote) | ✅ 2026-05-04 | 10 |
| **5** | Exports comptables (FEC DGFIP + CSV Excel + Cegid) | ✅ 2026-05-04 | 4 |
| **6** | TVA CA3/CA12 (calcul + archive + mapping CERFA) | ✅ 2026-05-04 | 3 |
| **7** | IA assistant comptable (Claude + contexte chiffré injecté) | ✅ 2026-05-04 | 5 |
| **8** | IA assistant paie (taux 2026 + conventions + anomalies) | ✅ 2026-05-04 | 4 |
| **9** | Bilan + SIG + Balance + GL + clôture exercices | ✅ 2026-05-04 | 5 |
| **10** | Rapprochement bancaire vitrine (parser CSV + matching) | ✅ 2026-05-04 | 3 |
| **11** | Audit trail métier compta (Logger::audit + page filtrable) | ✅ 2026-05-04 | 4 |
| **12** | Navbar restructurée + dashboard widgets + polish | ✅ 2026-05-04 | 2 |
| **Total** | | | **~71 j** |

**Convention multi-conventions activée** (Q2 du brief). **Espace salarié personnel activé** (Q3). **Périmètre élargi** (Q4) : tous les modules existants + loyers proprios + loyers résidents + services + hôtes + paie + admin + sinistre.

## Récapitulatif des URLs principales

| URL | Description | Rôles |
|---|---|---|
| `/comptabilite/index` | Tableau de bord (KPIs + actions à mener + accès rapides + Chart.js) | admin / directeur_residence / comptable |
| `/comptabilite/ecritures` | Table détaillée filtrable des écritures | idem |
| `/comptabilite/assistant` | Assistant IA comptable (Claude Sonnet) | idem |
| `/comptabilite/balance` | Balance comptable agrégée par compte | idem |
| `/comptabilite/grandLivre/{compteId?}` | Grand livre détail compte | idem |
| `/comptabilite/bilan` | Bilan simplifié actif/passif | idem |
| `/comptabilite/sig` | Soldes Intermédiaires de Gestion | idem |
| `/comptabilite/exercices` | Liste exercices + clôture/réouverture/archivage | idem (réouverture admin only) |
| `/comptabilite/tva` | Liste déclarations TVA + workflow | idem |
| `/comptabilite/tvaCalculer` | Calcul brouillon CA3/CA12 | idem |
| `/comptabilite/export` | Choix format export (FEC / CSV / Cegid) | idem |
| `/comptabilite/rapprochement` | Liste imports bancaires + suggestions matching | idem |
| `/comptabilite/auditTrail` | Audit trail légal (filtré par préfixes compta) | idem |
| `/salarie/index` | Liste fiches RH | idem |
| `/bulletinPaie/index` | Liste bulletins + workflow validation/émission | idem |
| `/bulletinPaie/assistant` | Assistant IA paie (Claude Sonnet) | idem |
| `/bulletinPaie/mesBulletins` | Espace salarié — ses bulletins émis | tout staff |
| `/salarie/mesInfos` | Espace salarié — sa fiche RH (lecture + édition IBAN) | tout staff |

## Audit trail (Phase 11)

Conforme PCG art. 410-1 et RGPD art. 30. **15 actions tracées** dans `logs_activite` :

```
ecriture_*    : create / update / delete / contre_passation
exercice_*    : create / cloture / reouverture / archive
bulletin_*    : create / valide / emis / annule / delete_brouillon
tva_*         : archive_brouillon / declaree / annulee / delete_brouillon
bank_*        : import_create / rapprocher / defaire / ignorer / import_delete
salarie_*     : rh_create / rh_update / rib_update (IBAN masqué : FR76********1234)
export_*      : fec / csv / cegid (traçabilité fiscale obligatoire)
```

**Whitelist par préfixes** : la page `/comptabilite/auditTrail` filtre via `Logger::COMPTA_ACTION_PREFIXES` — protection contre la pollution par d'autres sources écrivant dans `logs_activite`.

Migration 036 a droppé le trigger `trg_log_user_changes` (ancien `user_status_changed` qui polluait).

## Indicateurs "actions à mener" (Phase 12)

Bandeau warning sur dashboard si présent :
- Bulletins de paie en brouillon → lien vers liste filtrée
- Déclarations TVA en brouillon → lien vers liste TVA
- Opérations bancaires non rapprochées → lien vers rapprochement
- Salariés actifs sans fiche RH → lien vers `/salarie/index`
- (Note bas) Écritures sans `compte_comptable_id` (n'apparaissent pas au Bilan/SIG)

## Limites connues du pilote (à corriger en V2)

- **Écritures legacy sans `compte_comptable_id`** : créées avant Phase 0, classées en compte virtuel "TBD", ignorées par Bilan/SIG. Solution : campagne d'affectation rétroactive ou modification des controllers de modules pour remplir le compte à la création.
- **`taux_tva` parfois NULL** : controllers modules ne le passent pas systématiquement → `getDetailTva()` infère à partir du ratio TVA/HT (cas le plus courant). À normaliser en V2.
- **Bilan simplifié** : pas d'amortissements, pas de stocks, pas de bilan d'ouverture. Adapté au pilote, à compléter pour bilan officiel 2050/2051.
- **Bulletins paie** : watermark "PILOTE — DOCUMENT NON CONTRACTUEL". Calculs réalistes mais à valider expert-comptable avant remise officielle au salarié + génération DSN à implémenter.
- **TVA** : pas de gestion d'auto-liquidation, pas de gestion des immobilisations (ligne 20), pas de prorata de déduction. Suffisant pour CA3 simple.
- **Rapprochement bancaire** : pas de matching auto sans validation manuelle. Score 0-100 sur date + montant + libellé. Pas d'import programmé (cron/webhook).

---

## ✅ Phase 0 — Fondations (livrée 2026-05-04)

### Choix architecturaux validés (avant implémentation)

- **Q1 → Option B1** : refonte propre. `ecritures_comptables` devient la **source unique de vérité**. Les tables `jardin_comptabilite` / `menage_comptabilite` / `rest_comptabilite` seront supprimées en Phase 1 après migration des données.
- **Q2 → Multi-conventions** : table `conventions_collectives` à créer en Phase 3 (Services à la personne, HCR, Domicile, etc.).
- **Q3 → Espace salarié OUI** : page `/user/bulletins` à créer en Phase 4.
- **Q4 → Périmètre élargi** : tous les modules + loyers proprios/résidents + admin + sinistres + paie consolidés via `module_source` ENUM.

### Migration appliquée

| # | Fichier | Contenu |
|---|---|---|
| 030 | [`030_compta_unifiee.sql`](../../database/migrations/030_compta_unifiee.sql) | DROP ancienne `ecritures_comptables` (vide, vérifié) + nouvelle table unifiée + enrichissement `comptes_comptables` (type 'tiers', `taux_tva_par_defaut`, `code_module`) + seed PCG simplifié résidence-services (~30 comptes) |

### Schéma unifié `ecritures_comptables`

```sql
CREATE TABLE ecritures_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    exercice_id INT,                    -- FK exercices_comptables, NULL si non créé
    module_source ENUM(                  -- Discrimine le dashboard
        'jardinage','menage','restauration','maintenance',
        'loyer_proprio','loyer_resident','services','hote',
        'rh_paie','admin','sinistre','autre'
    ) NOT NULL,
    categorie VARCHAR(80) NOT NULL,      -- Libre par module : 'achat_fournisseur', 'recolte_miel', etc.
    date_ecriture DATE NOT NULL,
    type_ecriture ENUM('recette','depense') NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    taux_tva DECIMAL(5,2),               -- 5.5 / 10 / 20 / NULL si exonéré (utile CA3)
    montant_tva DECIMAL(12,2) DEFAULT 0,
    montant_ttc DECIMAL(12,2) NOT NULL,
    compte_comptable_id INT,             -- FK comptes_comptables (PCG)
    reference_externe_type VARCHAR(40),  -- Polymorphe : 'commande_fournisseur', 'bulletin_paie', etc.
    reference_externe_id INT,
    imputation_type VARCHAR(40),         -- Analytique : 'espace_jardin', 'salarie', 'chantier'
    imputation_id INT,
    libelle VARCHAR(255) NOT NULL,
    notes TEXT,
    piece_justificative VARCHAR(500),    -- Chemin PDF dans uploads/comptabilite/
    auto_genere TINYINT(1) DEFAULT 0,    -- 1 = créée auto par module, 0 = saisie manuelle
    statut ENUM('brouillon','validee','cloturee') DEFAULT 'validee',
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);
```

**Index posés** : `(residence_id, date_ecriture)`, `(module_source, date_ecriture)`, `(type_ecriture, date_ecriture)`, `(reference_externe_type, reference_externe_id)`, `(imputation_type, imputation_id)`, `(exercice_id, statut)`, `(compte_comptable_id, date_ecriture)`.

### Model créé

[`app/models/Ecriture.php`](../../app/models/Ecriture.php) — point d'entrée unique pour toute écriture comptable.

**Constantes exposées** :
- `Ecriture::MODULES` — map ENUM → label affichage
- `Ecriture::MODULE_COLORS` — couleurs Bootstrap par module
- `Ecriture::TYPES` — `['recette', 'depense']`
- `Ecriture::STATUTS` — `['brouillon', 'validee', 'cloturee']`

**Méthodes CRUD** :
- `create(array $data): int` — pousse une écriture, valide les champs requis, calcule TTC auto, lie l'exercice ouvert auto
- `update(int $id, array $data): bool` — modifie une écriture (refusé si clôturée)
- `delete(int $id): bool` — suppression réelle réservée aux brouillons
- `contrePasser(int $sourceId, int $userId, string $motif): ?int` — crée une écriture inverse pour annuler une écriture validée/clôturée (PCG art. 410-1, traçabilité préservée)

**Méthodes de lecture** :
- `find(int $id)` — un enregistrement
- `findFiltered(array $filters)` — filtres dashboard : `residence_ids`, `modules`, `date_min/max`, `type_ecriture`, `categorie`, `compte_comptable_id`, `reference_externe_type/id`, `search` (libellé), `limit`

**Méthodes d'agrégation** :
- `getTotaux($residenceIds, $dateMin, $dateMax, ?$modules)` → `{recettes_ttc, depenses_ttc, resultat, nb_ecritures}`
- `getSyntheseMensuelle($residenceIds, $annee, ?$modules)` → 12 lignes mois × (recettes, dépenses)
- `getVentilationParModule($residenceIds, $dateMin, $dateMax)` → ligne par module avec recettes/dépenses/nb
- `getVentilationParResidence($residenceIds, $dateMin, $dateMax, ?$modules)` → ligne par résidence (drill-down admin)
- `getRecapTva($residenceIds, $dateMin, $dateMax)` → table `(taux_tva, type, total_ht, total_tva)` pour CA3

### Convention d'usage par les modules (Phase 1+)

Tout module qui crée une opération financière (commande payée, repas servi, salaire, etc.) appelle :

```php
$ecritureModel = $this->model('Ecriture');
$ecritureModel->create([
    'residence_id'           => $residenceId,
    'module_source'          => 'jardinage',          // ENUM strict
    'categorie'              => 'achat_fournisseur',  // libre par module
    'date_ecriture'          => '2026-05-04',
    'type_ecriture'          => 'depense',
    'montant_ht'             => 100.00,
    'taux_tva'               => 20.00,
    'montant_tva'            => 20.00,
    // 'montant_ttc' calculé auto si absent
    'compte_comptable_id'    => $compteId,             // FK comptes_comptables (PCG)
    'reference_externe_type' => 'commande_fournisseur',
    'reference_externe_id'   => $commandeId,
    'imputation_type'        => 'espace_jardin',       // analytique optionnelle
    'imputation_id'          => $espaceId,
    'libelle'                => 'Achat engrais NPK',
    'auto_genere'            => 1,                     // 1 si créée par le module (pas saisie manuelle)
    'created_by'             => $_SESSION['user_id'],
]);
```

### PCG simplifié seedé (Phase 0)

~30 comptes ajoutés idempotemment dans `comptes_comptables` :

- **Classe 1 — Capitaux** : 101, 120, 129
- **Classe 4 — Tiers** : 401 fournisseurs, 411 résidents, 421 personnel dû, 431 URSSAF, 437 AGIRC-ARRCO, 44566 TVA déd, 44571 TVA col, 455 propriétaires
- **Classe 5 — Trésorerie** : 512 banque, 531 caisse
- **Classe 6 — Charges** : 601, 602, 606, 611, 613, 615, 616, 618, 621, 622, 625, 626, 631, 641, 645, 647
- **Classe 7 — Produits** : 701, 706, 7061-7067 (sous-comptes par activité), 708, 758

Chaque compte a un `taux_tva_par_defaut` et un `code_module` suggéré pour aider l'auto-affectation lors de la saisie.

### Architecture maintenance — note importante

**Maintenance n'a pas de table compta dédiée** (et n'en aura pas). Le model `MaintenanceComptabilite` continue d'agréger en lecture depuis `maintenance_interventions`, `chantiers`, `ascenseur_journal`. À Phase 1, on évaluera s'il faut **aussi** pousser ces agrégats dans `ecritures_comptables` pour les retrouver dans le dashboard global, ou si on les agrège en UNION ALL côté requête.

---

---

## ✅ Phase 1 — Refonte modules existants (livrée 2026-05-04)

### Décisions

- **Maintenance** : conservée en agrégation directe depuis `maintenance_interventions`, `chantiers`, `ascenseur_journal`. Pas de duplication dans `ecritures_comptables` — Maintenance est par nature un agrégateur. Le dashboard global Phase 2 fera UNION ALL avec ses 3 sources.
- **Tables `xxx_comptabilite` legacy** : supprimées (étaient vides en BDD avant DROP, vérifié).

### Migration appliquée

| # | Fichier | Contenu |
|---|---|---|
| 031 | [`031_drop_modules_compta_legacy.sql`](../../database/migrations/031_drop_modules_compta_legacy.sql) | DROP `jardin_comptabilite`, `menage_comptabilite`, `rest_comptabilite` |

### Models refondus (API publique inchangée)

Les 3 models continuent d'exposer les mêmes méthodes publiques (`createEcriture`, `getEcritures`, `getTotauxAnnuels`, `getSyntheseMensuelle`, `getEcrituresExport`, etc.). Seule l'implémentation a changé : elle pointe désormais vers `ecritures_comptables` filtrée par `module_source`.

| Module | Fichier | Particularité |
|---|---|---|
| Jardinage | [Jardinage.php:953-1163](../../app/models/Jardinage.php) | `espace_id` mappé vers `imputation_type='espace_jardin'` + `imputation_id`. `getCoutParEspace` et `getRecoltesNonComptabilisees` adaptés. |
| Ménage | [Menage.php:923-1020](../../app/models/Menage.php) | Mapping direct. |
| Restauration | [Restauration.php:990-1180](../../app/models/Restauration.php) | Mapping direct, conserve `getSyntheseParCategorie` et `getTVA` spécifiques. |

### Mappings `xxx_comptabilite` → `ecritures_comptables`

| Ancien champ | Nouveau champ |
|---|---|
| `module` (implicite par table) | `module_source` ENUM |
| `categorie` ENUM | `categorie` VARCHAR (libre) |
| `reference_type` / `reference_id` | `reference_externe_type` / `reference_externe_id` |
| `compte_comptable` (string numero) | `compte_comptable_id` (FK comptes_comptables) — résolu auto via lookup |
| `mois` / `annee` (stockés) | calculés depuis `date_ecriture` (`MONTH()` / `YEAR()`) |
| `espace_id` (jardinage) | `imputation_type='espace_jardin'` + `imputation_id` |

### Aucun controller modifié

Les 3 `XxxController::comptabilite()` n'ont pas été touchés — l'API publique des models est conservée. Les vues `xxx/comptabilite.php` restent identiques.

### Tests passés (2026-05-04)

- ✅ `php -l` clean sur les 3 models
- ✅ `grep` : 0 référence SQL restante aux 3 tables legacy
- ✅ Test fonctionnel : 1 écriture créée par module avec différents `categorie`/`reference_type`/`espace_id` (jardinage)
- ✅ **Isolation stricte par `module_source`** : chaque model ne voit que ses propres écritures
- ✅ `Ecriture::getVentilationParModule` (Phase 2) voit bien les 3 modules dans `ecritures_comptables`
- ✅ `tests/test_require_auth.php` : 257 méthodes scannées, 254 protégées, 0 violation

### Prochaine étape

**Phase 2 — Dashboard central + visualisations** (10 jours estimés). Préparation du `ComptabiliteController::index` avec les 6 KPI, sélecteur multi-modules, comparaison N/N-1, Chart.js.

---

---

## ✅ Phase 2 — Dashboard central + visualisations (livrée 2026-05-04)

### Routes implémentées

| URL | Méthode | Rôles | Description |
|---|---|---|---|
| `GET /comptabilite/index` | `index()` | admin, directeur_residence, comptable | Dashboard avec 6 KPIs + comparaison N/N-1 + Chart.js + section Maintenance |
| `GET /comptabilite/ecritures` | `ecritures()` | admin, directeur_residence, comptable | Table détaillée filtrable (date, module, type, libellé) |
| `GET /comptabilite/balance` | `balance()` | idem | Stub Phase 9 |
| `GET /comptabilite/grandLivre` | `grandLivre()` | idem | Stub Phase 9 |
| `GET /comptabilite/exercices` | `exercices()` | idem | Stub Phase 9 |

### Fichiers créés

- [`app/controllers/ComptabiliteController.php`](../../app/controllers/ComptabiliteController.php) — 250 lignes, refonte complète du stub
- [`app/views/comptabilite/index.php`](../../app/views/comptabilite/index.php) — dashboard
- [`app/views/comptabilite/ecritures.php`](../../app/views/comptabilite/ecritures.php) — table détaillée
- [`app/views/comptabilite/stub.php`](../../app/views/comptabilite/stub.php) — page placeholder

### Permissions (conformes au cahier des charges)

```php
private const ROLES = ['admin', 'directeur_residence', 'comptable'];
```

Filtrage strict par résidences accessibles via `user_residence` (sauf admin).

### Filtres dashboard

- **Résidence** : sélecteur (toutes accessibles ou une seule)
- **Année** : 5 dernières années
- **Mois** : optionnel (sinon année complète)
- **Modules** : multi-checkbox 12 modules (badges colorés cliquables)
- **Filtres rapides** : 4 boutons groupes prédéfinis (Recettes / Dépenses / Personnel / Maintenance)

### KPIs (6 cards)

1. Recettes TTC + variation N-1
2. Dépenses TTC + variation N-1
3. Résultat TTC (couleur = vert si bénéfice, orange si perte) + variation N-1
4. TVA collectée + variation N-1
5. TVA déductible + variation N-1
6. TVA à reverser + variation N-1

Chaque KPI affiche `↑ X% vs N-1` (vert) ou `↓ X% vs N-1` (rouge).

### Section Maintenance séparée (visible si module coché)

Card distincte (orange) avec 4 sous-KPIs : Total / Interventions / Chantiers payés / Ascenseurs. Lecture directe via `MaintenanceComptabilite::getTotauxAnnuels` (Maintenance n'écrit pas dans `ecritures_comptables` — agrégateur natif).

### Visualisations Chart.js

- **Évolution mensuelle** : 4 séries (Recettes N + Dépenses N en pleines, Recettes N-1 + Dépenses N-1 en pointillés)
- **Camembert ventilation par module** : couleurs alignées sur `Ecriture::MODULE_COLORS`

### Tableaux dashboard

- **Top résidences** (8 lignes max) : recettes + dépenses + solde par résidence (drill-down admin)
- **10 dernières écritures** : badge module + libellé tronqué + montant signé

### Page écritures détaillée

- 6 filtres : résidence, année, mois, module, type (recette/dépense), recherche libellé
- Table 9 colonnes : Date, Module, Catégorie, Libellé, Compte, Résidence, HT, TVA, TTC
- DataTableWithPagination 25/page si > 25 lignes (sinon DataTable simple)
- Limite serveur 1000 lignes pour éviter explosion mémoire

### Mise à jour navbar

[partials/navbar.php:201](../../app/views/partials/navbar.php) : dropdown "Comptabilité" enrichi
- ✅ **Tableau de bord** (nouveau, en gras)
- ✅ Écritures détaillées
- Appels de fonds (lien existant)
- Balance / Grand livre / Exercices (en muted, "Phase 9")

### Tests passés

- ✅ `php -l` clean sur 5 fichiers
- ✅ `tests/test_require_auth.php` : 257 méthodes, 254 protégées, 0 violation
- ✅ Test fonctionnel : 4 écritures cross-modules → 3 modules détectés (jardinage, ménage, restauration), totaux corrects

### Note technique

Le `taux_tva` n'est pas encore passé par les controllers de modules (`MenageController::storeEcritureMenage` etc.) — il est calculé en `montant_tva` mais le champ `taux_tva` reste NULL. Conséquence : la méthode `Ecriture::getRecapTva` (utile pour CA3) renvoie 0 ligne pour les écritures historiques. **À corriger en Phase 6 (TVA CA3/CA12)** où on passera `taux_tva` lors de la création.

---

---

## ✅ Phase 3 — RH Salariés (livrée 2026-05-04)

### Migration appliquée

| # | Fichier | Contenu |
|---|---|---|
| 032 | [`032_rh_salaries.sql`](../../database/migrations/032_rh_salaries.sql) | Tables `conventions_collectives` (8 CCN seedées) + `salaries_rh` (1:1 avec users) |

### Conventions collectives seedées (multi-conventions activé Q2)

| Code | Nom | IDCC | Modules |
|---|---|---|---|
| `services_personne` | Services à la personne | 3370 | menage, services, admin |
| `hcr` | Hôtels Cafés Restaurants | 1979 | restauration |
| `aide_domicile` | Aide à domicile (BAD) | 2941 | menage, services |
| `jardinage_paysage` | Entreprises du paysage | 7018 | jardinage |
| `immobilier` | Immobilier | 1527 | admin, services |
| `btp_ouvriers` | BTP — Ouvriers | 1596 | maintenance |
| `cadres_btp` | BTP — ETAM et Cadres | 2609 | maintenance |
| `default` | Convention par défaut | — | tous |

### Fichiers créés

- [`app/models/SalarieRh.php`](../../app/models/SalarieRh.php) — CRUD upsert 1:1 + auto-calcul taux horaire (`salaire / 151.67h`) + helpers validation IBAN/SS
- [`app/controllers/SalarieController.php`](../../app/controllers/SalarieController.php) — 5 méthodes (`index`, `show`, `edit`, `update`, `mesInfos`, `updateRib`)
- 4 vues : [`salaries/index.php`](../../app/views/salaries/index.php), [`show.php`](../../app/views/salaries/show.php), [`edit.php`](../../app/views/salaries/edit.php), [`mes_infos.php`](../../app/views/salaries/mes_infos.php)

### Routes implémentées

| URL | Rôles | Description |
|---|---|---|
| `GET /salarie/index` | admin, comptable, directeur_residence | Liste staff (filtres : résidence, avec/sans fiche, sortis) |
| `GET /salarie/show/{userId}` | idem | Fiche RH lecture |
| `GET /salarie/edit/{userId}` | idem | Formulaire édition (création si pas de fiche) |
| `POST /salarie/update/{userId}` | idem | Sauvegarde upsert + validation NumSS/IBAN |
| `GET /salarie/mesInfos` | tout user authentifié | Sa propre fiche en lecture (Q3 activé) |
| `POST /salarie/updateRib` | tout user | Modification IBAN/BIC personnel uniquement |

### Champs `salaries_rh`

- **Identité** : `numero_ss`, `date_embauche`, `date_sortie`, `motif_sortie`
- **Contrat** : `type_contrat` ENUM (CDI/CDD/Apprentissage/Stage/Intérim/Intermittent/Autre), `motif_cdd`, `cdd_date_fin`, `temps_travail`, `quotite_temps_partiel`
- **Convention** : `convention_collective_id` FK, `coefficient`, `categorie` (ouvrier/employé/agent_maitrise/cadre)
- **Rémunération** : `salaire_brut_base`, `taux_horaire_normal` (auto-calc), `taux_majoration_25` (default 25%), `taux_majoration_50` (default 50%)
- **Bancaire** : `iban`, `bic` (modifiables par le salarié lui-même)
- **Mutuelle / Prévoyance** : 4 taux (mutuelle salarial/patronal + prévoyance salarial/patronal) avec valeurs par défaut

### Permissions strictes

- **admin / comptable / directeur_residence** : CRUD complet sur fiches RH
- **Salarié lui-même** : lecture sa fiche + modification UNIQUEMENT IBAN/BIC
- Tout autre rôle (proprietaire, locataire_permanent) : pas d'accès

### Navbar mise à jour

- Dropdown "Comptabilité" : ajout lien **Salariés & fiches RH** (admin/comptable/directeur)
- Dropdown utilisateur : lien conditionnel **Mes informations RH** affiché pour 18 rôles staff (pas admin/proprietaire/locataire_permanent)

### Tests passés

- ✅ `php -l` clean sur 7 fichiers
- ✅ `tests/test_require_auth.php` : 0 violation
- ✅ Upsert testé : auto-calcul taux horaire 2200€/151.67h = 14.5052€/h ✓
- ✅ 8 conventions seedées avec IDCC officiels

### Prochaine phase

Phase 4 — **Bulletins de paie (vitrine pilote)** (10 jours). Calcul réaliste cotisations 2026 (URSSAF, AGIRC-ARRCO, CSG/CRDS, mutuelle, prévoyance, formation, SST), récupération heures via `planning_shifts`, génération PDF avec mentions légales obligatoires + workflow validation comptable.

---

---

## ✅ Phase 4 — Bulletins de paie (livrée 2026-05-04)

### ⚠️ Vitrine pilote
Les bulletins générés portent un **watermark "PILOTE — DOCUMENT NON CONTRACTUEL"** en diagonale. Calculs réalistes (taux URSSAF/AGIRC-ARRCO/CSG/CRDS 2026) mais à valider par expert-comptable avant remise officielle au salarié.

### Migration appliquée

| # | Fichier | Contenu |
|---|---|---|
| 033 | [`033_bulletins_paie.sql`](../../database/migrations/033_bulletins_paie.sql) | Table `bulletins_paie` (69 colonnes : snapshot identité + heures + brut + 11 cot. sal + 11 cot. pat + net + workflow) |

### Taux 2026 utilisés (constantes PHP, à mettre à jour annuellement)

**Salariales** : maladie 0%, vieillesse déplafonnée 0.40%, vieillesse plafonnée 6.90%, CSG déductible 6.80%, CSG non déductible 2.40%, CRDS 0.50%, AGIRC-ARRCO T1 4.15%, AGIRC-ARRCO T2 9.86%, mutuelle/prévoyance selon fiche RH.

**Patronales** : maladie 7%, vieillesse 8.55%, alloc fam 5.25%, AT/MP 1.80%, FNAL 0.50%, AGIRC-ARRCO T1 6.20%, T2 14.57%, formation 0.55%, taxe apprentissage 0.68%.

**Plafond Sécu (PMSS) 2026** : 3 925 €. **Heures mensuelles légales** : 151,67h.

### Fichiers créés

- [`app/models/BulletinPaie.php`](../../app/models/BulletinPaie.php) — calcul + workflow + import heures planning (340 lignes)
- [`app/controllers/BulletinPaieController.php`](../../app/controllers/BulletinPaieController.php) — 9 méthodes
- 4 vues : `bulletins/index`, `create`, `show`, `mes_bulletins`, **`printable`** (CSS print + watermark)

### Routes implémentées

| URL | Rôles | Description |
|---|---|---|
| `GET /bulletinPaie/index` | admin, comptable, directeur_residence | Liste + filtres (année, mois, statut) |
| `GET /bulletinPaie/create` | idem | Formulaire (sélection user + auto-import heures planning) |
| `POST /bulletinPaie/store` | idem | Création brouillon |
| `GET /bulletinPaie/show/{id}` | idem | Détail bulletin avec breakdown complet |
| `GET /bulletinPaie/print/{id}` | admin OR propriétaire | Vue imprimable (HTML print-ready, watermark) |
| `POST /bulletinPaie/valider/{id}` | admin | Workflow brouillon → valide |
| `POST /bulletinPaie/emettre/{id}` | admin | valide → emis (visible salarié) |
| `POST /bulletinPaie/annuler/{id}` | admin | Annulation avec motif |
| `POST /bulletinPaie/delete/{id}` | admin | Supprime un brouillon (uniquement) |
| `GET /bulletinPaie/mesBulletins` | tout user | Liste de SES bulletins (statut valide+emis uniquement) |

### Workflow

```
brouillon → valide → emis
       ↓        ↓        ↓
       └────────┴────────┴──→ annule (avec motif)
```

- Seuls les **brouillons** sont supprimables (DELETE physique)
- À partir de **emis**, le bulletin apparaît dans `/bulletinPaie/mesBulletins` du salarié
- Les **annulés** restent en BDD pour traçabilité

### Snapshot des infos salarié

À la création, on capture en BDD : nom, prénom, n°SS, type contrat, convention nom+IDCC, catégorie, coefficient, IBAN. Cela rend le bulletin **résistant aux modifications post-émission** de la fiche RH (audit comptable).

### Import heures depuis Planning

Le formulaire `create` lit automatiquement `planning_shifts` du user pour le mois donné, agrège par `type_heures` ('normales'/'supplementaires'), et applique une règle simplifiée : 35h sup max à 25%, le reste à 50%. Préremplissage modifiable manuellement.

### Vue imprimable (printable.php)

- Layout autonome (sans navbar, sans menus)
- CSS `@media print` propre
- **Watermark "PILOTE — DOCUMENT NON CONTRACTUEL"** en diagonale (rgba transparente)
- Bouton "Imprimer ou Enregistrer en PDF" (utilise le navigateur)
- Met les **mentions légales obligatoires** : période, identité, convention, détail brut/cotisations/net, coût employeur, footer non contractuel

### Tests passés

- ✅ `php -l` clean sur 8 fichiers
- ✅ `tests/test_require_auth.php` : 0 violation
- ✅ Test calcul SMIC : 1798€ brut → 1385€ net (ratio 77%, cohérent)
- ✅ Test calcul cadre 4000€ + sup 25% + PAS 5% : 4164€ brut → 3043€ net
- ✅ Cotisations cumulees ~22% sal, ~33% pat (réalistes pour vitrine)

### Navbar

- Dropdown "Comptabilité" : ajout **Bulletins de paie** (admin/comptable/dir)
- Dropdown utilisateur (staff) : ajout **Mes bulletins de paie**

### À améliorer en Phase 4b (optionnel)

- Génération PDF physique côté serveur (DomPDF ou TCPDF) si besoin légal
- Notification email automatique au salarié à l'émission
- Cumul annuel (bulletin année N : récap des 12 mois)
- Génération DSN (Déclaration Sociale Nominative)
- Gestion congés payés / CP acquis / CP pris

---

## Vision cible du module (sections suivantes — non encore implémentées)

## Périmètre
Controller à créer : `ComptabiliteController`
Vues à créer : `app/views/comptabilite/`
Modèle à créer : `Comptabilite`

## Contrôle d'accès

### Rôles autorisés
| Rôle | Périmètre de visibilité |
|------|------------------------|
| `admin` | **Toutes les résidences** — accès complet à toutes les données |
| `comptable` | Uniquement les résidences affectées via `user_residence` |
| `directeur_residence` | Uniquement les résidences affectées via `user_residence` |

```php
$this->requireRole(['admin', 'comptable', 'directeur_residence']);

// Filtrage obligatoire (sauf admin)
if ($currentRole !== 'admin') {
    $residencesAutorisees = User::getResidencesAffectees($_SESSION['user_id']);
    // Toutes les requêtes DOIVENT être filtrées par cette liste
}
```

### ⚠️ Vérification à faire dans le module Admin
Lors de la création d'un user avec le rôle `comptable` :
- [ ] Le formulaire doit afficher la sélection multi-résidences (comme pour `directeur_residence`)
- [ ] L'affectation `user_residence` doit être créée à l'insertion du comptable
- [ ] Sans affectation, le comptable ne voit AUCUNE donnée (pas d'accès par défaut)
- [ ] Vérifier dans `AdminController::createUser()` que la branche `comptable` traite bien les résidences

## Concept : Résumé comptable consolidé par résidence

Vue principale = tableau résumé où **chaque ligne = une résidence**, et **chaque colonne = un poste comptable consolidé** :

| Résidence | Loyers résidents | Recettes hôtes | Restauration | Ménage | Jardinage | Entretien | Travaux | Salaires staff | **Solde** |
|-----------|------------------|----------------|--------------|--------|------------|-----------|---------|----------------|-----------|
| Résidence A | +50 000 € | +2 500 € | +8 000 / -3 200 € | +1 200 / -800 € | +200 / -400 € | -1 500 € | -12 000 € | -25 000 € | **+19 000 €** |
| Résidence B | ... | ... | ... | ... | ... | ... | ... | ... | ... |

Le tableau doit être **triable + recherchable + paginé** (voir CLAUDE.md § Tableaux).
Pour chaque colonne, format `+recettes / -dépenses` (ou seulement la colonne unique si pas de recettes pour ce poste).

## Sources de données à agréger

### Recettes
| Source | Module | Table(s) | Colonne(s) clé(s) | Filtre |
|--------|--------|----------|------------------|--------|
| Loyers résidents | residents | `occupations_residents` | `loyer_mensuel_resident`, `forfait_services` | `statut='actif'` |
| Hôtes temporaires | hotes | `hotes_temporaires` | `tarif_nuit × duree × nb_personnes` | période, statut `termine`/`en_cours` |
| Repas servis | restauration | `rest_services_repas` | `montant` | période, par `residence_id` |
| Factures résidents restauration | restauration | `rest_factures` + `rest_facture_lignes` | `montant_total` | période |
| Services ménage | menage | `menage_comptabilite` | (à voir selon structure) | période |
| Récolte miel (si ruches) | jardinage | `jardin_ruches_visites` (`type=recolte`) × prix kg | `quantite_miel_kg` | période, résidences avec `coproprietees.ruches=1` |

### Dépenses
| Source | Module | Table(s) | Colonne(s) clé(s) |
|--------|--------|----------|------------------|
| Factures fournisseurs restauration | restauration | `rest_factures` | `montant_total` |
| Factures fournisseurs ménage | menage | `menage_commandes` (factures) | `montant_total` |
| Commandes jardinage | jardinage | `jardin_commandes` + `jardin_commande_lignes` | total commande |
| Sorties stock jardinage (coût valorisé) | jardinage | `jardin_inventaire_mouvements` (`type=sortie`) × prix unitaire | `quantite × prix` |
| Interventions entretien | entretien | `entretien_interventions` | `cout` |
| Sorties stock entretien (coût valorisé) | entretien | `entretien_inventaire_mouvements` (`type=sortie`) × prix unitaire | `quantite × prix` |
| Factures fournisseurs entretien | entretien | `factures_fournisseurs` (filtré catégorie entretien) | `montant_ttc` |
| **Travaux (chantiers)** | travaux | `travaux_chantiers` | `montant_paye` (réel) ou `montant_engage` (en cours) |
| Factures travaux | travaux | `travaux_documents` (`type=facture`) → `factures_fournisseurs` | `montant_ttc` |
| Salaires staff | RH | `planning_shifts` × `users_remuneration.taux_horaire_normal` | `heures_calculees` |

### Vue détail par résidence
Cliquer sur une résidence → drill-down avec :
- Comptes comptables détaillés (`comptes_comptables`)
- Écritures (`ecritures_comptables`) filtrées par résidence
- Exercice comptable courant (`exercices_comptables`)
- Export Excel/PDF par période
- **Onglets par module** : Restauration / Ménage / Jardinage / Entretien / Travaux / Salaires
  - Chaque onglet = liste détaillée des écritures du module pour cette résidence
  - Lien vers le module concerné (ex: clic sur une intervention entretien → fiche intervention)

### Distinction Travaux vs Entretien dans la vue
Les deux postes sont **séparés** dans le résumé pour distinguer :
- **Entretien** : dépenses courantes / récurrentes (budget de fonctionnement)
- **Travaux** : chantiers ponctuels / investissements (budget exceptionnel, parfois financés via appels de fonds)

⚠️ Si la décision finale est un module unique `entretien` avec ENUM `type_intervention` (voir @.claude/modules/entretien.md et @.claude/modules/travaux.md), adapter en filtrant sur `type_intervention='travaux_chantier'` pour la colonne Travaux.

## Périodes & filtres
- Filtre période : mois courant, trimestre, année, personnalisée
- Filtre exercice comptable
- Comparaison N vs N-1 (optionnel)

## ⚠️ Section IA — Génération de bulletins de salaire (à implémenter)

**Inspiré du module IA fiscalité** déjà implémenté dans `proprietaires` (utilise `ANTHROPIC_API_KEY` du `.env`, modèle `claude-sonnet-4-20250514`).

### Périmètre initial
Génération automatique des **bulletins de salaire** pour les employés des résidences :
- Source : `planning_shifts` (heures travaillées par employé sur la période)
- Calcul : heures normales × taux + heures supplémentaires × taux majoré
- Sortie : PDF de bulletin de salaire conforme (mentions obligatoires France)

### Architecture suggérée
```
ComptabiliteController::genererBulletins(int $userId, string $periode)
    ↓
ClaudeAIService::generateBulletinSalaire($shifts, $employeData)
    ↓ (appel API Anthropic)
PDF généré + sauvegardé dans uploads/bulletins/{user_id}/{periode}.pdf
```

### Données à passer à Claude
- Identité employé (nom, prénom, n° SS, poste, date embauche)
- Résidence d'affectation
- Période (mois)
- Heures normales + supplémentaires (depuis `planning_shifts.heures_calculees`)
- Taux horaire (à ajouter : table `users_remuneration` ou colonne sur `users`)
- Convention collective applicable (HCR pour restauration, autre pour ménage)
- Cumul annuel

### À prévoir avant implémentation
- [ ] Table `users_remuneration` : `user_id`, `taux_horaire_normal`, `taux_majoration_sup`, `convention_collective`, `date_debut`, `date_fin`
- [ ] Table `bulletins_salaire` : `user_id`, `periode`, `pdf_path`, `montant_brut`, `montant_net`, `cotisations`, `created_at`
- [ ] Endpoint sécurisé : seuls admin + comptable de la résidence peuvent générer
- [ ] Stockage PDF hors `public/` (téléchargement via controller authentifié)
- [ ] Vérification rate-limit API Claude (coût)

### Évolutions futures du module IA comptabilité
- Détection d'anomalies dans les écritures (doublons, montants suspects)
- Suggestions d'affectation comptable automatique
- Génération de rapports financiers narratifs (bilan, compte de résultat)
- Alertes sur écarts budgétaires

## Intégration Messagerie
Tous les rôles comptabilité ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible vers admin / direction depuis le dashboard comptable

## Modules consolidés dans la comptabilité
Pour comprendre la logique métier de chaque agrégat, se référer aux modules sources :
- @.claude/modules/residents.md — loyers, forfaits, services
- @.claude/modules/hotes.md — séjours courts, tarifs
- @.claude/modules/restauration.md — services repas, factures, inventaire
- @.claude/modules/menage.md (à créer/compléter) — services ménage, fournitures
- @.claude/modules/jardinage.md — produits, outils, miel (si ruches), commandes
- @.claude/modules/entretien.md — interventions, produits, factures
- @.claude/modules/travaux.md — chantiers, devis, garanties, quote-parts
- @.claude/modules/proprietaires.md — fiscalité, contrats de gestion (recettes loyers garantis)
- @.claude/modules/planning.md — heures travaillées (base de calcul salaires)

## À vérifier lors du dev
- [ ] **Filtrage `residence_id` strict** sur TOUTES les requêtes (sauf admin)
- [ ] Un comptable sans `user_residence` voit une page vide (pas d'erreur, juste vide)
- [ ] Aucune fuite cross-résidences (test avec 2 comptables sur résidences différentes)
- [ ] Calcul des soldes recettes - dépenses cohérent (vérifier signes)
- [ ] Période par défaut = mois en cours
- [ ] Export Excel/PDF respecte le filtrage par résidences autorisées
- [ ] Travaux comptés en `montant_paye` (réel) pour le solde, pas en `montant_engage` (engagé != décaissé)
- [ ] Sorties stock valorisées au prix unitaire courant (pas au prix d'achat historique sauf si demandé)

## Checklist générale module Comptabilité
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (génération bulletin, export, écritures manuelles)
- [ ] `htmlspecialchars()` sur tous les libellés affichés
- [ ] Affectation `user_residence` créée à la création d'un user comptable (à corriger dans Admin si manquant)
- [ ] Drill-down résidence → détail écritures fonctionnel
- [ ] IA bulletins : clé API masquée dans logs, pas exposée côté client
- [ ] PDF bulletins stockés HORS `public/`, accès via controller authentifié uniquement
