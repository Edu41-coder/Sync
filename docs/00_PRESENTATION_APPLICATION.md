# Synd_Gest — Présentation de l'application

**Plateforme de gestion intégrée pour résidences-services seniors (modèle Domitys)**

---

## En bref

Synd_Gest est une application web modulaire couvrant l'ensemble des opérations d'une résidence-services seniors :

- **Gestion administrative** : résidences, lots, propriétaires, contrats de gestion
- **Gestion résidentielle** : profils résidents seniors, occupations, services
- **Gestion opérationnelle** : restauration, ménage, jardinage, maintenance, accueil, animations
- **Gestion comptable** : 13 phases livrées (compta unifiée, paie, TVA, exports FEC, IA assistants, rapprochement bancaire, quittances, impayés)
- **Conformité** : RGPD, audit trail légal, FEC DGFIP, PCG art. 410-1
- **Espaces personnels** : propriétaires, résidents, salariés (chacun voit ses propres données)

**Stack technique** : PHP 8.2 · MariaDB 10.4 · Bootstrap 5.3 · Chart.js · Leaflet · TUI Calendar · Claude AI

---

## Catalogue des 14 modules

### 1. Module Admin (✅ production)

Gestion centrale de la plateforme.

- **Users & rôles** : 18 rôles dynamiques (admin, comptable, direction, propriétaires, résidents, 12 rôles staff)
- **Résidences (`coproprietees`)** : géocodage Leaflet, normalisation ville, soft delete intelligent (hard si vierge, soft sinon)
- **Lots** : 6 types (studio, t2, t2_bis, t3, parking, cave) + terrasse/loggia
- **Carte interactive** : 22+ résidences géolocalisées avec marqueurs colorés
- **Migrations BDD** : interface `/admin/migrate` pour appliquer les migrations SQL

### 2. Module Propriétaires & Contrats (✅ production)

Le propriétaire investit dans les lots, Domitys garantit le loyer.

- **Profils propriétaires** : 1:1 avec users (rôle `proprietaire`)
- **Contrats de gestion** : 1 actif/lot, 4 dispositifs fiscaux (LMNP réel/micro, Censi-Bouvard, nue-propriété)
- **Loyers garantis mensuels** : table `paiements_loyers_exploitant` (statut payé/retard/impayé/litige)
- **Espace propriétaire** : tableau de bord, mes lots, mes résidences, calendrier, GED 1 GB, comptabilité, déclaration fiscale assistée IA, AG en lecture, **quittances loyer garanti** (PDF imprimable)

### 3. Module Résidents Seniors & Occupations (✅ production)

Les résidents louent un logement + bénéficient de services.

- **Profils résidents** : 34 champs (santé, CNI, contact urgence, médecin, allergies, APL/APA)
- **Occupations** : 1 occupation active/lot, 1 résident peut louer plusieurs lots dans plusieurs résidences
- **Services** : catalogue inclus/supplémentaires, prix appliqué négociable
- **Espace résident** : tableau de bord, mes lots, mes occupations, vitrine résidences (carte), calendrier auto-généré (loyers + animations + rappels fiscaux), GED 500 MB, comptabilité, **mes quittances** (téléchargement PDF), déclaration fiscale assistée IA

### 4. Module Hôtes Temporaires (✅ production)

Visiteurs courts séjours sans compte utilisateur (séjours d'essai, convalescence, famille).

- **Pas de user_id** : géré par fiche autonome
- Anti-chevauchement lot vérifié (résident actif + autre séjour hôte)
- Calcul auto durée × tarif × nb personnes

### 5. Module Planning Staff (✅ production)

Affectation horaire de tout le personnel.

- **TUI Calendar v1.15.3** avec catégories colorées
- **13 catégories** : ménage, restauration, technique, jardinage, animation, etc.
- Heures normales vs supplémentaires (calcul auto via colonne MySQL GENERATED)
- Drag & drop pour réaffectation

### 6. Module Restauration (✅ production)

Service repas + approvisionnement.

- **Catalogue plats** : 6 catégories, 6 régimes (vegan, sans gluten, halal...), allergènes, photo
- **Menus du jour** : 4 services (petit-déjeuner, déjeuner, goûter, dîner)
- **Suivi services repas** : 3 types client (résident/hôte/passage), 3 modes facturation (pension complète/menu/carte)
- **Approvisionnement** : commandes fournisseurs, inventaire, factures
- **Comptabilité dédiée** (manager only)
- **Section laverie restauration** : cycles envoi/retour du linge de table (nappes, serviettes, torchons, tabliers cuisine, tenue service) — workflow envoyé → reçu / partiel / perdu, traçabilité user + coût interne

### 7. Module Ménage (✅ production)

3 sections distinctes : intérieur (chambres) / extérieur (zones) / laverie (service à la demande).

- **Auto-routage par rôle** : `menage_interieur` ne voit jamais l'extérieur etc.
- **Niveau de service** par tâche : aucun/basique/premium (impacte le poids horaire)
- **Distribution équitable automatique** des tâches
- **Laverie** : workflow demandée → en cours → prête → livrée → facturée (vendu au résident)
- **Comptabilité bloquée pour rôles d'exécution** (manager only strict)

### 8. Module Jardinage (✅ production complète + apiculture)

Gestion espaces verts + apiculture si la résidence dispose de ruches.

- **Espaces** : 12 types (potager, parterre, pelouse, rucher, etc.), tâches récurrentes, photo
- **Catalogue produits** : 11 catégories, mention BIO/danger, photo
- **Inventaire** avec mouvements traçables (transaction FOR UPDATE, refus stock négatif)
- **Commandes fournisseurs** : workflow brouillon → envoyée → livrée → facturée (réception auto = entrée stock)
- **Apiculture** : config réglementaire (NAPI, référent, distance habitations), CRUD ruches + photo, **carnet de visite avec export CSV conforme FR**, audit trail statut, **alertes traitements saisonniers** (varroa, nourrissement)
- **Comptabilité** : Chart.js, coût par espace, dépenses fournisseurs, récoltes miel à comptabiliser

### 9. Module Maintenance Technique (✅ production complète)

6 spécialités : piscine, ascenseur, travaux, plomberie, électricité, peinture.

- **Spécialités multi-affectées** par user (debutant/confirmé/expert)
- **Certifications** avec date expiration + alerte 3 mois avant
- **Interventions courantes** + photos avant/après
- **Piscine** : journal analyses chimiques (pH, chlore), contrôles ARS, alertes santé publique
- **Ascenseurs** : entité par résidence + journal + 3 contrôles réglementaires (annuel, quinquennal, conformité)
- **Chantiers** : workflow 9 phases, multi-devis, jalons, **3 garanties auto à la réception** (parfait achèvement, biennale, décennale), quote-part propriétaires
- **Stock** : produits, inventaire, commandes
- **Comptabilité agrégée** (manager only) : interventions + chantiers + ascenseurs

### 10. Module Sinistres (✅ MVP)

Déclarations + suivi assureur + GED + audit trail.

- **9 types** : dégât des eaux, incendie, vol, bris, catastrophe naturelle, vandalisme, chute résident, panne, autre
- **4 niveaux gravité** : mineur, modéré, majeur, catastrophe
- **Workflow 7 statuts** : déclaré → transmis assureur → expertise → réparation → indemnisé → clos / refusé
- **GED** : constats, photos, devis, expertises, courriers (HORS public/, accès controllé)
- **Audit trail** dédié (table `sinistres_log`)
- **Intégration Maintenance** : bouton "Créer un chantier de réparation" depuis fiche sinistre

### 11. Module Documents (✅ MVP)

GED admin/staff direction (HQ Domitys + par résidence).

- **2 scopes** : global Domitys (modèles, contrats cadre, RGPD) ou par résidence (AG, factures archivées)
- **Permissions multi-rôles** : admin/dir/exploitant écriture, comptable lecture seule
- **18 extensions** : PDF, Office, ODF, images, vidéos, ZIP, CSV
- **Triple validation upload** : taille → extension → MIME finfo (anti-polyglot)
- **50 MB max/fichier**, stockage HORS public/

### 12. Module Accueil (✅ production complète)

Service accueil de la résidence.

- **2 rôles** : `accueil_manager` + `accueil_employe`
- **Notes résidents** texte libre
- **Catalogues** : salles communes (capacité, photo, équipements) + équipements prêtables (mobilité, info, loisirs, médical)
- **Réservations** multi-types (salle, équipement, service personnel : coiffeur, pédicure, taxi, etc.) avec workflow validation et **anti-chevauchement**
- **Animations** : créées comme shifts planning + inscriptions résidents + pointage présent/absent
- **Planning double-vue** TUI Calendar (résidents/staff/tout)
- **Messagerie groupée** multi-cibles (résidents/staff/propriétaires) avec présélection

### 13. Module Assemblées Générales (✅ production complète)

AGO/AGE avec workflow + résolutions + votes pondérés + lien chantiers.

- **Workflow** : planifiée → convoquée → tenue / annulée
- **Résolutions** avec votes (voix + tantièmes) + calcul résultat auto
- **Upload PDF** convocation + PV
- **Quorum** atteint coché si délibérations valides
- **Convocation par messagerie** : pré-rempli (proprios + sujet + corps + priorité haute)
- **Espace propriétaire** : lecture AG des résidences avec contrats actifs
- **Calendrier propriétaire** : AG en violet, click → fiche
- **Lien chantiers** : un chantier > 5000€ HT auto-coché `nécessite_ag`, alerte orange "chantiers en attente d'AG"

### 14. Module Comptabilité (✅ 13 phases livrées sur 13)

**Le module le plus riche — couvre tout le besoin comptable d'un parc de résidences.**

#### Vue d'ensemble
- **Schéma unifié** : 1 seule table `ecritures_comptables` agrège jardinage + ménage + restauration + maintenance + loyers + paie + admin + sinistres + hôtes + services
- **PCG simplifié** : 44 comptes seedés (actif, passif, charges, produits, tiers)

#### Les 13 phases
1. **Fondations** : schéma + Model `Ecriture` + helper Logger::audit()
2. **Refonte modules** : tous les modules pushent vers la table unifiée
3. **Dashboard central** : 6 KPIs N vs N-1, Chart.js évolution mensuelle, ventilation par module/résidence, top 10 écritures, section Maintenance séparée
4. **RH salariés** : 8 conventions collectives FR seedées (HCR, Services à la personne, Aide à domicile, etc.), fiches RH, espace salarié `mesInfos` (édition IBAN seulement)
5. **Bulletins de paie** : taux 2026 réalistes (URSSAF, AGIRC-ARRCO, CSG/CRDS), import auto heures planning, workflow 4 statuts, **vue HTML imprimable avec watermark "PILOTE — NON CONTRACTUEL"**, espace salarié `mesBulletins`
6. **Exports comptables** : **FEC DGFIP officiel** (18 colonnes art. A.47 A-1 LPF) + CSV Excel + Cegid Quadratus
7. **TVA CA3/CA12** : calcul auto par taux (20/10/5,5/2,1/exonéré), inférence taux quand NULL via ratio TVA/HT, archive workflow brouillon → déclarée, **mapping CERFA 3310-CA3** (lignes 01 à 32)
8. **IA Assistant Comptable** : Claude Sonnet, contexte chiffré injecté (~700 tokens), 6 suggestions cliquables (vue d'ensemble, anomalies, comparaison N/N-1, etc.)
9. **IA Assistant Paie** : taux 2026 + conventions + détection anomalies (sans fiche RH, salaire <SMIC, dépassement 48h hebdo), 7 suggestions
10. **Bilan + SIG + Balance + GL + Exercices** : balance avec totaux débit/crédit/solde, grand livre avec solde progressif, bilan simplifié actif/passif, **SIG cascade** (Production → VA → EBE → Résultat), workflow exercices (création/clôture/réouverture admin/archivage)
11. **Rapprochement bancaire** : import CSV (séparateur `;` ou `,`, format date FR/ISO, montant français), **matching automatique** (score 0-100 = 40 date + 40 montant + 20 libellé), workflow manuel rapprocher/défaire/ignorer
12. **Audit trail métier** : 18 actions tracées (`ecriture_*`, `exercice_*`, `bulletin_*`, `tva_*`, `bank_*`, `salarie_*`, `export_*`, `quittance_*`, `relance_*`, `impaye_*`), masquage IBAN RGPD, page `/comptabilite/auditTrail` filtrable
13. **Quittances + Gestion des impayés** : génération mensuelle en lot, escalade auto J+10, **3 niveaux de relance** (amiable J+15 / mise en demeure J+45 / saisine procédure J+60) avec **9 templates de message FR**, vue unifiée 3 sources (résidents + propriétaires + fournisseurs), notifications messagerie auto

#### Phase 12 — Polish navbar + dashboard
- Dropdown Comptabilité réorganisé en 6 sections logiques
- **Bandeau "Actions à mener"** sur dashboard : bulletins brouillon, TVA brouillon, ops bancaires non rapprochées, fiches RH manquantes, écritures sans compte
- **8 cards "Accès rapides"** vers les nouvelles fonctions

---

## Modules transversaux

### Messagerie interne

- Accessible à **tous** les utilisateurs authentifiés (sauf hôtes temporaires sans compte)
- Multi-destinataires (individuel ou groupe par rôle)
- Compteur non-lus en navbar avec badge dynamique
- Archive séparée expéditeur/destinataire
- Utilisée pour : convocations AG, notifications quittances, relances impayés, échanges génériques

### Sécurité & conformité

- **Sessions** : timeout 30 min + avertissement 5 min avant
- **CSRF** : token sur tous les POST + header X-CSRF-Token sur AJAX
- **Rate limiting** : 200 req/min par IP
- **Logs sécurité** fichier (`logs/security.log`) : 4 types (UNAUTHORIZED_ACCESS, FAILED_LOGIN, CSRF_VIOLATION, RATE_LIMIT_EXCEEDED)
- **Audit trail métier BDD** (`logs_activite`) : 18 actions comptables tracées avec ip + user_agent + JSON details
- **RGPD** : masquage IBAN dans logs, accès données santé restreint, droit d'accès via espaces personnels
- **Headers HTTP sécurité** : CSP, X-Frame-Options, X-Content-Type-Options, etc.

### IA (Claude Sonnet)

3 assistants intégrés via service `ClaudeService` (clé API dans `.env`) :
1. **Budget résident** : conseils sur reste à charge, aides supplémentaires (ASH)
2. **Comptable** : analyse écritures, anomalies, comparaisons N/N-1, suggestions
3. **Paie** : taux 2026, conventions collectives, simulations brut→net, conformité Code du travail

Tous avec garde-fous : "ne jamais prétendre être un expert agréé", "validation cabinet obligatoire avant décision".

---

## Architecture technique

### Routage
URL `admin/carteResidence/61` → Controller `Admin`, méthode `carteResidence`, params `[61]`. Pas de fichier routes — convention par naming.

### Sécurité par contrôleur
```php
$this->requireAuth();
$this->requireRole(['admin']);
$this->verifyCsrf(); // sur POST uniquement
```
**347+ méthodes scannées, 0 violation** (test `tests/test_require_auth.php`).

### Stockage fichiers — convention `uploads/` privé vs `public/uploads/`
| Type | Dossier | Accès |
|---|---|---|
| Sensible (GED, certifs, justificatifs) | `uploads/` racine | Stream via controller authentifié |
| Illustration publique (photos profil, photos catalogue) | `public/uploads/` | Direct par URL |

### Tableaux
**Tout tableau de liste a tri colonnes + recherche + pagination** — règle absolue.
- `DataTableWithPagination` (client-side, < 500 lignes)
- `pagination.php` (server-side, > 1000 lignes)

---

## Comptes de test

| Username | Rôle | Mot de passe |
|---|---|---|
| `admin` | Administrateur | `admin123` |
| `dir_residence` | Directeur Résidence | `Dir1234` |
| `comptable` | Comptable | `Comptable1234` |
| `proprietaire1` | Propriétaire | `Prop1234` |
| `resident1` | Résident Senior | `Resi1234` |
| `accueil_chef` | Accueil Manager | `Acc1234` |
| `accueil_emp` | Accueil Employé | `Acc1234` |
| `employe_res` | Employé Résidence | `Emp1234` |
| `technicien` | Technicien | `Tech1234` |

---

## Statut global de l'application

| Catégorie | État |
|---|---|
| Modules fondamentaux (admin, propriétaires, résidents, occupations, hôtes) | ✅ Production |
| Modules métier (restauration, ménage, jardinage, maintenance, accueil, sinistres, AG, documents) | ✅ Production complète |
| Module Comptabilité (13 phases) | ✅ **100% livré** |
| Audit trail légal compta | ✅ Conforme PCG art. 410-1 + RGPD art. 30 |
| Espaces personnels (propriétaire / résident / salarié) | ✅ Production |
| Module Bulletins paie | ⚠️ Pilote (watermark non contractuel — à valider expert-comptable) |
| Module Quittances | ⚠️ Pilote (HTML printable — à valider juridique) |
| Module Appels de fonds | 💤 Dormant (model existant, UI non implémentée) |

**Lignes de code estimées** : ~50 000 lignes PHP + 10 000 SQL + 5 000 JS/CSS.
**Migrations BDD** : 37 appliquées.
**Tests automatisés** : `test_require_auth.php` (347 méthodes scannées, 0 violation).
