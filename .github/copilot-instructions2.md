# Logiciel de Gestion Immobilière et Syndic - Synd_Gest

## 📋 Description du Projet
Application web de gestion immobilière et de syndic de copropriété similaire à PartnerImmo.
- **Technologies**: PHP 8.0+, MariaDB (XAMPP), JavaScript, HTML5, CSS3
- **Architecture**: MVC (Model-View-Controller) avec routeur personnalisé
- **Framework CSS**: Bootstrap 5.3 (classes Bootstrap uniquement)
- **Icônes**: Font Awesome 6
- **Type**: Application web full-stack
- **Public cible**: Syndics de copropriété, gestionnaires immobiliers, administrateurs de biens

## 🏗️ Architecture MVC

### Structure du projet :
```
Synd_Gest/
├── app/
│   ├── core/               # Classes core du framework MVC
│   │   ├── Router.php      # Routeur URL personnalisé
│   │   ├── Controller.php  # Contrôleur de base
│   │   └── Model.php       # Modèle de base avec PDO
│   ├── controllers/        # Contrôleurs (logique métier)
│   │   ├── HomeController.php
│   │   ├── AuthController.php
│   │   └── ...
│   ├── models/            # Modèles (accès aux données)
│   │   ├── User.php
│   │   └── ...
│   └── views/             # Vues (templates HTML)
│       ├── layouts/       # Layouts principaux
│       ├── partials/      # Composants réutilisables
│       ├── home/          # Vues du HomeController
│       ├── auth/          # Vues du AuthController
│       └── ...
├── public/                # Point d'entrée accessible (DocumentRoot)
│   ├── index.php          # Front controller
│   ├── .htaccess          # URL rewriting
│   ├── assets/            # Ressources statiques
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/           # Fichiers uploadés
├── config/                # Configuration
│   ├── database.php       # Config base de données
│   ├── config.php         # Config générale
│   └── constants.php      # Constantes
├── database/              # Migrations SQL
│   └── schema.sql
└── logs/                  # Logs applicatifs
```

### Principes MVC :
1. **Model** : Gère les données et la logique métier (interaction avec la BDD)
2. **View** : Affiche les données (templates HTML avec PHP)
3. **Controller** : Gère les requêtes, appelle les modèles et les vues

### Routage :
- URL format : `http://localhost/Synd_Gest/public/controller/method/param1/param2`
- Exemple : `http://localhost/Synd_Gest/public/auth/login`
- Par défaut : `HomeController::index()`

## 🎨 Design et Styles

### Framework CSS : Bootstrap 5.3
- **Utiliser UNIQUEMENT les classes Bootstrap** pour tout le styling
- Ne pas créer de CSS personnalisé complexe sauf si absolument nécessaire
- Classes Bootstrap à privilégier : `container`, `row`, `col-*`, `card`, `btn`, `form-control`, `navbar`, etc.
- Système de grille responsive : `col-12`, `col-md-6`, `col-lg-4`, etc.
- Utilitaires spacing : `m-*`, `p-*`, `mt-*`, `mb-*`, `mx-auto`, etc.
- Utilitaires couleurs : `bg-*`, `text-*`, `border-*`

### Icônes : Font Awesome 6
- **Utiliser Font Awesome 6** pour toutes les icônes
- Format : `<i class="fas fa-icon-name"></i>` ou `<i class="far fa-icon-name"></i>`
- Exemples : `fa-building`, `fa-user`, `fa-home`, `fa-cog`, `fa-sign-out-alt`, etc.
- Tailles : `fa-xs`, `fa-sm`, `fa-lg`, `fa-2x`, `fa-3x`, etc.

### Composants Bootstrap à utiliser :
- **Cards** : Pour afficher du contenu structuré
- **Navbar** : Pour la navigation principale
- **Forms** : `form-control`, `form-label`, `input-group`, `form-select`
- **Buttons** : `btn btn-primary`, `btn btn-success`, `btn btn-danger`, etc.
- **Alerts** : Pour les messages flash (success, error, warning, info)
- **Modal** : Pour les dialogues et confirmations
- **Tables** : `table`, `table-striped`, `table-hover`, `table-bordered`
- **Badges** : Pour afficher les statuts
- **Dropdowns** : Pour les menus déroulants
- **Breadcrumb** : Pour la navigation
- **Pagination** : Pour les listes paginées

### Palette de couleurs Bootstrap :
- Primary (bleu) : Actions principales
- Success (vert) : Validation, succès
- Danger (rouge) : Erreurs, suppressions
- Warning (jaune) : Avertissements
- Info (cyan) : Informations
- Secondary (gris) : Actions secondaires
- Light/Dark : Fond et texte

---

## 🎯 Fonctionnalités Principales

### 1. GESTION DES COPROPRIÉTÉS
- Création et gestion des fiches copropriétés
- Informations générales (adresse, nombre de lots, règlement)
- Documents de copropriété (règlement, procès-verbaux AG)
- Planning des assemblées générales
- Suivi des travaux votés et réalisés
- Gestion des clés et accès

### 2. GESTION DES LOTS
- Fiches détaillées des lots (appartements, caves, parkings)
- Tantièmes et quotes-parts
- Historique des propriétaires
- Documents associés aux lots
- Equipements et caractéristiques

### 3. GESTION DES COPROPRIÉTAIRES
- Base de données des copropriétaires
- Coordonnées et informations de contact
- Lots possédés par copropriétaire
- Historique des communications
- Espace personnel sécurisé
- Gestion des mandats

### 4. GESTION LOCATIVE
- Fiches locataires
- Contrats de location
- Quittances de loyer
- Révisions de loyer
- États des lieux (entrée/sortie)
- Dépôts de garantie
- Assurances locataires

### 5. COMPTABILITÉ COPROPRIÉTÉ
- Plan comptable syndic
- Appels de fonds (provisions)
- Budget prévisionnel
- Comptes individuels copropriétaires
- Rapprochements bancaires
- Clôture d'exercice
- Répartition des charges
- Comptes spéciaux (travaux, fonds de roulement)

### 6. GESTION DES CHARGES
- Charges courantes (eau, électricité, entretien)
- Charges exceptionnelles (travaux)
- Répartition selon tantièmes
- Régularisation annuelle
- Suivi des impayés

### 7. FACTURATION ET PAIEMENTS
- Émission des factures
- Gestion des échéanciers
- Suivi des paiements
- Relances automatiques
- Historique des transactions
- États de rapprochement

### 8. GESTION DES FOURNISSEURS
- Base de données fournisseurs
- Contrats d'entretien
- Factures fournisseurs
- Bons de commande
- Suivi des interventions

### 9. GESTION DES TRAVAUX
- Demandes de travaux
- Devis et comparatifs
- Planning des travaux
- Suivi de l'avancement
- Réception des travaux
- Garanties

### 10. GESTION DES SINISTRES
- Déclaration de sinistres
- Suivi des dossiers
- Correspondance avec assurances
- Indemnisations
- Documents justificatifs

### 11. COMMUNICATION
- Messagerie interne
- Envoi d'emails groupés
- Notifications automatiques
- Historique des communications
- Affichage de notes d'information

### 12. DOCUMENTS ET GED
- Stockage centralisé de documents
- Catégorisation des documents
- Recherche et filtres
- Téléchargement et partage
- Versions et historique

### 13. REPORTING ET STATISTIQUES
- Tableaux de bord
- Statistiques financières
- Rapports d'activité
- Taux d'impayés
- Exports Excel/PDF

### 14. ADMINISTRATION
- Gestion des utilisateurs
- Rôles et permissions
- Paramétrage de l'application
- Logs d'activité
- Sauvegardes

---

## 🗄️ Structure de la Base de Données (MariaDB)

### Tables Principales

#### 1. **users** (Utilisateurs du système)
```sql
- id (PK)
- username
- email
- password_hash
- role (admin, gestionnaire, copropriétaire, locataire)
- first_name
- last_name
- phone
- active
- created_at
- updated_at
- last_login
```

#### 2. **coproprietees** (Copropriétés)
```sql
- id (PK)
- nom
- adresse
- code_postal
- ville
- nombre_lots
- nombre_batiments
- date_construction
- syndic_id (FK users)
- reglement_document
- immatriculation
- created_at
- updated_at
```

#### 3. **lots** (Lots de copropriété)
```sql
- id (PK)
- copropriete_id (FK)
- numero_lot
- type (appartement, parking, cave, commerce)
- etage
- surface
- tantiemes_generaux
- tantiemes_chauffage
- tantiemes_ascenseur
- nombre_pieces
- description
- created_at
- updated_at
```

#### 4. **coproprietaires** (Copropriétaires)
```sql
- id (PK)
- user_id (FK users)
- civilite
- nom
- prenom
- date_naissance
- adresse_principale
- code_postal
- ville
- telephone
- email
- profession
- created_at
- updated_at
```

#### 5. **possessions** (Relation Copropriétaire-Lot)
```sql
- id (PK)
- coproprietaire_id (FK)
- lot_id (FK)
- date_acquisition
- date_cession
- pourcentage_possession
- mandat_gestion
- actif
- created_at
```

#### 6. **locataires** (Locataires)
```sql
- id (PK)
- user_id (FK users)
- civilite
- nom
- prenom
- date_naissance
- telephone
- email
- employeur
- garant_nom
- garant_telephone
- created_at
- updated_at
```

#### 7. **baux** (Contrats de location)
```sql
- id (PK)
- lot_id (FK)
- locataire_id (FK)
- date_debut
- date_fin
- loyer_mensuel
- charges_mensuelles
- depot_garantie
- type_bail (vide, meublé)
- indexation
- etat
- created_at
- updated_at
```

#### 8. **comptes_comptables** (Plan comptable)
```sql
- id (PK)
- numero_compte
- libelle
- type (actif, passif, charge, produit)
- parent_id (FK self)
- actif
```

#### 9. **exercices_comptables** (Exercices)
```sql
- id (PK)
- copropriete_id (FK)
- annee
- date_debut
- date_fin
- budget_previsionnel
- statut (ouvert, cloture)
- created_at
```

#### 10. **appels_fonds** (Appels de charges)
```sql
- id (PK)
- copropriete_id (FK)
- exercice_id (FK)
- trimestre
- date_emission
- date_echeance
- montant_total
- type (provision, regularisation, travaux)
- statut
- created_at
```

#### 11. **lignes_appel_fonds** (Détail appels par copropriétaire)
```sql
- id (PK)
- appel_fonds_id (FK)
- coproprietaire_id (FK)
- lot_id (FK)
- montant
- statut_paiement (attente, partiel, paye, impaye)
- date_paiement
- mode_paiement
```

#### 12. **ecritures_comptables** (Comptabilité)
```sql
- id (PK)
- copropriete_id (FK)
- exercice_id (FK)
- date_ecriture
- compte_debit (FK comptes_comptables)
- compte_credit (FK comptes_comptables)
- montant
- libelle
- piece_justificative
- type_piece (facture, paiement, od)
- created_at
```

#### 13. **fournisseurs** (Fournisseurs)
```sql
- id (PK)
- nom
- siret
- adresse
- code_postal
- ville
- telephone
- email
- type_service
- actif
- created_at
- updated_at
```

#### 14. **factures_fournisseurs** (Factures)
```sql
- id (PK)
- fournisseur_id (FK)
- copropriete_id (FK)
- numero_facture
- date_facture
- date_echeance
- montant_ht
- montant_tva
- montant_ttc
- type_charge
- statut (attente, validee, payee)
- document
- created_at
```

#### 15. **travaux** (Travaux)
```sql
- id (PK)
- copropriete_id (FK)
- titre
- description
- type (entretien, reparation, amelioration)
- urgence (basse, moyenne, haute)
- budget_estime
- date_vote_ag
- statut (devis, vote, planifie, encours, termine)
- date_debut
- date_fin_prevue
- date_fin_reelle
- created_at
- updated_at
```

#### 16. **devis** (Devis travaux)
```sql
- id (PK)
- travaux_id (FK)
- fournisseur_id (FK)
- date_devis
- montant_ht
- montant_ttc
- delai_execution
- validite
- selectionne
- document
- created_at
```

#### 17. **sinistres** (Sinistres)
```sql
- id (PK)
- copropriete_id (FK)
- lot_id (FK)
- date_sinistre
- type (dégât des eaux, incendie, vol, autre)
- description
- montant_estime
- assurance
- numero_dossier_assurance
- statut (declaré, expertise, indemnisation, clos)
- created_at
- updated_at
```

#### 18. **documents** (GED - Gestion documentaire)
```sql
- id (PK)
- nom_fichier
- chemin_fichier
- type_document
- categorie
- copropriete_id (FK)
- lot_id (FK)
- coproprietaire_id (FK)
- uploaded_by (FK users)
- taille_fichier
- date_upload
- created_at
```

#### 19. **assemblees_generales** (AG)
```sql
- id (PK)
- copropriete_id (FK)
- type (ordinaire, extraordinaire)
- date_ag
- lieu
- ordre_du_jour
- proces_verbal
- statut (planifiee, tenue, annulee)
- created_at
```

#### 20. **messages** (Communication)
```sql
- id (PK)
- expediteur_id (FK users)
- destinataire_id (FK users)
- copropriete_id (FK)
- sujet
- contenu
- date_envoi
- lu
- date_lecture
```

#### 21. **notifications** (Notifications système)
```sql
- id (PK)
- user_id (FK users)
- type
- titre
- message
- lien
- lu
- created_at
```

#### 22. **parametres** (Configuration)
```sql
- id (PK)
- cle
- valeur
- description
- type (string, number, boolean, json)
```

#### 23. **logs_activite** (Logs)
```sql
- id (PK)
- user_id (FK users)
- action
- table_name
- record_id
- details
- ip_address
- created_at
```

---

## 📁 Structure MVC des Contrôleurs et Vues

### Contrôleurs à créer (app/controllers/) :
- **HomeController.php** - ✅ Créé
- **AuthController.php** - ✅ Créé
- **CoproprieteController.php** - Gestion des copropriétés
- **LotController.php** - Gestion des lots
- **CoproprietaireController.php** - Gestion des copropriétaires
- **LocataireController.php** - Gestion des locataires
- **BailController.php** - Gestion des baux
- **ComptabiliteController.php** - Gestion comptable
- **ChargeController.php** - Appels de fonds et charges
- **FournisseurController.php** - Gestion des fournisseurs
- **TravauxController.php** - Gestion des travaux
- **SinistreController.php** - Gestion des sinistres
- **AssembleeController.php** - Assemblées générales
- **DocumentController.php** - Gestion documentaire
- **MessageController.php** - Communication
- **ReportController.php** - Reporting
- **AdminController.php** - Administration

### Modèles à créer (app/models/) :
- **User.php** - ✅ Créé
- **Copropriete.php**
- **Lot.php**
- **Coproprietaire.php**
- **Locataire.php**
- **Bail.php**
- **CompteComptable.php**
- **ExerciceComptable.php**
- **AppelFonds.php**
- **EcritureComptable.php**
- **Fournisseur.php**
- **FactureFournisseur.php**
- **Travaux.php**
- **Devis.php**
- **Sinistre.php**
- **AssembleeGenerale.php**
- **Document.php**
- **Message.php**

### Vues à créer (app/views/) :
```
views/
├── layouts/
│   └── main.php              # ✅ Layout principal créé
├── partials/
│   ├── flash.php             # ✅ Messages flash créé
│   ├── navbar.php            # Navbar à créer
│   └── sidebar.php           # Sidebar à créer
├── home/
│   └── index.php             # ✅ Tableau de bord créé
├── auth/
│   ├── login.php             # ✅ Page de connexion créée
│   └── register.php          # Page création utilisateur à créer
├── coproprietes/
│   ├── index.php             # Liste des copropriétés
│   ├── view.php              # Détails d'une copropriété
│   ├── create.php            # Créer une copropriété
│   └── edit.php              # Modifier une copropriété
├── lots/
│   ├── index.php
│   ├── view.php
│   ├── create.php
│   └── edit.php
├── coproprietaires/
│   ├── index.php
│   ├── view.php
│   ├── create.php
│   └── edit.php
└── ... (autres modules)
```

---

## 🔧 Étapes de Développement

### PHASE 1: CONFIGURATION ET BASE
**Durée estimée: 1-2 semaines**

1. **Installation de l'environnement**
   - ✅ Installer XAMPP (Apache, PHP, MariaDB)
   - ✅ Configurer les chemins et variables d'environnement
   - ✅ Créer la base de données `synd_gest`

2. **Création de la structure**
   - ✅ Créer l'arborescence des dossiers
   - ✅ Configurer .htaccess pour URL rewriting
   - ✅ Créer config/database.php avec PDO

3. **Base de données**
   - ✅ Créer le schéma complet SQL
   - ✅ Définir les relations et clés étrangères
   - ✅ Ajouter les indexes pour performances
   - ✅ Insérer données de test

4. **Système d'authentification**
   - ✅ Créer classe Auth.php
   - ✅ Page de connexion avec sessions
   - ✅ Page d'inscription
   - ✅ Gestion des mots de passe (hash)
   - ✅ Récupération de mot de passe
   - ✅ Système de rôles et permissions

### PHASE 2: GESTION DES ENTITÉS PRINCIPALES
**Durée estimée: 3-4 semaines**

5. **Module Copropriétés**
   - ✅ CRUD copropriétés
   - ✅ Affichage détaillé
   - ✅ Upload de documents
   - ✅ Validation des formulaires

6. **Module Lots**
   - ✅ CRUD lots
   - ✅ Association avec copropriété
   - ✅ Gestion des tantièmes
   - ✅ Historique des modifications

7. **Module Copropriétaires**
   - ✅ CRUD copropriétaires
   - ✅ Association copropriétaires-lots
   - ✅ Gestion des dates de possession
   - ✅ Espace personnel copropriétaire

8. **Module Locataires et Baux**
   - ✅ CRUD locataires
   - ✅ Gestion des baux
   - ✅ Génération de quittances de loyer
   - ✅ États des lieux

### PHASE 3: COMPTABILITÉ ET FINANCES
**Durée estimée: 4-5 semaines**

9. **Plan comptable**
   - ✅ Créer plan comptable syndic
   - ✅ Gestion des comptes
   - ✅ Hiérarchie des comptes

10. **Exercices comptables**
    - ✅ Création d'exercices
    - ✅ Budget prévisionnel
    - ✅ Clôture d'exercice

11. **Appels de fonds**
    - ✅ Génération automatique des appels
    - ✅ Répartition par copropriétaire
    - ✅ Calcul selon tantièmes
    - ✅ Édition PDF

12. **Écritures comptables**
    - ✅ Saisie des écritures
    - ✅ Journaux comptables
    - ✅ Grand livre
    - ✅ Balance générale

13. **Gestion des paiements**
    - ✅ Enregistrement des paiements
    - ✅ Rapprochement bancaire
    - ✅ Suivi des impayés
    - ✅ Relances automatiques

### PHASE 4: GESTION OPÉRATIONNELLE
**Durée estimée: 3-4 semaines**

14. **Module Fournisseurs**
    - ✅ CRUD fournisseurs
    - ✅ Gestion des contrats
    - ✅ Suivi des interventions

15. **Facturation fournisseurs**
    - ✅ Saisie factures
    - ✅ Validation et paiement
    - ✅ Imputation comptable

16. **Module Travaux**
    - ✅ Gestion des demandes
    - ✅ Système de devis
    - ✅ Comparaison devis
    - ✅ Planning travaux
    - ✅ Suivi d'avancement

17. **Module Sinistres**
    - ✅ Déclaration sinistres
    - ✅ Suivi des dossiers
    - ✅ Interface avec assurances

### PHASE 5: COMMUNICATION ET DOCUMENTS
**Durée estimée: 2-3 semaines**

18. **Assemblées Générales**
    - ✅ Planification AG
    - ✅ Génération convocations
    - ✅ Ordre du jour
    - ✅ Procès-verbaux

19. **Gestion documentaire (GED)**
    - ✅ Upload de documents
    - ✅ Catégorisation
    - ✅ Recherche avancée
    - ✅ Contrôle d'accès par rôle
    - ✅ Versioning

20. **Messagerie interne**
    - ✅ Système de messages
    - ✅ Notifications
    - ✅ Envoi d'emails groupés
    - ✅ Templates d'emails

### PHASE 6: REPORTING ET ADMINISTRATION
**Durée estimée: 2-3 semaines**

21. **Tableaux de bord**
    - ✅ Dashboard admin
    - ✅ Dashboard gestionnaire
    - ✅ Dashboard copropriétaire
    - ✅ Graphiques et statistiques

22. **Rapports**
    - ✅ Rapports financiers
    - ✅ Rapports d'impayés
    - ✅ Rapports de travaux
    - ✅ Exports Excel/PDF

23. **Module Administration**
    - ✅ Gestion des utilisateurs
    - ✅ Gestion des rôles
    - ✅ Paramètres système
    - ✅ Logs d'activité
    - ✅ Sauvegarde/Restauration

### PHASE 7: OPTIMISATION ET SÉCURITÉ
**Durée estimée: 2 semaines**

24. **Sécurité**
    - ✅ Protection CSRF
    - ✅ Validation des inputs
    - ✅ Prévention SQL injection
    - ✅ Prévention XSS
    - ✅ HTTPS
    - ✅ Sessions sécurisées

25. **Optimisation**
    - ✅ Cache
    - ✅ Optimisation requêtes SQL
    - ✅ Compression assets
    - ✅ Lazy loading

26. **Responsive Design**
    - ✅ Adaptation mobile
    - ✅ Adaptation tablette
    - ✅ Tests multi-navigateurs

### PHASE 8: TESTS ET DÉPLOIEMENT
**Durée estimée: 1-2 semaines**

27. **Tests**
    - ✅ Tests fonctionnels
    - ✅ Tests de sécurité
    - ✅ Tests de performance
    - ✅ Tests utilisateurs

28. **Documentation**
    - ✅ Manuel utilisateur
    - ✅ Guide administrateur
    - ✅ Documentation technique
    - ✅ Documentation API

29. **Déploiement**
    - ✅ Configuration serveur production
    - ✅ Migration données
    - ✅ Formation utilisateurs
    - ✅ Support et maintenance

---

## 🛠️ Technologies et Bibliothèques

### Backend (MVC)
- **PHP 8.0+** avec PDO (Prepared Statements)
- **Architecture MVC personnalisée** (Router, Controller, Model)
- **MariaDB 10.x**
- **Sessions PHP** pour authentification
- **PHPMailer** pour envoi d'emails (à intégrer)
- **TCPDF** ou **mPDF** pour génération PDF (à intégrer)

### Frontend (Bootstrap + Font Awesome)
- **HTML5 / CSS3**
- **Bootstrap 5.3** (CDN) - Framework CSS principal
  - Utiliser UNIQUEMENT les classes Bootstrap
  - Système de grille responsive
  - Composants : cards, forms, buttons, navbar, alerts, modals, tables
- **Font Awesome 6** (CDN) - Bibliothèque d'icônes
  - Utiliser pour toutes les icônes de l'application
  - Format : `<i class="fas fa-icon-name"></i>`
- **JavaScript ES6+** (vanilla JS)
- **jQuery 3.7** (pour compatibilité avec Bootstrap)
- **DataTables** (à intégrer) - Pour tableaux avancés
- **Chart.js** (à intégrer) - Pour graphiques
- **Select2** (à intégrer) - Pour sélections avancées
- **Flatpickr** (à intégrer) - Pour date pickers

### Outils de développement
- **XAMPP** (Apache, PHP, MariaDB)
- **Git** pour versioning
- **Composer** pour dépendances PHP (optionnel)
- **VS Code** avec extensions PHP

---

## 🔐 Sécurité

### Mesures essentielles
1. **Authentification sécurisée**
   - Hashage passwords (password_hash/verify)
   - Sessions avec jetons CSRF
   - Limitation tentatives de connexion
   - Déconnexion automatique après inactivité

2. **Protection des données**
   - Validation stricte des inputs
   - Préparation des requêtes SQL (PDO)
   - Échappement des outputs (htmlspecialchars)
   - Protection XSS et CSRF

3. **Contrôle d'accès**
   - Système de rôles granulaire
   - Vérification des permissions à chaque action
   - Logs des actions sensibles

4. **Protection des fichiers**
   - Validation type MIME
   - Limitation taille uploads
   - Stockage hors web root
   - Renommage sécurisé

---

## 📊 Indicateurs de Performance

### KPIs à suivre
- Nombre de copropriétés gérées
- Nombre de lots/copropriétaires
- Taux de recouvrement des charges
- Délai moyen de traitement des travaux
- Nombre de documents stockés
- Taux d'utilisation des espaces personnels
- Satisfaction utilisateurs

---

## 🚀 Roadmap des Fonctionnalités Futures

### Version 2.0
- API REST complète
- Application mobile (iOS/Android)
- Signature électronique documents
- Intégration bancaire automatique
- OCR pour factures
- Chatbot d'assistance
- Portail fournisseurs

### Version 3.0
- Intelligence artificielle (prédiction charges)
- Blockchain pour votes AG
- Plateforme marketplace services
- Intégration domotique
- Réalité augmentée (visites virtuelles)

---

## 📝 Conventions de Code

### PHP (Architecture MVC)
- **Contrôleurs** : PascalCase avec suffixe `Controller` (ex: `HomeController`)
- **Modèles** : PascalCase, singulier (ex: `User`, `Copropriete`)
- **Méthodes** : camelCase (ex: `index()`, `findByUsername()`)
- **Variables** : camelCase (ex: `$userName`, `$isActive`)
- **Classes** : Une classe par fichier
- **DocBlocks** : Obligatoires pour classes et méthodes publiques
- **Try-catch** : Pour toutes les opérations critiques

### Routage MVC
- Format URL : `/controller/method/param1/param2`
- Contrôleur par défaut : `HomeController`
- Méthode par défaut : `index()`
- Exemple : `/copropriete/view/5` → `CoproprieteController::view(5)`

### Base de données
- **Tables** : snake_case, pluriel (ex: `users`, `coproprietes`)
- **Colonnes** : snake_case (ex: `user_id`, `date_creation`)
- **Clés primaires** : `id` (auto-increment)
- **Clés étrangères** : `table_id` (ex: `user_id`, `copropriete_id`)
- **Dates** : `created_at`, `updated_at` (timestamp)

### Vues (Bootstrap)
- **Utiliser UNIQUEMENT des classes Bootstrap 5** pour le styling
- **Structure HTML** : Sémantique et accessible
- **Composants Bootstrap** : cards, forms, buttons, navbar, alerts, modals, tables
- **Grid system** : `container`, `row`, `col-*`
- **Spacing** : `m-*`, `p-*`, `mt-*`, `mb-*`, `mx-auto`
- **Couleurs** : `bg-primary`, `text-danger`, `btn-success`
- **Icons** : Font Awesome uniquement (`<i class="fas fa-icon"></i>`)

### JavaScript
- **Variables** : camelCase (ex: `userName`, `isActive`)
- **Fonctions** : camelCase (ex: `loadData()`, `validateForm()`)
- **Classes** : PascalCase (ex: `DataTable`, `FormValidator`)
- **Constantes** : UPPER_SNAKE_CASE (ex: `API_URL`, `MAX_ITEMS`)
- **Use strict mode** : `'use strict';` en début de fichier
- **Commentaires** : JSDoc pour fonctions complexes

### CSS (Minimal)
- **Priorité** : Utiliser Bootstrap en priorité
- **CSS personnalisé** : Seulement si nécessaire
- **Variables CSS** : Dans `:root` pour personnalisation
- **Nommage** : BEM si CSS personnalisé nécessaire
- **Mobile-first** : Approche responsive

---

## 🔄 Cycle de Développement

### Méthodologie
1. **Planification** - Définir les fonctionnalités du sprint
2. **Design** - Maquettes et schémas BDD
3. **Développement** - Coder selon phases
4. **Tests** - Tests unitaires et fonctionnels
5. **Review** - Revue de code
6. **Déploiement** - Mise en production
7. **Monitoring** - Suivi et corrections

### Bonnes pratiques
- Commits Git fréquents et descriptifs
- Branches pour nouvelles fonctionnalités
- Code reviews systématiques
- Documentation continue
- Backups réguliers
- Tests avant chaque déploiement

---

## 📞 Support et Maintenance

### Plan de maintenance
- **Corrections bugs**: En continu
- **Mises à jour sécurité**: Mensuelle
- **Nouvelles fonctionnalités**: Trimestrielle
- **Optimisations**: Semestrielle
- **Backups**: Quotidienne (automatique)

---

## 📖 Ressources Utiles

### Documentation
- [PHP Manual](https://www.php.net/manual/fr/)
- [MariaDB Documentation](https://mariadb.com/kb/en/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [Chart.js Documentation](https://www.chartjs.org/docs/)

### Références légales (France)
- Loi du 10 juillet 1965 (copropriété)
- Décret du 17 mars 1967
- Loi ALUR et ELAN
- RGPD pour données personnelles

---

## ✅ Checklist de Démarrage

- [ ] Installer XAMPP
- [ ] Créer base de données MariaDB
- [ ] Cloner/créer structure de dossiers
- [ ] Configurer config/database.php
- [ ] Exécuter schema.sql
- [ ] Installer Bootstrap et jQuery
- [ ] Créer système d'authentification
- [ ] Créer première page dashboard
- [ ] Tester connexion BDD
- [ ] Créer premier module (Copropriétés)

---

## 🎯 Objectifs du Projet

### Court terme (3 mois)
- Système fonctionnel de base
- Gestion copropriétés/lots/copropriétaires
- Système comptable basique
- Interface responsive

### Moyen terme (6 mois)
- Toutes fonctionnalités principales
- Modules de reporting
- GED complète
- Tests et optimisations

### Long terme (12 mois)
- Produit stable et sécurisé
- Base utilisateurs
- Évolutions v2.0
- Support commercial

---

**Date de création**: 29 novembre 2025  
**Version**: 1.0  
**Statut**: En développement  

---

*Ce document est un guide complet pour le développement de Synd_Gest. Il doit être mis à jour régulièrement au fur et à mesure de l'avancement du projet.*
