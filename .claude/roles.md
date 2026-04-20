# Rôles & Permissions — Synd_Gest

## Table des rôles (18 rôles dynamiques via `User::getAllRoles()`)

| Catégorie | Rôles | Profil lié | Espace |
|-----------|-------|-----------|--------|
| admin | `admin`, `comptable` | — | /admin |
| direction | `directeur_residence`, `exploitant` | `exploitants` | /admin |
| proprietaire | `proprietaire` | `coproprietaires` | /coproprietaire |
| staff | `employe_residence`, `technicien` | — | /dashboard |
| staff jardin | `jardinier_chef`, `jardinier` | — | /dashboard |
| staff entretien | `entretien_chef`, `entretien` | — | /dashboard |
| staff ménage | `menage_chef`, `menage` | — | /dashboard |
| staff restauration | `restauration_chef`, `restauration` | — | /dashboard |
| staff laverie | `employe_laverie` | — | /dashboard |
| resident | `locataire_permanent` | `residents_seniors` | /resident |

## Règles strictes

- `locataire_temporel` est **désactivé** — les hôtes temporaires ne sont PAS des users, gérés via `/hote/`
- Rôles avec profil lié (`proprietaire`, `locataire_permanent`, `exploitant`) : **changement de rôle interdit**
- `admin` : rôle ET statut **verrouillés** — impossible à modifier même par un admin
- Désactivation d'un user avec profil lié → désactiver aussi le profil associé

## Vérifications dans les controllers

```php
// Accès admin uniquement
$this->requireRole(['admin']);

// Accès direction
$this->requireRole(['admin', 'directeur_residence', 'exploitant']);

// Accès propriétaire (son espace)
$this->requireRole(['proprietaire']);

// Accès staff planning
$this->requireRole(['admin', 'directeur_residence', 'employe_residence', 'technicien']);
```

## Checklist sécurité rôles
- [ ] `requireAuth()` présent sur chaque méthode
- [ ] `requireRole([...])` adapté au contexte (ne pas over-autoriser)
- [ ] Un propriétaire ne voit que SES lots/contrats (filtrer par `user_id`)
- [ ] Un résident ne voit que SON profil et SES occupations
- [ ] Le staff ne voit que SA résidence (`user_residence`)
