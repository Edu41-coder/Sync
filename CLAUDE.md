# Synd_Gest — Plateforme de gestion Domitys (Résidences Seniors)

## Modèle économique
**Propriétaire (850€ loyer garanti)** ← **Domitys (marge ~600€)** → **Résident Senior (1450€ loyer)**

## Stack technique
- PHP 8.2 + MariaDB 10.4 (MVC custom, PDO Singleton)
- Bootstrap 5.3 dark theme + Font Awesome 6.4 + Leaflet.js + TUI Calendar v1.15.3
- Apache/XAMPP Linux, `public/` comme document root
- UTF-8 partout — utiliser PHP PDO (jamais mysql CLI) pour les données françaises

## Architecture MVC
```
app/
  controllers/   → AdminController, HomeController, PlanningController, HoteController, etc.
  models/        → User, Lot, Residence, ResidentSenior, Exploitant, Service, etc.
  views/         → admin/, dashboard/, residences/, lots/, occupations/, hotes/, planning/, coproprietaires/
  core/          → Controller.php, Model.php, Router.php, Security.php, Database.php, Logger.php
config/          → config.php (BASE_URL, DB credentials)
public/          → index.php (entry point), assets/css/, assets/js/
```

## Routage
- URL `admin/carteResidence/61` → controller=`Admin`, method=`carteResidence`, params=`[61]`
- `$this->view('path/vue', $data, true)` — `true` = inclure layout main.php (navbar, CSS, JS)
- `$this->redirect('admin/users')` — préfixe BASE_URL automatiquement
- `$this->setFlash('success', 'message')` — message flash en session

## Sécurité (obligatoire sur chaque méthode)
```php
$this->requireAuth();
$this->requireRole(['admin']); // adapter selon le rôle
$this->verifyCsrf();           // sur tous les POST uniquement
```
- Rate limiting : 200 req/min par IP (fichier cache)
- Session timeout : 30 min (PHP + JS, avertissement 5 min avant)
- `htmlspecialchars()` sur **toute** donnée affichée en vue
- `Security.php` : CSRF tokens, sanitize, rate limiting, password hashing, security headers

## Conventions code

### Controllers
```php
$this->requireAuth();
$this->requireRole(['admin']);
$this->verifyCsrf(); // POST uniquement
// ... logique métier
$this->view('chemin/vue', $data, true);
```

### Vues
- Breadcrumb : **toujours** inclure via le partial `app/views/partials/breadcrumb.php`
- Pas de `<style>` inline — utiliser `style.css`
- Pas d'inclusion manuelle de `flash.php` — `main.php` le fait automatiquement
- `alert-permanent` class pour empêcher auto-dismiss des alertes

### Breadcrumb (obligatoire sur toutes les vues)
```php
$breadcrumb = [
    ['icon' => 'fas fa-home',     'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-building', 'text' => 'Résidences',      'url' => BASE_URL . '/admin/residences'],
    ['icon' => 'fas fa-eye',      'text' => 'Détail',          'url' => null], // null = page actuelle
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
```

### Tableaux — règle absolue
**Tout tableau de liste DOIT avoir les 3 éléments : tri sur colonnes + recherche + pagination.**
Aucune exception. Choisir le système selon le volume :

### Tableaux — deux systèmes selon le volume de données

#### 1. Client-side — `DataTableWithPagination` (< ~500 lignes, données déjà chargées)
```html
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableId', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    filters: [{ id: 'filterStatut', column: 3 }],
    excludeColumns: [7],      // colonnes sans tri (actions, etc.)
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
```
- Tri sur colonne numérique : ajouter `data-sort="valeur_brute"` sur le `<td>`
  ```html
  <td data-sort="1450">1 450 €</td>
  ```
- Les IDs `pagination` et `tableInfo` doivent exister dans le HTML

#### 2. Server-side — `pagination.php` (si table prévue > 1000 lignes)
**Ne pas utiliser par défaut.** Réservé aux tables à fort volume : logs d'activité, historique comptable, etc.
Si le volume prévu dépasse 1000 lignes, utiliser ce système pour éviter de charger toutes les données en mémoire.
```php
<?php include ROOT_PATH . '/app/views/partials/pagination.php'; ?>
```

### Dashboard accès rapides
Chaque rôle a ses propres boutons — ne jamais mélanger les privilèges entre rôles.

## Comptes de test
| Username | Rôle | Mot de passe |
|----------|------|-------------|
| admin | Administrateur | admin123 |
| dir_residence | Directeur Résidence | Dir1234 |
| proprietaire1 | Propriétaire | Prop1234 |
| employe_res | Employé Résidence | Emp1234 |
| technicien | Technicien | Tech1234 |
| resident1 | Résident Senior | Resi1234 |
| comptable | Comptable | Comptable1234 |

## Fichiers de référence par domaine
- Schéma BD complet → @.claude/database.md
- Rôles & permissions → @.claude/roles.md
- Module Admin (users, résidences, lots) → @.claude/modules/admin.md
- Module Résidents & Occupations → @.claude/modules/residents.md
- Module Propriétaires & Contrats → @.claude/modules/proprietaires.md
- Module Planning staff → @.claude/modules/planning.md
- Module Hôtes temporaires → @.claude/modules/hotes.md
- Module Restauration → @.claude/modules/restauration.md
- Module Jardinerie (à créer) → @.claude/modules/jardinerie.md
- Module Entretien Technique (à créer) → @.claude/modules/entretien.md
- Module Travaux (proposition) → @.claude/modules/travaux.md
- Module Comptabilité (à créer) → @.claude/modules/comptabilite.md
- Module Messagerie interne → @.claude/modules/messagerie.md
