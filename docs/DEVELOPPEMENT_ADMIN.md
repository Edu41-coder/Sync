# 📊 Dashboard Admin - Plan de Développement

**Date** : 1er décembre 2025  
**Version** : 1.0

---

## 📋 Fonctionnalités à Développer

### 1️⃣ **Stats Cards** (Déjà fait ✅)
- ✅ Total Résidences
- ✅ Total Utilisateurs actifs
- ✅ Total Contrats actifs
- ✅ Total Résidents

### 2️⃣ **Tableaux Interactifs** (À faire)

#### 📍 **Tableau Résidences Seniors**
- **Route** : `/admin/residences`
- **Colonnes** :
  - Nom de la résidence
  - Ville + Code postal
  - Type (residence_seniors)
  - Exploitant (Domitys)
  - Nombre de lots
  - Taux d'occupation (%)
  - Revenus mensuels
  - Statut (actif/inactif)
- **Actions** :
  - 👁️ Voir détails
  - ✏️ Modifier
  - 🗑️ Supprimer
- **Fonctionnalités** :
  - Recherche (nom, ville)
  - Filtres (ville, exploitant, taux occupation)
  - Tri par colonnes
  - Pagination (25/50/100)
  - Export Excel/PDF
  - Bouton "➕ Nouvelle résidence"

#### 👥 **Tableau Propriétaires**
- **Route** : `/admin/proprietaires`
- **Colonnes** :
  - Nom complet
  - Email + Téléphone
  - Nombre de biens
  - Total revenus mensuels
  - Régime fiscal (LMNP/Censi-Bouvard)
  - Utilisateur lié (compte actif)
  - Statut
- **Actions** :
  - 👁️ Voir détails
  - ✏️ Modifier
  - 📄 Voir contrats
  - 💶 Voir paiements
- **Fonctionnalités** :
  - Recherche (nom, email)
  - Filtres (régime fiscal, ville)
  - Export données

#### 🏢 **Tableau Exploitants (Domitys)**
- **Route** : `/admin/exploitants`
- **Colonnes** :
  - Nom société
  - SIRET
  - Contact principal
  - Nombre de résidences gérées
  - Nombre de résidents actifs
  - CA mensuel estimé
  - Utilisateur lié
  - Statut
- **Actions** :
  - 👁️ Voir détails
  - ✏️ Modifier
  - 🏠 Voir résidences
  - 👴 Voir résidents
- **Fonctionnalités** :
  - Recherche (nom, SIRET)
  - Filtres (statut)
  - Export

#### 🔧 **Tableau Gestionnaires**
- **Route** : `/admin/gestionnaires`
- **Colonnes** :
  - Nom complet
  - Email + Téléphone
  - Nombre de copropriétés gérées
  - Total copropriétaires
  - Appels de fonds en cours
  - Total impayés
  - Utilisateur lié
  - Statut
- **Actions** :
  - 👁️ Voir détails
  - ✏️ Modifier
  - 🏢 Voir copropriétés
- **Fonctionnalités** :
  - Recherche (nom, email)
  - Filtres (statut)
  - Export

### 3️⃣ **Carte Interactive France** (À faire)
- **Route** : `/admin/carte-residences`
- **Bibliothèque** : Leaflet.js
- **Fonctionnalités** :
  - 🗺️ Carte de France centrée
  - 📍 Markers pour chaque résidence
  - **Popup marker** :
    - Nom résidence
    - Adresse complète
    - Exploitant
    - Taux occupation (gauge)
    - Nb résidents / Nb lots
    - Bouton "Voir détails"
  - **Clustering** : Regroupement markers si zoom out
  - **Filtres carte** :
    - Par exploitant
    - Par taux occupation (< 50%, 50-80%, > 80%)
    - Par région
  - **Légende** :
    - 🟢 Vert : > 80% occupation
    - 🟠 Orange : 50-80%
    - 🔴 Rouge : < 50%
  - **Vue liste/carte** : Toggle entre tableau et carte

### 4️⃣ **Section Graphiques** (À faire)
- **Graphiques Chart.js** :
  - 📊 Evolution taux occupation (12 derniers mois)
  - 💰 Evolution revenus (12 derniers mois)
  - 🏘️ Répartition résidences par région (pie chart)
  - 👴 Pyramide des âges résidents
  - 📈 Nouveaux contrats par mois

### 5️⃣ **Gestion Utilisateurs** (À améliorer)
- **Route** : `/admin/users`
- **Colonnes** :
  - Nom + Email
  - Rôle (admin, gestionnaire, exploitant, proprietaire, resident)
  - Statut (actif/inactif)
  - Dernière connexion
  - Date création
  - Actions
- **Actions** :
  - ➕ Créer utilisateur
  - ✏️ Modifier
  - 🔒 Réinitialiser mot de passe
  - ✅ Activer/Désactiver
  - 🗑️ Supprimer
- **Filtres** :
  - Par rôle
  - Par statut
  - Par date connexion

### 6️⃣ **Logs & Activités** (À faire)
- **Route** : `/admin/logs`
- **Colonnes** :
  - Date/Heure
  - Utilisateur
  - Action (connexion, création, modification, suppression)
  - Module (résidence, contrat, paiement...)
  - IP
  - Détails
- **Filtres** :
  - Par utilisateur
  - Par action
  - Par date
  - Par module

### 7️⃣ **Rapports & Exports** (À faire)
- **Route** : `/admin/rapports`
- **Rapports disponibles** :
  - 📄 Rapport mensuel occupations
  - 💰 Rapport revenus par propriétaire
  - 🏢 Rapport performance exploitants
  - 📊 Rapport statistiques globales
- **Formats** : PDF, Excel, CSV

---

## 🎯 Ordre de Développement Recommandé

### **Phase 1** : Tableaux (Priorité HAUTE)
1. ✅ Stats cards (déjà fait)
2. 🔄 Tableau Résidences (avec données de test)
3. 🔄 Tableau Propriétaires
4. 🔄 Tableau Exploitants
5. 🔄 Tableau Gestionnaires

### **Phase 2** : Carte (Priorité HAUTE)
6. 🔄 Intégration Leaflet.js
7. 🔄 Carte France avec markers résidences
8. 🔄 Popups + clustering
9. 🔄 Filtres carte

### **Phase 3** : Graphiques (Priorité MOYENNE)
10. 🔄 Graphiques Chart.js
11. 🔄 Evolution données temporelles

### **Phase 4** : Admin avancé (Priorité BASSE)
12. 🔄 Gestion utilisateurs complète
13. 🔄 Logs système
14. 🔄 Rapports & exports

---

## 📋 Structure Fichiers à Créer

```
app/controllers/
├── AdminController.php (nouveau)

app/views/admin/
├── dashboard.php (améliorer existant)
├── residences.php (nouveau)
├── proprietaires.php (nouveau)
├── exploitants.php (nouveau)
├── gestionnaires.php (nouveau)
├── carte_residences.php (nouveau)
├── users.php (nouveau)
├── logs.php (nouveau)
└── rapports.php (nouveau)

public/assets/js/
├── leaflet-map.js (nouveau)
├── admin-charts.js (nouveau)
└── datatables-init.js (nouveau)
```

---

## 🛠️ Technologies & Bibliothèques

### Frontend
- **Bootstrap 5.3** : Framework CSS
- **Leaflet.js 1.9** : Cartes interactives
- **Chart.js 4.x** : Graphiques
- **DataTables** : Tableaux interactifs (optionnel)
- **Font Awesome 6.4** : Icônes

### Backend
- **PHP 8.2** : Langage serveur
- **MariaDB 10.4** : Base de données
- **PDO** : Accès base de données

---

## 📊 Exemple de Requêtes SQL

### Tableau Résidences avec Stats
```sql
SELECT 
    c.id,
    c.nom,
    c.adresse,
    c.ville,
    c.code_postal,
    e.nom_societe as exploitant,
    COUNT(DISTINCT l.id) as nb_lots,
    COUNT(DISTINCT o.id) as nb_occupations,
    ROUND((COUNT(DISTINCT o.id) / COUNT(DISTINCT l.id)) * 100, 2) as taux_occupation,
    COALESCE(SUM(o.loyer_mensuel_resident), 0) as revenus_mensuels,
    c.actif as statut
FROM coproprietees c
LEFT JOIN exploitants e ON c.exploitant_id = e.id
LEFT JOIN lots l ON c.id = l.copropriete_id
LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
WHERE c.type_residence = 'residence_seniors'
GROUP BY c.id
ORDER BY c.nom ASC;
```

### Tableau Propriétaires avec Stats
```sql
SELECT 
    cp.id,
    CONCAT(cp.prenom, ' ', cp.nom) as nom_complet,
    cp.email,
    cp.telephone,
    COUNT(DISTINCT cg.id) as nb_biens,
    COALESCE(SUM(cg.loyer_mensuel_garanti), 0) as revenus_mensuels,
    cg.dispositif_fiscal,
    u.active as compte_actif
FROM coproprietaires cp
LEFT JOIN contrats_gestion cg ON cp.id = cg.coproprietaire_id AND cg.statut = 'actif'
LEFT JOIN users u ON cp.user_id = u.id
GROUP BY cp.id
ORDER BY revenus_mensuels DESC;
```

### Tableau Exploitants avec Stats
```sql
SELECT 
    e.id,
    e.nom_societe,
    e.siret,
    e.contact_principal,
    COUNT(DISTINCT c.id) as nb_residences,
    COUNT(DISTINCT r.id) as nb_residents,
    COALESCE(SUM(o.loyer_mensuel_resident), 0) as ca_mensuel,
    u.active as compte_actif
FROM exploitants e
LEFT JOIN coproprietees c ON e.id = c.exploitant_id
LEFT JOIN occupations_residents o ON e.id = o.exploitant_id AND o.statut = 'actif'
LEFT JOIN residents_seniors r ON o.resident_id = r.id AND r.actif = 1
LEFT JOIN users u ON e.user_id = u.id
GROUP BY e.id
ORDER BY ca_mensuel DESC;
```

---

## 🎨 Maquette UI

### Layout Tableau Standard
```html
<div class="card">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-building"></i> Résidences Seniors</h5>
            <div>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle résidence
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export Excel
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Filtres -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Rechercher...">
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option>Toutes les villes</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option>Tous exploitants</option>
                </select>
            </div>
        </div>
        
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Ville</th>
                        <th>Exploitant</th>
                        <th>Lots</th>
                        <th>Taux occup.</th>
                        <th>Revenus</th>
                        <th>Actions</th>
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

---

## ✅ Checklist Développement

### Tableau Résidences
- [ ] Créer `AdminController.php`
- [ ] Créer méthode `residences()`
- [ ] Créer vue `admin/residences.php`
- [ ] Requête SQL avec stats
- [ ] Système de recherche
- [ ] Filtres (ville, exploitant, taux)
- [ ] Pagination
- [ ] Export Excel
- [ ] Actions (voir, modifier, supprimer)
- [ ] Responsive mobile

### Carte Leaflet
- [ ] Télécharger Leaflet.js
- [ ] Créer vue `admin/carte_residences.php`
- [ ] Initialiser carte centrée France
- [ ] Ajouter markers résidences
- [ ] Créer popups avec infos
- [ ] Implémenter clustering
- [ ] Ajouter filtres carte
- [ ] Ajouter légende couleurs
- [ ] Toggle vue liste/carte

---

## 🚀 Prochaines Étapes

**Commencer par :**
1. 📍 Tableau Résidences (route `/admin/residences`)
2. 🗺️ Carte Interactive (route `/admin/carte-residences`)

**Ensuite :**
3. 👥 Tableau Propriétaires
4. 🏢 Tableau Exploitants
5. 🔧 Tableau Gestionnaires

---

**Note** : Ce plan est évolutif et peut être ajusté selon les priorités métier.
