# Module Ménage

## Périmètre
Controller : `MenageController`
Modèle : `Menage`
Vues : `app/views/menage/`

## Contrôle d'accès

### Constantes de rôles
```php
private const ROLES_ALL     = ['admin', 'directeur_residence', 'entretien_manager',
                               'menage_interieur', 'menage_exterieur', 'employe_laverie'];
private const ROLES_MANAGER = ['admin', 'directeur_residence', 'entretien_manager'];
```

### Permissions par section

| Section | `admin` / `directeur_residence` / `entretien_manager` | `menage_interieur` | `menage_exterieur` | `employe_laverie` |
|---------|------------------------------------------------------|--------------------|--------------------|-------------------|
| Dashboard | ✅ | ✅ (filtré intérieur) | ✅ (filtré extérieur) | ✅ (filtré laverie) |
| Planning | ✅ | ✅ | ✅ | ✅ |
| Intérieur (chambres) | ✅ | ✅ | ❌ | ❌ |
| Extérieur (zones) | ✅ | ❌ | ✅ | ❌ |
| Zones extérieures (CRUD) | ✅ | ❌ | ❌ (lecture) | ❌ |
| Laverie | ✅ | ❌ | ❌ | ✅ |
| Catalogue produits | ✅ | ✅ (lecture) | ✅ (lecture) | ✅ (lecture) |
| Inventaire (consultation) | ✅ | ✅ | ✅ | ✅ |
| Inventaire (mouvements) | ✅ | ✅ | ✅ | ✅ |
| Commandes fournisseurs | ✅ | ❌ | ❌ | ❌ |
| Fournisseurs | ✅ | ❌ | ❌ | ❌ |
| **Comptabilité** | ✅ | **❌** | **❌** | **❌** |
| Équipe | ✅ | ❌ | ❌ | ❌ |

⚠️ **Comptabilité interdite à tous les rôles d'exécution** (`menage_interieur`, `menage_exterieur`, `employe_laverie`). `requireRole(ROLES_MANAGER)` strict.

### Auto-routage par rôle
`MenageController` détecte la section selon le rôle :
- `menage_interieur` → vue intérieur uniquement
- `menage_exterieur` → vue extérieur uniquement
- `employe_laverie` → vue laverie uniquement
- Manager → accès aux 3 sections

## Modèle de données

### Tâches & affectations
| Table | Description |
|-------|-------------|
| `menage_taches_jour` | Tâches générées pour la journée — `type_tache` ENUM('interieur','exterieur','laverie'), `lot_id`/`zone_exterieure_id`, `niveau_service` ENUM('aucun','basique','premium'), `poids` (charge), `employe_id`, `statut` |
| `menage_taches_checklist` | Items de checklist par tâche — `libelle`, `fait`, `heure_fait` |
| `menage_affectations` | Affectations journalières par employé — `nb_taches`, `poids_total`, `statut` (génération auto pour distribution équitable) |
| `menage_checklist_templates` | Templates réutilisables de checklists |

### Zones extérieures
| Table | Description |
|-------|-------------|
| `menage_zones_exterieures` | Zones configurables par résidence — `type_zone` ENUM(terrasse, parking, entrée, local_poubelles, couloir, ascenseur, jardin, piscine, salle_commune, autre), `frequence` (quotidien/hebdo/bihebdo/mensuel), `jour_semaine`, `priorite` |

### Laverie
| Table | Description |
|-------|-------------|
| `menage_laverie_demandes` | Demandes de service laverie (à la demande des résidents) — `resident_id`, `type_linge` (draps_1p, draps_2p, housse_couette, serviettes, peignoir, linge_personnel), `quantite`, `prix_unitaire`, `montant_total`, `statut` (demandee/en_cours/prete/livree/facturee/annulee) |
| `menage_laverie_tarifs` | Tarifs par résidence et type de linge |

### Stock & approvisionnement
| Table | Description |
|-------|-------------|
| `menage_produits` | Catalogue produits ménage (séparé restauration) |
| `menage_inventaire` | Stock courant par résidence et produit |
| `menage_inventaire_mouvements` | Historique mouvements (entrée/sortie/ajustement) |
| `menage_commandes` + `menage_commande_lignes` | Commandes fournisseurs |
| `menage_comptabilite` | Suivi financier dédié ménage |

## Sections fonctionnelles

### Dashboard (`/menage/index`)
- Vue d'ensemble : tâches du jour, affectations, alertes inventaire bas, demandes laverie en attente
- Filtré automatiquement selon le rôle (intérieur / extérieur / laverie / global)

### Intérieur (`/menage/interieur`)
- Tâches de nettoyage des **chambres / lots** (résidents et hôtes temporaires)
- Niveau de service : `aucun` / `basique` / `premium` (impacte le poids / charge horaire)
- Génération automatique des tâches pour la journée (`generated_auto`)
- Statut : `a_faire` → `en_cours` → `termine` (ou `pas_deranger` / `annule`)
- Checklist par tâche (cases à cocher)
- Vue détail tâche : `/menage/interieur/tache/{id}` (changer statut, cocher items, signaler problème)

### Extérieur (`/menage/exterieur`)
- Tâches sur **zones extérieures configurables** par résidence
- Récurrence selon `frequence` de la zone (quotidien/hebdo/bihebdo/mensuel)
- Génération auto basée sur `jour_semaine` et fréquence
- Même workflow de statuts que l'intérieur
- Affectation aux employés `menage_exterieur`

### Zones extérieures (`/menage/zones`) — MANAGER uniquement
- CRUD des zones extérieures par résidence
- Configurer fréquence, jour, priorité, description
- Activation/désactivation des zones (ne supprime pas l'historique)

### Laverie (`/menage/laverie`)
- Service laverie **à la demande** des résidents
- Workflow : `demandee` → `en_cours` → `prete` → `livree` → `facturee` (ou `annulee`)
- Liaison résident pour facturation directe
- Tarifs configurables par résidence (`menage_laverie_tarifs`)
- ⚠️ Modèle distinct du `restauration/laverie` (qui gère le linge de salle, pas un service vendu)

### Planning (`/menage/planning`)
- TUI Calendar v1.15.3 (cohérent avec autres modules)
- Endpoints AJAX : `planningAjax($action)`
- Vue par employé / par section / par résidence
- Distribution équitable automatique (algorithme basé sur `poids` des tâches)

### Catalogue produits & inventaire (`/menage/produits`, `/menage/inventaire`)
Structure analogue restauration/jardinage :
- Mouvements traçables (`menage_inventaire_mouvements`)
- Sortie liée à un employé / une tâche (analyse coût)

### Commandes fournisseurs (`/menage/commandes`)
- Multi-lignes, statut workflow
- Réception → mouvement d'entrée auto

### Fournisseurs (`/menage/fournisseurs`)
- Liaison avec table `fournisseurs` commune (catégorie ménage)

### Comptabilité ménage (`/menage/comptabilite`) — MANAGER UNIQUEMENT
- Coût par section (intérieur / extérieur / laverie)
- Recettes laverie vs dépenses (produits, salaires)
- Intégration module Comptabilité global (voir @.claude/modules/comptabilite.md)
- ⚠️ `requireRole(ROLES_MANAGER)` strict

### Équipe (`/menage/equipe`) — MANAGER uniquement
- Liste staff ménage de la résidence par section
- Affectations en cours

## Règles métier transversales

- Filtrage strict par `residence_id` selon le rôle
- Auto-routage par rôle d'employé (un `menage_interieur` ne voit jamais les tâches extérieures)
- Génération auto des tâches du jour évite les doublons (`tachesDejaGenerees`)
- `poids` et `niveau_service` permettent la distribution équitable des tâches
- Mouvement inventaire ne peut pas créer de quantité négative (alerte)
- Tâches `pas_deranger` : à reprogrammer le lendemain (résident absent, malade, etc.)

## Distinction Laverie ménage vs Laverie restauration

| | **Laverie ménage** | **Laverie restauration** |
|--|--|--|
| Type de linge | Draps, serviettes bain, peignoir, linge personnel résident | Nappes, serviettes table, torchons, tabliers cuisine |
| Modèle | Service à la demande (vendu au résident) | Cycles d'envoi/retour (stock interne) |
| Workflow | demandee → en_cours → prete → livree → facturee | envoye → recu / partiel / perdu |
| Facturation | Oui (au résident) | Non (coût interne) |
| Table | `menage_laverie_demandes` | `rest_laverie` |
| Voir aussi | — | @.claude/modules/restauration.md § Laverie |

## Intégration Messagerie
Tous les rôles ménage ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible vers `entretien_manager` / direction
- [ ] Notification messagerie en cas de demande laverie urgente

## À vérifier lors du dev
- [ ] Tous les endpoints comptables ont `requireRole(ROLES_MANAGER)` strict
- [ ] Filtrage par section selon rôle (intérieur/extérieur/laverie) systématique
- [ ] Mouvements inventaire toujours créés (pas d'UPDATE direct sur stock)
- [ ] Auto-génération des tâches : pas de doublons sur même jour/résidence/section
- [ ] Distribution équitable : algorithme basé sur `poids_total` cumulé par employé
- [ ] Demandes laverie : `prix_unitaire` figé au moment de la création (snapshot tarif)

## Checklist générale module Ménage
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (y compris AJAX planning, mouvements inventaire)
- [ ] Filtrage par `residence_id` selon rôle
- [ ] **Comptabilité bloquée pour rôles d'exécution** (vérification serveur stricte)
- [ ] `htmlspecialchars()` sur libellés tâches, notes, descriptions zones
- [ ] DataTable sur listes (tâches, zones, demandes laverie, produits, inventaire, commandes)
- [ ] Auto-routage par rôle vérifié (employé voit uniquement SA section)
