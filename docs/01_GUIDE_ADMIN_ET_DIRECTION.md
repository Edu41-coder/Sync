# Guide d'utilisation — Admin & Direction

**Public** : `admin`, `comptable`, `directeur_residence`, `exploitant`

---

## Connexion

URL : `https://votre-domaine.fr/login`

| Rôle | Username démo | Mot de passe démo |
|---|---|---|
| Administrateur | `admin` | `admin123` |
| Comptable | `comptable` | `Comptable1234` |
| Directeur de résidence | `dir_residence` | `Dir1234` |

> ⚠️ **Sécurité** : changer ces mots de passe en production. Le mot de passe est visible par l'admin (`password_plain`) — fonctionnalité utile pour les démos mais à désactiver en prod.

---

## Périmètre par rôle

| Action | admin | comptable | directeur_residence | exploitant |
|---|:---:|:---:|:---:|:---:|
| Toutes les résidences | ✅ | ❌ ses résidences | ❌ ses résidences | ❌ ses résidences |
| Créer / modifier / supprimer un user | ✅ | ❌ | ❌ | ❌ |
| Créer / modifier une résidence | ✅ | ❌ | ❌ | ❌ |
| Gérer lots, occupations, contrats | ✅ | ❌ | ✅ | ✅ |
| Voir comptabilité | ✅ | ✅ | ✅ | ✅ |
| Saisir écritures comptables | ✅ | ✅ | ✅ | ✅ |
| Clôturer un exercice | ✅ | ✅ | ✅ | ✅ |
| **Ré-ouvrir** un exercice clôturé | ✅ | ❌ | ❌ | ❌ |
| Bulletins de paie | ✅ | ✅ | ✅ | ❌ |
| Salariés / fiches RH | ✅ | ✅ | ✅ | ❌ |
| Module Audit Trail | ✅ | ✅ | ✅ | ❌ |

---

## Navigation principale

Une fois connecté, votre navbar comporte selon votre rôle :
- **Tableau de bord** (page d'accueil)
- **Administration** : users, résidences, lots, carte
- **Résidents & Occupations**
- **Hôtes temporaires**
- **Planning staff**
- **Modules métier** : Restauration, Ménage, Jardinage, Maintenance, Accueil
- **Sinistres** + **Documents** + **Assemblées Générales**
- **Comptabilité** (le plus riche — voir section dédiée plus bas)
- **Messagerie** (avec badge non-lus)

---

## Module Administration

### Créer un nouvel utilisateur

1. **Admin → Users → Nouveau**
2. Remplir : nom d'utilisateur, email, mot de passe, prénom, nom, rôle
3. **Si rôle propriétaire ou résident senior** → impossible de modifier le rôle après création (verrou métier)
4. **Si rôle staff** → cocher les résidences d'affectation (table `user_residence`)
5. Le mot de passe est stocké hashé + en clair (`password_plain`) — le clair n'est visible qu'à vous
6. Cliquer "Créer"

### Désactiver un utilisateur

- Bouton "Désactiver" sur la fiche → `actif = 0`
- Si le user a une fiche résident liée → terminer ses occupations actives **automatiquement** (cascade)
- Si le user est admin → action **bloquée** (compte verrouillé)

### Créer une résidence

1. **Admin → Résidences → Nouvelle résidence**
2. Renseigner adresse → géocodage automatique au `blur` du champ (Leaflet + API gouvernementale)
3. Cocher les options : ascenseur, piscine, ruches (active les modules correspondants)
4. À la création → l'exploitant Domitys (id=1) est lié automatiquement à 100%

### Suppression d'une résidence

- Si **vierge** (0 lot, 0 user) → suppression hard (DELETE)
- Sinon → **soft delete** (`actif = 0`), exclue des listes par défaut

---

## Module Comptabilité (le plus riche)

### Tableau de bord

URL : `/comptabilite/index`

- **Bandeau "Actions à mener"** en haut : bulletins en brouillon, déclarations TVA non transmises, opérations bancaires à rapprocher, salariés sans fiche RH
- **6 KPIs** : Recettes / Dépenses / Résultat / TVA collectée / TVA déductible / TVA à reverser — avec variation N vs N-1
- **Section Maintenance** séparée (orange) : interventions + chantiers + ascenseurs
- **8 cards "Accès rapides"** : Assistant IA, Assistant Paie IA, TVA, Bilan, Rapprochement, Exports, Exercices, Audit
- **Chart.js** : évolution mensuelle 4 séries (R N + D N + R N-1 + D N-1) + camembert ventilation modules
- **Top 5 résidences** par volume + 10 dernières écritures

### Consulter les écritures détaillées

`/comptabilite/ecritures` — table filtrable (résidence, année, mois, module, type, recherche libellé), DataTable 25/page, limite 1000 lignes serveur.

### Balance comptable

`/comptabilite/balance` — agrégation par compte avec totaux Débit / Crédit / Solde.
- **Bouton 📖 Grand livre** sur chaque ligne pour voir le détail des écritures du compte
- **Vérification équilibre** : badge ✅ si débit = crédit, ⚠️ sinon

### Grand livre

`/comptabilite/grandLivre` — sélectionner un compte → liste de toutes les écritures avec **solde progressif** ligne par ligne.

### Bilan

`/comptabilite/bilan` — bilan simplifié actif/passif par exercice :
- **Actif** : immobilisations / créances tiers / TVA déductible / trésorerie
- **Passif** : capitaux propres (incl. résultat) / dettes fournisseurs / dettes personnel / dettes sociales fiscales / TVA collectée
- Synthèse : total produits − total charges = résultat net
- ⚠️ **Pilote** : pas d'amortissements, pas de bilan d'ouverture

### SIG (Soldes Intermédiaires de Gestion)

`/comptabilite/sig` — cascade comptable normalisée :
```
Production de l'exercice (comptes 70x sauf 701, 758)
− Consommations en provenance des tiers (60x, 61x, 62x)
= VALEUR AJOUTÉE
− Charges de personnel (64x)
− Impôts et taxes (63x)
= EXCÉDENT BRUT D'EXPLOITATION (EBE)
= Résultat d'exploitation (pas d'amortissements pilote)
+ Produits exceptionnels (701, 758)
− Charges exceptionnelles (67x)
= RÉSULTAT NET
```

### Exercices comptables

`/comptabilite/exercices` :
- **Nouvel exercice** : bouton + modal (résidence + année + budget)
- **🔒 Clôturer** : gel des écritures (statut `validee` → `cloturee`), refus si brouillons restants
- **🔓 Ré-ouvrir** (admin only) : dégèle les écritures en cas de correction comptable exceptionnelle
- **🗄️ Archiver** : statut final, irréversible UI

### Déclarations TVA (CA3 / CA12)

`/comptabilite/tva` :
- Liste des déclarations archivées avec statut (brouillon / déclarée / annulée)
- Bouton **"Nouveau calcul"** → choisir résidence + régime (mensuel/trimestriel/annuel) + période
- Le système calcule automatiquement les assiettes par taux (20/10/5,5/2,1/exonéré) à partir des écritures
- **Inférence du taux TVA** quand `taux_tva` est NULL (cas legacy) via ratio TVA/HT
- Récupération auto du **crédit antérieur** depuis la déclaration précédente
- Bouton **"Archiver"** → crée le brouillon en BDD
- Sur la fiche : **mapping CERFA 3310-CA3** lignes 01 à 32, bouton "Marquer comme transmise au SIE"

### Exports comptables (FEC + CSV + Cegid)

`/comptabilite/export` — choisir période + résidences + modules → 3 boutons de téléchargement :
- **FEC DGFIP** (.txt, séparateur tab, 18 colonnes obligatoires art. A.47 A-1 LPF) — pour contrôle fiscal
- **CSV Excel** (.csv, BOM UTF-8, séparateur `;`) — analyse interne
- **Cegid Quadratus** (.csv) — import cabinet comptable

⚠️ Tous les exports sont **tracés dans l'audit trail** (obligation fiscale).

### Rapprochement bancaire

`/comptabilite/rapprochement` :
- **Importer un relevé CSV** (séparateur `;` ou `,`, format date FR ou ISO, max 5 Mo)
- Le système parse automatiquement les opérations
- Sur la fiche import : 4 KPIs (total / rapprochées / à rapprocher / total débits-crédits)
- Pour chaque opération non rapprochée : bouton **"X match(s)"** → liste les écritures comptables candidates avec **score 0-100** (40 date + 40 montant + 20 libellé)
- Cliquer "Rapprocher" → l'opération + l'écriture sont liées
- Bouton "Ignorer" pour les frais bancaires hors compta

### Audit trail (traçabilité légale)

`/comptabilite/auditTrail` :
- Liste filtrable par utilisateur, action, table, période, recherche
- **18 actions tracées** automatiquement : ecriture_*, exercice_*, bulletin_*, tva_*, bank_*, salarie_*, export_*, quittance_*, relance_*, impaye_*
- Détails JSON des changements (avec **masquage IBAN** : `FR76********1234`)
- IP + user_agent capturés
- Conforme **PCG art. 410-1** (traçabilité écritures comptables) + **RGPD art. 30**

### Quittances résidents

`/comptabilite/quittancesResidents` :
- Liste filtrable par résidence / année / mois / statut
- Bouton **"Générer les quittances d'un mois"** → modal :
  - Choisir résidence (ou toutes accessibles) + année + mois
  - Cocher "Notifier les résidents par messagerie interne" (recommandé)
  - Cliquer "Générer" → 1 quittance créée par occupation active sur le mois
- Sur chaque ligne : bouton "Détail" + "PDF" (HTML imprimable avec watermark "PILOTE — NON CONTRACTUEL")
- Sur la fiche détail : **"Marquer comme payée"** (montant + mode paiement + référence + date) + historique relances

### Gestion des impayés

`/comptabilite/impayes` :
- **Auto-escalade** à l'ouverture : quittances `emise` depuis > 10 jours basculées en `impayee`
- Vue unifiée 3 sources : quittances résidents impayées + paiements propriétaires retard/impayé + factures fournisseurs en retard
- 4 KPIs : total impayés / montant total / + de 60j / récents (≤15j)
- Filtres par source : Tous / Résidents / Propriétaires / Fournisseurs
- Sur chaque ligne : badge **niveau de retard** (vert/orange/rouge/noir) + niveau dernière relance
- Bouton **🔔 Relancer** → modal :
  - Niveau auto-déterminé (1 si aucune relance, sinon niveau précédent + 1)
  - Sujet + corps **pré-remplis depuis le template** (modifiables)
  - Variables auto : {prenom}, {nom}, {montant}, {periode}, {numero}, {residence}, {date_n1}, {contact}
  - Envoi via **messagerie interne** au destinataire
- Bouton **✓ Marquer payé** → met à jour la source + clôt toutes les relances actives

### Bulletins de paie

`/bulletinPaie/index` — workflow brouillon → validé → émis → annulé.

**Créer un bulletin** :
1. **Nouveau bulletin** → choisir un user + année + mois
2. Le système **importe automatiquement les heures** depuis `planning_shifts` du mois
3. Calcul réaliste avec taux 2026 (URSSAF, AGIRC-ARRCO, CSG/CRDS, mutuelle, prévoyance)
4. Vérifier le breakdown brut → cotisations → net à payer
5. Cliquer "Créer" → statut `brouillon`

**Workflow** :
- **Valider** : brouillon → validé (visible RH)
- **Émettre** : validé → émis → **visible par le salarié dans son espace**
- **Annuler** : avec motif (statut `annule` mais conservé pour audit)
- **Supprimer** : réservé aux brouillons

**Vue imprimable** : bouton "Imprimer / PDF" → page autonome avec toutes les mentions légales obligatoires + watermark "PILOTE — DOCUMENT NON CONTRACTUEL".

### Salariés & fiches RH

`/salarie/index` — liste des staff avec ou sans fiche RH.

Pour chaque user staff :
- **Voir** : fiche RH complète (identité, contrat, convention collective, salaire, IBAN, mutuelle, prévoyance)
- **Éditer** : créer ou modifier — auto-calcul du taux horaire si salaire renseigné

### Assistants IA Comptable & Paie

`/comptabilite/assistant` (Claude Sonnet, contexte chiffré injecté ~700 tokens) :
- Filtrer résidence + année + mois
- 6 suggestions cliquables : Vue d'ensemble / Analyse dépenses par module / Comparaison N vs N-1 / Détection anomalies / Préparation TVA / Optimisation budgétaire
- Réponses en français, format markdown léger, **rappels constants "validation expert-comptable nécessaire"**

`/bulletinPaie/assistant` :
- Filtrer année + mois
- 7 suggestions : État de la paie / Couverture fiches RH / Anomalies / Simulation brut→net / Heures sup / Conventions collectives / Préparation DSN
- Connaît : Code du travail FR, taux 2026, 8 conventions collectives (HCR, Services à la personne, Aide à domicile, Paysage, Immobilier, BTP)

---

## Modules métier

### Planning staff

`/planning/index` — TUI Calendar v1.15.3 avec 13 catégories colorées.
- Vue semaine par défaut, vue jour/mois disponibles
- Drag & drop pour réaffecter un shift
- Filtre par résidence (selon vos affectations)
- Heures normales vs supplémentaires calculées automatiquement

### Sinistres

`/sinistre/index` — déclaration et suivi.
- **Déclarer** un sinistre : 9 types, 4 niveaux gravité, lieu (lot ou partie commune — XOR strict)
- **Workflow** : déclaré → transmis assureur → expertise → réparation → indemnisé → clos
- **Upload pièces** : constats, photos, devis, expertises, courriers (max 50 Mo, MIME whitelist)
- **Bouton "Créer un chantier de réparation"** depuis la fiche → bascule vers `/chantier/form?sinistre_id=X`
- **Audit trail** dédié visible dans `sinistres_log`

### Documents (GED)

`/document/index` — GED admin/direction.
- 2 onglets : **Global Domitys** (modèles, contrats cadre) ou **Par résidence**
- Arborescence libre de dossiers + sous-dossiers
- Upload jusqu'à 50 Mo par fichier (18 extensions whitelist)
- **Triple validation** : taille → extension → MIME finfo (anti-polyglot)
- Preview inline pour images/vidéos/PDF, download pour le reste
- Bandeau "Lecture seule" si vous n'avez pas le droit d'écriture sur le scope

### Assemblées Générales

`/assemblee/index` — gestion AGO/AGE.
- **Créer** une AG : type (ordinaire/extraordinaire), date, lieu, mode (présentiel/visio/mixte), OdJ
- **Convoquer** : modal "changer statut" + upload PDF convocation → date d'envoi auto-renseignée
- **Envoyer convocation par messagerie** : bouton orange → arrive sur `/accueil/messageGroupe?ag_id=X` avec propriétaires pré-cochés
- **Tenir** : modal saisie PV + quorum + président + secrétaire + upload PV
- **Résolutions** : ajouter avec votes (voix + tantièmes) → résultat calculé automatiquement
- **Lien chantiers** : sur la fiche AG, "Chantiers en attente d'AG" (alerte orange) + "Chantiers liés"

---

## Modules opérationnels (vue manager)

Pour Restauration / Ménage / Jardinage / Maintenance / Accueil — voir leurs modules dédiés. En tant qu'admin/direction, vous y avez **accès complet** :
- Dashboards avec KPIs
- Catalogues (plats / produits / équipements / etc.)
- Inventaires + commandes fournisseurs
- Planning + équipes
- **Comptabilité** dédiée par module (visible aux managers uniquement)

---

## FAQ Admin

**Q : Comment voir si un mot de passe est correct sans le réinitialiser ?**
A : Sur la fiche user → bouton 👁️ "Voir mot de passe" (seul l'admin peut, depuis `password_plain`).

**Q : Un user staff voit toutes les résidences, c'est normal ?**
A : Non, vérifiez ses affectations dans `user_residence`. Sans affectation, un comptable / directeur ne voit AUCUNE donnée.

**Q : J'ai supprimé un exercice par erreur, comment le récupérer ?**
A : Pas possible. Les exercices clôturés sont gelés mais peuvent être ré-ouverts (admin only). Les exercices archivés sont récupérables via une restauration de backup.

**Q : Pourquoi une écriture n'apparaît pas au Bilan/SIG ?**
A : Probablement parce qu'elle n'a pas de `compte_comptable_id` affecté (cas écritures legacy avant Phase 0 compta). Visible dans le bandeau "Actions à mener" du dashboard. À affecter manuellement.

**Q : Un bulletin de paie a un montant suspect, que faire ?**
A : Annuler (motif obligatoire) → créer un nouveau brouillon → vérifier les heures importées du planning → ajuster manuellement si besoin → valider → émettre.

**Q : Comment savoir qui a fait quoi en comptabilité ?**
A : `/comptabilite/auditTrail` — filtrer par utilisateur, action ou période. 18 actions sont tracées automatiquement avec ip + user_agent.

**Q : Les exports FEC sont-ils acceptés par l'administration fiscale ?**
A : Le format est **conforme** à l'art. A.47 A-1 LPF (18 colonnes, séparateur tabulation, UTF-8). En cas de contrôle, votre cabinet comptable doit valider que toutes les écritures attendues y figurent (cohérence avec le bilan transmis).

**Q : Un résident me demande sa quittance pour la CAF, où la trouver ?**
A : Soit lui dire de la télécharger lui-même depuis son espace `/resident/mesQuittances`, soit aller dans `/comptabilite/quittancesResidents` → cliquer sur la quittance → bouton "Imprimer / PDF".
