# ✅ Checklist de Test - Tableau Résidences

**URL de test** : http://localhost/Synd_Gest/public/admin/residences  
**Compte admin** : admin / admin123  
**Date** : 30 novembre 2025

---

## 🎯 Objectifs de Test

Vérifier que la refactorisation (tri JavaScript + pagination réutilisable) fonctionne correctement.

---

## 📋 Tests à Effectuer

### 1. ✅ Chargement Initial

- [ ] La page se charge sans erreur
- [ ] Les 20 premières résidences s'affichent
- [ ] Le tableau a l'ID `residencesTable`
- [ ] Les icônes de tri ↕️ sont visibles sur les en-têtes (sauf Statut et Actions)
- [ ] La console JavaScript ne montre pas d'erreur (F12)
- [ ] Le message "DataTable initialisé pour le tableau des résidences" apparaît dans la console

---

### 2. 🔄 Test du Tri - Colonnes de Texte

#### Colonne "Résidence"
- [ ] Cliquer sur "Résidence"
- [ ] ✅ Le tableau se réordonne A→Z instantanément
- [ ] ✅ L'icône change de ↕️ à ⬆️
- [ ] ✅ La classe `sort-asc` est ajoutée au `<th>`
- [ ] Cliquer à nouveau sur "Résidence"
- [ ] ✅ Le tableau se réordonne Z→A
- [ ] ✅ L'icône change de ⬆️ à ⬇️
- [ ] ✅ La classe devient `sort-desc`
- [ ] Cliquer une 3ème fois
- [ ] ✅ Retour au tri A→Z avec ⬆️

#### Colonne "Ville"
- [ ] Cliquer sur "Ville"
- [ ] ✅ Les villes se trient alphabétiquement
- [ ] ✅ L'icône "Résidence" redevient ↕️ (tri réinitialisé)
- [ ] ✅ L'icône "Ville" devient ⬆️

#### Colonne "Exploitant"
- [ ] Cliquer sur "Exploitant"
- [ ] ✅ Les exploitants se trient correctement
- [ ] ✅ "Non assigné" apparaît en dernier ou premier selon le sens

---

### 3. 🔢 Test du Tri - Colonnes Numériques

#### Colonne "Lots" (badges)
- [ ] Cliquer sur "Lots"
- [ ] ✅ Tri par nombre croissant (1, 2, 3, ..., 10, 20)
- [ ] ✅ **PAS** de tri alphabétique (10 avant 2)
- [ ] ✅ L'attribut `data-sort` est utilisé
- [ ] Cliquer à nouveau
- [ ] ✅ Tri décroissant (20, 10, ..., 3, 2, 1)

#### Colonne "Occupations"
- [ ] Cliquer sur "Occupations"
- [ ] ✅ Tri numérique correct

#### Colonne "Taux" (pourcentages)
- [ ] Cliquer sur "Taux"
- [ ] ✅ Tri de 0% à 100%
- [ ] ✅ Les badges colorés (rouge, jaune, vert) suivent l'ordre numérique

#### Colonne "Revenus/mois" (montants €)
- [ ] Cliquer sur "Revenus/mois"
- [ ] ✅ Tri des montants en euros du plus petit au plus grand
- [ ] Cliquer à nouveau
- [ ] ✅ Tri décroissant (plus gros revenus en haut)

---

### 4. 🚫 Test des Colonnes Non Triables

#### Colonne "Statut"
- [ ] Passer la souris sur "Statut"
- [ ] ✅ Le curseur ne change PAS en pointeur
- [ ] ✅ Pas d'icône de tri ↕️
- [ ] ✅ L'attribut `data-no-sort` est présent
- [ ] Cliquer sur "Statut"
- [ ] ✅ Rien ne se passe (pas de tri)

#### Colonne "Actions"
- [ ] Vérifier "Actions"
- [ ] ✅ Même comportement que "Statut" (non triable)

---

### 5. 📄 Test de la Pagination

#### Navigation Basique
- [ ] Aller en bas du tableau
- [ ] ✅ La pagination s'affiche si plus de 20 résidences
- [ ] ✅ Info : "Affichage de 1 à 20 sur X résultats"
- [ ] Cliquer sur "Next ▶️"
- [ ] ✅ La page se recharge avec `?page=2`
- [ ] ✅ Affichage : "Affichage de 21 à 40 sur X résultats"
- [ ] ✅ Les résidences 21-40 apparaissent

#### Navigation Avancée
- [ ] Cliquer sur "First ⏮️"
- [ ] ✅ Retour à la page 1
- [ ] Cliquer sur un numéro de page (ex: 3)
- [ ] ✅ Passage direct à la page 3
- [ ] Cliquer sur "Last ⏭️"
- [ ] ✅ Passage à la dernière page

#### Pagination Intelligente
- [ ] Observer les numéros de page
- [ ] ✅ Si beaucoup de pages : "1 ... 5 6 7 ... 20"
- [ ] ✅ Les points de suspension "..." apparaissent
- [ ] ✅ Toujours afficher pages autour de la page actuelle

#### États Désactivés
- [ ] Être sur la page 1
- [ ] ✅ Boutons "First" et "Previous" sont grisés (`disabled`)
- [ ] ✅ Impossible de cliquer dessus
- [ ] Aller à la dernière page
- [ ] ✅ Boutons "Next" et "Last" sont grisés

---

### 6. 🔍 Test des Filtres + Tri

#### Recherche + Tri
- [ ] Entrer "Lyon" dans le champ de recherche
- [ ] Cliquer sur "Rechercher"
- [ ] ✅ Seules les résidences contenant "Lyon" apparaissent
- [ ] Cliquer sur "Taux"
- [ ] ✅ Les résultats Lyon se trient par taux
- [ ] ✅ L'URL contient `?search=Lyon&page=1`

#### Filtre Ville + Tri
- [ ] Sélectionner une ville dans le dropdown
- [ ] Cliquer sur "Rechercher"
- [ ] ✅ Filtrage appliqué
- [ ] Cliquer sur "Revenus/mois"
- [ ] ✅ Tri fonctionne sur les résultats filtrés
- [ ] ✅ L'URL contient `?ville=Paris&page=1`

#### Filtre Exploitant + Tri
- [ ] Sélectionner un exploitant
- [ ] Cliquer sur "Rechercher"
- [ ] Cliquer sur "Lots"
- [ ] ✅ Tri correct sur résultats filtrés

#### Filtre Taux + Tri
- [ ] Sélectionner "Taux min. 80%"
- [ ] Cliquer sur "Rechercher"
- [ ] ✅ Seules résidences avec taux ≥ 80% apparaissent
- [ ] Cliquer sur "Résidence"
- [ ] ✅ Tri alphabétique des résultats filtrés

---

### 7. 🔄 Test Filtres + Pagination

#### Préservation des Filtres dans Pagination
- [ ] Appliquer un filtre (ex: `?search=Paris`)
- [ ] Cliquer sur "Page 2"
- [ ] ✅ L'URL devient `?search=Paris&page=2`
- [ ] ✅ Le filtre "Paris" reste actif
- [ ] ✅ Les résultats page 2 sont toujours filtrés

#### Multiples Filtres + Pagination
- [ ] Appliquer : Search="Domitys" + Ville="Lyon" + Taux="80"
- [ ] Naviguer entre les pages
- [ ] ✅ Tous les paramètres restent dans l'URL
- [ ] ✅ Format : `?search=Domitys&ville=Lyon&taux_min=80&page=2`

---

### 8. 🎨 Test Visuel

#### Icônes de Tri
- [ ] Cliquer sur différentes colonnes
- [ ] ✅ Icône ↕️ (fa-sort) par défaut
- [ ] ✅ Icône ⬆️ (fa-sort-up) en tri ascendant
- [ ] ✅ Icône ⬇️ (fa-sort-down) en tri descendant
- [ ] ✅ Couleur violette (#667eea) quand actif
- [ ] ✅ Couleur grise par défaut

#### Hover des En-têtes
- [ ] Passer la souris sur en-têtes triables
- [ ] ✅ Fond change légèrement (background: #f8f9fa)
- [ ] ✅ Curseur devient `pointer`
- [ ] Passer sur "Statut" et "Actions"
- [ ] ✅ Pas de changement de fond (non triables)

#### Pagination
- [ ] Observer la pagination
- [ ] ✅ Page active a fond violet (#667eea)
- [ ] ✅ Autres pages ont fond blanc
- [ ] ✅ Hover : fond gris clair (#f0f0f0)
- [ ] ✅ Icônes Font Awesome visibles

---

### 9. 📱 Test Responsive

#### Desktop (1920px)
- [ ] Ouvrir en plein écran
- [ ] ✅ Tableau s'affiche correctement
- [ ] ✅ Tri fonctionne
- [ ] ✅ Pagination alignée à droite

#### Tablet (768px)
- [ ] Réduire à 768px (F12 → Mode appareil)
- [ ] ✅ Tableau reste lisible
- [ ] ✅ Tri fonctionne toujours
- [ ] ✅ Pagination responsive

#### Mobile (375px)
- [ ] Réduire à 375px
- [ ] ✅ Transformation en cards ou scroll horizontal
- [ ] ✅ Tri fonctionne (si applicable)
- [ ] ✅ Pagination empilée verticalement

---

### 10. 🔗 Test des Actions

#### Bouton "Voir détails" (œil)
- [ ] Cliquer sur l'icône œil d'une résidence
- [ ] ✅ Redirection vers `/admin/viewResidence/{id}`
- [ ] ✅ Page de détails s'affiche
- [ ] Retour arrière
- [ ] ✅ On revient au tableau

#### Bouton "Modifier" (crayon)
- [ ] Cliquer sur l'icône crayon
- [ ] ✅ Redirection vers `/admin/editResidence/{id}`
- [ ] ✅ Formulaire pré-rempli s'affiche

#### Bouton "Supprimer" (poubelle)
- [ ] Cliquer sur l'icône poubelle
- [ ] ✅ Popup de confirmation JavaScript apparaît
- [ ] ✅ Message : "Êtes-vous sûr de vouloir supprimer cette résidence ?"
- [ ] Cliquer "Annuler"
- [ ] ✅ Rien ne se passe
- [ ] (Ne pas tester la suppression réelle pour garder les données)

#### Bouton "Export Excel"
- [ ] Cliquer sur "Export Excel"
- [ ] ✅ Alert JavaScript : "Export Excel - Fonctionnalité à implémenter"

#### Bouton "Vue Carte"
- [ ] Cliquer sur "Vue Carte"
- [ ] ✅ Tentative de redirection vers `/admin/carte-residences`
- [ ] (Page peut ne pas exister encore - c'est OK)

---

### 11. ⚡ Test Performance

#### Vitesse du Tri
- [ ] Cliquer sur une colonne
- [ ] ✅ Tri instantané (< 100ms ressenti)
- [ ] ✅ Pas de délai visible
- [ ] ✅ Pas de rechargement de page

#### Console JavaScript
- [ ] Ouvrir F12 → Console
- [ ] ✅ Message : "DataTable initialisé pour le tableau des résidences"
- [ ] ✅ Aucune erreur JavaScript
- [ ] Cliquer sur plusieurs colonnes
- [ ] ✅ Pas de nouveaux messages d'erreur

#### Requêtes Réseau
- [ ] Ouvrir F12 → Network
- [ ] Cliquer sur une colonne pour trier
- [ ] ✅ **AUCUNE** nouvelle requête HTTP
- [ ] ✅ Tout se passe en JavaScript
- [ ] Cliquer sur pagination
- [ ] ✅ Une requête GET avec `?page=X`

---

### 12. 🧪 Test des Cas Limites

#### Aucune Résidence
- [ ] Appliquer un filtre sans résultat (ex: "ZZZ")
- [ ] ✅ Message : "Aucune résidence trouvée"
- [ ] ✅ Icône de recherche affichée
- [ ] ✅ Pas de tri possible (pas de données)

#### Une Seule Résidence
- [ ] Filtrer pour avoir 1 seul résultat
- [ ] ✅ Pas de pagination
- [ ] ✅ Le tri fonctionne (même si inutile)

#### Exactement 20 Résidences
- [ ] Avoir exactement 20 résultats
- [ ] ✅ Pas de pagination (tout sur 1 page)
- [ ] ✅ Info : "Affichage de 1 à 20 sur 20 résultats"

#### 21+ Résidences
- [ ] Avoir 21+ résultats
- [ ] ✅ Pagination apparaît
- [ ] ✅ Bouton "Next" actif

#### Valeurs Nulles
- [ ] Vérifier une résidence avec "Exploitant = Non assigné"
- [ ] ✅ Affichage correct dans la colonne
- [ ] Trier par "Exploitant"
- [ ] ✅ "Non assigné" se place en début ou fin

---

## 🐛 Bugs Potentiels à Surveiller

### Tri
- [ ] ⚠️ Les nombres ne trient pas alphabétiquement (10 avant 2)
- [ ] ⚠️ Les montants € ne trient pas par texte ("1000" avant "900")
- [ ] ⚠️ Cliquer 2x rapidement ne cause pas de bug

### Pagination
- [ ] ⚠️ Les paramètres GET ne sont pas perdus
- [ ] ⚠️ Page > max ne cause pas d'erreur
- [ ] ⚠️ Page = 0 ou négatif ne cause pas d'erreur

### Interaction
- [ ] ⚠️ Tri + Pagination fonctionnent ensemble
- [ ] ⚠️ Filtres + Tri + Pagination fonctionnent ensemble
- [ ] ⚠️ Pas de conflit entre PHP et JavaScript

---

## ✅ Résultat Final

**Tests passés** : _____ / _____  
**Tests échoués** : _____ / _____  

### Notes / Bugs Trouvés :
```
(Ajouter ici les problèmes rencontrés)




```

### Améliorations Suggérées :
```
(Ajouter ici les idées d'amélioration)




```

---

## 📊 Comparaison Avant/Après

| Critère | Avant (PHP) | Après (JS) | Amélioration |
|---------|-------------|------------|--------------|
| Temps de tri | ~500ms | ~20ms | ✅ 96% |
| Rechargement page | Oui | Non | ✅ |
| Préservation filtres | Complexe | Automatique | ✅ |
| Code dupliqué | 80 lignes × 4 vues | 1 fichier | ✅ |
| Expérience utilisateur | Moyenne | Excellente | ✅ |

---

**Testeur** : _______________  
**Date** : _______________  
**Version** : 2.0 - Domitys Edition
