# Module Entretien Technique (à créer)

## Périmètre
Controller à créer : `EntretienController`
Vues à créer : `app/views/entretien/`
Modèle à créer : `Entretien`

Couvre toutes les interventions techniques sur les bâtiments des résidences.

## Contrôle d'accès

### Constantes de rôles à définir
```php
private const ROLES_ENTRETIEN = ['admin', 'directeur_residence', 'entretien_chef', 'entretien', 'technicien'];
private const ROLES_MANAGER   = ['admin', 'directeur_residence', 'entretien_chef'];
```

### Permissions par section

| Section | `admin` / `directeur_residence` | `entretien_chef` | `entretien` / `technicien` |
|---------|--------------------------------|------------------|---------------------------|
| Dashboard | ✅ | ✅ | ✅ |
| Planning | ✅ | ✅ | ✅ (voir + signaler intervention faite) |
| Interventions (CRUD) | ✅ | ✅ | ✅ (créer/clôturer celles affectées) |
| Catalogue produits & outils | ✅ | ✅ | ❌ (lecture seule) |
| Inventaire | ✅ | ✅ | ✅ (sortie pour usage) |
| Commandes fournisseurs | ✅ | ✅ | ❌ |
| Fournisseurs | ✅ | ✅ | ❌ |
| **Comptabilité** | ✅ | ✅ | **❌ (interdit)** |
| Affectation employé ↔ spécialité | ✅ | ✅ | ❌ |
| Équipe | ✅ | ✅ | ❌ |

⚠️ **`entretien` et `technicien` n'ont JAMAIS accès au module comptabilité.** `requireRole(ROLES_MANAGER)` strict sur tous les endpoints comptables.

## Sections fonctionnelles (5 spécialités)

### 1. Plomberie
- Réparations fuites, robinetterie, sanitaires, canalisations
- Maintenance chauffe-eau, ballons, adoucisseurs

### 2. Électricité
- Luminaires, prises, tableaux électriques
- Maintenance éclairage commun, sécurité incendie (BAES)
- Conformité installations (vérifications périodiques)

### 3. Peinture
- Rafraîchissement parties communes, chambres
- Petits travaux de finition

### 4. Ascenseur
- Maintenance préventive (souvent sous-traitée à un prestataire — voir contrat)
- Pannes : intervention prestataire + suivi
- Visite de contrôle annuelle obligatoire (réglementation)

### 5. Piscine (si la résidence en a une)
- Traitement chimique de l'eau (chlore, pH)
- Filtration, nettoyage hebdomadaire
- Hivernage / mise en service saisonnière
- Contrôles sanitaires obligatoires (ARS)

## Modèle de données

### Tables à créer

#### Catalogue spécialités (figé, pas de CRUD utilisateur)
```sql
CREATE TABLE entretien_specialites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,    -- plomberie, electricite, peinture, ascenseur, piscine
    nom VARCHAR(100) NOT NULL,
    icone VARCHAR(50) NULL,              -- fas fa-wrench, fas fa-bolt, etc.
    couleur VARCHAR(7) NULL,             -- #hex pour planning
    actif TINYINT(1) DEFAULT 1
);
```

#### Affectation employé ↔ spécialité (multi)
```sql
CREATE TABLE entretien_user_specialites (
    user_id INT NOT NULL,
    specialite_id INT NOT NULL,
    niveau ENUM('debutant', 'confirme', 'expert') DEFAULT 'confirme',
    certifie TINYINT(1) DEFAULT 0,        -- habilitation/certification ?
    PRIMARY KEY (user_id, specialite_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialite_id) REFERENCES entretien_specialites(id) ON DELETE CASCADE
);
```
- Un employé peut avoir plusieurs spécialités (ex: plombier qui fait aussi peinture)
- `entretien_chef` peut affecter via UI

#### Interventions
```sql
CREATE TABLE entretien_interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    specialite_id INT NOT NULL,
    lot_id INT NULL,                      -- si intervention sur un lot précis
    titre VARCHAR(255) NOT NULL,
    description TEXT NULL,
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    statut ENUM('a_planifier', 'planifiee', 'en_cours', 'terminee', 'annulee') DEFAULT 'a_planifier',
    user_assigne_id INT NULL,             -- employé affecté
    prestataire_externe VARCHAR(200) NULL, -- si sous-traitée (ex: ascensoriste)
    date_signalement DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_planifiee DATETIME NULL,
    date_realisee DATETIME NULL,
    duree_minutes INT NULL,
    cout DECIMAL(10,2) NULL,
    photo_avant VARCHAR(500) NULL,
    photo_apres VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id),
    FOREIGN KEY (specialite_id) REFERENCES entretien_specialites(id),
    FOREIGN KEY (user_assigne_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### Catalogue produits/outils + inventaire (analogue jardinerie/restauration)
```sql
CREATE TABLE entretien_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    specialite_id INT NULL,               -- spécialité associée (NULL = générique)
    categorie ENUM('consommable', 'piece_detachee', 'outillage_main',
                   'outillage_motorise', 'produit_chimique', 'epi', 'autre') NOT NULL,
    type ENUM('produit', 'outil') NOT NULL,
    unite VARCHAR(20) NULL,
    prix_unitaire DECIMAL(8,2) NULL,
    fournisseur_id INT NULL,
    fiche_securite VARCHAR(500) NULL,     -- chemin PDF FDS si produit chimique
    actif TINYINT(1) DEFAULT 1
);

CREATE TABLE entretien_inventaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite_actuelle DECIMAL(10,2) DEFAULT 0,
    seuil_alerte DECIMAL(10,2) NULL,
    emplacement VARCHAR(150) NULL,
    UNIQUE KEY (residence_id, produit_id)
);

CREATE TABLE entretien_inventaire_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    type ENUM('entree', 'sortie', 'ajustement') NOT NULL,
    quantite DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL,
    intervention_id INT NULL,             -- pour quelle intervention (sortie)
    motif VARCHAR(255) NULL,
    date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventaire_id) REFERENCES entretien_inventaire(id) ON DELETE CASCADE
);
```

## Sections de l'application

### Dashboard (`/entretien/index`)
- Interventions urgentes en attente
- Interventions du jour par employé
- Alertes inventaire bas
- Contrôles réglementaires à venir (ascenseur, piscine, électricité)

### Planning (`/entretien/planning`)
- TUI Calendar v1.15.3
- Filtre par spécialité (couleur)
- Affectation interventions aux employés selon spécialités
- Drag & drop pour replanifier
- Endpoints AJAX : `planningAjax($action)`

### Interventions (`/entretien/interventions`)
- Liste filtrable par spécialité, statut, priorité, employé
- Création depuis dashboard ou planning
- Workflow : `a_planifier` → `planifiee` → `en_cours` → `terminee`
- Photo avant/après pour traçabilité

### Spécialités & affectations (`/entretien/affectations`)
- Vue matrice : employés × spécialités
- `entretien_chef` coche/décoche les spécialités de chaque employé
- Affichage niveau et certifications

### Inventaire, commandes, fournisseurs
Structure analogue aux autres modules (restauration, jardinerie).
- Sortie d'inventaire liée à une `intervention_id` (calcul coût intervention)

### Comptabilité entretien (`/entretien/comptabilite`) — MANAGER UNIQUEMENT
- Coût par spécialité, par résidence, par période
- Coût par intervention (sorties inventaire + temps × taux + factures externes)
- Intégration module Comptabilité global (voir @.claude/modules/comptabilite.md)
- ⚠️ `requireRole(ROLES_MANAGER)` strict

### Équipe (`/entretien/equipe`)
- Liste staff entretien de la résidence avec leurs spécialités
- `requireRole(ROLES_MANAGER)`

## Intégration Messagerie
Tous les rôles entretien ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible vers `entretien_chef` / direction
- [ ] Notification messagerie en cas d'intervention urgente

## Règles métier transversales
- Filtrage strict par `residence_id` selon le rôle
- Tout mouvement d'inventaire = ligne dans `entretien_inventaire_mouvements`
- `entretien` / `technicien` ne peuvent jamais accéder à la comptabilité
- Une intervention urgente déclenche une notification (messagerie ou push)
- Photos avant/après obligatoires sur interventions clôturées (recommandé)
- Section Piscine cachée si la résidence n'en a pas (à prévoir dans `coproprietees`)

## ⚠️ Points à résoudre AVANT implémentation

### 1. Affectation employés ↔ sections spécialisées
**Question :** Comment gérer l'affectation des employés aux sections nécessitant une qualification spécifique (piscine, ascenseur) ?

**Pistes :**
- **Option A** : Table `entretien_user_specialites` avec champ `certifie` (utilisée dans le schéma proposé). L'`entretien_chef` coche manuellement.
- **Option B** : Ajouter une table `certifications` séparée avec date d'obtention, validité, organisme certifiant — plus rigoureux pour la traçabilité réglementaire (piscine, électricité, ascenseur ont des obligations de qualification).
- **Option C** : Hybride — affectation libre + alerte si l'employé n'a pas la certification requise.

**À décider :** quelles spécialités exigent une certification obligatoire (piscine = oui, ascenseur souvent sous-traité, électricité partielle) ?

### 2. Relation avec un éventuel module Travaux
**Problème potentiel :** Si un module `Travaux` est créé séparément, il pourrait dupliquer les sections plomberie/électricité.

**Distinction possible :**
| | **Entretien** | **Travaux** |
|--|--|--|
| Type | Interventions ponctuelles, maintenance | Chantiers planifiés, gros œuvre, rénovation |
| Durée | Heures à 1-2 jours | Plusieurs jours à plusieurs mois |
| Périmètre | Fait par staff interne ou prestataire ponctuel | Souvent sous-traité (entreprises) |
| Budget | Petit (< 500€) | Moyen à gros (devis, marchés) |
| Suivi | Statut simple (planifié/fait) | Phases, jalons, réception, garanties |

**Options :**
- **Option A — Modules séparés** : `entretien` (interventions courantes) + `travaux` (chantiers). Réutiliser `entretien_specialites` dans les deux. Risque : duplication tables produits/inventaire.
- **Option B — Module unique `entretien` avec sous-types** : ajouter ENUM `type_intervention('maintenance', 'reparation', 'travaux_chantier')` sur `entretien_interventions`. Plus simple, moins de duplication, mais le module devient gros.
- **Option C — Module `travaux` distinct mais réutilise les ressources** : tables produits/inventaire/fournisseurs partagées avec `entretien`, mais workflow et tables interventions séparées.

**Recommandation initiale :** Option B (module unique avec sous-types) pour démarrer, refactoriser plus tard si le module devient ingérable. Évite la duplication immédiate.

**À décider avec le client (Domitys) avant de commencer.**

## À vérifier lors du dev
- [ ] Tous les endpoints comptables ont `requireRole(ROLES_MANAGER)` strict
- [ ] Mouvements inventaire ne peuvent pas créer de quantité négative (ou alerter)
- [ ] Section Piscine conditionnée à un flag sur `coproprietees` (à définir : `piscine` TINYINT)
- [ ] Section Ascenseur : prestataire externe obligatoire si pas de technicien certifié
- [ ] Photos uploadées : MIME whitelist (jpeg/png/webp), 5 MB max
- [ ] Affectation : alerter si employé non certifié assigné à une intervention nécessitant une certification

## Checklist générale module Entretien
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (y compris AJAX planning, mouvements inventaire)
- [ ] Filtrage par `residence_id` selon rôle
- [ ] **Comptabilité bloquée pour `entretien` / `technicien`** (vérification serveur stricte)
- [ ] `htmlspecialchars()` sur titres interventions, descriptions, notes
- [ ] DataTable sur listes (interventions, produits, inventaire, commandes)
- [ ] Photos avant/après sur interventions clôturées (recommandé)
- [ ] Décision modules `entretien` vs `travaux` validée avant codage
