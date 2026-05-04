# Module Appels de Fonds

## État : 💤 Feature dormante — à activer si demande client

Le model et les tables BDD existent depuis le début mais aucun controller ni vue n'a jamais été créé. **À implémenter uniquement si Domitys (ou un autre client) le demande explicitement.**

## Pourquoi dormant ?

Domitys exploite ses résidences via un **bail commercial** : le propriétaire reçoit un loyer garanti net de charges, donc les appels de fonds classiques de copropriété (provisions trimestrielles, fonds travaux ALUR, régularisation annuelle) **ne s'appliquent pas dans le flux normal**.

Cas où la feature redeviendrait utile :
1. **Gros travaux structurels** non couverts par le bail (toiture, ravalement, sinistre majeur) → quote-part appelée aux propriétaires
2. **Cession totale du bail** par Domitys → la résidence redevient une copro classique
3. **Fonds travaux ALUR** si la jurisprudence évolue défavorablement pour les résidences-services
4. **Pivot du produit** vers le marché des syndics classiques (élargissement)

## Ce qui existe déjà

| Élément | État | Fichier |
|---|---|---|
| Tables BDD `appels_fonds` + `lignes_appel_fonds` | ✅ | [database/synd_gest.sql](../../database/synd_gest.sql) |
| Model complet (CRUD + lignes + stats) | ✅ | [app/models/AppelFonds.php](../../app/models/AppelFonds.php) (240+ lignes) |
| Préférence email user `email_appel_fonds` | ✅ | [app/views/users/settings.php](../../app/views/users/settings.php), [UserController.php:71](../../app/controllers/UserController.php) |
| Controller `AppelFondsController.php` | ❌ | À créer |
| Vues `app/views/appels_fonds/` | ❌ | À créer |
| Lien navbar | ❌ | À ajouter conditionnellement (admin/comptable) |

## Plan d'activation (si déclenché)

### Étape 1 — Branchement Chantiers (1-2 j)
- Bouton "Générer un appel de fonds" sur la fiche d'un chantier validé
- Calcul automatique des quote-parts via `travaux_lots_impactes` (déjà existant module Travaux)
- Workflow statut : `genere → envoye → paye_partiel → solde`

### Étape 2 — Espace propriétaire (1-2 j)
- Onglet "Mes appels de fonds" dans `/coproprietaire/`
- Liste filtrée par `proprietaire_id` (sécurité stricte — voir `.claude/modules/proprietaires.md`)
- Téléchargement bordereau PDF (mention légale + RIB)

### Étape 3 — Controller + vues admin (1 j)
- `AppelFondsController` : index, show, create (formulaire multi-lignes), update, delete
- `requireRole(['admin', 'comptable'])` strict
- CSRF via le pattern `requirePostCsrf()` (cohérent avec le reste du projet)
- Notification email aux propriétaires concernés (la préférence existe déjà → juste à câbler)
- Export CSV pour rapprochement bancaire (BOM UTF-8 + `;`, pattern jardinage/comptabilité)

### Étape 4 — Décision lourde : gestion des paiements
- **Option A** — Suivi manuel (admin coche "payé") → 0.5 j
- **Option B** — Intégration paiement bancaire (Stripe, GoCardless) → 1-2 semaines + frais transaction
- **Option C** — Import relevé bancaire CSV avec matching auto → 3-5 j

**Total min sans paiement intégré : ~5 jours de dev.**

## À vérifier avant activation

- [ ] **Validation business** : Domitys (ou client) confirme qu'il en a besoin réellement, pas juste "ce serait bien"
- [ ] Périmètre exact des cas d'usage (travaux structurels uniquement ? Fonds ALUR ? Régularisation charges ?)
- [ ] Source des quote-parts (tantièmes des lots ? autre clé de répartition ?)
- [ ] Modalités paiement (chèque admin coché ? virement ? prélèvement SEPA ?)
- [ ] Gabarit du bordereau PDF (mentions légales obligatoires France selon situation)
- [ ] Délai légal de paiement à appliquer
- [ ] Procédure de relance (combien de relances ? majorations de retard ?)

## Décision documentée (2026-05-04)

Discussion avec utilisateur : feature laissée dormante car non prioritaire pour Domitys dans le modèle actuel (bail commercial, loyer garanti). Le model est conservé pour ne pas perdre le travail déjà fait, mais aucune ressource investie sur la partie UI tant qu'un client n'en formule pas la demande explicite.

Si réveil de la feature : commencer par Étape 1 (branchement Chantiers) qui apporte le plus de valeur immédiate avec le moins d'effort.
