# Guide d'utilisation — Personnel (Staff)

**Public** : tous les rôles staff opérationnels (12 rôles différents)

| Rôle BDD | Métier | Section principale |
|---|---|---|
| `accueil_manager` | Manager Accueil | Module Accueil |
| `accueil_employe` | Employé Accueil | Module Accueil |
| `restauration_manager` | Manager Restauration | Module Restauration |
| `restauration_serveur` | Serveur restauration | Module Restauration (service repas) |
| `restauration_cuisine` | Cuisinier | Module Restauration (cuisine + inventaire) |
| `entretien_manager` | Manager Ménage / Entretien | Module Ménage |
| `menage_interieur` | Agent ménage intérieur | Module Ménage (chambres) |
| `menage_exterieur` | Agent ménage extérieur | Module Ménage (zones) |
| `employe_laverie` | Employé laverie | Module Ménage (laverie) |
| `jardinier_manager` | Chef jardinier | Module Jardinage |
| `jardinier_employe` | Jardinier | Module Jardinage |
| `technicien_chef` | Chef technique | Module Maintenance |
| `technicien` | Technicien (selon spécialités) | Module Maintenance |
| `employe_residence` | Employé polyvalent (legacy) | Sinistres + Hôtes |

---

## Connexion (commun à tous)

URL : `https://votre-domaine.fr/login`

Comptes démo :
- `accueil_chef` / `Acc1234` (Manager Accueil)
- `accueil_emp` / `Acc1234` (Employé Accueil)
- `technicien` / `Tech1234` (Technicien)
- `employe_res` / `Emp1234` (Employé Résidence polyvalent)

---

## Pages communes à tous les staff

### Tableau de bord

`/dashboard` (ou page d'accueil après connexion) — adapté selon votre rôle métier. Affiche les actions à mener du jour.

### Mon planning

`/planning/index` — TUI Calendar avec catégories colorées par métier.
- Vous voyez **vos shifts** affectés (par votre manager via le module Planning Staff)
- Filtre par **résidence** (selon vos affectations dans `user_residence`)
- Visualisation par jour / semaine / mois
- Catégorie colorée selon votre métier

### Mes informations RH

`/salarie/mesInfos` — votre fiche RH.

Vous voyez :
- Identité, type de contrat (CDI/CDD/etc.), date d'embauche
- Convention collective applicable, coefficient, catégorie
- Salaire brut de base + taux horaire
- Mutuelle / prévoyance (taux salarial + patronal)

Vous pouvez **modifier uniquement** :
- Votre **IBAN / BIC** (pour le virement de salaire)

Pour toute autre modification → demander via la messagerie auprès du service RH.

### Mes bulletins de paie

`/bulletinPaie/mesBulletins` — liste de vos bulletins **émis** (les brouillons ne sont pas visibles).

Pour chaque bulletin :
- Période (mois / année)
- Salaire brut total
- Net à payer
- Statut (validé / émis)
- Date d'émission
- Bouton **"Voir / Imprimer"** → vue HTML imprimable avec watermark "PILOTE — DOCUMENT NON CONTRACTUEL"

> ⚠️ **Pilote** : les bulletins sont calculés avec les taux 2026 réalistes mais à valider par un cabinet de paie avant utilisation officielle (transmission DSN, justificatif administratif).

### Messagerie interne

Icône 📧 en navbar avec badge dynamique.

- **Recevoir** : notifications de votre manager, demandes de l'admin, communications inter-services
- **Envoyer** : demandes RH, signalements, échanges avec collègues / direction

---

## Module Accueil (`accueil_manager`, `accueil_employe`)

### Tableau de bord Accueil

`/accueil/index` — 5 KPIs : résidences accessibles, résidents hébergés, hôtes présents, notes 7j, **réservations en attente** (cliquable).

### Liste résidents avec notes

`/accueil/residents?residence_id=N` — liste des résidents avec leur fiche.

Sur la fiche résident `/accueil/residentNotes/{id}` :
- Notes texte libre (anniversaires, préférences, infos importantes pour l'équipe)
- Bouton "Nouvelle note" — l'auteur et la date sont enregistrés

### Catalogue salles communes (manager only)

`/accueil/salles?residence_id=N` :
- CRUD : créer, modifier, supprimer
- Photo upload (5 Mo max, JPG/PNG/WEBP)
- Capacité, équipements inclus, statut

### Catalogue équipements prêtables (manager only)

`/accueil/equipements?residence_id=N` :
- 4 catégories : mobilité (déambulateur, fauteuil), info (tablette), loisirs (jeux), médical (tensiomètre)
- Statut : disponible / prêté / en maintenance / hors service

### Réservations

`/accueil/reservations` — liste filtrable.

**Créer une réservation** :
1. Bouton "Nouvelle réservation"
2. Type : salle / équipement / service personnel (coiffeur, pédicure, taxi, etc.)
3. Choisir le résident (avec compte ou hôte temporaire)
4. Plage horaire (début + fin)
5. Notes (optionnel)
6. Le système vérifie **anti-chevauchement** automatiquement

**Workflow** :
- `en_attente` → validation **manager only** (`confirmee` ou `refusee`)
- `confirmee` → l'équipement est marqué `prete`
- À la fin du service : `realisee` (manager only)
- Annulation possible à tout moment par l'employé qui a saisi

### Animations

`/accueil/animations` — liste des shifts catégorie animation (id=14).

**Créer une animation** (manager only) :
- Animateur (staff de la résidence)
- Date / heure
- Lieu (salle commune)
- Description

**Inscriptions résidents** (employé OU manager) :
- Sur la fiche animation, cliquer "Inscrire" → choisir résident
- Pointage présent/absent disponible une fois l'animation commencée
- Annulation = passage en statut `annule`

### Planning double-vue

`/accueil/planning` — TUI Calendar avec **3 onglets** :
- **Résidents** : animations + réservations confirmées + hôtes
- **Staff** : shifts toutes catégories sauf animation
- **Tout** : superposition

### Équipe (manager only)

`/accueil/equipe?residence_id=N` — cartes du staff accueil avec photos, badges rôles, boutons rapides messagerie / email / téléphone.

### Message groupé (manager only)

`/accueil/messageGroupe?residence_id=N` :
- 3 onglets : Résidents (avec compte) / Staff / Propriétaires
- Sélection multi-checkbox + filtres recherche live
- Sujet + priorité + contenu
- Envoi **individuel** à chaque destinataire (pas un thread partagé)

### Hôtes temporaires

`/hote/index` — gestion des séjours courts terme (pas de compte utilisateur).

**Créer un séjour** :
1. Identité hôte (nom, prénom, téléphone, email externe)
2. Lot (vérification disponibilité automatique)
3. Date arrivée / départ / nombre de personnes
4. Tarif / nuit
5. Calcul auto : durée × tarif × nb personnes

---

## Module Restauration (`restauration_*`)

### Tableau de bord Restauration

`/restauration/index` — vue d'ensemble : repas du jour, services en cours, alertes inventaire, équipe présente.

### Catalogue des plats (manager only)

`/restauration/plats` :
- 6 catégories (entrée, plat, dessert, boisson, snack, petit-déjeuner)
- 6 régimes (normal, végétarien, vegan, sans gluten, sans lactose, halal)
- Allergènes (champ libre)
- Calories
- Photo upload

### Menus du jour (manager only)

`/restauration/menus` :
- Création par jour + service (petit-déjeuner / déjeuner / goûter / dîner) + résidence
- Composition : sélectionner les plats du catalogue

### Service de repas (serveur + manager)

`/restauration/service` — saisie temps réel.
- 3 types de client : résident / hôte / passage
- 3 modes facturation : pension complète (forfait, montant 0) / menu / carte
- Affectation au serveur

### Approvisionnement (manager only)

`/restauration/commandes` — multi-lignes, statut workflow brouillon → envoyée → reçue → annulée.

`/restauration/inventaire` — stock courant, mouvements traçables (entrée/sortie/ajustement).

`/restauration/factures` — saisie factures fournisseurs + rapprochement commandes.

### Comptabilité (manager only)

`/restauration/comptabilite` — recettes (services repas) vs dépenses (factures) — visible aux managers uniquement.

### Section laverie (cuisine + manager)

`/restauration/laverie?residence_id=N` — gestion des **cycles d'envoi/retour** du linge de table.

- 6 types de linge : nappes, serviettes de table, torchons, tabliers cuisine, tenue de service, autre
- Workflow : `envoyé` → `reçu` / `partiel` / `perdu`
- Quantité envoyée vs quantité reçue (détection écart automatique)
- Dates envoi / retour
- Coût (pour suivi budgétaire interne — pas facturé au résident)
- Traçabilité : qui a envoyé, qui a réceptionné

> 💡 Différent de la laverie ménage (`/menage/laverie`) qui est un **service vendu aux résidents** pour leur linge personnel (draps, peignoirs).

---

## Module Ménage (`entretien_manager`, `menage_interieur`, `menage_exterieur`, `employe_laverie`)

### Tableau de bord Ménage

`/menage/index` — KPIs filtrés selon votre rôle (intérieur / extérieur / laverie / global manager).

### Section Intérieur (`menage_interieur` + manager)

`/menage/interieur` :
- **Tâches du jour** sur les chambres / lots
- Niveau de service : aucun / basique / premium (impacte le poids horaire)
- Auto-génération des tâches au début de la journée
- Workflow par tâche : `a_faire` → `en_cours` → `termine` (ou `pas_deranger` si résident absent / malade, ou `annule`)
- **Checklist** par tâche (cases à cocher)

Sur la fiche détail `/menage/interieur/tache/{id}` :
- Changer statut
- Cocher items checklist
- Signaler problème

### Section Extérieur (`menage_exterieur` + manager)

`/menage/exterieur` — tâches sur les zones extérieures configurables.

### Zones extérieures (manager only)

`/menage/zones` — CRUD des zones par résidence.
- Type : terrasse, parking, entrée, local poubelles, couloir, ascenseur, jardin, piscine, salle commune, autre
- Fréquence : quotidien / hebdo / bihebdo / mensuel
- Jour de la semaine + priorité

### Section Laverie (`employe_laverie` + manager)

`/menage/laverie` :
- **Service à la demande** des résidents (workflow : demandée → en cours → prête → livrée → facturée)
- Liaison résident pour facturation directe
- Tarifs configurables par résidence et type de linge (draps, serviettes, peignoir, linge personnel)

### Catalogue produits ménage

`/menage/produits` — manager only en CRUD, lecture pour les autres.

### Inventaire

`/menage/inventaire` — accessible à tous, mouvements traçables.

### Comptabilité ménage (manager strict)

`/menage/comptabilite` — **bloqué pour les rôles d'exécution** (`menage_interieur`, `menage_exterieur`, `employe_laverie`).

---

## Module Jardinage (`jardinier_manager`, `jardinier_employe`)

### Tableau de bord

`/jardinage/index` — 4 KPIs : espaces (+ ruchers), catalogue, ruches actives + visites, alertes.

**Bandeaux d'alerte** :
- Stock bas (manager)
- Ruches sans visite > 30j
- **Traitements obligatoires cette période** (rouge — varroa, nourrissement)

### Espaces jardin (manager CRUD, employé lecture)

`/jardinage/espaces` :
- 12 types : potager, parterre, pelouse, haie, arbre fruitier, serre, verger, rocaille, bassin, compost, **rucher**, autre
- Photos, surface, description
- Tâches récurrentes par espace

### Catalogue produits & outils (manager only)

`/jardinage/produits` :
- 11 catégories : engrais, terreau, semence, plant, phytosanitaire, outillage, arrosage, etc.
- Mention BIO / danger
- Photos

### Inventaire

`/jardinage/inventaire` :
- Sélection résidence
- **Mouvement stock** : entrée / sortie / ajustement avec motif (livraison, usage, perte, casse, inventaire)
- Imputation à un espace (pour coût par espace)
- **Refus stock négatif** + transaction FOR UPDATE

### Commandes fournisseurs (manager only)

`/jardinage/commandes` — workflow brouillon → envoyée → livrée_partiel → livrée → facturée.
Réception = entrée stock auto.

### Apiculture (si résidence avec ruches)

#### Configuration (manager edit, employé lecture)
`/jardinage/apiculture?residence_id=X` — NAPI, déclaration préfecture, référent, type rucher, distance habitations.

#### Ruches
`/jardinage/ruches` :
- CRUD (manager only)
- Photo, statut (active/essaim/inactive/morte), espace rucher
- Détail `/jardinage/ruches/show/{id}` :
  - Carnet de visite avec tri/recherche/pagination
  - **Traitements recommandés** (3 états : fait ✓ / à faire / hors fenêtre)
  - **Historique statut** (timeline)
  - **Bouton export CSV** du carnet (conforme pratique FR)

#### Ajouter une visite (employé + manager)
- Type intervention (visite, nourrissement, traitement, récolte)
- État couvain, reine vue
- Kg miel (si récolte)
- Produit traitement (si traitement)
- Observations

#### Traitements saisonniers (manager only)
`/jardinage/traitements` :
- 6 templates seedés (varroa été/hiver, nourrissement, etc.)
- Alertes auto si fenêtre dépassée sans traitement
- Bandeau rouge dashboard

### Comptabilité jardinage (manager strict)

`/jardinage/comptabilite` — **bloquée pour `jardinier_employe`**.
- Chart.js, coût par espace, récoltes miel à comptabiliser

### Équipe (manager only)

`/jardinage/equipe` — staff jardinage avec contacts.

---

## Module Maintenance Technique (`technicien_chef`, `technicien`)

### 6 spécialités gérées

| Spécialité | Certification obligatoire | Organisme |
|---|---|---|
| Piscine | ✅ | ARS / Préfecture |
| Ascenseur | ✅ | COFRAC / Bureau de contrôle |
| Travaux | — | — |
| Plomberie | — | — |
| Électricité | ✅ | CONSUEL / habilitation |
| Peinture | — | — |

### Affectation par spécialités

Le `technicien` voit uniquement les sections correspondant aux spécialités cochées dans sa fiche par le manager (`/maintenance/specialites`, manager only).

### Tableau de bord

`/maintenance/index` — vue d'ensemble + alertes certifications expirantes (3 mois avant).

### Certifications

`/maintenance/certifications/{userId?}` — liste vos certifications avec date d'obtention + date d'expiration + fichier preuve.
- Vous voyez **vos** certifications
- Manager voit toutes

### Interventions courantes

`/maintenance/interventions` — liste filtrée par vos spécialités + résidences accessibles.

**Créer une intervention** :
1. Bouton "Nouvelle intervention"
2. Spécialité, type, résidence, lot (si applicable)
3. Date, durée estimée, technicien assigné
4. Photos avant
5. Workflow 4 statuts

### Piscine (manager + spé piscine)

`/maintenance/piscine` — journal de bord :
- Analyses chimiques (pH, chlore)
- **Alertes auto** : pH hors plage 7.0-7.6, chlore <1 mg/L (critique <0.5)
- Contrôles ARS, hivernage
- Upload PV ARS

### Ascenseurs (manager + spé ascenseur)

`/maintenance/ascenseurs` :
- Entité 1:N par résidence
- Journal des entrées (maintenance préventive, visite annuelle, contrôle quinquennal)
- **Auto-calcul prochaine échéance** selon périodicité
- Alertes : visite annuelle expirée / dans <30j, contrôle quinquennal expiré, etc.

### Chantiers (manager only en édition)

`/chantier/index` — workflow 9 phases (avant-projet → étude → consultation → décision → exécution → réception → garantie → close).

**Créer un chantier** :
- Auto-coché `nécessite_ag` si montant_estimé > 5 000€ HT
- Multi-devis avec workflow décision (retenir / refuser)
- Jalons d'avancement (% complétion)
- Quote-part propriétaires (table `chantier_lots_impactes`)
- **3 garanties auto à la réception** : parfait achèvement (1 an) / biennale (2 ans) / décennale (10 ans)

### Stock maintenance

`/maintenance/produits` — manager only (CRUD).
`/maintenance/inventaire` — accessible à tous.
`/maintenance/commandes` — manager only.

### Comptabilité (manager strict)

`/maintenance/comptabilite` — agrégation interventions + chantiers + ascenseurs + Chart.js. **Techniciens exclus**.

### Équipe (manager strict)

`/maintenance/equipe` — vue cards techniciens + spécialités + certifications + alertes expirantes.

---

## Module Sinistres (commun : staff de la résidence)

`/sinistre/index` — accessible à tous les staff de la résidence pour déclarer un sinistre.

### Déclarer un sinistre
1. Bouton "Déclarer un sinistre"
2. Type (9 choix : dégât eaux, incendie, vol, bris, etc.) + gravité (mineur/modéré/majeur/catastrophe)
3. Lieu : **soit** un lot **soit** une partie commune (XOR strict)
4. Date survenue + date constat
5. Description détaillée
6. **Upload pièces** (constat amiable, photos, devis, etc.)
7. Soumettre

### Suivi
- Workflow : déclaré → transmis assureur → expertise → réparation → indemnisé → clos / refusé
- L'admin / direction gère les changements de statut
- Vous pouvez ajouter des documents complémentaires

---

## Module Documents (lecture limitée pour le staff)

`/document/index` — **pas accessible aux rôles staff classiques**. Seuls admin / directeur / exploitant / comptable y ont accès.

Pour récupérer un document de votre résidence (procédure interne, charte) → demandez à votre manager.

---

## FAQ Staff

**Q : Mon manager ne voit pas mes heures de la semaine, pourquoi ?**
A : Vérifier que vos shifts sont bien créés dans `/planning/index` avec votre `user_id` et la bonne résidence. Sans shift créé par votre manager, vous n'apparaissez pas au planning.

**Q : Je ne reçois pas mon bulletin de paie ?**
A : Les bulletins n'apparaissent dans votre espace que **lorsqu'ils sont émis** (statut `emis`). Si votre bulletin reste en `valide` ou `brouillon`, il n'est pas encore prêt à vous être communiqué. Demandez à votre RH.

**Q : Comment changer mon mot de passe ?**
A : Pas de fonction self-service dans cette version pilote. Demandez à votre manager qui transmet à l'admin.

**Q : Pourquoi je n'ai pas accès à la comptabilité de mon module ?**
A : La comptabilité est **réservée aux managers** par règle métier stricte. Si vous êtes manager et n'avez pas accès → vérifier votre rôle exact (`*_manager` requis).

**Q : Comment savoir si je dois m'inscrire à une formation pour ma certification ?**
A : `/maintenance/certifications/{votre_id}` → vérifiez les dates d'expiration. Une alerte apparaît 3 mois avant. Discutez-en avec votre manager.

**Q : Une tâche reste bloquée en "à faire", pourquoi ?**
A : Vérifier que vous avez bien le rôle adapté (`menage_interieur` pour les chambres). Aussi, certaines tâches sont assignées à un employé spécifique — vous ne pouvez modifier que les vôtres.

**Q : Mon planning affiche des shifts d'une autre résidence, c'est normal ?**
A : Si vous êtes affecté à plusieurs résidences (`user_residence`), oui. Sinon, vérifiez avec votre manager qui peut filtrer.

**Q : J'ai pris une photo "avant" sur une intervention mais elle ne s'affiche pas ?**
A : Vérifier la taille du fichier (max 5 Mo) et le format (JPG, PNG, WEBP). Les autres formats sont rejetés à l'upload.

**Q : Une demande de relance impayé est arrivée par messagerie, que faire ?**
A : Si vous êtes salarié, vous n'êtes pas concerné par les relances. Celles-ci sont gérées exclusivement par le service comptable / direction.

---

## Vos données personnelles (RGPD)

### Ce que Domitys stocke sur vous
- Identité, contact, photo profil
- Affectations résidences (`user_residence`)
- Fiche RH si créée : numéro SS, dates contrat, salaire, IBAN, mutuelle
- Historique des bulletins de paie
- Vos shifts au planning

### Vos droits
- **Accès** : voir vos infos dans `/salarie/mesInfos`
- **Modification IBAN** : self-service
- **Autres modifications** : via la messagerie auprès du RH
- **Audit trail** : toutes les modifications sur votre fiche sont tracées (consultable par admin/RH uniquement)

### IBAN sécurisé
Votre IBAN est **masqué dans tous les logs d'audit** (`FR76********1234`) — seule la valeur en BDD est complète, accessible uniquement aux rôles autorisés (admin, comptable).
