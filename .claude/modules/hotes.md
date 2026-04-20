# Module Hôtes Temporaires

## Périmètre
Controller : `HoteController`
Vues : `app/views/hotes/`
Accès : `requireRole(['admin', 'directeur_residence', 'exploitant', 'employe_residence'])`

## Concept clé
Les hôtes temporaires sont des **visiteurs courts séjours** — ils ne sont **PAS des utilisateurs** de la plateforme. Pas de `user_id`, pas de compte, pas de mot de passe.

## Modèle de données (`hotes_temporaires`)

| Colonne | Description |
|---------|-------------|
| `id` | PK |
| `nom`, `prenom` | Identité |
| `telephone`, `email` | Contact |
| `lot_id` | Lot occupé pendant le séjour |
| `residence_id` | Résidence |
| `date_arrivee` | Date d'entrée |
| `date_depart` | Date de sortie prévue |
| `nombre_personnes` | Occupants |
| `tarif_nuit` | Tarif appliqué |
| `statut` | `reserve` / `en_cours` / `termine` / `annule` |
| `notes` | Observations |

## Règles métier

- Un hôte temporaire **n'a pas de compte** dans `users` — jamais de `user_id`
- Le lot utilisé doit être **disponible** sur la période (pas d'occupation résidente active ET pas d'autre séjour hôte qui se chevauche)
- Le lot pour hôte est typiquement de type `studio` ou `t2` (pas cave/parking)
- Séjour terminé (`date_depart` dépassée) → statut passe automatiquement à `termine`

## Vérifications avant création de séjour

```php
// 1. Lot libre de tout résident actif ?
$occupationActive = Occupation::getLotActif($lot_id); // doit être NULL

// 2. Pas de chevauchement avec un autre séjour hôte ?
$chevauche = Hote::checkChevauchement($lot_id, $date_arrivee, $date_depart); // doit être FALSE
```

## Calculs
- `duree_sejour` = date_depart - date_arrivee (en jours)
- `total_facturation` = duree_sejour × tarif_nuit × nombre_personnes

## À vérifier lors du dev
- [ ] Aucun champ `user_id` dans les formulaires hôtes
- [ ] Double vérification disponibilité lot (résident actif + chevauchement hôte)
- [ ] Calcul total affiché avant confirmation de réservation
- [ ] Changement automatique de statut si date_depart dépassée (cron ou à l'affichage)
- [ ] Filtrage par résidence selon rôle utilisateur

## Intégration Messagerie
**Les hôtes temporaires N'ONT PAS accès à la messagerie** (pas de compte user, voir @.claude/modules/messagerie.md).
La communication avec un hôte se fait par téléphone ou email externe (champs `telephone`, `email` de la fiche hôte).

## Checklist générale module Hôtes
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] Pas de référence à `users` ou `user_id` dans la logique hôtes
- [ ] CSRF sur tous les POST
- [ ] Vérification double disponibilité lot avant INSERT
- [ ] Statuts mis à jour correctement
- [ ] DataTable sur la liste des séjours (filtres : statut, résidence, dates)
- [ ] `htmlspecialchars()` sur toutes les données affichées
