# Synd_Gest - Plateforme de gestion Domitys (Résidences Seniors)

## Modèle économique
**Propriétaire (850€ loyer garanti)** ← **Domitys (marge ~600€)** → **Résident Senior (1450€ loyer)**

## Stack technique
- PHP 8.2 + MariaDB 10.4 (MVC custom, PDO Singleton)
- Bootstrap 5.3 dark theme + Font Awesome 6.4 + Leaflet.js + TUI Calendar v1.15.3
- Apache/XAMPP, Windows, `public/` comme document root
- UTF-8 partout. Sur Windows, utiliser PHP PDO (pas mysql CLI) pour les données françaises.

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
URL `admin/carteResidence/61` → Router parse: controller=`Admin`, method=`carteResidence`, params=`[61]`
- `$this->view('path/vue', $data, true)` — true = inclure layout main.php (navbar, CSS, JS)
- `$this->redirect('admin/users')` — préfixe BASE_URL automatiquement
- `$this->setFlash('success', 'message')` — message flash en session

## Rôles (table `roles`, 18 rôles, dynamiques via `User::getAllRoles()`)

| Catégorie | Rôles | Profil lié |
|-----------|-------|-----------|
| admin | `admin`, `comptable` | — |
| direction | `directeur_residence`, `exploitant` | `exploitants` |
| proprietaire | `proprietaire` | `coproprietaires` |
| staff (13) | `employe_residence`, `technicien`, `jardinier_*`, `entretien_*`, `menage_*`, `restauration_*`, `employe_laverie` | — |
| resident | `locataire_permanent` | `residents_seniors` |

- `locataire_temporel` est **désactivé** — les hôtes temporaires ne sont pas des users, gérés via module `/hote/`
- Rôles avec fiche liée (`proprietaire`, `locataire_permanent`, `exploitant`) : changement de rôle interdit
- Admin : rôle et statut verrouillés

## Tables principales

### Users & Profils
- `users` — tous les comptes (password_hash + password_plain visible admin)
- `roles` — 18 rôles avec slug, catégorie, couleur, icône, ordre_affichage
- `user_residence` — liaison user ↔ résidence (staff, direction)
- `coproprietaires` — profil propriétaire (user_id FK)
- `residents_seniors` — profil résident senior complet (34 champs : santé, CNI, urgence, etc.)
- `hotes_temporaires` — séjours court terme (PAS de user_id)
- `exploitants` — sociétés exploitantes (Domitys = id 1)

### Résidences & Lots
- `coproprietees` — résidences (type_residence='residence_seniors' uniquement, colonne `actif` pour soft delete)
- `lots` — type ENUM('studio','t2','t2_bis','t3','parking','cave'), terrasse ENUM('non','oui','loggia')
- `exploitant_residences` — many-to-many avec pourcentage_gestion (Domitys 100% par défaut)

### Occupations & Contrats
- `occupations_residents` — résident ↔ lot, avec loyer, forfait, services, aides sociales
- `contrats_gestion` — propriétaire ↔ lot ↔ exploitant (loyer garanti, dispositif fiscal)
- `revenus_fiscaux_proprietaires` — fiscalité annuelle par propriétaire

### Services & Planning
- `services` — catalogue (inclus/supplémentaire, prix, icône)
- `occupation_services` — pivot occupation ↔ service avec prix_applique
- `planning_shifts` — planning staff (user_id, residence_id, dates, heures_calculees GENERATED, type_heures)
- `planning_categories` — 13 catégories (ménage, restauration, technique, etc.)

## Règles métier importantes

### Occupations
- 1 logement max par résident (studio/t2/t2_bis/t3)
- + 1 cave + 1 parking possibles (3 occupations max)
- 1 occupant max par lot
- Désactivation résident → termine automatiquement ses occupations

### Contrats
- 1 contrat actif max par lot
- Un propriétaire peut avoir plusieurs lots (plusieurs contrats)
- Vérification doublon à la création

### Résidences
- Création : Domitys lié automatiquement à 100% via exploitant_residences
- Géocodage : JS (blur) + fallback PHP (curl api-adresse.data.gouv.fr)
- Suppression : hard delete si vierge (0 lot, 0 user), soft delete sinon (actif=0)
- Ville normalisée : ucfirst(mb_strtolower()) à la création/édition

## Sécurité
- `$this->requireAuth()` + `$this->requireRole([...])` sur chaque méthode
- `$this->verifyCsrf()` sur tous les POST
- Rate limiting : 200 req/min par IP (fichier cache)
- Session timeout : 30 minutes (PHP + JS avec avertissement 5 min avant)
- `Security.php` : CSRF tokens, sanitize, rate limiting, password hashing, security headers
- `redirect()` : protection anti-doublement d'URL

## Conventions code

### Controllers
```php
$this->requireAuth();
$this->requireRole(['admin']);
$this->verifyCsrf(); // sur POST
// ... logique
$this->view('chemin/vue', $data, true); // true = layout
```

### Vues
- Breadcrumb : toujours commencer par "Tableau de bord" → lien BASE_URL
- Pas de `<style>` inline — utiliser style.css
- Pas d'inclusion flash.php — main.php le fait
- `htmlspecialchars()` sur toute donnée affichée
- `alert-permanent` class pour empêcher auto-dismiss des alertes

### DataTable (tri + pagination + filtres)
```html
<script src="BASE_URL/assets/js/datatable.js"></script>
<script src="BASE_URL/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableId', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    filters: [{ id: 'filterX', column: 3 }],
    excludeColumns: [7],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
```

### Accès rapides dashboard
Chaque rôle a ses propres boutons d'accès rapide — ne pas mélanger les privilèges.

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

## Modules actifs
- Admin (users CRUD, résidences, lots, carte Leaflet)
- Résidents seniors (profil complet, occupations, services)
- Propriétaires (espace dédié, contrats, fiscalité, comptabilité)
- Hôtes temporaires (réservations, pas de compte user)
- Occupations (affectation lot, vérification disponibilité)
- Services (catalogue dynamique, occupation_services pivot)
- Contrats de gestion (propriétaire ↔ lot ↔ exploitant)
- Planning staff (TUI Calendar, shifts, catégories, heures normales/supplémentaires)
