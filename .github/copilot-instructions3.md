# Synd_Gest - Plateforme Domitys de Gestion Résidences Seniors

## 📋 Vue d'ensemble

**Modèle économique** : `Propriétaire (850€) ← Domitys (600€ marge) → Résident (1450€)`

**Technologies** :
- Backend : PHP 8.2 + MariaDB 10.4 (MVC personnalisé, PDO)
- Frontend : Bootstrap 5.3 + Font Awesome 6.4 + Leaflet.js
- Theme : Violet (#667eea, #764ba2), responsive mobile-first

---

## 👥 Les 5 Rôles Utilisateurs

### 1. **Admin** - Super administrateur
- Accès complet à toutes les données
- Gestion des utilisateurs et permissions
- Configuration système
- Statistiques globales

### 2. **Gestionnaire** - Gestionnaire de syndic
- Gestion comptabilité copropriétés
- Appels de fonds et charges
- Travaux et fournisseurs
- Vue filtrée : seulement ses copropriétés

### 3. **Exploitant** - Domitys (opérateur résidences)
- Gestion des résidents seniors
- Suivi des occupations et loyers
- Tableaux de bord : taux d'occupation, revenus
- Vue filtrée : seulement ses résidences

### 4. **Propriétaire** - Investisseur immobilier
- Mes contrats de gestion
- Mes paiements de loyers garantis
- Mes déclarations fiscales (LMNP, Censi-Bouvard)
- Carte des mes biens

### 5. **Résident** - Senior occupant
- Mon profil et occupation actuelle
- Mes paiements de loyer
- Mes services et forfait
- Messagerie avec Domitys

---

## 🗄️ Base de Données Principales

### Tables Domitys (nouvelles)
- `exploitants` - Entreprises comme Domitys
- `contrats_gestion` - Baux commerciaux Propriétaire ↔ Domitys
- `residents_seniors` - Seniors occupants
- `occupations_residents` - Qui occupe quel logement
- `paiements_loyers_exploitant` - Domitys → Propriétaires
- `revenus_fiscaux_proprietaires` - Déclarations LMNP

### Tables Syndic (existantes)
- `users` - Authentification (5 rôles)
- `permissions` - Contrôle d'accès granulaire
- `coproprietees` - Résidences (type: residence_seniors pour Domitys)
- `lots` - Appartements, parkings, caves
- `coproprietaires` - Propriétaires investisseurs
- `comptes_comptables`, `appels_fonds`, etc.

### Vues SQL
- `v_revenus_proprietaires` - Revenus par propriétaire
- `v_taux_occupation` - Occupation des résidences
- `v_residents_logements` - Résidents + leurs logements
- `v_suivi_paiements_exploitant` - Paiements Domitys

---

## 🎨 Design Pattern

### Bootstrap 5.3 - Classes uniquement
```html
<!-- Grille responsive -->
<div class="container-fluid">
  <div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
      <div class="card">...</div>
    </div>
  </div>
</div>

<!-- Boutons empilés mobile -->
<div class="btn-stack-mobile">
  <button class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau</button>
  <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filtrer</button>
</div>

<!-- Table responsive -->
<div class="table-container-mobile">
  <table class="table table-hover">...</table>
</div>
```

### Responsive Mobile
- Breakpoints : 991px (tablet), 767px (mobile), 575px (small), 379px (tiny)
- CSS : `style.css` + `mobile.css`
- Touch targets : 44px minimum
- Transformations tables → cards sur mobile

---

## 📊 Espaces Utilisateurs - Vues Requises

### 🔑 ADMIN - Dashboard Complet

#### Vue Principale (`admin/dashboard.php`)
- **Stats globales** : Résidences, Propriétaires, Résidents, Revenus
- **Graphiques** : Evolution occupations, revenus mensuels
- **Alertes** : Paiements en retard, contrats expirant

#### Tableaux de Gestion
- **Utilisateurs** (`admin/users.php`)
  - Liste tous utilisateurs (5 rôles)
  - Colonnes : Nom, Email, Rôle, Statut, Dernière connexion
  - Actions : Créer, Modifier, Désactiver, Permissions
  - Filtres : Par rôle, statut
  - Recherche : Nom, email
  - Pagination : 25/50/100 par page

- **Résidences** (`admin/residences.php`)
  - Liste toutes résidences Domitys
  - Carte Leaflet.js avec markers
  - Filtres : Ville, taux d'occupation
  - Stats par résidence

- **Contrats** (`admin/contrats.php`)
  - Liste tous contrats de gestion
  - Colonnes : Propriétaire, Résidence, Lot, Loyer, Statut
  - Filtres : Statut, exploitant, date
  - Exports Excel/PDF

- **Logs Système** (`admin/logs.php`)
  - Historique actions utilisateurs
  - Filtres : Date, utilisateur, action

---

### 💼 GESTIONNAIRE - Gestion Syndic

#### Vue Principale (`gestionnaire/dashboard.php`)
- **Mes copropriétés** : Seulement celles sous sa gestion
- **Appels de fonds** : À émettre, en cours
- **Travaux en cours** : Planifiés, urgents
- **Impayés** : Copropriétaires en retard

#### Tableaux Filtrés
- **Copropriétés** (`gestionnaire/coproprietees.php`)
  - Vue filtrée : WHERE syndic_id = user_id
  - Carte Leaflet avec ses biens
  - Stats comptables par copropriété

- **Copropriétaires** (`gestionnaire/coproprietaires.php`)
  - Liste propriétaires de ses copropriétés
  - Colonnes : Nom, Contact, Lots, Solde
  - Actions : Relancer impayés

- **Appels de Fonds** (`gestionnaire/appels_fonds.php`)
  - Génération trimestre/annuelle
  - Répartition par tantièmes
  - Suivi paiements

- **Travaux** (`gestionnaire/travaux.php`)
  - Demandes, devis, planning
  - Suivi avancement

---

### 🏢 EXPLOITANT - Gestion Domitys

#### Vue Principale (`exploitant/dashboard.php`)
- **Mes résidences** : Résidences Domitys opérées
- **Taux d'occupation** : Par résidence (gauge charts)
- **Revenus du mois** : Loyers résidents - Loyers propriétaires = Marge
- **Résidents** : Nouveaux, départs prévus

#### Tableaux Métier
- **Résidences** (`exploitant/residences.php`)
  - Liste résidences Domitys
  - Carte Leaflet interactive
  - Colonnes : Nom, Ville, Apparts, Taux occup%, Revenus
  - Drill-down : Clic → Détails résidence

- **Résidents** (`exploitant/residents.php`)
  - Liste résidents actifs
  - Colonnes : Nom, Âge, Résidence, Lot, Autonomie, Loyer
  - Filtres : Résidence, niveau autonomie, âge
  - Recherche : Nom, contact urgence
  - Actions : Voir détails, Modifier, Terminer occupation
  - Pagination

- **Occupations** (`exploitant/occupations.php`)
  - Liste occupations actives/terminées
  - Colonnes : Résident, Lot, Entrée, Sortie, Loyer, Statut
  - Filtres : Résidence, statut, période
  - Calcul automatique : Marge (loyer résident - loyer propriétaire)

- **Paiements aux Propriétaires** (`exploitant/paiements.php`)
  - Liste échéances mensuelles
  - Colonnes : Propriétaire, Contrat, Montant, Échéance, Statut, Retard
  - Actions : Marquer payé, Télécharger quittance
  - Filtres : Mois, statut, propriétaire
  - Alertes : Retards de paiement

- **Contrats de Gestion** (`exploitant/contrats.php`)
  - Baux commerciaux avec propriétaires
  - Colonnes : Propriétaire, Lot, Résidence, Loyer garanti, Durée, Expiration
  - Filtres : Statut, résidence
  - Alertes : Contrats expirant < 6 mois

---

### 🏠 PROPRIETAIRE - Espace Investisseur

#### Vue Principale (`proprietaire/dashboard.php`)
- **Mes contrats** : Nombre actifs, revenus mensuels
- **Mes paiements** : Dernier reçu, prochain prévu
- **Fiscalité** : Résumé année en cours (Censi-Bouvard)
- **Carte de mes biens** : Leaflet avec markers résidences

#### Vues Personnalisées
- **Mes Contrats** (`proprietaire/contrats.php`)
  - Liste mes baux avec Domitys
  - Colonnes : Résidence, Lot, Loyer, Durée, Début, Fin, Statut
  - Détails : Télécharger bail, inventaire, état des lieux
  - Carte : Localisation de mes biens

- **Mes Paiements** (`proprietaire/paiements.php`)
  - Historique loyers reçus
  - Colonnes : Mois, Montant, Date paiement, Statut, Quittance
  - Filtres : Année, contrat
  - Total annuel : Somme reçue

- **Ma Fiscalité** (`proprietaire/fiscalite.php`)
  - Résumé annuel LMNP
  - Revenus bruts : Total loyers
  - Charges déductibles : Intérêts, travaux, assurances
  - Amortissement : Calcul automatique
  - Réduction Censi-Bouvard : 11% sur 9 ans
  - Résultat fiscal
  - Export PDF pour comptable

- **Mes Documents** (`proprietaire/documents.php`)
  - Contrats de gestion
  - Quittances de loyer
  - Relevés annuels
  - Avis d'échéance

- **Mes Résidences** (`proprietaire/residences_carte.php`)
  - **Carte Leaflet** avec markers de mes biens
  - Popup : Adresse, loyer, taux occupation
  - Filtres : Ville, type

---

### 👴 RESIDENT - Espace Senior

#### Vue Principale (`resident/dashboard.php`)
- **Mon profil** : Photo, infos personnelles
- **Mon logement** : Résidence, numéro lot, surface
- **Mon forfait** : Services inclus (Essentiel/Sérénité/Confort/Premium)
- **Mes prochains paiements** : Loyer du mois

#### Vues Simplifiées
- **Mon Occupation** (`resident/occupation.php`)
  - Résidence, adresse
  - Numéro d'appartement, surface
  - Date d'entrée
  - Loyer mensuel + services
  - Forfait choisi
  - Dépôt de garantie

- **Mes Paiements** (`resident/paiements.php`)
  - Historique loyers payés
  - Colonnes : Mois, Montant, Date, Statut, Quittance
  - Télécharger quittances

- **Mon Profil** (`resident/profil.php`)
  - Informations personnelles
  - Contact d'urgence
  - Médecin traitant
  - Allergies, traitements
  - Centres d'intérêt
  - Animal de compagnie
  - Modifier coordonnées

- **Messagerie** (`resident/messages.php`)
  - Contacter Domitys
  - Demandes de service
  - Historique échanges

---

## 🗺️ Carte Leaflet.js - Implémentation

### Configuration
```javascript
// public/assets/js/map.js
const map = L.map('map').setView([46.603354, 1.888334], 6); // Centre France

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 18
}).addTo(map);
```

### Markers Résidences
```javascript
// Données depuis PHP/JSON
residences.forEach(res => {
    const marker = L.marker([res.lat, res.lng])
        .bindPopup(`
            <strong>${res.nom}</strong><br>
            ${res.ville}<br>
            Taux occup: ${res.taux}%<br>
            <a href="/residence/view/${res.id}">Voir détails</a>
        `)
        .addTo(map);
});
```

### Utilisations par Rôle
- **Admin** : Toutes résidences avec statistiques
- **Gestionnaire** : Ses copropriétés + filtres
- **Exploitant** : Résidences Domitys opérées
- **Propriétaire** : Ses biens uniquement
- **Résident** : Pas de carte (seulement son adresse)

---

## 📋 Tableaux DataTables - Standard

### Configuration Type
```html
<div class="card">
  <div class="card-header">
    <h5><i class="fas fa-table"></i> Titre</h5>
    <div class="btn-stack-mobile">
      <button class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau</button>
      <button class="btn btn-secondary"><i class="fas fa-file-excel"></i> Export</button>
    </div>
  </div>
  <div class="card-body">
    <!-- Filtres -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4">
        <select class="form-select" id="filter-status">
          <option value="">Tous les statuts</option>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <input type="text" class="form-control" placeholder="Rechercher...">
      </div>
    </div>

    <!-- Table responsive -->
    <div class="table-container-mobile">
      <table class="table table-hover" id="dataTable">
        <thead>
          <tr>
            <th>Colonne 1</th>
            <th>Colonne 2</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Données PHP -->
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav>
      <ul class="pagination">...</ul>
    </nav>
  </div>
</div>
```

### Fonctionnalités Obligatoires
- ✅ **Recherche** : Input texte global
- ✅ **Filtres** : Par statut, date, catégorie
- ✅ **Tri** : Colonnes cliquables (ASC/DESC)
- ✅ **Pagination** : 25/50/100 résultats
- ✅ **Export** : Excel, PDF
- ✅ **Actions** : Voir, Modifier, Supprimer (avec confirmation)
- ✅ **Responsive** : Transformation en cards sur mobile

---

## 🔐 Contrôle d'Accès

### Vérification Permissions
```php
// Dans chaque controller
public function index() {
    $this->requireAuth();
    $this->requireRole(['admin', 'gestionnaire']);
    
    // Check permission granulaire
    if (!User::hasPermission($_SESSION['user_id'], 'residences', 'read')) {
        Flash::error("Accès refusé");
        redirect('/');
    }
    
    // Filtre données selon rôle
    if ($_SESSION['role'] === 'gestionnaire') {
        $residences = Copropriete::getBySyndic($_SESSION['user_id']);
    } elseif ($_SESSION['role'] === 'exploitant') {
        $residences = Copropriete::getByExploitant($_SESSION['user_id']);
    } else {
        $residences = Copropriete::getAll();
    }
    
    $this->view('gestionnaire/residences', compact('residences'));
}
```

### Filtrage SQL Automatique
```php
// Model : Copropriete.php
public static function getByRole($userId, $role) {
    $db = Database::getInstance()->getConnection();
    
    if ($role === 'gestionnaire') {
        $sql = "SELECT * FROM coproprietees WHERE syndic_id = :user_id";
    } elseif ($role === 'exploitant') {
        $sql = "SELECT c.* FROM coproprietees c 
                JOIN exploitants e ON c.exploitant_id = e.id
                WHERE e.user_id = :user_id";
    } elseif ($role === 'proprietaire') {
        $sql = "SELECT DISTINCT c.* FROM coproprietees c
                JOIN lots l ON c.id = l.copropriete_id
                JOIN contrats_gestion cg ON l.id = cg.lot_id
                JOIN coproprietaires cp ON cg.coproprietaire_id = cp.id
                WHERE cp.user_id = :user_id";
    } else {
        $sql = "SELECT * FROM coproprietees";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

---

## 🚀 Workflow Développement

### 1. Créer un nouveau module

#### Étape 1 : Controller
```php
// app/controllers/ExempleController.php
class ExempleController extends Controller {
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $items = Exemple::getByRole($_SESSION['user_id'], $_SESSION['role']);
        $this->view('gestionnaire/exemples', compact('items'));
    }
}
```

#### Étape 2 : Model
```php
// app/models/Exemple.php
class Exemple extends Model {
    public static function getByRole($userId, $role) {
        // Filtrage selon rôle
    }
}
```

#### Étape 3 : Vue
```php
// app/views/gestionnaire/exemples.php
<?php $title = "Exemples"; ?>

<div class="container-fluid py-4">
    <h1><i class="fas fa-icon"></i> Exemples</h1>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nom']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
```

### 2. Tester Responsive
```bash
# Chrome DevTools : F12 → Mode mobile (Ctrl+Shift+M)
# Tester : 375px, 768px, 1024px
```

### 3. Vérifier Permissions
```sql
-- Tester dans SQL
SELECT * FROM permissions 
WHERE role = 'gestionnaire' AND module = 'residences';
```

---

## 📁 Structure Fichiers à Créer

```
app/views/
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── residences.php
│   ├── contrats.php
│   └── logs.php
├── gestionnaire/
│   ├── dashboard.php
│   ├── coproprietees.php
│   ├── coproprietaires.php
│   ├── appels_fonds.php
│   └── travaux.php
├── exploitant/
│   ├── dashboard.php
│   ├── residences.php (avec carte)
│   ├── residents.php
│   ├── occupations.php
│   ├── paiements.php
│   └── contrats.php
├── proprietaire/
│   ├── dashboard.php
│   ├── contrats.php
│   ├── paiements.php
│   ├── fiscalite.php
│   ├── documents.php
│   └── residences_carte.php
└── resident/
    ├── dashboard.php
    ├── occupation.php
    ├── paiements.php
    ├── profil.php
    └── messages.php
```

---

## 🎨 Composants Réutilisables

### Stat Card
```php
<!-- partials/stat_card.php -->
<div class="col-12 col-md-6 col-lg-3">
    <div class="card text-white bg-<?= $color ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="text-white-50"><?= $title ?></h6>
                    <h2><?= $value ?></h2>
                </div>
                <div>
                    <i class="<?= $icon ?> fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Filtre Panel
```php
<!-- partials/filter_panel.php -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Rechercher...">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filtrer
                </button>
            </div>
        </form>
    </div>
</div>
```
 À faire pour les autres modules
Ajouter des breadcrumbs cohérents dans :

Copropriétés (app/views/coproprietes/*)
Lots (app/views/lots/*)
Copropriétaires (app/views/coproprietaires/*)
Autres modules de gestion
📐 Pattern à suivre
---
<?php
<!-- Fil d'Ariane -->
<?php
$breadcrumb = [
    ['icon' => 'fas fa-home', 'text' => 'Accueil', 'url' => BASE_URL],
    ['icon' => 'fas fa-icon', 'text' => 'Section', 'url' => BASE_URL . '/section'],
    ['icon' => 'fas fa-icon', 'text' => 'Page actuelle', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>





---

## ✅ Checklist Nouvelle Vue

- [ ] Controller créé avec requireAuth() + requireRole()
- [ ] Model avec filtrage par rôle
- [ ] Vue avec template Bootstrap responsive
- [ ] Tableau avec recherche + filtres + pagination
- [ ] Actions (Voir, Modifier, Supprimer)
- [ ] Permissions vérifiées en SQL
- [ ] Responsive testé (375px, 768px, 1024px)
- [ ] Icônes Font Awesome ajoutées
- [ ] Messages flash (success/error)
- [ ] Export Excel/PDF (si applicable)

---

## 📖 Priorités Développement

### Phase 1 : Authentification & Dashboards ✅
- [x] Login/Logout
- [x] Système de rôles
- [ ] Dashboard admin
- [ ] Dashboard gestionnaire
- [ ] Dashboard exploitant
- [ ] Dashboard propriétaire
- [ ] Dashboard résident

### Phase 2 : Exploitant Domitys 🔄
- [ ] Liste résidences avec carte Leaflet
- [ ] Gestion résidents (tableau DataTable)
- [ ] Gestion occupations
- [ ] Suivi paiements propriétaires
- [ ] Contrats de gestion

### Phase 3 : Propriétaire 🔜
- [ ] Mes contrats
- [ ] Mes paiements
- [ ] Ma fiscalité LMNP
- [ ] Carte de mes biens
- [ ] Documents

### Phase 4 : Résident 🔜
- [ ] Mon occupation
- [ ] Mes paiements
- [ ] Mon profil
- [ ] Messagerie

### Phase 5 : Gestionnaire 🔜
- [ ] Copropriétés
- [ ] Appels de fonds
- [ ] Travaux
- [ ] Comptabilité

---

## 🎯 Objectif Final

**Application web complète** permettant la gestion du modèle Domitys avec :
- ✅ 5 espaces utilisateurs distincts et sécurisés
- ✅ Tableaux de données avec filtres et pagination
- ✅ Cartes interactives Leaflet.js
- ✅ Design responsive mobile-first
- ✅ Contrôle d'accès granulaire par rôle
- ✅ Export de données (Excel, PDF)
- ✅ Interface intuitive et moderne

---

**Date** : 30 novembre 2025  
**Version** : 2.0 - Domitys Edition
