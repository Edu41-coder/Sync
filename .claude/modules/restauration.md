# Module Restauration

## Périmètre
Controller : `RestaurationController`
Modèle : `Restauration`
Vues : `app/views/restauration/`

### Rôles
```php
ROLES_RESTO   = ['admin', 'restauration_manager', 'restauration_serveur', 'restauration_cuisine']
ROLES_MANAGER = ['admin', 'restauration_manager']
```

| Action | Rôle requis |
|--------|-------------|
| Consulter dashboard, planning, services | `ROLES_RESTO` |
| Gérer plats, menus, fournisseurs, factures, comptabilité | `ROLES_MANAGER` |
| Gérer inventaire | `admin`, `restauration_manager`, `restauration_cuisine` |
| Gérer service / facturer | `admin`, `restauration_manager`, `restauration_serveur` |
| Gérer équipe | `ROLES_MANAGER` |

## Modèle de données

### Catalogue
| Table | Description |
|-------|-------------|
| `rest_plats` | Catalogue des plats — `categorie` (entree/plat/dessert/boisson/snack/petit_dejeuner), `type_service`, `regime` (normal/vegetarien/vegan/sans_gluten/sans_lactose/halal), allergènes, calories, photo |
| `rest_menus` | Menus du jour par résidence + `type_service` (petit_dejeuner/dejeuner/gouter/diner) |
| `rest_menu_plats` | Pivot menu ↔ plats |

### Services & Facturation
| Table | Description |
|-------|-------------|
| `rest_services_repas` | Suivi des repas servis — `type_client` (resident/hote/passage), `mode_facturation` (pension_complete/menu/carte), `nb_couverts`, `montant`, `serveur_id` |
| `rest_factures` + `rest_facture_lignes` | Facturation périodique aux résidents/hôtes |
| `rest_tarifs` | Tarifs par résidence et type de service |

### Approvisionnement
| Table | Description |
|-------|-------------|
| `rest_produits` | Catalogue produits achetés (matières premières) |
| `rest_fournisseur_residence` | Liaison fournisseurs ↔ résidence (réutilise `fournisseurs`) |
| `rest_commandes` + `rest_commande_lignes` | Commandes fournisseurs |
| `rest_inventaire` + `rest_inventaire_mouvements` | Stock + historique mouvements |

### Comptabilité
| Table | Description |
|-------|-------------|
| `rest_comptabilite` | Suivi financier dédié restauration (réutilise aussi `ecritures_comptables`) |

## Sections fonctionnelles

### Dashboard
- Vue d'ensemble : repas du jour, services en cours, alertes inventaire, équipe

### Plats (`/restauration/plats`)
- CRUD catalogue plats
- Filtre par catégorie, type de service, régime
- Upload photo plat (image/jpeg, image/png)
- Gestion des allergènes (champ libre VARCHAR 500)

### Menus (`/restauration/menus`)
- Création menu par jour + type_service + résidence
- Composition : drag & drop plats depuis catalogue
- Affichage planning hebdomadaire/mensuel

### Services de repas (`/restauration/service`)
- Enregistrement temps réel des couverts servis
- 3 types client : `resident` / `hote` / `passage`
- 3 modes facturation : `pension_complete` (forfait) / `menu` (prix menu) / `carte` (à la carte)
- Affectation serveur

### Résidents (`/restauration/residents`)
- Liste résidents avec régime alimentaire, allergies
- Préférences mémorisées

### Planning (`/restauration/planning`)
- TUI Calendar v1.15.3 — shifts cuisine, salle, livraison
- Endpoints AJAX : `planningAjax($action)`

### Équipe (`/restauration/equipe`)
- Vue staff restauration de la résidence
- Accès `ROLES_MANAGER` uniquement

### Commandes fournisseurs (`/restauration/commandes`)
- Création commande multi-lignes
- Statut : brouillon / envoyée / reçue / annulée

### Inventaire (`/restauration/inventaire`)
- Stock courant par produit
- Historique mouvements (entrée/sortie/ajustement)

### Factures fournisseurs (`/restauration/factures`)
- Saisie factures + rapprochement commandes

### Comptabilité (`/restauration/comptabilite`)
- Suivi recettes (services repas) vs dépenses (factures fournisseurs)

## Règles métier

- Filtrage strict par `residence_id` selon le rôle (staff voit sa résidence uniquement)
- Service repas : si `type_client = resident` → `resident_id` obligatoire ; si `hote` → `hote_id` obligatoire
- `mode_facturation = pension_complete` → `montant` peut être 0 (déjà inclus dans forfait résident)
- Plat désactivé (`actif=0`) → exclu des nouveaux menus mais conservé dans les menus existants
- Inventaire : tout mouvement doit créer une ligne `rest_inventaire_mouvements` (traçabilité)

## Intégration Messagerie
Tous les rôles restauration ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible vers manager / direction depuis les fiches

## Checklist générale module Restauration
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (y compris AJAX planning)
- [ ] Filtrage par `residence_id` selon rôle (staff voit sa résidence)
- [ ] Vérification cohérence `type_client` ↔ FK (`resident_id` ou `hote_id`)
- [ ] Upload photo plat : MIME whitelist (jpeg/png/webp uniquement)
- [ ] Mouvements inventaire systématiques (jamais d'UPDATE direct sur stock sans trace)
- [ ] `htmlspecialchars()` sur noms plats, allergènes, notes
- [ ] DataTable sur listes (plats, commandes, factures, services, inventaire)

---

## ✅ Section Laverie restauration (livrée)

Gestion des **cycles d'envoi/retour du linge de table** (cuisine + manager).

URL : `/restauration/laverie?residence_id=N`

### Modèle BDD : `rest_laverie`

| Colonne | Type | Notes |
|---|---|---|
| `id`, `residence_id` | FK | |
| `type_linge` | ENUM | `nappe`, `serviette_table`, `torchon`, `tablier_cuisine`, `tenue_service`, `autre` |
| `quantite_envoyee`, `quantite_recue` | INT | détection écart auto |
| `date_envoi`, `date_retour` | DATETIME | |
| `statut` | ENUM | `envoye` / `recu` / `partiel` / `perdu` |
| `cout` | DECIMAL(8,2) | suivi budgétaire interne (non facturé au résident) |
| `user_envoi_id`, `user_reception_id` | FK users | traçabilité |
| `notes` | TEXT | |

### Permissions
`requireRole(['admin', 'directeur_residence', 'restauration_manager', 'restauration_cuisine'])`

### Endpoints
| URL | Action |
|---|---|
| `GET /restauration/laverie?residence_id=N` | Liste cycles |
| `POST /restauration/laverie/create` | Créer cycle envoi |
| `POST /restauration/laverie/update/{id}` | Modifier cycle |
| `POST /restauration/laverie/recevoir/{id}` | Saisir réception |
| `POST /restauration/laverie/delete/{id}` | Supprimer |

### Distinction laverie restauration vs laverie ménage

| | **rest_laverie** | **menage_laverie_demandes** |
|---|---|---|
| Type de linge | Nappes, serviettes de table, torchons, tabliers cuisine, tenue service | Draps, serviettes bain, peignoir, linge personnel résident |
| Modèle | Cycles envoi/retour (stock interne) | Service à la demande (vendu au résident) |
| Workflow | envoyé → reçu / partiel / perdu | demandée → en cours → prête → livrée → facturée |
| Facturation | Non (coût d'exploitation interne) | Oui (au résident, tarif par type) |
| Voir aussi | — | @.claude/modules/menage.md § Laverie |
