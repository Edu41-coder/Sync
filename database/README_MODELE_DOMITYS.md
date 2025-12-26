# 📋 Modèle Domitys - Gestion Locative Investisseur

## 🎯 Vue d'ensemble

L'extension du schéma permet de gérer le **modèle d'investissement locatif Domitys** :

```
Propriétaire Investisseur → Domitys (Exploitant) → Résident Senior
        (Bail commercial)              (Contrat de séjour)
```

---

## 🏗️ Nouvelles Tables Créées

### 1. **`exploitants`** - Gestionnaires de résidences
Représente les entreprises comme **Domitys** qui exploitent les résidences seniors.

**Données clés :**
- Raison sociale, SIRET, SIREN
- Contacts et coordonnées bancaires
- Assurances et certifications (AFNOR, VISEHA)
- Documents (KBIS, assurance RC)

**Exemple :**
```sql
DOMITYS SAS
SIRET: 80377408400385
Téléphone: 01 41 18 29 29
Certifications: AFNOR, VISEHA, ISO9001
```

---

### 2. **`contrats_gestion`** - Baux commerciaux Propriétaire ↔ Domitys

Représente le **bail commercial** entre le propriétaire investisseur et Domitys.

**Caractéristiques :**
- ✅ **Loyer garanti** fixe mensuel (ex: 850€)
- ✅ Durée : 9 ou 12 ans
- ✅ **Franchise de loyer** : 3-6 mois (période sans loyer au début)
- ✅ Indexation annuelle (IRL)
- ✅ Appartement meublé avec inventaire
- ✅ Garantie loyer impayé

**Fiscalité :**
- Dispositif **Censi-Bouvard** : 11% de réduction d'impôt
- Statut **LMNP** (Loueur Meublé Non Professionnel)
- Récupération de la **TVA** sur l'achat
- Charges déductibles

**Champs importants :**
```sql
loyer_mensuel_garanti: 850.00 €
dispositif_fiscal: 'Censi-Bouvard'
reduction_impot_taux: 11%
recuperation_tva: TRUE
franchise_loyer_mois: 3
garantie_loyer: TRUE
```

---

### 3. **`residents_seniors`** - Occupants finaux

Représente les **seniors** qui habitent dans les résidences Domitys (clients de Domitys, pas du propriétaire).

**Données collectées :**
- Identité complète (nom, prénom, date de naissance)
- Contacts d'urgence (famille)
- Niveau d'autonomie (GIR 1 à 6)
- Informations médicales basiques (médecin traitant, allergies)
- Animaux de compagnie
- Centres d'intérêt (pour animations)

**Exemple :**
```sql
Madame Jeanne DUPONT, 86 ans
Autonome, aime le jardinage, la lecture et le bridge
Animal: Chat (Minou)
Contact urgence: Marie DUPONT-SIMON (fille)
```

---

### 4. **`occupations_residents`** - Association Lot ↔ Résident

Gère **qui occupe quel appartement** et les conditions financières entre le résident et Domitys.

**Informations clés :**
- Dates d'entrée/sortie
- **Loyer payé par le résident à Domitys** (ex: 1450€/mois)
- Forfait choisi (Essentiel, Sérénité, Confort, Premium)
- Services inclus (repas, ménage, animations)
- APL / APA (aides)
- Dépôt de garantie

**Note importante :**
> Le loyer payé par le résident à Domitys (1450€) est **différent et supérieur** au loyer garanti versé par Domitys au propriétaire (850€). 
> 
> La différence (600€) correspond à la **marge de gestion** de Domitys pour les services, l'entretien, le personnel, etc.

---

### 5. **`paiements_loyers_exploitant`** - Suivi des loyers garantis

Enregistre les **paiements mensuels** de Domitys aux propriétaires.

**Suivi automatique :**
- Date d'échéance (ex: le 5 de chaque mois)
- Montant (loyer + charges)
- Statut (payé, en attente, retard)
- Calcul automatique des jours de retard
- Génération de quittances

**Exemple :**
```sql
Novembre 2025
Propriétaire: M. MARTIN
Loyer: 850€ + Charges: 120€ = 970€
Statut: Payé le 05/11/2025
Référence: VIR-DOM-2025-11-001
```

---

### 6. **`revenus_fiscaux_proprietaires`** - Déclarations fiscales

Aide les propriétaires à préparer leur **déclaration d'impôts** (LMNP).

**Calculs automatiques :**
- Revenus bruts (total loyers perçus)
- Charges déductibles :
  - Intérêts d'emprunt
  - Travaux
  - Assurances
  - Taxe foncière
  - Charges de copropriété
- Amortissement du bien
- Réduction Censi-Bouvard
- Récupération TVA

**Régimes fiscaux :**
- Micro-BIC (abattement 50%)
- Réel simplifié (recommandé)
- Réel normal

---

## 💰 Flux Financiers

### Schéma des flux :

```
┌─────────────────────┐
│  Résident Senior    │
│  (Mme Dupont)       │
└──────┬──────────────┘
       │ 1450€/mois
       │ (loyer + services)
       ▼
┌─────────────────────┐
│     DOMITYS         │
│   (Exploitant)      │
└──────┬──────────────┘
       │ 850€/mois
       │ (loyer garanti)
       ▼
┌─────────────────────┐
│  Propriétaire       │
│  (M. Martin)        │
└─────────────────────┘

Marge Domitys: 1450€ - 850€ = 600€/mois
(services, entretien, personnel, gestion)
```

### Exemple de rentabilité pour le propriétaire :

**Investissement :**
- Achat appartement T2 : 150 000€
- Frais de notaire : 15 000€
- Mobilier : 15 000€
- **Total : 180 000€**

**Revenus annuels :**
- Loyer garanti : 850€ × 12 = **10 200€/an**
- Rendement brut : 10 200 / 180 000 = **5.67%**

**Avantages fiscaux :**
- Réduction Censi-Bouvard : 180 000€ × 11% = **19 800€** (sur 9 ans)
- Récupération TVA : 180 000€ × 20% = **36 000€**

---

## 📊 Vues SQL Créées

### 1. `v_revenus_proprietaires`
Synthèse des revenus locatifs par propriétaire investisseur.

**Colonnes :**
- Nombre de contrats
- Nombre de lots possédés
- Revenus mensuels totaux
- Revenus annuels
- Loyer moyen

**Exemple :**
```
M. MARTIN: 1 contrat, 1 lot, 850€/mois, 10 200€/an
```

### 2. `v_taux_occupation`
Taux d'occupation des résidences seniors.

**Calculs :**
- Total appartements
- Appartements occupés
- Taux d'occupation en %

**Exemple :**
```
Domitys - La Badiane (Marseille)
Total: 85 appartements
Occupés: 72 appartements
Taux: 84.7%
```

### 3. `v_residents_logements`
Liste des résidents avec leurs logements actuels.

**Informations :**
- Nom du résident, âge
- Numéro de lot, type, surface
- Résidence, ville
- Loyer mensuel
- Statut occupation

### 4. `v_suivi_paiements_exploitant`
Suivi des paiements Domitys → Propriétaires.

**Colonnes :**
- Exploitant, propriétaire
- Mois, montant
- Statut paiement
- Jours de retard

---

## 🔧 Triggers Automatiques

### 1. `trg_calcul_montant_paiement`
Calcule automatiquement le montant total (loyer + charges + régularisation).

### 2. `trg_calcul_jours_retard`
Calcule les jours de retard de paiement automatiquement.

### 3. `trg_validation_occupation`
Empêche qu'un lot soit occupé par 2 résidents simultanément.

---

## 📝 Modification des Tables Existantes

### Table `coproprietees`

**Nouveaux champs ajoutés :**

```sql
exploitant_id INT           -- Lien vers Domitys
type_residence ENUM         -- 'residence_seniors' pour Domitys
```

Toutes les résidences Domitys sont automatiquement marquées comme `type_residence = 'residence_seniors'`.

---

## 🚀 Comment utiliser le système

### Étape 1: Exécuter les scripts SQL

```bash
# 1. Créer l'extension du schéma
mysql -u root synd_gest < database/extension_domitys_model.sql

# 2. Insérer les 20 résidences Domitys
mysql -u root synd_gest < database/domitys_residences.sql

# 3. Insérer les données de test
mysql -u root synd_gest < database/data_domitys_test.sql
```

### Étape 2: Vérifier les données

```sql
-- Voir tous les exploitants
SELECT * FROM exploitants;

-- Voir tous les contrats actifs
SELECT * FROM contrats_gestion WHERE statut = 'actif';

-- Voir les résidents actuels
SELECT * FROM v_residents_logements;

-- Voir les revenus des propriétaires
SELECT * FROM v_revenus_proprietaires;

-- Voir les paiements du mois
SELECT * FROM paiements_loyers_exploitant 
WHERE annee = 2025 AND mois = 11;
```

### Étape 3: Créer un nouveau contrat

```sql
-- 1. Créer le propriétaire investisseur
INSERT INTO coproprietaires (...) VALUES (...);

-- 2. Créer le contrat de gestion avec Domitys
INSERT INTO contrats_gestion (...) VALUES (...);

-- 3. Créer le résident senior
INSERT INTO residents_seniors (...) VALUES (...);

-- 4. Associer résident → lot
INSERT INTO occupations_residents (...) VALUES (...);

-- 5. Les paiements sont générés automatiquement
```

---

## 📈 Statistiques & Reporting

### Requêtes utiles :

**Total des revenus mensuels garantis :**
```sql
SELECT SUM(loyer_mensuel_garanti) AS total_mensuel
FROM contrats_gestion 
WHERE statut = 'actif';
```

**Moyenne d'âge des résidents :**
```sql
SELECT AVG(age) AS age_moyen 
FROM residents_seniors 
WHERE actif = TRUE;
```

**Taux de paiement Domitys :**
```sql
SELECT 
    COUNT(*) AS total_echeances,
    SUM(CASE WHEN statut = 'paye' THEN 1 ELSE 0 END) AS paye,
    ROUND(SUM(CASE WHEN statut = 'paye' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS taux_pct
FROM paiements_loyers_exploitant;
```

**Revenus fiscaux annuels :**
```sql
SELECT 
    CONCAT(cp.prenom, ' ', cp.nom) AS proprietaire,
    r.annee_fiscale,
    r.revenus_bruts,
    r.revenus_nets,
    r.reduction_censi_bouvard,
    r.resultat_fiscal
FROM revenus_fiscaux_proprietaires r
JOIN coproprietaires cp ON r.coproprietaire_id = cp.id
ORDER BY r.annee_fiscale DESC;
```

---

## ✅ Avantages du nouveau système

### Pour les Propriétaires Investisseurs :
- ✅ Suivi automatique des loyers garantis
- ✅ Aide à la déclaration fiscale LMNP
- ✅ Calcul des avantages Censi-Bouvard
- ✅ Historique complet des paiements
- ✅ Alertes en cas de retard de paiement

### Pour Domitys (Exploitant) :
- ✅ Gestion centralisée de tous les contrats
- ✅ Suivi des résidents et occupations
- ✅ Génération automatique des échéances
- ✅ Statistiques d'occupation en temps réel

### Pour les Résidents :
- ✅ Dossier complet avec informations médicales
- ✅ Suivi des services et forfaits
- ✅ Contacts d'urgence accessibles
- ✅ Historique des occupations

---

## 🎓 Cas d'usage typiques

### Cas 1: Nouveau propriétaire investisseur

1. M. Dupont achète un T2 dans la résidence Domitys de Marseille
2. Il signe un bail commercial de 9 ans avec Domitys
3. Loyer garanti : 850€/mois
4. Domitys trouve un résident (Mme Martin, 82 ans)
5. Mme Martin paye 1450€/mois à Domitys (loyer + services)
6. Domitys verse 850€/mois à M. Dupont (garanti même si inoccupé)
7. M. Dupont bénéficie de la réduction Censi-Bouvard : 19 800€ sur 9 ans

### Cas 2: Suivi mensuel des paiements

1. Le 1er de chaque mois : échéance générée automatiquement
2. Le 5 : Domitys effectue le virement au propriétaire
3. Enregistrement automatique dans `paiements_loyers_exploitant`
4. Génération de la quittance PDF
5. Envoi par email au propriétaire

### Cas 3: Déclaration fiscale annuelle

1. Fin d'année : calcul automatique des revenus
2. Génération du tableau récapitulatif :
   - Revenus bruts : 10 200€
   - Charges déductibles : 4 700€
   - Amortissement : 3 500€
   - Résultat fiscal : +2 000€
3. Préparation formulaire 2044 (revenus fonciers)
4. Application réduction Censi-Bouvard : -2 200€ d'impôt

---

## 📞 Support

Pour toute question sur le modèle Domitys :
- Documentation : ce fichier
- Scripts SQL : `/database/extension_domitys_model.sql`
- Données test : `/database/data_domitys_test.sql`

---

**Date de création :** 30 novembre 2025  
**Version :** 1.0  
**Auteur :** Synd_Gest Development Team
