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
| staff accueil | `accueil_manager`, `accueil_employe` | — | /accueil |
| staff maintenance | `technicien_chef`, `technicien` | — | /maintenance (sections selon spécialités assignées) |
| staff ménage | `menage_chef`, `menage` | — | /dashboard |
| staff restauration | `restauration_chef`, `restauration` | — | /dashboard |
| staff laverie | `employe_laverie` | — | /dashboard |
| resident | `locataire_permanent` | `residents_seniors` | /resident |

## Règles strictes

- `locataire_temporel` est **désactivé** — les hôtes temporaires ne sont PAS des users, gérés via `/hote/`
- Rôles avec profil lié (`proprietaire`, `locataire_permanent`, `exploitant`) : **changement de rôle interdit**
- `admin` : rôle ET statut **verrouillés** — impossible à modifier même par un admin
- Désactivation d'un user avec profil lié → désactiver aussi le profil associé

## ⚠️ Rôle `employe_residence` — résiduel / legacy (à terme à déprécier)

**Statut actuel** : rôle historique conservé pour souplesse mais sans identité métier forte. Initialement fourre-tout pour tout le personnel résidence, il a été progressivement supplanté par les rôles métier spécialisés (`accueil_*`, `menage_*`, `restauration_*`, `jardinier_*`, `technicien*`, `entretien_*`, `employe_laverie`).

### Ce que peut faire `employe_residence` aujourd'hui

| Action | Endroit | Rôle nécessaire |
|---|---|---|
| ✅ Voir et déclarer un sinistre (lecture + création) | `/sinistre/index`, `/sinistre/declarer` | `ROLES_DECLARANT` |
| ✅ Gérer les hôtes temporaires (CRUD complet) | `/hote/*` | inclus dans la liste |
| ✅ Accès au tableau de bord standard | `/dashboard` | staff |
| ✅ Messagerie interne | `/message/*` | tout user authentifié |
| ✅ Mes infos RH + Mes bulletins de paie | `/salarie/mesInfos`, `/bulletinPaie/mesBulletins` | tout staff |
| ✅ Apparaître dans le planning staff | `/planning/index` | filtrage `user_residence` |
| ✅ Recevoir un salaire (fiche RH + bulletin paie) | depuis `/salarie/edit/{id}` | rôle staff générique |
| ❌ Modules métier spécialisés : Ménage, Restauration, Jardinage, Maintenance, Accueil → rôles dédiés exigés |

### Cas d'usage légitimes restants

1. **Personnel polyvalent** sans spécialisation métier (rare)
2. **Personnel administratif** d'une résidence (secrétariat, agent polyvalent)
3. **Backup historique** : héritage du module Hôtes temporaires créé avant le rôle `accueil_employe`
4. **Compte de test générique** dans les démos (ex: `employe_res` / `Emp1234`)

### Plan de dépréciation (si besoin futur)

Si un jour on veut nettoyer :
1. Identifier les users actuels avec ce rôle (1-2 maximum)
2. Les migrer vers `accueil_employe` (qui couvre déjà Hôtes + accueil)
3. Remplacer `requireRole(['employe_residence'])` par `requireRole(['accueil_employe'])` ou `requireRole(['directeur_residence'])` selon le contexte
4. Supprimer le rôle de la table `roles` + ENUM `users.role`
5. Effort estimé : 2-3 jours

**Décision actuelle (2026-05-04)** : statu quo. Documenté ici comme dette technique connue. Pas urgent tant qu'il ne pose pas de problème opérationnel.

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
