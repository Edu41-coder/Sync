# Module Comptabilité (à créer)

## Périmètre
Controller à créer : `ComptabiliteController`
Vues à créer : `app/views/comptabilite/`
Modèle à créer : `Comptabilite`

## Contrôle d'accès

### Rôles autorisés
| Rôle | Périmètre de visibilité |
|------|------------------------|
| `admin` | **Toutes les résidences** — accès complet à toutes les données |
| `comptable` | Uniquement les résidences affectées via `user_residence` |
| `directeur_residence` | Uniquement les résidences affectées via `user_residence` |

```php
$this->requireRole(['admin', 'comptable', 'directeur_residence']);

// Filtrage obligatoire (sauf admin)
if ($currentRole !== 'admin') {
    $residencesAutorisees = User::getResidencesAffectees($_SESSION['user_id']);
    // Toutes les requêtes DOIVENT être filtrées par cette liste
}
```

### ⚠️ Vérification à faire dans le module Admin
Lors de la création d'un user avec le rôle `comptable` :
- [ ] Le formulaire doit afficher la sélection multi-résidences (comme pour `directeur_residence`)
- [ ] L'affectation `user_residence` doit être créée à l'insertion du comptable
- [ ] Sans affectation, le comptable ne voit AUCUNE donnée (pas d'accès par défaut)
- [ ] Vérifier dans `AdminController::createUser()` que la branche `comptable` traite bien les résidences

## Concept : Résumé comptable consolidé par résidence

Vue principale = tableau résumé où **chaque ligne = une résidence**, et **chaque colonne = un poste comptable consolidé** :

| Résidence | Loyers résidents | Recettes hôtes | Restauration | Ménage | Jardinage | Entretien | Travaux | Salaires staff | **Solde** |
|-----------|------------------|----------------|--------------|--------|------------|-----------|---------|----------------|-----------|
| Résidence A | +50 000 € | +2 500 € | +8 000 / -3 200 € | +1 200 / -800 € | +200 / -400 € | -1 500 € | -12 000 € | -25 000 € | **+19 000 €** |
| Résidence B | ... | ... | ... | ... | ... | ... | ... | ... | ... |

Le tableau doit être **triable + recherchable + paginé** (voir CLAUDE.md § Tableaux).
Pour chaque colonne, format `+recettes / -dépenses` (ou seulement la colonne unique si pas de recettes pour ce poste).

## Sources de données à agréger

### Recettes
| Source | Module | Table(s) | Colonne(s) clé(s) | Filtre |
|--------|--------|----------|------------------|--------|
| Loyers résidents | residents | `occupations_residents` | `loyer_mensuel_resident`, `forfait_services` | `statut='actif'` |
| Hôtes temporaires | hotes | `hotes_temporaires` | `tarif_nuit × duree × nb_personnes` | période, statut `termine`/`en_cours` |
| Repas servis | restauration | `rest_services_repas` | `montant` | période, par `residence_id` |
| Factures résidents restauration | restauration | `rest_factures` + `rest_facture_lignes` | `montant_total` | période |
| Services ménage | menage | `menage_comptabilite` | (à voir selon structure) | période |
| Récolte miel (si ruches) | jardinage | `jardin_ruches_visites` (`type=recolte`) × prix kg | `quantite_miel_kg` | période, résidences avec `coproprietees.ruches=1` |

### Dépenses
| Source | Module | Table(s) | Colonne(s) clé(s) |
|--------|--------|----------|------------------|
| Factures fournisseurs restauration | restauration | `rest_factures` | `montant_total` |
| Factures fournisseurs ménage | menage | `menage_commandes` (factures) | `montant_total` |
| Commandes jardinage | jardinage | `jardin_commandes` + `jardin_commande_lignes` | total commande |
| Sorties stock jardinage (coût valorisé) | jardinage | `jardin_inventaire_mouvements` (`type=sortie`) × prix unitaire | `quantite × prix` |
| Interventions entretien | entretien | `entretien_interventions` | `cout` |
| Sorties stock entretien (coût valorisé) | entretien | `entretien_inventaire_mouvements` (`type=sortie`) × prix unitaire | `quantite × prix` |
| Factures fournisseurs entretien | entretien | `factures_fournisseurs` (filtré catégorie entretien) | `montant_ttc` |
| **Travaux (chantiers)** | travaux | `travaux_chantiers` | `montant_paye` (réel) ou `montant_engage` (en cours) |
| Factures travaux | travaux | `travaux_documents` (`type=facture`) → `factures_fournisseurs` | `montant_ttc` |
| Salaires staff | RH | `planning_shifts` × `users_remuneration.taux_horaire_normal` | `heures_calculees` |

### Vue détail par résidence
Cliquer sur une résidence → drill-down avec :
- Comptes comptables détaillés (`comptes_comptables`)
- Écritures (`ecritures_comptables`) filtrées par résidence
- Exercice comptable courant (`exercices_comptables`)
- Export Excel/PDF par période
- **Onglets par module** : Restauration / Ménage / Jardinage / Entretien / Travaux / Salaires
  - Chaque onglet = liste détaillée des écritures du module pour cette résidence
  - Lien vers le module concerné (ex: clic sur une intervention entretien → fiche intervention)

### Distinction Travaux vs Entretien dans la vue
Les deux postes sont **séparés** dans le résumé pour distinguer :
- **Entretien** : dépenses courantes / récurrentes (budget de fonctionnement)
- **Travaux** : chantiers ponctuels / investissements (budget exceptionnel, parfois financés via appels de fonds)

⚠️ Si la décision finale est un module unique `entretien` avec ENUM `type_intervention` (voir @.claude/modules/entretien.md et @.claude/modules/travaux.md), adapter en filtrant sur `type_intervention='travaux_chantier'` pour la colonne Travaux.

## Périodes & filtres
- Filtre période : mois courant, trimestre, année, personnalisée
- Filtre exercice comptable
- Comparaison N vs N-1 (optionnel)

## ⚠️ Section IA — Génération de bulletins de salaire (à implémenter)

**Inspiré du module IA fiscalité** déjà implémenté dans `proprietaires` (utilise `ANTHROPIC_API_KEY` du `.env`, modèle `claude-sonnet-4-20250514`).

### Périmètre initial
Génération automatique des **bulletins de salaire** pour les employés des résidences :
- Source : `planning_shifts` (heures travaillées par employé sur la période)
- Calcul : heures normales × taux + heures supplémentaires × taux majoré
- Sortie : PDF de bulletin de salaire conforme (mentions obligatoires France)

### Architecture suggérée
```
ComptabiliteController::genererBulletins(int $userId, string $periode)
    ↓
ClaudeAIService::generateBulletinSalaire($shifts, $employeData)
    ↓ (appel API Anthropic)
PDF généré + sauvegardé dans uploads/bulletins/{user_id}/{periode}.pdf
```

### Données à passer à Claude
- Identité employé (nom, prénom, n° SS, poste, date embauche)
- Résidence d'affectation
- Période (mois)
- Heures normales + supplémentaires (depuis `planning_shifts.heures_calculees`)
- Taux horaire (à ajouter : table `users_remuneration` ou colonne sur `users`)
- Convention collective applicable (HCR pour restauration, autre pour ménage)
- Cumul annuel

### À prévoir avant implémentation
- [ ] Table `users_remuneration` : `user_id`, `taux_horaire_normal`, `taux_majoration_sup`, `convention_collective`, `date_debut`, `date_fin`
- [ ] Table `bulletins_salaire` : `user_id`, `periode`, `pdf_path`, `montant_brut`, `montant_net`, `cotisations`, `created_at`
- [ ] Endpoint sécurisé : seuls admin + comptable de la résidence peuvent générer
- [ ] Stockage PDF hors `public/` (téléchargement via controller authentifié)
- [ ] Vérification rate-limit API Claude (coût)

### Évolutions futures du module IA comptabilité
- Détection d'anomalies dans les écritures (doublons, montants suspects)
- Suggestions d'affectation comptable automatique
- Génération de rapports financiers narratifs (bilan, compte de résultat)
- Alertes sur écarts budgétaires

## Intégration Messagerie
Tous les rôles comptabilité ont accès à la messagerie (voir @.claude/modules/messagerie.md).
- [ ] Lien "Contacter" disponible vers admin / direction depuis le dashboard comptable

## Modules consolidés dans la comptabilité
Pour comprendre la logique métier de chaque agrégat, se référer aux modules sources :
- @.claude/modules/residents.md — loyers, forfaits, services
- @.claude/modules/hotes.md — séjours courts, tarifs
- @.claude/modules/restauration.md — services repas, factures, inventaire
- @.claude/modules/menage.md (à créer/compléter) — services ménage, fournitures
- @.claude/modules/jardinage.md — produits, outils, miel (si ruches), commandes
- @.claude/modules/entretien.md — interventions, produits, factures
- @.claude/modules/travaux.md — chantiers, devis, garanties, quote-parts
- @.claude/modules/proprietaires.md — fiscalité, contrats de gestion (recettes loyers garantis)
- @.claude/modules/planning.md — heures travaillées (base de calcul salaires)

## À vérifier lors du dev
- [ ] **Filtrage `residence_id` strict** sur TOUTES les requêtes (sauf admin)
- [ ] Un comptable sans `user_residence` voit une page vide (pas d'erreur, juste vide)
- [ ] Aucune fuite cross-résidences (test avec 2 comptables sur résidences différentes)
- [ ] Calcul des soldes recettes - dépenses cohérent (vérifier signes)
- [ ] Période par défaut = mois en cours
- [ ] Export Excel/PDF respecte le filtrage par résidences autorisées
- [ ] Travaux comptés en `montant_paye` (réel) pour le solde, pas en `montant_engage` (engagé != décaissé)
- [ ] Sorties stock valorisées au prix unitaire courant (pas au prix d'achat historique sauf si demandé)

## Checklist générale module Comptabilité
- [ ] **Toutes les listes** ont tri colonnes + recherche + pagination (voir CLAUDE.md § Tableaux)
- [ ] CSRF sur tous les POST (génération bulletin, export, écritures manuelles)
- [ ] `htmlspecialchars()` sur tous les libellés affichés
- [ ] Affectation `user_residence` créée à la création d'un user comptable (à corriger dans Admin si manquant)
- [ ] Drill-down résidence → détail écritures fonctionnel
- [ ] IA bulletins : clé API masquée dans logs, pas exposée côté client
- [ ] PDF bulletins stockés HORS `public/`, accès via controller authentifié uniquement
