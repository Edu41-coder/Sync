# Synd_Gest - Plateforme Domitys

## 🎯 Modèle économique
**Propriétaire (850€)** ← **Domitys (600€ marge)** → **Résident (1450€)**

## 🔧 Stack technique
- **Backend** : PHP 8.2 + MariaDB 10.4 (MVC, PDO)
- **Frontend** : Bootstrap 5.3 + Font Awesome 6.4 + Leaflet.js
- **Thème** : Violet (#667eea, #764ba2), responsive mobile-first

---

## 👥 Les 15 Rôles (table `roles`)

> Les rôles sont stockés dans la table `roles` (slug VARCHAR). **Ne jamais hardcoder** la liste — utiliser `User::getAllRoles()`.

| Slug | Nom affiché | Catégorie | Filtrage données |
|------|-------------|-----------|------------------|
| `admin` | Administrateur | admin | Toutes les données |
| `directeur_residence` | Directeur de Résidence | direction | `user_residence.residence_id` |
| `proprietaire` | Propriétaire | proprietaire | `WHERE coproprietaire.user_id = user_id` |
| `employe_residence` | Employé Résidence | staff | `user_residence.residence_id` |
| `technicien` | Technicien | staff | `user_residence.residence_id` |
| `jardinier_manager` | Jardinier-Paysagiste (Chef) | staff | `user_residence.residence_id` |
| `jardinier_employe` | Jardinier-Paysagiste | staff | `user_residence.residence_id` |
| `entretien_manager` | Responsable Entretien | staff | `user_residence.residence_id` |
| `menage_interieur` | Ménage Intérieur | staff | `user_residence.residence_id` |
| `menage_exterieur` | Ménage Extérieur | staff | `user_residence.residence_id` |
| `restauration_manager` | Responsable Restauration | staff | `user_residence.residence_id` |
| `restauration_serveur` | Serveur/Serveuse | staff | `user_residence.residence_id` |
| `restauration_cuisine` | Cuisine | staff | `user_residence.residence_id` |
| `locataire_permanent` | Résident Senior | resident | `WHERE resident.user_id = user_id` |
| `locataire_temporel` | Hôte Temporaire | resident | `WHERE resident.user_id = user_id` |

### 🔗 Tables d'association user ↔ résidence
- **`user_residence`** : lie un user à une résidence avec son rôle (staff, directeur, etc.)
- **`exploitant_residences`** : conservé pour les exploitants Domitys (legacy)

### 🔑 Accès mots de passe (Admin uniquement)
- Colonne `password_plain` dans `users` — visible uniquement via Admin > Gestion Utilisateurs
- Toujours sauvegarder `password_plain` lors de création/modification mot de passe

---

## 📐 Architecture MVC - Règles strictes

### ❌ INTERDIT : SQL dans les Controllers
```php
// ❌ MAUVAIS - SQL dans controller
$db->query("SELECT * FROM users");

// ✅ BON - SQL dans model
User::getAll();
```

### ✅ Pattern MVC correct
```php
// Controller
public function index() {
    $this->requireAuth();
    $this->requireRole(['admin']);
    $users = User::getAll();
    $this->view('admin/users/index', compact('users'), true); // ⚠️ true = inclure layout
}

// Model
class User extends Model {
    public static function getAll() {
        $db = self::getDB();
        return $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### ⚠️ IMPORTANT : Appel de vue avec layout
```php
// ✅ TOUJOURS ajouter le 3ème paramètre true pour inclure le layout (header, CSS, JS)
$this->view('chemin/vue', $data, true);

// ❌ JAMAIS sans le true (la page s'affichera sans styles)
$this->view('chemin/vue', $data);
```

### ⚠️ IMPORTANT : Messages Flash
```php
// ❌ NE PAS inclure flash.php dans les vues (duplication)
<?php require_once __DIR__ . '/../partials/flash.php'; ?>

// ✅ Le layout main.php gère DÉJÀ l'affichage du flash
// Aucune inclusion nécessaire dans les vues
<div class="container-fluid py-4">
    <!-- Le flash s'affiche automatiquement ici -->
    <h1>Mon titre</h1>
</div>
```

### 🔐 Sécurité obligatoire
```php
$this->requireAuth(); // Chaque méthode
$this->requireRole(['admin', 'gestionnaire']); // Si nécessaire
$this->verifyCsrf(); // POST/PUT/DELETE
```

---

## 📊 Tableaux - Système centralisé

### 📦 Fichiers (NE PAS DUPLIQUER)
- **`datatable.js`** : Tri de colonnes
- **`datatable-pagination.js`** : Tri + Pagination + Filtres
- **`pagination.php`** : Partial PHP (pagination serveur)

### 🎯 Template HTML + JS (à copier)
```html
<table id="monTable" class="table table-hover">
    <thead class="table-light">
        <tr>
            <th class="sortable" data-column="id" data-type="number">#</th>
            <th class="sortable" data-column="nom">Nom</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td data-sort="<?= $item['id'] ?>"><?= $item['id'] ?></td>
            <td data-sort="<?= $item['nom'] ?>"><?= htmlspecialchars($item['nom']) ?></td>
            <td><!-- Actions --></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div id="tableInfo">
    Affichage de <span id="startEntry">1</span> à <span id="endEntry">10</span> 
    sur <span id="totalEntries">100</span>
</div>
<ul class="pagination" id="pagination"></ul>

<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('monTable', {
    excludeColumns: [2],
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    filters: [{ id: 'filterStatus', column: 1 }]
});
</script>
```

### 🎨 CSS standardisé (NE PAS dupliquer)
```css
/* Cartes avec bordure gauche colorée */
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }
.border-left-danger { border-left: 4px solid #e74a3b !important; }

.text-gray-800 { color: #5a5c69 !important; }
.text-gray-300 { color: #dddfeb !important; }

/* Tri - Icônes ajoutées par datatable.js, PAS par CSS ::after */
.table thead th.sortable {
    cursor: pointer;
    user-select: none;
}
```

### ⚠️ RÈGLES STRICTES pour les tableaux

#### ❌ INTERDIT - CSS inline dans les vues
```php
// ❌ JAMAIS ça dans une vue PHP
<style>
.table thead th.sortable { ... }
.pagination .page-link { ... }
</style>
```

#### ✅ OBLIGATOIRE - Utiliser style.css centralisé
```php
// ✅ TOUJOURS ça
<!-- Aucun <style> dans la vue -->
<table class="table table-hover">
    <thead class="table-light">
        <!-- Les styles sont dans style.css -->
    </thead>
</table>

<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
```

#### 📋 Checklist tableau conforme
- [ ] Headers : `<thead class="table-light">`
- [ ] Hover : `class="table-hover"`
- [ ] Tri : `class="sortable"` + `data-column="nom"`
- [ ] Pas de `<style>` dans la vue
- [ ] Script JS : `datatable.js` ou `datatable-pagination.js`
- [ ] Avatar : `class="avatar-circle avatar-sm"` (pas de style inline)

---

## 🎨 Composants UI

### Carte statistique
```php
<div class="card border-left-primary shadow h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <div>
                <h6 class="text-primary mb-1 fw-bold">Titre</h6>
                <h3 class="mb-0 text-gray-800">42</h3>
            </div>
            <i class="fas fa-icon fa-3x text-gray-300"></i>
        </div>
    </div>
</div>
```

### Formulaires de création
```php
<!-- En-tête : icône NOIRE + h3 -->
<h1 class="h3">
    <i class="fas fa-plus-circle text-dark"></i>
    Créer un élément
</h1>

<!-- Header carte : bg-danger (dégradé rouge/rose auto) -->
<div class="card-header bg-danger text-white">
    <h5 class="mb-0"><i class="fas fa-icon me-2"></i>Informations</h5>
</div>
```

### Formulaires d'édition
```php
<!-- En-tête : icône JAUNE + h3 -->
<h1 class="h3">
    <i class="fas fa-edit text-warning"></i>
    Modifier l'élément
</h1>

<!-- Header carte : bg-warning (jaune) -->
<div class="card-header bg-warning text-dark">
    <h5 class="mb-0"><i class="fas fa-icon me-2"></i>Informations</h5>
</div>
```

### Pages de détails (Show)
```php
<!-- En-tête : icône NOIRE + h3 -->
<h1 class="h3 mb-1">
    <i class="fas fa-eye text-dark"></i>
    Nom de l'élément
</h1>

<!-- Bouton Modifier : JAUNE (btn-warning) -->
<a href="<?= BASE_URL ?>/controller/edit/<?= $id ?>" class="btn btn-warning">
    <i class="fas fa-edit"></i> Modifier
</a>

<!-- Header carte principale : bg-danger (rouge) -->
<div class="card-header bg-danger text-white">
    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h5>
</div>

<!-- Toutes les icônes dans le contenu : NOIRES (text-dark) -->
<i class="fas fa-icon text-dark me-2"></i>
```

### Modal de suppression
```php
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                <h5 id="deleteMessage"></h5>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### Breadcrumb
```php
<?php
$breadcrumb = [
    ['icon' => 'fas fa-home', 'text' => 'Accueil', 'url' => BASE_URL],
    ['icon' => 'fas fa-icon', 'text' => 'Page actuelle', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>
```

---

## 🗄️ Tables principales

### Résidents
- `residents_seniors` → `locataire_permanent` (fiche senior complète : santé, CNI, urgence...)
- `hotes_temporaires` → `locataire_temporel` (séjour court : dates, chambre, facturation)
- ~~`locataires`~~ **supprimée** (remplacée par `hotes_temporaires`)

### Résidences & Gestion
- `coproprietees`, `lots`, `user_residence`, `exploitant_residences`
- `exploitants`, `contrats_gestion`, `occupations_residents`
- `paiements_loyers_exploitant`, `revenus_fiscaux_proprietaires`

### Syndic / Copropriété
- `users`, `permissions`, `roles`, `coproprietaires`
- `baux` (locataire_id nullable — module syndic futur)

---

## 🚨 Erreurs à éviter

```php
// ❌ JAMAIS
$db->query("SELECT..."); // SQL dans controller
<style>.sortable { ... }</style> // CSS inline dans les vues
<?= $user['nom'] ?> // Oublier htmlspecialchars
Flash::success('Message'); // Utiliser $this->setFlash()
<div style="width: 35px;"> // Style inline (utiliser classes CSS)
$this->view('vue', $data); // Oublier le paramètre true (pas de layout)
<?php require_once __DIR__ . '/../partials/flash.php'; ?> // Flash déjà dans layout

// ✅ TOUJOURS
User::getAll(); // SQL dans model
// Styles dans style.css ou mobile.css uniquement
<?= htmlspecialchars($user['nom']) ?>
$this->setFlash('success', 'Message');
$this->verifyCsrf(); // Pour POST/DELETE
<div class="avatar-circle avatar-sm"> // Classes CSS centralisées
$this->view('vue', $data, true); // Paramètre true pour inclure layout
// Ne PAS inclure flash.php (main.php le fait déjà)
```

---

## 📝 Priorités

- **Phase 1** : Admin ✅ (Auth, Users CRUD)
- **Phase 2** : Exploitant 🔄 (Dashboard, Résidents, Occupations)
- **Phase 3** : Propriétaire 🔜 (Contrats, Paiements, Fiscalité)
- **Phase 4** : Résident 🔜 (Profil, Paiements)

---

**Version 4.0 - Ultra-Simplifié | 10 décembre 2025**

