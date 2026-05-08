# Guide d'utilisation — Propriétaire

**Public** : utilisateurs avec rôle `proprietaire` (investisseurs en lots dans les résidences Domitys)

---

## Connexion

URL : `https://votre-domaine.fr/login`

Compte démo : `proprietaire1` / `Prop1234`

> Si vous oubliez votre mot de passe, contactez l'administration Domitys via la messagerie ou par téléphone — il sera réinitialisé pour vous.

---

## Modèle économique en 1 minute

```
VOUS (propriétaire) → loyer garanti 850€/mois → DOMITYS (exploitant)
                                                       ↓
                                             location 1 450€/mois
                                                       ↓
                                              RÉSIDENT SENIOR
```

- Vous achetez un lot (studio, T2, T3) dans une résidence Domitys
- Vous signez un **contrat de gestion** avec Domitys (bail commercial)
- Domitys vous verse un **loyer mensuel garanti** indépendamment de l'occupation effective
- Domitys gère tout : trouver les résidents, encaisser leurs loyers, services, maintenance, etc.

---

## Votre tableau de bord

URL : `/coproprietaire/espace`

Vous y trouvez :
- **Bandeau "Prochaine AG"** si une assemblée générale convoquée approche
- Résumé de vos lots (combien, dans combien de résidences)
- Loyer total garanti mensuel
- Accès rapides : Mes lots, Mes résidences, Calendrier, GED, Comptabilité, AG, Messagerie

---

## Mes lots

`/coproprietaire/mesLots` — liste de tous vos lots avec :
- Type (studio, T2, T2 bis, T3, parking, cave)
- Surface, étage
- Résidence d'appartenance
- Statut occupation (occupé / vacant)
- Loyer garanti mensuel
- Lien vers le détail complet du lot

---

## Mes résidences

`/coproprietaire/mesResidences` — vitrine des résidences où vous possédez au moins un lot.

- **Carte Leaflet** plein écran avec marqueurs
- Marqueurs **orange** = vos résidences, marqueurs **bleus** = autres résidences Domitys
- Tableau filtrable + pagination
- Cliquer pour voir la fiche détaillée d'une résidence (photos, services, équipe, etc.)

---

## Mon calendrier

`/coproprietaire/calendrier` — TUI Calendar v1.15.3 avec **événements auto-générés** :

| Catégorie | Couleur | Description |
|---|---|---|
| Loyers garantis | Vert | Au jour d'échéance de chaque mois |
| AG (Assemblées Générales) | Violet | Si une AG est convoquée — clic = fiche AG |
| Rappels fiscaux | Orange | Avril (ouverture déclaration), Mai (date limite) |
| RDV personnels | Bleu | Vos événements ajoutés manuellement |

**Drag & drop** disponible sur les événements personnels uniquement (les auto-générés sont en lecture seule).

---

## Mes assemblées générales

`/coproprietaire/assemblees` — vous voyez **uniquement** les AG des résidences où vous avez un contrat actif.

Statuts visibles : **convoquée** (à venir), **tenue** (passée), **annulée**.
Les AG en `planifiée` (en cours de préparation) ne sont pas encore visibles.

### Sur la fiche d'une AG

`/coproprietaire/assembleeShow/{id}` :
- Date, lieu, mode (présentiel / visio / mixte)
- Ordre du jour
- Bouton **"Télécharger la convocation"** (PDF) si elle est en `convoquée`
- Bouton **"Télécharger le PV"** si elle est `tenue`
- Tableau des **résolutions votées** avec résultats (adopté / rejeté / reporté)
- Liste des **chantiers votés** lors de cette AG (montant estimé, montant engagé)

### Recevoir une convocation

Vous recevez la convocation d'AG **par messagerie interne** (notification visible dans la navbar avec badge) — vous pouvez aussi recevoir un email externe selon la configuration.

---

## Mes documents (GED)

`/coproprietaireDocument/index` — votre espace de stockage personnel.

### Capacité
- **Quota global : 1 GB** par propriétaire
- **Taille max par fichier : 50 Mo**
- 18 extensions autorisées : PDF, Word, Excel, ODF, images (JPG/PNG/WEBP/GIF), vidéos (MP4/MOV/WEBM), ZIP, CSV, TXT

### Comment ça marche
1. Créer une arborescence de dossiers libres (ex: "Contrats" / "Quittances 2026" / "AG" / "Photos lot")
2. Uploader vos documents dans le dossier voulu
3. Renommer ou supprimer dossiers/fichiers à volonté
4. Prévisualiser les images/vidéos/PDF en ligne
5. Télécharger en cliquant sur le fichier

### Sécurité
- Vos documents sont stockés **HORS du répertoire public** du serveur
- Seul vous (et l'admin Domitys) y avez accès
- Téléchargement **uniquement via votre compte authentifié**

---

## Ma comptabilité

`/coproprietaire/comptabilite` — vue de vos revenus locatifs.

### Ce que vous voyez
- **Loyers garantis perçus** sur l'année (mois par mois)
- **Statut de chaque versement** : payé / retard / impayé / litige
- **Total annuel encaissé**
- Lien vers chaque **quittance de loyer garanti** (téléchargeable en PDF)

### Quittances de loyer garanti

URL : `/comptabilite/quittanceProprioPrintable/{paiementId}`

- HTML imprimable avec watermark "PILOTE — DOCUMENT NON CONTRACTUEL"
- Mentions obligatoires : votre identité, le bien concerné, la période, le montant, le mode de paiement
- Bouton "Imprimer ou Enregistrer en PDF" via votre navigateur

---

## Ma déclaration fiscale (assistée IA)

`/coproprietaire/declarationFiscale` — préparation de votre déclaration de revenus fonciers.

### Ce qui est calculé
- **Revenus bruts** (loyers garantis perçus)
- **Charges déductibles** (selon votre dispositif)
- **Amortissements** (LMNP au réel)
- **Revenu net imposable** estimé

### Dispositifs fiscaux supportés
| Dispositif | Description |
|---|---|
| **LMNP réel** | Loueur Meublé Non Professionnel — déduction des charges réelles + amortissements |
| **LMNP micro-BIC** | Abattement forfaitaire 50% |
| **Censi-Bouvard** | Réduction d'impôt 11% étalée sur 9 ans |
| **Nue-propriété** | Pas de revenus à déclarer (démembrement) |

### Assistant IA (Claude Sonnet)

Sur la page, un chatbox vous aide à :
- Comprendre les cases du formulaire 2042 (1AS, 7DB, 7CD)
- Calculer votre crédit d'impôt potentiel
- Analyser un avis d'imposition uploadé (vision IA)

⚠️ **Important** : l'assistant est indicatif. Pour une déclaration officielle, faites valider par un expert-comptable.

---

## Messagerie interne

Accessible depuis l'icône 📧 en navbar (badge dynamique pour les non-lus).

### Cas d'usage typiques pour vous
- **Recevoir** : convocations AG, notifications de quittance émise, relances impayés (rare), informations Domitys
- **Envoyer** : questions à l'admin, demandes de documents, signalement d'un problème

### Comment composer un message
1. Icône 📧 → "Composer"
2. Choisir le destinataire (admin, comptable, directeur de votre résidence)
3. Sujet + contenu (markdown léger supporté)
4. Priorité : normale / haute / urgente
5. Envoyer

---

## Que faire si...

### ...je ne reçois pas mon loyer garanti ?

1. Vérifier dans `/coproprietaire/comptabilite` le statut du mois concerné
2. Si statut `payé` mais pas de virement reçu → contacter le service comptable Domitys via la messagerie en mentionnant la référence de paiement
3. Si statut `attente` ou `retard` → attendre quelques jours puis relancer
4. Si statut `litige` → contacter directement votre directeur de résidence

### ...je vends mon lot ?

Démarche externe (notaire). Une fois la vente actée, contactez Domitys pour mettre à jour le contrat de gestion. Votre compte sera ensuite désactivé après transmission au nouveau propriétaire.

### ...je n'arrive pas à voir une AG sur mon compte ?

L'AG n'apparaît que si :
- Vous avez un contrat de gestion **actif** (`statut='actif'`) sur au moins un lot de cette résidence
- L'AG est au statut `convoquée` ou `tenue` ou `annulée` (les AG en `planifiée` ne sont pas encore visibles)

Si problème persiste, contactez votre directeur de résidence.

### ...je veux voter à distance pour une AG ?

Le vote en ligne n'est **pas encore disponible** dans cette version pilote. Les modalités de vote (présentiel, procuration, courrier) sont précisées dans la convocation que vous recevez.

### ...j'ai oublié mon mot de passe ?

Pas de fonction "mot de passe oublié" en self-service dans cette version pilote. Contactez l'admin Domitys par téléphone ou email externe — il vous communiquera un nouveau mot de passe.

---

## Vos données personnelles (RGPD)

### Ce que Domitys stocke sur vous
- Identité : civilité, nom, prénom, date de naissance
- Coordonnées : adresse, téléphone, email
- Données fiscales : SIRET (si LMNP), dispositif fiscal
- IBAN (pour virement loyer garanti) — **masqué dans les logs** (`FR76********1234`)
- Vos contrats de gestion + historique des versements

### Vos droits
- **Accès** : consulter vos données dans votre espace propriétaire
- **Rectification** : demander modification via la messagerie (Domitys met à jour)
- **Effacement** : à la résiliation du contrat de gestion, vos données sont conservées pendant la durée légale (10 ans pour la comptabilité), puis anonymisées

### Audit trail
Toutes les modifications sur votre fiche, vos contrats, vos paiements sont tracées dans le module Audit (consultable par Domitys uniquement). Conforme **PCG art. 410-1** + **RGPD art. 30**.

---

## FAQ Propriétaire

**Q : Comment vérifier que mes loyers garantis sont à jour ?**
A : `/coproprietaire/comptabilite` → tableau mois par mois, statut "payé" en vert.

**Q : Comment télécharger la quittance de loyer garanti pour ma déclaration d'impôt ?**
A : Sur `/coproprietaire/comptabilite`, cliquer sur le mois concerné → bouton PDF.

**Q : Puis-je modifier mon RIB pour le virement ?**
A : Non en self-service. Envoyer le nouveau RIB par messagerie interne — Domitys met à jour.

**Q : Les chantiers votés en AG, qui les paie ?**
A : Selon la nature du chantier :
- Maintenance courante (entretien) → Domitys (loyer garanti vous est versé indépendamment)
- Gros travaux structurels (toiture, ravalement) → quote-part appelée aux propriétaires (table `chantier_lots_impactes`) — un appel de fonds peut être émis (fonctionnalité dormante en pilote, sera activée si demande)

**Q : Combien de temps mes documents GED sont-ils conservés ?**
A : Tant que votre compte est actif. Au plus tard à la résiliation du contrat de gestion + 10 ans (durée légale comptable). Vous êtes responsable du download avant la fin de votre relation avec Domitys.

**Q : L'assistant IA fiscal est-il vraiment fiable ?**
A : Indicatif. Il connaît les bases de la fiscalité LMNP/Censi-Bouvard et peut analyser vos documents fiscaux (vision). Mais pour la déclaration officielle, faites valider par un expert-comptable. L'assistant le rappelle systématiquement.
