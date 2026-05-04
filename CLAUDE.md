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

## Stockage fichiers — convention `uploads/` privé vs `public/uploads/`

**Deux répertoires distincts par design** :

| Type de fichier | Dossier | Accès |
|---|---|---|
| **Sensible / personnel** (GED, certifications, justificatifs fiscaux) | `uploads/` à la racine du projet (HORS `public/`) | Stream via controller authentifié — vérif rôle + ownership |
| **Illustration publique** (photos profil, photos catalogues, photos avant/après interventions) | `public/uploads/...` | Accessible directement par URL `<img src="<?= BASE_URL ?>/uploads/...">` |

**Exemples** :
- `uploads/coproprietaires/{user_id}/...` ← GED propriétaire (PDF privés, controller `download()`)
- `uploads/residents/{user_id}/...` ← GED résident
- `uploads/maintenance/certifs/{user_id}/...` ← Certifications pro
- `public/uploads/photos/user_X.jpg` ← Photos profil utilisateurs
- `public/uploads/jardinage/espaces/...` ← Photos espaces jardin
- `public/uploads/maintenance/photos/...` ← Photos avant/après interventions

**Règle** : si la fuite du fichier compromet la vie privée ou viole le RGPD, c'est `uploads/` racine. Sinon `public/uploads/`.

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
- Module Ménage → @.claude/modules/menage.md
- Module Jardinage ✅ → @.claude/modules/jardinage.md
- Module Maintenance Technique ✅ (piscine, ascenseur, travaux/chantiers, plomberie, électricité, peinture) → @.claude/modules/maintenance.md
- Module Sinistres ✅ MVP (déclarations, suivi assureur, GED constats/expertises, audit trail) → @.claude/modules/sinistres.md
- Module Documents ✅ MVP (GED admin : global Domitys + par résidence, permissions multi-rôles) → @.claude/modules/documents.md
- Module Accueil ✅ (résidents+notes, salles, équipements, réservations, animations, planning double-vue, équipe, messagerie groupée) → @.claude/modules/accueil.md
- Module Assemblées Générales ✅ (workflow AGO/AGE, résolutions+votes, convocation messagerie, espace propriétaire lecture, calendrier proprio) → @.claude/modules/ag.md
- Module Comptabilité 🚧 (Phases 0+1+2+3+4 ✅ — fondations + refonte modules + dashboard + RH + bulletins paie pilote, 8 phases restantes ~35 j) → @.claude/modules/comptabilite.md
- Module Messagerie interne → @.claude/modules/messagerie.md
- Module Appels de fonds 💤 (feature dormante — model existant, à activer si demande client) → @.claude/modules/appels_fonds.md
