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

## ⚠️ À implémenter — Section Laverie restauration

**Manque actuellement.** Créer une section `laverie` pour la restauration, **analogue à `app/views/menage/laverie.php`** qui gère le linge des chambres.

### Périmètre attendu
Gestion du **linge de restauration** : nappes, serviettes de table, torchons, tabliers cuisine, vêtements de service.

### Modèle suggéré (à créer)
Table à créer : `rest_laverie` (analogue à la table laverie ménage si elle existe, ou structure équivalente)
- `id`, `residence_id`
- `type_linge` ENUM('nappe', 'serviette', 'torchon', 'tablier', 'tenue_service', 'autre')
- `quantite_envoyee`, `quantite_recue`, `date_envoi`, `date_retour`
- `prestataire` (interne / externe)
- `cout`, `notes`

### Vue à créer
`app/views/restauration/laverie.php` — basée sur la structure de `app/views/menage/laverie.php`

### Controller
Ajouter méthode `laverie()` dans `RestaurationController`, avec `requireRole(['admin', 'restauration_manager', 'restauration_cuisine'])` (ou nouveau rôle `restauration_laverie` si pertinent).

### Checklist implémentation
- [ ] Reprendre la structure exacte de `menage/laverie.php` pour cohérence UX
- [ ] DataTableWithPagination (tri + recherche + pagination)
- [ ] CSRF sur tous les POST
- [ ] Filtrage par `residence_id`
- [ ] Lien depuis dashboard restauration
- [ ] Mouvements de stock laverie traçables (analogue à `rest_inventaire_mouvements`)
