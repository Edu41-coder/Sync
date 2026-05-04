# Module Maintenance Technique

## État : ✅ 100 % livré (6 phases)

Module unifié couvrant 6 spécialités techniques avec un système de permissions granulaires (spécialités multiples par utilisateur + certifications réglementaires + sections conditionnelles selon les flags résidence).

## Périmètre fonctionnel

### 6 spécialités gérées
| Slug | Nom | Certif obligatoire | Organisme |
|------|-----|--------------------|-----------|
| `piscine` | Piscine | ✅ | ARS / Préfecture |
| `ascenseur` | Ascenseur | ✅ | COFRAC / Bureau de contrôle |
| `travaux` | Travaux | — | — |
| `plomberie` | Plomberie | — | — |
| `electricite` | Électricité | ✅ | CONSUEL / habilitation |
| `peinture` | Peinture | — | — |

### Sections fonctionnelles
1. **Dashboard** (`/maintenance/index`) — vue d'ensemble + alertes certifs expirantes
2. **Spécialités & affectation** (`/maintenance/specialites`) — matrice user × spécialité (manager only)
3. **Certifications** (`/maintenance/certifications`) — gestion qualifications avec date d'expiration + fichier preuve
4. **Interventions courantes** (`/maintenance/interventions`) — CRUD + photos avant/après + filtrage rôle
5. **Planning** (`/maintenance/planning`) — TUI Calendar v1.15.3 (1 catégorie par spécialité, drag & drop manager)
6. **Piscine** (`/maintenance/piscine`) — journal de bord (analyses chimiques, contrôles ARS, hivernage)
7. **Ascenseurs** (`/maintenance/ascenseurs`) — entité 1:N par résidence + journal + 3 contrôles réglementaires
8. **Chantiers** (`/chantier/index`) — workflow 9 phases + devis + jalons + garanties auto + quote-part propriétaires
9. **Catalogue produits** (`/maintenance/produits`) — CRUD produits/outils (manager strict)
10. **Inventaire** (`/maintenance/inventaire`) — stock par résidence + mouvements (entrée/sortie/ajustement) + alertes seuil
11. **Commandes** (`/maintenance/commandes`) — création multi-lignes + workflow `brouillon → envoyée → livrée` + réception auto-stock
12. **Fournisseurs** (`/maintenance/fournisseurs`) — vue lecture des fournisseurs liés aux résidences accessibles
13. **Équipe** (`/maintenance/equipe`) — vue cards techniciens + spécialités + certifications + alertes expirantes + contact direct (manager strict)
14. **Comptabilité** (`/maintenance/comptabilite`) — agrégation interventions + chantiers + ascenseurs (manager strict)

## Permissions — récap

### Rôles
| Rôle BDD | Description |
|----------|-------------|
| `technicien_chef` (id=19) | Chef Technique — accès complet |
| `technicien` (id=5) | Technicien — sections selon spécialités assignées |
| `admin` / `directeur_residence` | Direction — accès complet |

> **Distinction stricte** : `technicien_chef` ≠ `entretien_manager` (qui couvre ménage/laverie). Aucun chevauchement.

### Grille des permissions
| Endpoint | Rôles autorisés |
|---|---|
| `/maintenance/index` | `admin`, `directeur_residence`, `technicien_chef`, `technicien` |
| `/maintenance/specialites` | Manager strict (admin, directeur_residence, technicien_chef) |
| `/maintenance/affecterSpecialite` | Manager strict |
| `/maintenance/certifications/{userId?}` | Tous (technicien limité à sa propre fiche) |
| `/maintenance/interventions` | Tous, **filtré par spécialités assignées + résidences accessibles** |
| `/maintenance/interventionForm` | Tous (techniciens peuvent créer mais filtrage à la consultation) |
| `/maintenance/interventionDelete` | Manager strict |
| `/maintenance/planning` | Tous (drag & drop manager only) |
| `/maintenance/piscine` | Manager OU `userHasSpecialite('piscine')` |
| `/maintenance/piscineEntreeDelete` | Manager strict |
| `/maintenance/ascenseurs` | Manager OU `userHasSpecialite('ascenseur')` |
| `/maintenance/ascenseurForm` | Manager strict |
| `/maintenance/ascenseurEntree` | Tous (avec spécialité) |
| `/maintenance/ascenseurEntreeDelete` | Manager strict |
| `/maintenance/produits` | **Manager strict** (CRUD catalogue) |
| `/maintenance/produitForm`, `/maintenance/produitDelete` | Manager strict |
| `/maintenance/inventaire` | Tous (consultation + mouvements) |
| `/maintenance/inventaireAjouter` | Manager strict |
| `/maintenance/inventaireMouvement` | Tous (entrée/sortie/ajustement) |
| `/maintenance/commandes`, `/commandeForm`, `/commandeShow` | Manager strict |
| `/maintenance/commandeReceptionner` | Manager strict (déclenche entrée stock auto) |
| `/maintenance/fournisseurs` | Manager strict |
| `/maintenance/equipe` | **Manager strict** |
| `/maintenance/comptabilite` | **Manager strict** (techniciens exclus) |
| `/chantier/index`, `/chantier/show` | Tous |
| `/chantier/form`, `/chantier/delete`, `/chantier/phase` | Manager strict |
| `/chantier/devis*`, `/chantier/jalon*`, `/chantier/document*`, `/chantier/reception*`, `/chantier/lotsImpactes` | Manager strict |

### Sections conditionnelles (navbar)
- **Piscine** : visible si user a spé `piscine` (ou manager) **ET** ≥ 1 résidence accessible avec `coproprietees.piscine = 1`
- **Ascenseurs** : visible si user a spé `ascenseur` (ou manager) **ET** ≥ 1 résidence avec `coproprietees.ascenseur = 1` (auto-recalculé via triggers)
- **Comptabilité** : visible uniquement aux managers

## Architecture technique

### Migrations appliquées
| # | Nom | Contenu |
|---|-----|---------|
| 019 | `019_module_maintenance_technique.sql` | Tables fondations : specialites (6 seedées), user_specialites, user_certifications, maintenance_interventions, chantiers + 6 tables associées, maintenance_produits/inventaire/mouvements + flags coproprietees.piscine/ascenseur + rôle technicien_chef |
| 020 | `020_drop_travaux_legacy.sql` | Suppression table `travaux` legacy (vide, stub) |
| 021 | `021_piscine_journal.sql` | Table piscine_journal (analyses + contrôles ARS + saisonniers) |
| 022 | `022_ascenseurs.sql` | Tables ascenseurs + ascenseur_journal + **3 triggers** (auto-maintien `coproprietees.ascenseur`) |
| 023 | `023_maintenance_stock.sql` | Étend ENUMs `commandes.module` + `produit_fournisseurs.produit_module` avec `'maintenance'` (utilise infrastructure unifiée) |
| 027 | `027_chantiers_sinistre_link.sql` | `chantiers.sinistre_id` (NULLABLE, FK `sinistres(id)` ON DELETE SET NULL) — matérialise la relation 1→N sinistre → chantiers de réparation. Permet le bouton « Créer un chantier de réparation » depuis la fiche sinistre + la colonne « Origine » dans la liste des chantiers. Voir [sinistres.md § Intégration Maintenance](sinistres.md). |

### Fichiers du module

#### Modèles ([app/models/](../../app/models/))
- [`Specialite.php`](../../app/models/Specialite.php) — référentiel + affectation user × spécialité + certifications
- [`MaintenanceIntervention.php`](../../app/models/MaintenanceIntervention.php) — interventions courantes + filtrage rôle
- [`Piscine.php`](../../app/models/Piscine.php) — journal piscine + alertes santé publique
- [`Ascenseur.php`](../../app/models/Ascenseur.php) — entité ascenseur + journal + auto-calcul prochaine échéance
- [`Chantier.php`](../../app/models/Chantier.php) — workflow chantier + devis + jalons + garanties auto
- [`MaintenanceComptabilite.php`](../../app/models/MaintenanceComptabilite.php) — agrégation comptable
- [`MaintenanceStock.php`](../../app/models/MaintenanceStock.php) — catalogue produits + inventaire + commandes (table unifiée `commandes` avec `module='maintenance'`) + fournisseurs

#### Contrôleurs ([app/controllers/](../../app/controllers/))
- [`MaintenanceController.php`](../../app/controllers/MaintenanceController.php) — 25+ endpoints (dashboard, spécialités, certifications, interventions, planning, piscine, ascenseurs, comptabilité)
- [`ChantierController.php`](../../app/controllers/ChantierController.php) — 14 endpoints dédiés chantiers (index, form, show, phase, devis, jalons, documents, réception, lotsImpactes)

#### Vues ([app/views/maintenance/](../../app/views/maintenance/) + [app/views/chantiers/](../../app/views/chantiers/))
- `maintenance/index.php` — dashboard avec alertes
- `maintenance/specialites.php` — matrice cochable user × spécialité
- `maintenance/certifications.php` — liste + modal création + alertes expiration
- `maintenance/interventions.php`, `intervention_show.php`, `intervention_form.php` — CRUD interventions
- `maintenance/planning.php` — calendrier TUI v1.15.3 par spécialité
- `maintenance/piscine.php`, `piscine_entree_form.php` — journal piscine + édition
- `maintenance/ascenseurs.php`, `ascenseur_form.php`, `ascenseur_show.php`, `ascenseur_entree_form.php` — gestion ascenseurs + journal
- `maintenance/comptabilite.php` — KPI + Chart.js + ventilations + détail écritures
- `chantiers/index.php`, `form.php`, `show.php` — gestion chantiers complète

### Stockage fichiers
```
uploads/maintenance/                  ← HORS public/ (accès via controller authentifié)
  ├── certifs/{user_id}/              → PDF/images certifications professionnelles
  ├── piscine_pv/{residence_id}/      → PV ARS contrôles piscine
  ├── ascenseur_pv/{ascenseur_id}/    → PV visites annuelles + contrôles quinquennaux
  └── chantier_docs/{chantier_id}/    → devis signés, plans, photos, factures, garanties

public/uploads/maintenance/
  └── photos/                         → photos avant/après interventions (illustration)
```

## Règles métier transversales

### Auto-logique
- **`coproprietees.ascenseur`** : auto-maintenu par 3 triggers BDD (INSERT/UPDATE/DELETE sur `ascenseurs`) — vaut 1 si ≥ 1 ascenseur actif
- **`chantiers.necessite_ag`** : auto-coché si `montant_estime > 5 000 € HT` (forçable manuellement via `necessite_ag_force` Oui/Non). Liaison à une AG via `chantiers.ag_id` — voir @.claude/modules/ag.md § Intégration chantiers (chantiers en attente d'AG visibles sur fiche AG, alerte orange)
- **`chantiers.montant_engage`** : mis à jour automatiquement quand un devis est retenu (transaction)
- **3 garanties auto-créées à la réception** chantier : parfait achèvement (1 an), biennale (2 ans), décennale (10 ans)
- **`ascenseur_journal.prochaine_echeance`** : auto-calculée selon périodicité (maintenance préventive +30j, visite annuelle +365j, contrôle quinquennal +5 ans). Recalcul JS au changement de date côté formulaire édition.

### Validations & alertes
- **Piscine** : alertes pH hors plage 7.0-7.6, chlore < 1 mg/L (critique < 0.5), pas d'analyse > 3j en saison ouverte, contrôle ARS > 30j manquant
- **Ascenseurs** : visite annuelle EXPIRÉE / dans < 30j, contrôle quinquennal expiré / < 6 mois, maintenance préventive > 45j sans entrée, conformité non OK
- **Certifications** : alertes 3 mois avant expiration sur le dashboard

### Filtrage strict
- Toutes les requêtes filtrent par `residence_id` accessible via `user_residence` (sauf admin)
- Techniciens : visibilité interventions limitée à leurs spécialités cochées **OU** interventions assignées
- RGPD : un technicien voit ses propres certifications uniquement (manager voit toutes)

## Comptabilité unifiée

### Sources agrégées
- **Interventions** : `maintenance_interventions.cout`
- **Chantiers** : `chantiers.montant_paye` (réel) — `montant_engage` également affiché en parallèle
- **Ascenseurs** : `ascenseur_journal.cout`

### Vues de la page `/maintenance/comptabilite`
- **4 KPI annuels** : total dépenses, interventions, chantiers payés (engagé en sous-titre), ascenseurs
- **Graphique Chart.js** : évolution mensuelle 12 mois (barres oranges)
- **Ventilation par spécialité** : nb interventions / coût + nb chantiers / coût + total
- **Ventilation par résidence** : breakdown 3 colonnes (interv / chantiers / asc) + total
- **Détail écritures** : table UNION ALL des 3 sources avec DataTable + recherche + pagination

### Sécurité comptabilité
- `requireRole(ROLES_MANAGER)` strict — techniciens **interdits** d'accès
- Lien navbar conditionnel sur le rôle
- Filtrage strict `residence_id` pour les non-admin

## Tests recommandés

### Permissions (à valider manuellement)
- [ ] Connecté en `technicien` (sans spé) → 200 sur `/maintenance/index`, **403 ou redirect avec flash erreur** sur `/maintenance/comptabilite`, `/maintenance/piscine`, `/maintenance/ascenseurs`
- [ ] Connecté en `technicien` avec spé `piscine` cochée → 200 sur `/maintenance/piscine`
- [ ] Connecté en `technicien` → liste `/maintenance/interventions` filtrée à ses spécialités/résidences
- [ ] Connecté en `technicien` → 200 sur `/chantier/index` mais boutons Modifier/Supprimer absents
- [ ] Connecté en `admin` → tout accessible

### Workflow chantier
- [ ] Création chantier > 5 000 € HT → `necessite_ag` auto = 1
- [ ] Création chantier 1 000 € → `necessite_ag` = 0
- [ ] Forcer `necessite_ag_force = Non` sur chantier > 5 000 → reste à 0 (override manuel)
- [ ] Ajouter 2 devis → cliquer « Retenir » sur l'un → l'autre passe à `refuse`, `montant_engage` mis à jour
- [ ] Réception chantier → 3 garanties créées automatiquement, phase passe à `garantie`
- [ ] Configurer lots impactés (33%/33%/33%) → quote-part par propriétaire calculée correctement

### Triggers ascenseurs
- [ ] INSERT ascenseur statut=actif → `coproprietees.ascenseur = 1` ✓
- [ ] UPDATE statut → hors_service (plus aucun actif) → flag = 0 ✓
- [ ] DELETE ascenseur → flag recalculé ✓

### Édition entrées journal
- [ ] Modification entrée ascenseur → recalcul JS automatique de `prochaine_echeance` au changement de date
- [ ] Modification entrée piscine → conservation du PV existant si pas de nouveau fichier uploadé
- [ ] Case « Supprimer le PV existant » → fichier physique supprimé du disque

## Comptes de test
| Username | Rôle | Mot de passe | Pour tester |
|---|---|---|---|
| `admin` | Administrateur | `admin123` | Tout (gestion globale + compta) |
| `dir_residence` | Directeur Résidence | `Dir1234` | Manager — accès complet |
| `technicien` | Technicien (id=5) | `Tech1234` | Permissions limitées (selon spécialités cochées) |

> Pour activer une section côté technicien : se connecter en `admin` → Maintenance → Affecter spécialités → cocher pour le `technicien`.
