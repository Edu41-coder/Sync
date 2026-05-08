# Guide d'utilisation — Résident Senior

**Public** : utilisateurs avec rôle `locataire_permanent` (résidents seniors hébergés en résidence Domitys)

---

## Connexion

URL : `https://votre-domaine.fr/login`

Compte démo : `resident1` / `Resi1234`

> **Si vous oubliez votre mot de passe**, demandez à l'accueil de votre résidence — l'admin Domitys le réinitialisera pour vous.

> **Lecture confortable** : la plupart des pages sont conçues pour un public senior — interface en français clair, boutons de bonne taille, pas de jargon technique.

---

## Page d'accueil — Mon espace

URL : `/resident/monEspace`

Vous y trouvez :
- **4 indicateurs clés** : nombre de lots actifs, résidences, loyer mensuel total, niveau d'autonomie
- **6 cartes d'accès rapide** : Mes lots, Résidences Domitys, Calendrier, Mes documents, Comptabilité, Messagerie
- **Tableau de vos lots** avec liens vers le détail

---

## Mes lots et occupations

### Mes lots

`/resident/mesLots` — grille de cartes pour chaque lot que vous occupez (résidence, type, surface, étage, loyer).

### Mes occupations

`/resident/mesOccupations` — tableau de vos occupations actives + historique (date d'entrée, date de sortie, lot, résidence, loyer).

### Vitrine des résidences Domitys

`/resident/mesResidences` — carte interactive de toutes les résidences Domitys.
- Marqueurs **orange** = résidences où vous habitez
- Marqueurs **bleus** = autres résidences (pour visiter ou déménager)

---

## Mon calendrier

`/resident/calendrier` — TUI Calendar v1.15.3 avec **6 catégories** :

| Catégorie | Couleur | Auto-généré ? |
|---|---|:---:|
| Loyers mensuels | Vert | ✅ au jour de prélèvement (défaut 5) |
| Animations résidence | Orange | ✅ depuis le planning d'animation |
| Rappels fiscaux | Rouge | ✅ avril (ouverture) + mai (date limite) |
| RDV médicaux | Bleu | ❌ vous les ajoutez |
| Famille | Violet | ❌ vous les ajoutez |
| Autres | Gris | ❌ vous les ajoutez |

### Ajouter un événement
1. Double-cliquer sur la date voulue
2. Choisir la catégorie + remplir les détails
3. Enregistrer

### Modifier ou déplacer
- **Drag & drop** pour déplacer un événement personnel
- Double-clic pour ouvrir l'éditeur
- Les événements **auto-générés** (loyers, animations, fiscal) ne sont **pas modifiables** — c'est normal

---

## Ma comptabilité

URL : `/resident/comptabilite`

### Ce que vous voyez

#### Vue d'ensemble (KPIs)
- **Dépenses du mois** (loyer + charges + services - aides)
- **Aides perçues** (APL, APA)
- **Reste à charge mensuel et annuel**

#### Détail des dépenses
- Loyer + charges + forfait services
- Services supplémentaires (laverie, animations payantes, etc.)
- Déduction APL (Aide Personnalisée au Logement)
- Déduction APA (Allocation Personnalisée d'Autonomie)

#### Mes dernières quittances (5 plus récentes)
- Période, numéro, montant, statut
- Bouton **PDF** pour télécharger chaque quittance

### Assistant Budget IA

En bas de la page, un chatbox vous permet de poser des questions à un assistant intelligent :
- "Combien me reste-t-il après mes charges fixes ?"
- "Comment demander l'ASH (Aide Sociale à l'Hébergement) ?"
- "Quels services puis-je économiser ?"

L'assistant connaît **votre budget réel** (en lecture seule) et vous répond de façon bienveillante adaptée à un public senior. Il rappelle toujours qu'il est un assistant et que pour les démarches officielles, l'équipe Domitys ou un travailleur social peut vous accompagner.

---

## Mes quittances (nouveauté Phase 13)

URL : `/resident/mesQuittances`

### Ce qu'on y trouve
- **Liste complète** de toutes vos quittances mensuelles (depuis votre arrivée)
- Filtres : période, statut (émise / payée / impayée)
- Pour chaque ligne : période, numéro de quittance, résidence, montant, statut, bouton **Télécharger PDF**

### À quoi ça sert ?
Une quittance de loyer est un **document officiel** qui prouve que vous avez payé votre loyer. Elle est demandée notamment pour :
- **Votre déclaration d'impôt** (réduction d'impôt résidence services médicalisée)
- **La CAF** (calcul ou maintien de votre APL)
- **Les services sociaux** (demande d'aide ASH, APA)
- **Vos archives personnelles**

### Comment télécharger
1. Cliquer sur le bouton **PDF** de la ligne voulue
2. La quittance s'ouvre dans un nouvel onglet
3. Cliquer sur le bouton **🖨️ Imprimer ou Enregistrer en PDF** en haut
4. Choisir "Enregistrer en PDF" dans le dialogue d'impression de votre navigateur
5. Le fichier est téléchargé sur votre ordinateur

> ⚠️ **Pilote** : les quittances actuelles portent un filigrane "PILOTE — DOCUMENT NON CONTRACTUEL". Elles sont calculées correctement mais à valider avec le service comptable Domitys avant utilisation officielle.

---

## Ma déclaration fiscale

`/resident/declarationFiscale` — page dédiée pour préparer votre déclaration de revenus.

### Ce qui est calculé pour vous (estimations indicatives)
- **Crédit d'impôt services à la personne** (50%, plafond 12 000€/an, **case 7DB** du formulaire 2042)
- **Réduction d'impôt résidence services médicalisée** (25%, plafond 10 000€, **case 7CD**)
- **Aide-mémoire des cases** : 1AS pour la pension, 7DB et 7CD

### Assistant IA fiscal

Un chatbox spécialisé dans la fiscalité des résidents seniors :
- Vous explique chaque case du formulaire 2042
- Précise les plafonds et conditions
- Peut **analyser votre avis d'imposition** (uploadez le PDF, l'IA en extrait les chiffres pertinents)
- Connaît les aides : APL non imposable, APA non imposable, ASH non imposable

⚠️ **Important** : assistant indicatif. Pour la déclaration officielle, votre travailleur social ou un expert-comptable vous accompagnera.

---

## Mes documents (GED)

URL : `/residentDocument/index`

### Espace personnel sécurisé
- **Quota : 500 Mo** par résident (largement suffisant pour des documents)
- **Taille max par fichier : 50 Mo**
- 18 extensions autorisées : PDF, Word, Excel, photos, vidéos, ZIP

### Comment organiser
1. Créer des dossiers (ex: "Médical", "Famille", "Fiscal", "Quittances 2026", "Photos")
2. Glisser-déposer ou cliquer "Uploader" pour ajouter des fichiers
3. Renommer / déplacer / supprimer à volonté

### Idées de documents à conserver
- Vos **avis d'imposition** des dernières années
- Vos **bilans médicaux** (en cas d'urgence, pour partager avec famille/médecin)
- **Photos de famille**
- **Quittances** téléchargées (pour archive)
- **Documents administratifs** : carte d'identité, livret de famille, etc.

### Sécurité
- Vos documents sont **stockés hors du répertoire public** du serveur
- Seul vous (et l'admin Domitys en cas de problème) y avez accès
- Téléchargement uniquement via votre compte authentifié

---

## Profil & sécurité

URL : `/resident/profile`

### Ce que vous voyez
- Vos infos compte (en **lecture seule** — modifications via la messagerie auprès de la direction)
- Votre **photo de profil** (vous pouvez la changer)

### Aide-mémoire mot de passe
- Un encadré affiche votre mot de passe actuel (avec un œil 👁️ pour le révéler)
- Bouton "Copier" pour faciliter

### Changer mon mot de passe
1. Section "Sécurité"
2. Saisir l'ancien mot de passe
3. Saisir le nouveau (minimum 6 caractères)
4. Confirmer
5. Cliquer "Changer"

---

## Animations & vie de la résidence

Les **animations** organisées par l'accueil de votre résidence apparaissent automatiquement dans votre calendrier (catégorie animation, couleur orange).

Pour vous **inscrire ou désinscrire** d'une animation :
1. Demandez à l'accueil de votre résidence (ils ont la liste des animations + inscriptions)
2. Ou utilisez le calendrier puis double-clic sur l'animation pour voir les détails

L'inscription en self-service depuis votre compte n'est **pas encore disponible** dans cette version pilote. Évolution V2 envisagée.

---

## Réservations (salles communes, équipements, services personnels)

Pour réserver :
- Une **salle commune** (salle de réunion, salle famille pour anniversaire)
- Un **équipement** (déambulateur, fauteuil roulant, livres, jeux)
- Un **service personnel** (coiffeur, pédicure, manucure, taxi)

→ Demandez à l'accueil de votre résidence. Ils valident la disponibilité (pas de chevauchement) et vous confirment.

L'auto-réservation depuis votre compte n'est **pas encore disponible** dans cette version pilote.

---

## Sinistres (en cas de problème)

Si un sinistre survient dans votre logement (dégât des eaux, panne, etc.) :

### Que faire en urgence
1. **Sécuriser** la situation (couper l'eau, appeler les pompiers si feu, etc.)
2. **Prévenir** immédiatement l'accueil de votre résidence
3. L'accueil ou le directeur **déclare le sinistre** dans le système (vous n'avez pas besoin de le faire vous-même)
4. Vous serez tenu informé de l'avancement par messagerie

### Ce que vous pouvez faire en self-service
- Voir les sinistres concernant votre lot via `/sinistre/index` (selon paramétrage de votre compte)
- Déclarer un sinistre depuis votre espace si l'option est activée pour vous (sinon, passez par l'accueil)

---

## Messagerie interne

Icône 📧 en navbar avec badge dynamique pour les non-lus.

### Cas typiques
- **Recevoir** : notifications de quittances émises, informations Domitys, réponses à vos demandes
- **Envoyer** : questions à l'admin, demandes de modification de profil, signalement, demande d'aide

### Composer un message
1. Icône 📧 → "Composer"
2. Choisir le destinataire :
   - **Direction** (votre directeur de résidence)
   - **Accueil** (équipe d'accueil de votre résidence)
   - **Comptable** (questions financières)
3. Sujet + contenu
4. Envoyer

---

## FAQ Résident

**Q : Pourquoi je ne vois pas certaines pages dont on me parle ?**
A : Les pages affichées dépendent de votre rôle. Si vous pensez qu'une fonctionnalité devrait être accessible, demandez à l'accueil de vérifier vos permissions.

**Q : Comment imprimer une quittance ?**
A : `/resident/mesQuittances` → bouton PDF sur la ligne voulue → bouton "Imprimer" en haut de la page → choisir "Imprimer" sur papier ou "Enregistrer au format PDF".

**Q : Mon APL ne s'affiche plus, est-ce normal ?**
A : Vérifiez avec votre directeur de résidence — l'APL est saisi dans votre fiche d'occupation. Si vous avez un changement de situation (modification CAF), informez Domitys pour mise à jour.

**Q : L'assistant IA voit-il mes données médicales ?**
A : Non. L'assistant Budget ne voit que les données financières (loyer, charges, aides). L'assistant Fiscal ne voit que les chiffres fiscaux que vous lui montrez. Vos données médicales (allergies, traitements, médecin traitant) sont protégées et accessibles uniquement à l'admin Domitys et à vous-même.

**Q : Mes données sont-elles partagées avec d'autres résidents ?**
A : **Jamais.** Chaque résident voit uniquement SES propres données. Les autres résidents ne voient ni votre nom, ni vos chiffres, ni votre dossier.

**Q : Combien de temps mes données sont-elles conservées après mon départ ?**
A : Selon les obligations légales (10 ans pour la comptabilité, 5 ans pour les contrats). Vous pouvez télécharger tous vos documents avant votre départ. Sur demande écrite, vos données personnelles non comptables peuvent être effacées (RGPD article 17).

**Q : J'ai oublié mon mot de passe, que faire ?**
A : Demandez à l'accueil de votre résidence — ils contactent l'admin Domitys qui le réinitialise pour vous. Pas de "mot de passe oublié" en self-service dans cette version.

**Q : Puis-je accéder à mon compte depuis mon téléphone ?**
A : Oui, l'application est conçue pour fonctionner sur ordinateur, tablette et téléphone (responsive). Connectez-vous via votre navigateur (Safari, Chrome, Firefox).

---

## Mes droits (RGPD)

### Vos données personnelles
Domitys stocke sur vous : identité, contact, contact d'urgence, infos santé (médecin, allergies, traitement), infos financières (loyer, aides), CNI, sécurité sociale, contrat d'occupation.

### Vos droits
- **Accès** : tout est consultable dans votre espace personnel
- **Rectification** : demande via la messagerie ou auprès de l'accueil
- **Effacement** : possible après votre départ (sauf données comptables conservées 10 ans)
- **Portabilité** : vous pouvez télécharger vos documents GED à tout moment

### Sécurité
- Vos données sensibles (santé, CNI) sont accessibles **uniquement aux rôles autorisés** (admin, directeur de résidence, équipe accueil)
- Toutes les modifications sur votre fiche sont **tracées** dans un journal d'audit (consultable par Domitys)
- Vos documents GED sont **chiffrés au repos** et **accessibles uniquement via votre compte authentifié**
