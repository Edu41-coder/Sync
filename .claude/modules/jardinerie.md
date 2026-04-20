# Module Jardinerie (à créer)

## Périmètre
Controller à créer : `JardinerieController`
Vues à créer : `app/views/jardinerie/`
Modèle à créer : `Jardinerie`

## Contrôle d'accès

### Constantes de rôles à définir
```php
private const ROLES_JARDIN  = ['admin', 'directeur_residence', 'jardinier_chef', 'jardinier'];
private const ROLES_MANAGER = ['admin', 'directeur_residence', 'jardinier_chef'];
```

### Permissions par section

| Section | `admin` / `directeur_residence` | `jardinier_chef` | `jardinier` |
|---------|--------------------------------|------------------|-------------|
| Dashboard | ✅ | ✅ | ✅ |
| Planning | ✅ | ✅ | ✅ (voir + signaler) |
| Espaces jardin (CRUD) | ✅ | ✅ | ❌ (lecture seule) |
| Catalogue produits & outils | ✅ | ✅ | ❌ (lecture seule) |
| Inventaire (consultation) | ✅ | ✅ | ✅ |
| Inventaire (mouvements) | ✅ | ✅ | ✅ (sortie pour usage) |
| Commandes fournisseurs | ✅ | ✅ | ❌ |
| Fournisseurs | ✅ | ✅ | ❌ |
| **Comptabilité** | ✅ | ✅ | **❌ (interdit)** |
| Soins ruches (si actif) | ✅ | ✅ | ✅ |
| Équipe | ✅ | ✅ | ❌ |

⚠️ **`jardinier` n'a JAMAIS accès au module comptabilité.** Vérifier avec `requireRole(ROLES_MANAGER)` sur tous les endpoints comptables.

## Sections fonctionnelles

### Dashboard (`/jardinerie/index`)
- Vue d'ensemble : tâches du jour, alertes inventaire (engrais bas, etc.), météo (optionnel), état des espaces

### Espaces jardin personnalisables (`/jardinerie/espaces`)
**Concept clé** : chaque résidence définit ses propres espaces jardin selon sa configuration réelle.

#### Table à créer : `jardin_espaces`
```sql
CREATE TABLE jardin_espaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,                  -- "Potager nord", "Parterre entrée", etc.
    type ENUM('potager', 'parterre_fleuri', 'pelouse', 'haie', 'arbre_fruitier',
             'serre', 'verger', 'rocaille', 'bassin', 'compost', 'rucher', 'autre') NOT NULL,
    surface_m2 DECIMAL(10,2) NULL,
    description TEXT NULL,
    photo VARCHAR(500) NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
);
```
- Le `jardinier_chef` crée les espaces selon le terrain de SA résidence
- Chaque espace peut avoir des tâches récurrentes affectées (arrosage, taille, désherbage)

#### Table tâches associées : `jardin_taches`
```sql
CREATE TABLE jardin_taches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    espace_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    frequence ENUM('quotidien', 'hebdo', 'bi_mensuel', 'mensuel', 'saisonnier', 'ponctuel') NOT NULL,
    saison ENUM('toutes', 'printemps', 'ete', 'automne', 'hiver') DEFAULT 'toutes',
    duree_estimee_min INT NULL,
    notes TEXT NULL,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE CASCADE
);
```

### Planning (`/jardinerie/planning`)
- TUI Calendar v1.15.3 (cohérent avec autres modules)
- Affectation des tâches aux jardiniers
- Drag & drop pour replanifier
- Endpoints AJAX : `planningAjax($action)`
- `categorie_id` lié à `planning_categories.jardinage`

### Catalogue produits & outils (`/jardinerie/produits`)

#### Table à créer : `jardin_produits`
```sql
CREATE TABLE jardin_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    categorie ENUM('engrais', 'terreau', 'semence', 'plant', 'phytosanitaire',
                   'outillage_main', 'outillage_motorise', 'arrosage',
                   'protection', 'consommable', 'autre') NOT NULL,
    type ENUM('produit', 'outil') NOT NULL,        -- distinction stockable vs immobilisé
    unite VARCHAR(20) NULL,                         -- kg, L, sac, pièce
    prix_unitaire DECIMAL(8,2) NULL,
    fournisseur_id INT NULL,
    bio TINYINT(1) DEFAULT 0,
    danger TEXT NULL,                                -- pictogrammes/mentions sécurité
    photo VARCHAR(500) NULL,
    actif TINYINT(1) DEFAULT 1
);
```

### Inventaire (`/jardinerie/inventaire`)
Analogue au module restauration/menage.

#### Tables à créer
```sql
CREATE TABLE jardin_inventaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite_actuelle DECIMAL(10,2) DEFAULT 0,
    seuil_alerte DECIMAL(10,2) NULL,
    emplacement VARCHAR(150) NULL,                  -- "Cabane jardin", "Réserve cave"
    UNIQUE KEY (residence_id, produit_id)
);

CREATE TABLE jardin_inventaire_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    type ENUM('entree', 'sortie', 'ajustement') NOT NULL,
    quantite DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL,                            -- qui a fait le mouvement
    espace_id INT NULL,                              -- pour quel espace (sortie)
    motif VARCHAR(255) NULL,
    date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventaire_id) REFERENCES jardin_inventaire(id) ON DELETE CASCADE
);
```
- Tout mouvement crée une ligne (traçabilité, comme restauration)
- Sortie = jardinier prend du produit pour un espace donné

### Commandes fournisseurs (`/jardinerie/commandes`)
Analogue restauration :
- `jardin_commandes` + `jardin_commande_lignes`
- Statut : brouillon / envoyée / reçue / annulée
- Réception → mouvement d'entrée automatique dans inventaire

### Fournisseurs (`/jardinerie/fournisseurs`)
- Liaison via table `fournisseurs` (commune au projet) avec catégorie "jardinerie"
- Table pivot `jardin_fournisseur_residence` (analogue à `rest_fournisseur_residence`)

### Comptabilité jardinerie (`/jardinerie/comptabilite`) — MANAGER UNIQUEMENT
- Suivi des dépenses (factures fournisseurs jardinerie)
- Coût par espace (somme des sorties d'inventaire affectées à l'espace)
- Intégration avec module Comptabilité global (voir @.claude/modules/comptabilite.md)
- ⚠️ `requireRole(ROLES_MANAGER)` strict — jamais accessible au jardinier de base

### Équipe (`/jardinerie/equipe`)
- Liste staff jardinerie de la résidence
- `requireRole(ROLES_MANAGER)`

## Section spéciale : Soins ruches (apiculture)

### Modification table `coproprietees`
Ajouter une colonne booléenne :
```sql
ALTER TABLE coproprietees ADD COLUMN ruches TINYINT(1) DEFAULT 0 AFTER actif;
```
- `ruches = 0` → la section "Apiculture" est cachée dans l'interface jardinerie
- `ruches = 1` → la section apparaît dans le menu et le dashboard

### Tables à créer pour les ruches
```sql
CREATE TABLE jardin_ruches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    espace_id INT NULL,                              -- FK vers jardin_espaces (type=rucher)
    numero VARCHAR(50) NOT NULL,                     -- "Ruche A1", "R-2024-03"
    type_ruche VARCHAR(100) NULL,                    -- Dadant, Warré, Langstroth, etc.
    date_installation DATE NULL,
    race_abeilles VARCHAR(100) NULL,                 -- Buckfast, Carnica, Noire, etc.
    statut ENUM('active', 'essaim_capture', 'inactive', 'morte') DEFAULT 'active',
    notes TEXT NULL,
    photo VARCHAR(500) NULL,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE SET NULL
);

CREATE TABLE jardin_ruches_visites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruche_id INT NOT NULL,
    date_visite DATE NOT NULL,
    user_id INT NOT NULL,
    type_intervention ENUM('inspection', 'recolte', 'traitement', 'nourrissement',
                           'changement_reine', 'division', 'urgence', 'autre') NOT NULL,
    couvain_etat ENUM('excellent', 'bon', 'moyen', 'faible', 'absent') NULL,
    reine_vue TINYINT(1) NULL,
    quantite_miel_kg DECIMAL(6,2) NULL,              -- si récolte
    traitement_produit VARCHAR(150) NULL,
    observations TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruche_id) REFERENCES jardin_ruches(id) ON DELETE CASCADE
);
```

### Règles apiculture
- Section "Apiculture" affichée uniquement si `coproprietees.ruches = 1`
- Carnet de visite obligatoire (réglementation française : déclaration ruches préfecture)
- Récoltes de miel intégrées en recette dans la comptabilité jardinerie
- Alertes traitements obligatoires (varroa, etc.) selon saison

### À vérifier dans le module Admin
- [ ] Formulaire édition résidence : checkbox "Cette résidence a des ruches"
- [ ] Si décoché alors qu'il y a des ruches actives → alerte ou interdiction

## Intégration Messagerie
Tous les rôles jardinerie ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible vers `jardinier_chef` / direction depuis le dashboard

## Règles métier transversales
- Filtrage strict par `residence_id` selon le rôle (staff voit sa résidence uniquement)
- Tout mouvement d'inventaire = ligne dans `jardin_inventaire_mouvements` (traçabilité)
- `jardinier` ne peut jamais accéder aux endpoints comptabilité (vérifier serveur ET masquer côté UI)
- Espaces jardin avec `actif=0` exclus des listes par défaut
- Photo upload : MIME whitelist (jpeg/png/webp), 5 MB max

## À vérifier lors du dev
- [ ] Tous les endpoints comptables ont `requireRole(ROLES_MANAGER)` (jamais ROLES_JARDIN)
- [ ] Section apiculture conditionnée à `coproprietees.ruches = 1`
- [ ] Mouvements inventaire ne peuvent pas créer de quantité négative (ou alerter si configuré)
- [ ] Sortie d'inventaire : `espace_id` recommandé pour calcul coût par espace
- [ ] Carnet de visite ruches : `user_id` obligatoire (qui a fait l'intervention)

## Checklist générale module Jardinerie
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (y compris AJAX planning, mouvements inventaire)
- [ ] Filtrage par `residence_id` selon rôle
- [ ] **Comptabilité bloquée pour le rôle `jardinier`** (vérification serveur stricte)
- [ ] `htmlspecialchars()` sur noms espaces, observations ruches, notes
- [ ] DataTable sur listes (espaces, produits, inventaire, commandes, ruches, visites)
- [ ] Photos uploadées : MIME whitelist + redimensionnement
- [ ] Section apiculture cachée si `coproprietees.ruches = 0`
