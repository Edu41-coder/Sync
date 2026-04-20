# Module Travaux (proposition de design)

> ⚠️ Ce fichier décrit une **proposition** de module — rien n'est encore implémenté.
> À valider avec le client (Domitys) avant de commencer le développement.
> Voir aussi @.claude/modules/entretien.md (point à résoudre : entretien vs travaux).

## Objectif
Gérer les **chantiers planifiés** (gros œuvre, rénovation, mises en conformité, gros entretien) sur les résidences seniors, en distinction des interventions courantes du module Entretien.

## Distinction Entretien vs Travaux

| | **Entretien** (module existant proposé) | **Travaux** (ce module) |
|--|--|--|
| Type | Interventions ponctuelles, maintenance | Chantiers planifiés, projets |
| Durée | Heures à 1-2 jours | Plusieurs jours à plusieurs mois |
| Périmètre | Staff interne ou prestataire ponctuel | Quasi systématiquement sous-traité (entreprises) |
| Budget | Petit (< 500 €) | Moyen à très élevé (devis, marchés) |
| Décision | Manager local (`entretien_chef`) | Direction + parfois AG copropriété |
| Suivi | Statut simple (planifié / fait) | Phases, jalons, réception, garanties |
| Financement | Budget courant | Appels de fonds, provision travaux, emprunt |

## Périmètre (proposé)
Controller à créer : `TravauxController`
Vues à créer : `app/views/travaux/`
Modèle à créer : `Travaux`

## Contrôle d'accès

### Rôles autorisés
```php
private const ROLES_TRAVAUX = ['admin', 'directeur_residence', 'exploitant', 'comptable'];
private const ROLES_MANAGER = ['admin', 'directeur_residence', 'exploitant'];
```

### Permissions par section

| Section | `admin` | `directeur_residence` | `exploitant` | `comptable` | `proprietaire` |
|---------|---------|----------------------|--------------|-------------|---------------|
| Consultation chantiers | ✅ | ✅ (sa résidence) | ✅ (ses résidences) | ✅ (résidences affectées) | ✅ (chantiers affectant ses lots) |
| Création / édition chantier | ✅ | ✅ | ✅ | ❌ | ❌ |
| Validation devis | ✅ | ✅ | ✅ | ❌ | ❌ |
| Saisie factures | ✅ | ✅ | ✅ | ✅ | ❌ |
| Comptabilité travaux | ✅ | ✅ | ✅ | ✅ | ❌ |
| Validation réception | ✅ | ✅ | ✅ | ❌ | ❌ |

⚠️ Le rôle `proprietaire` accède en **lecture seule** aux chantiers qui affectent ses lots, avec sa quote-part de coût.

## Workflow d'un chantier (8 phases)

```
1. Demande / Diagnostic
   ↓
2. Cahier des charges
   ↓
3. Demande de devis (multi-prestataires)
   ↓
4. Comparaison devis + Décision (interne ou AG si vote requis)
   ↓
5. Commande / Bon de commande
   ↓
6. Exécution (suivi avancement, jalons)
   ↓
7. Réception (PV de réception, réserves éventuelles)
   ↓
8. Garanties (parfait achèvement 1 an, biennale 2 ans, décennale 10 ans)
```

## Modèle de données proposé

### Chantiers
```sql
CREATE TABLE travaux_chantiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT NULL,
    categorie ENUM('gros_oeuvre', 'second_oeuvre', 'plomberie', 'electricite',
                   'chauffage', 'peinture', 'toiture', 'facade', 'ascenseur',
                   'mise_aux_normes', 'amenagement', 'autre') NOT NULL,
    phase ENUM('diagnostic', 'cahier_charges', 'devis', 'decision',
               'commande', 'execution', 'reception', 'garantie', 'cloture') DEFAULT 'diagnostic',
    statut ENUM('actif', 'suspendu', 'termine', 'annule') DEFAULT 'actif',
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    necessite_ag TINYINT(1) DEFAULT 0,        -- vote AG requis ?
    ag_id INT NULL,                            -- FK vers `assemblees_generales`
    date_debut_prevue DATE NULL,
    date_fin_prevue DATE NULL,
    date_debut_reelle DATE NULL,
    date_fin_reelle DATE NULL,
    montant_estime DECIMAL(12,2) NULL,
    montant_engage DECIMAL(12,2) DEFAULT 0,
    montant_paye DECIMAL(12,2) DEFAULT 0,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id),
    FOREIGN KEY (ag_id) REFERENCES assemblees_generales(id) ON DELETE SET NULL
);
```

### Lots impactés (calcul quote-part propriétaires)
```sql
CREATE TABLE travaux_lots_impactes (
    chantier_id INT NOT NULL,
    lot_id INT NOT NULL,
    quote_part_pourcentage DECIMAL(5,2) DEFAULT 0,    -- % du coût pour ce lot
    PRIMARY KEY (chantier_id, lot_id),
    FOREIGN KEY (chantier_id) REFERENCES travaux_chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE
);
```
- Si chantier sur partie commune : tous les lots impactés au prorata des tantièmes
- Si chantier sur lot précis : quote-part 100% sur ce lot

### Devis (multi-prestataires)
```sql
CREATE TABLE travaux_devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    reference VARCHAR(100) NULL,                       -- réf devis prestataire
    date_devis DATE NOT NULL,
    date_validite DATE NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    tva_pourcentage DECIMAL(5,2) DEFAULT 20.00,
    montant_ttc DECIMAL(12,2) NOT NULL,
    delai_execution_jours INT NULL,
    fichier_pdf VARCHAR(500) NULL,
    statut ENUM('recu', 'analyse', 'retenu', 'refuse') DEFAULT 'recu',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chantier_id) REFERENCES travaux_chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id)
);
```

### Jalons / phases d'avancement
```sql
CREATE TABLE travaux_jalons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,                          -- "Démolition", "Câblage", etc.
    date_prevue DATE NULL,
    date_realisee DATE NULL,
    pourcentage_avancement INT DEFAULT 0,                -- 0 à 100
    notes TEXT NULL,
    FOREIGN KEY (chantier_id) REFERENCES travaux_chantiers(id) ON DELETE CASCADE
);
```

### Documents (devis signés, plans, photos, PV réception)
```sql
CREATE TABLE travaux_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    type ENUM('devis_signe', 'plan', 'photo_avant', 'photo_apres',
             'photo_chantier', 'pv_reception', 'facture', 'garantie',
             'attestation', 'autre') NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100),
    taille_octets BIGINT,
    uploaded_by INT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chantier_id) REFERENCES travaux_chantiers(id) ON DELETE CASCADE
);
```

### Réception et garanties
```sql
CREATE TABLE travaux_receptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    date_reception DATE NOT NULL,
    avec_reserves TINYINT(1) DEFAULT 0,
    reserves_description TEXT NULL,
    reserves_levees DATE NULL,
    pv_pdf VARCHAR(500) NULL,
    signe_par_id INT NULL,                               -- user qui signe côté Domitys
    FOREIGN KEY (chantier_id) REFERENCES travaux_chantiers(id) ON DELETE CASCADE
);

CREATE TABLE travaux_garanties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    type ENUM('parfait_achevement', 'biennale', 'decennale') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    fournisseur_id INT NULL,
    notes TEXT NULL,
    FOREIGN KEY (chantier_id) REFERENCES travaux_chantiers(id) ON DELETE CASCADE
);
```
- À la réception (statut → `garantie`), création automatique des 3 lignes de garantie

### Lien avec assemblées générales
- Réutilisation table existante `assemblees_generales` (voir @.claude/database.md)
- Si `travaux_chantiers.necessite_ag = 1`, le chantier doit être lié à une AG votée
- Décision AG = blocage transition phase `decision` → `commande` tant que vote pas favorable

### Lien avec appels de fonds
- Réutilisation tables existantes `appels_fonds` + `lignes_appel_fonds`
- Un appel de fonds peut financer un ou plusieurs chantiers (lien à prévoir)

## Sections fonctionnelles

### Dashboard (`/travaux/index`)
- Chantiers en cours par phase
- Chantiers nécessitant une décision (devis à comparer)
- Échéances proches (jalons en retard, réceptions à venir)
- Garanties expirant dans les 6 mois
- Budget consommé vs estimé (alertes dépassement)

### Liste chantiers (`/travaux/chantiers`)
- Filtres : résidence, catégorie, phase, statut, priorité
- Tri par date, montant, avancement
- DataTable (tri + recherche + pagination)

### Fiche chantier (`/travaux/show/{id}`)
- Onglets : Détails / Devis / Jalons / Documents / Comptabilité / Réception / Garanties
- Timeline visuelle des phases
- Galerie photos avant/après

### Comparateur de devis (`/travaux/devis/{chantierId}`)
- Tableau comparatif côte à côte (montant HT/TTC, délai, références)
- Bouton "Retenir ce devis" → passe en phase `commande`

### Comptabilité travaux (`/travaux/comptabilite`)
- Coût total par chantier, par résidence, par catégorie
- Suivi engagé vs payé
- Intégration module Comptabilité global (voir @.claude/modules/comptabilite.md)
- Quote-part par lot/propriétaire (export pour appels de fonds)

### Suivi garanties (`/travaux/garanties`)
- Liste de toutes les garanties actives
- Alertes 3 mois avant expiration (action préventive : vérifier état)
- Lien rapide vers le chantier d'origine

## Espace propriétaire — vue chantiers
Dans `/coproprietaire/travaux` :
- Liste des chantiers passés/en cours/à venir affectant SES lots
- Sa quote-part de coût pour chaque chantier
- Documents accessibles : PV de réception, factures, garanties (lecture seule)
- Lien depuis le dashboard propriétaire

⚠️ Filtrage strict : un propriétaire ne voit QUE les chantiers où l'un de ses lots apparaît dans `travaux_lots_impactes`.

## Intégration Messagerie
Tous les rôles travaux ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Notification messagerie aux propriétaires impactés à la création d'un chantier
- [ ] Notification quand un devis est retenu / chantier démarre
- [ ] Notification PV de réception disponible

## Sections IA potentielles (futures)
- Comparaison automatique de devis (analyse postes, alerte écarts anormaux)
- Génération de cahier des charges depuis description en langage naturel
- Détection de garanties oubliées sur chantiers anciens
- Estimation de coût d'un nouveau chantier basée sur historique

## ⚠️ Points à résoudre AVANT implémentation

### 1. Frontière exacte avec module Entretien
À trancher en amont (voir @.claude/modules/entretien.md).
Recommandation initiale : **module unique `entretien` avec ENUM `type_intervention`** pour démarrer, puis split si nécessaire. Mais si Domitys a déjà des process distincts (équipe interne entretien vs sous-traitants travaux), modules séparés peuvent se justifier.

### 2. Vote AG : quelles catégories ?
- Quelles catégories de travaux nécessitent obligatoirement un vote en AG ?
- Quel seuil de montant déclenche un vote (ex: > 10 000 € HT) ?
- À configurer dans une table `travaux_regles_ag` ou en dur ?

### 3. Quote-part propriétaires : calcul automatique ?
- Pour les parties communes, recalculer automatiquement les quote-parts au prorata des tantièmes ?
- Stocker les tantièmes dans la table `lots` (ajouter colonne `tantiemes` INT) ?
- Possibilité de surcharger manuellement en cas de répartition spéciale ?

### 4. Stockage des documents
- Volume potentiellement important (PDFs devis, photos chantier, plans)
- Quota par résidence ? Par chantier ?
- Stockage : `uploads/travaux/{residence_id}/{chantier_id}/`
- Hors `public/` (sécurité)

### 5. Rétention des données
- Garanties décennales = obligation de conserver 10 ans minimum les docs liés
- Prévoir politique d'archivage et backup

## À vérifier lors du dev
- [ ] Filtrage strict `residence_id` selon rôle
- [ ] Filtrage propriétaire via `travaux_lots_impactes` (jamais accès aux autres chantiers)
- [ ] Phase `commande` interdite tant que `necessite_ag = 1` et AG pas favorable
- [ ] Création automatique des 3 garanties à la réception
- [ ] Alertes garanties expirantes (cron ou à l'affichage dashboard)
- [ ] Documents stockés HORS `public/` (téléchargement via controller authentifié)
- [ ] Quote-part somme = 100% (validation à la sauvegarde)

## Checklist générale module Travaux
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (création chantier, validation devis, upload doc, signature PV)
- [ ] Filtrage par `residence_id` selon rôle
- [ ] Comptabilité bloquée pour rôles non autorisés
- [ ] Propriétaire voit uniquement chantiers impactant ses lots
- [ ] `htmlspecialchars()` sur titres, descriptions, notes, réserves
- [ ] DataTable sur listes (chantiers, devis, jalons, documents, garanties)
- [ ] Upload documents : MIME whitelist (PDF, images, plans), 50 MB max
- [ ] Stockage hors `public/`, accès via controller authentifié
- [ ] Décision modules `entretien` vs `travaux` validée AVANT codage
