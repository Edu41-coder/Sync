# Module Résidents & Occupations

## Périmètre
Controllers : `ResidentController`, `OccupationController`, `ServiceController`
Vues : `app/views/residences/residents/`, `app/views/occupations/`
Accès : `requireRole(['admin', 'directeur_residence', 'exploitant'])`

## Profil Résident Senior (`residents_seniors`)

### Champs principaux (34 champs)
- Identité : nom, prénom, date_naissance, lieu_naissance, nationalite
- CNI : numero_cni, date_expiration_cni
- Contact : telephone, email, adresse_avant
- Urgence : contact_urgence_nom, contact_urgence_tel, contact_urgence_lien
- Santé : medecin_traitant, groupe_sanguin, allergies, traitement_medical
- Financier : caisse_retraite, numero_securite_sociale, revenus_mensuels
- Statut : actif (BOOLEAN)

### Règles
- Lié à un `user` via `user_id` (rôle `locataire_permanent`)
- Désactivation résident (`actif=0`) → **terminer automatiquement** toutes ses occupations actives
- Un résident ne peut avoir qu'**1 compte user** (1:1)

### À vérifier lors du dev
- [ ] Désactivation résident → UPDATE occupations SET date_fin = NOW() WHERE resident_id = X AND date_fin IS NULL
- [ ] Désactivation user lié simultanément
- [ ] Données sensibles (santé, CNI) accessibles uniquement aux rôles autorisés

## Occupations (`occupations_residents`)

### Règles critiques
- **1 logement max** par résident (types : studio, t2, t2_bis, t3)
- **+ 1 cave max** possible
- **+ 1 parking max** possible
- **3 occupations max** au total par résident
- **1 occupant max** par lot (vérifier disponibilité avant affectation)
- Champs : `loyer`, `forfait_services`, `aide_sociale_1`, `aide_sociale_2`, `date_entree`, `date_fin`

### Vérifications avant création d'occupation
```php
// 1. Lot disponible ?
$lotOccupe = Occupation::getLotActif($lot_id); // doit être NULL

// 2. Résident a déjà un logement ?
$logementExistant = Occupation::getLogementActif($resident_id); // doit être NULL si type logement

// 3. Max 3 occupations ?
$countOccupations = Occupation::countActives($resident_id); // doit être < 3
```

### À vérifier lors du dev
- [ ] Les 3 vérifications ci-dessus avant tout INSERT
- [ ] `date_fin` NULL = occupation active
- [ ] Calcul loyer net = loyer - aides sociales affiché correctement
- [ ] Liste des occupations filtrée par résidence si directeur

## Services (`services` + `occupation_services`)

### Règles
- Services `inclus` : forfait mensuel global
- Services `supplémentaire` : facturation individuelle avec `prix_applique`
- `prix_applique` peut différer du prix catalogue (négociation)

### À vérifier lors du dev
- [ ] Prix appliqué ≠ prix catalogue possible (champ éditable)
- [ ] Calcul total services correct dans la vue occupation
- [ ] Ajout/suppression service sans clôturer l'occupation

## Intégration Messagerie
Le résident senior (`locataire_permanent`) a accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible depuis la fiche résident vers admin/direction

## Checklist générale module Résidents
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST
- [ ] Vérification des 3 règles d'occupation avant création
- [ ] Désactivation en cascade (résident → occupations → user)
- [ ] `htmlspecialchars()` sur données sensibles affichées
- [ ] Accès restreint aux données santé/financières