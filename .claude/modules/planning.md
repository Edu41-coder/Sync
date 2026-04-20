# Module Planning Staff

## Périmètre
Controller : `PlanningController`
Vues : `app/views/planning/`
Accès direction : `requireRole(['admin', 'directeur_residence', 'exploitant'])`
Accès staff : `requireRole(['admin', 'directeur_residence', ...<tous rôles staff>])`

## Modèle de données

### `planning_shifts`
| Colonne | Type | Description |
|---------|------|-------------|
| `user_id` | FK | Employé concerné |
| `residence_id` | FK | Résidence du shift |
| `categorie_id` | FK | Catégorie de travail |
| `date_debut` | DATETIME | Début du shift |
| `date_fin` | DATETIME | Fin du shift |
| `heures_calculees` | GENERATED | Calculé automatiquement (date_fin - date_debut) |
| `type_heures` | ENUM | `normales` / `supplementaires` |
| `notes` | TEXT | Observations |

### `planning_categories` (13 catégories)
- ménage, restauration, technique, jardinage, entretien, laverie
- accueil, animation, administratif, sécurité, livraison, soins, autre

## Intégration TUI Calendar v1.15.3

### Initialisation
```javascript
const calendar = new tui.Calendar('#calendar', {
    defaultView: 'week',
    timezone: { zones: [{ timezoneName: 'Europe/Paris' }] },
    theme: { /* dark theme */ },
    template: { /* custom templates */ }
});
```

### Format des événements (shifts → calendar)
```javascript
{
    id: shift.id,
    calendarId: shift.categorie_id,
    title: shift.user_nom + ' — ' + shift.categorie_nom,
    start: shift.date_debut,
    end: shift.date_fin,
    backgroundColor: shift.categorie_couleur
}
```

### Règles TUI Calendar
- Version fixée à **1.15.3** — ne pas mettre à jour (breaking changes v2)
- Les événements sont chargés via AJAX (JSON endpoint)
- Drag & drop → appel AJAX pour UPDATE shift

## Règles métier planning

- Un employé peut avoir plusieurs shifts le même jour (pas de contrainte d'unicité)
- `heures_calculees` est une colonne GENERATED — ne pas l'inclure dans INSERT/UPDATE
- Heures supplémentaires = shifts dépassant le quota hebdomadaire (à calculer côté PHP)
- Le staff ne voit que les shifts de SA résidence (`user_residence`)
- Un directeur voit tous les shifts de ses résidences

## Endpoints AJAX attendus
```
GET  planning/getShifts?residence_id=X&start=Y&end=Z  → JSON shifts
POST planning/createShift                              → JSON {success, shift}
POST planning/updateShift                              → JSON {success}
POST planning/deleteShift                              → JSON {success}
```

## À vérifier lors du dev
- [ ] `heures_calculees` exclue des INSERT/UPDATE (colonne GENERATED)
- [ ] CSRF token inclus dans les requêtes AJAX (header ou champ caché)
- [ ] Filtrage par résidence selon le rôle de l'utilisateur connecté
- [ ] Réponses AJAX en JSON avec code HTTP correct (200/400/403)
- [ ] Affichage correct des heures normales vs supplémentaires
- [ ] Couleurs des catégories cohérentes entre légende et calendrier

## Intégration Messagerie
Tous les rôles staff ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible depuis la fiche staff vers direction/admin

## Checklist générale module Planning
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (y compris AJAX)
- [ ] Accès filtré par résidence selon rôle
- [ ] `heures_calculees` non modifiée manuellement
- [ ] TUI Calendar version 1.15.3 (ne pas upgrader)
- [ ] Gestion des erreurs AJAX côté JS (alert ou toast)