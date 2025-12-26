# 📦 Gestion des Lots - Documentation

## 🎯 Vue d'ensemble

Le système de gestion des lots permet de créer et gérer les unités immobilières (appartements, parkings, caves) au sein des résidences.

---

## 🔗 Navigation

### Accès aux fonctionnalités

1. **Liste des résidences** : `/admin/residences`
2. **Détails d'une résidence** : `/admin/viewResidence/{id}`
3. **Créer un lot** : `/lot/create/{residenceId}` ou clic sur "Ajouter un lot"
4. **Modifier un lot** : `/lot/edit/{lotId}`

---

## 📋 Structure des données

### Table `lots`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique |
| `copropriete_id` | INT | ID de la résidence |
| `numero_lot` | VARCHAR | Numéro du lot (ex: 101, A12) |
| `type` | ENUM | Type : appartement, parking, cave, commerce, autre |
| `surface` | DECIMAL | Surface en m² |
| `nombre_pieces` | INT | Nombre de pièces principales |
| `etage` | INT | Étage (0=RDC, -1=Sous-sol) |
| `batiment` | VARCHAR | Identifiant du bâtiment |
| `tantieme` | DECIMAL | Quotes-parts de copropriété |
| `description` | TEXT | Informations complémentaires |
| `statut` | ENUM | disponible, occupe, maintenance |

---

## 🎨 Fichiers créés

### Vues

1. **`app/views/lots/create.php`**
   - Formulaire de création de lot
   - Champs : numéro, type, surface, pièces, étage, bâtiment, tantièmes, description
   - Validation côté client (JavaScript)
   - Sidebar d'aide

2. **`app/views/lots/edit.php`**
   - Formulaire d'édition (pré-rempli)
   - Même structure que create.php
   - Affiche dates de création/modification
   - Carte informations du lot

### Contrôleur

**`app/controllers/LotController.php`** - Méthodes implémentées :

```php
// Afficher le formulaire de création
public function create($residenceId)
- Vérifie que la résidence existe
- Charge les données de la résidence
- Affiche le formulaire

// Enregistrer un nouveau lot
public function store()
- Validation CSRF
- Validation des champs obligatoires (numero, type)
- Nettoyage et conversion des données
- Appel Lot::create()
- Redirection vers viewResidence avec message flash

// Afficher le formulaire d'édition
public function edit($id)
- Charge le lot avec findWithDetails()
- Charge la résidence
- Pré-remplit le formulaire

// Mettre à jour un lot
public function update($id)
- Validation CSRF
- Validation des champs
- Appel Lot::updateLot()
- Redirection vers viewResidence
```

### Modèle

**`app/models/Lot.php`** - Méthodes disponibles :

- `create($data)` - Créer un nouveau lot
- `updateLot($id, $data)` - Modifier un lot
- `findWithDetails($id)` - Récupérer lot + résidence + occupation
- `getByResidence($residenceId)` - Tous les lots d'une résidence
- `getAvailable($residenceId)` - Lots disponibles
- `deleteLot($id)` - Supprimer un lot
- `isOccupied($lotId)` - Vérifier si occupé
- `getResidenceStats($residenceId)` - Statistiques résidence

---

## ✅ Validation des données

### Champs obligatoires

- ✅ `numero` : Numéro de lot (texte, unique par résidence)
- ✅ `type` : Type de lot (liste déroulante)
- ✅ `copropriete_id` : ID de la résidence (hidden field)

### Champs optionnels

- `surface` : Surface en m² (nombre décimal)
- `nombre_pieces` : Nombre de pièces (entier)
- `etage` : Étage (entier, peut être négatif)
- `batiment` : Identifiant du bâtiment (texte)
- `tantiemes` : Quotes-parts (nombre décimal)
- `description` : Informations complémentaires (texte long)

### Sécurité

- ✅ Token CSRF vérifié sur store() et update()
- ✅ Authentification requise (requireAuth)
- ✅ Rôles autorisés : admin, gestionnaire
- ✅ htmlspecialchars sur tous les affichages
- ✅ trim() sur les champs texte
- ✅ Conversion de types (int, float)

---

## 🔄 Workflow utilisateur

### Création d'un lot

1. Accéder à la page de détail d'une résidence
2. Cliquer sur "Ajouter un lot" (bouton violet)
3. Remplir le formulaire :
   - Numéro de lot *
   - Type *
   - Surface (optionnel)
   - Nombre de pièces (optionnel)
   - Étage (optionnel)
   - Bâtiment (optionnel)
   - Tantièmes (optionnel)
   - Description (optionnel)
4. Cliquer sur "Créer le lot"
5. Redirection vers la page de la résidence
6. Message de confirmation affiché

### Modification d'un lot

1. Depuis la page de détail de la résidence
2. Dans la liste des lots, cliquer sur l'icône "Modifier" (crayon)
3. Modifier les champs souhaités
4. Cliquer sur "Enregistrer les modifications"
5. Redirection vers la page de la résidence
6. Message de confirmation affiché

---

## 🎯 Intégration avec residence_view.php

### Liens existants

```php
<!-- Bouton dans le header de la carte Lots -->
<a href="<?= BASE_URL ?>/lot/create/<?= $residence['id'] ?>" 
   class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i>Ajouter un lot
</a>

<!-- État vide (aucun lot) -->
<a href="<?= BASE_URL ?>/lot/create/<?= $residence['id'] ?>" 
   class="btn btn-primary">
    <i class="fas fa-plus me-2"></i>Créer le premier lot
</a>

<!-- Bouton modifier dans la liste -->
<a href="<?= BASE_URL ?>/lot/edit/<?= $lot['id'] ?>" 
   class="btn btn-sm btn-outline-primary">
    <i class="fas fa-edit"></i>
</a>
```

---

## 🚀 Points d'extension futurs

### Fonctionnalités à implémenter

1. **Suppression de lots**
   - Méthode delete() dans LotController
   - Modal de confirmation
   - Vérification si lot occupé

2. **Gestion des propriétaires**
   - Association lot ↔ propriétaire
   - Table `coproprietaires_lots` (déjà existe)
   - Dropdown dans create/edit

3. **Gestion des occupations**
   - Assigner un résident à un lot
   - Dates début/fin occupation
   - Statut : actif, terminé, à venir

4. **Upload de documents**
   - Plans du lot
   - Diagnostics (DPE, amiante, etc.)
   - Photos

5. **Historique**
   - Suivi des modifications
   - Historique des occupations
   - Historique des loyers

---

## 🐛 Dépannage

### Erreurs courantes

**Problème** : "Identifiant de résidence manquant"
- **Cause** : URL `/lot/create` sans ID de résidence
- **Solution** : Toujours utiliser `/lot/create/{residenceId}`

**Problème** : "Résidence introuvable"
- **Cause** : ID de résidence invalide
- **Solution** : Vérifier que la résidence existe dans la base

**Problème** : "Token CSRF invalide"
- **Cause** : Session expirée ou token absent
- **Solution** : Rafraîchir la page et réessayer

**Problème** : Le lot n'apparaît pas dans la liste
- **Cause** : Lot créé mais liste non rafraîchie
- **Solution** : Vider le cache du navigateur (Ctrl+F5)

---

## 📊 Base de données

### Requête test - Vérifier les lots d'une résidence

```sql
SELECT 
    l.id,
    l.numero_lot,
    l.type,
    l.surface,
    l.etage,
    c.nom as residence_nom,
    o.statut as occupation_statut
FROM lots l
INNER JOIN coproprietees c ON l.copropriete_id = c.id
LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
WHERE c.id = 61
ORDER BY l.numero_lot;
```

### Requête test - Statistiques d'une résidence

```sql
SELECT 
    COUNT(*) as total_lots,
    COUNT(CASE WHEN l.type = 'appartement' THEN 1 END) as total_appartements,
    COUNT(o.id) as lots_occupes,
    ROUND(COUNT(o.id) * 100.0 / NULLIF(COUNT(*), 0), 2) as taux_occupation
FROM lots l
LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
WHERE l.copropriete_id = 61;
```

---

## ✅ Checklist de test

- [ ] Créer un lot depuis une résidence
- [ ] Vérifier que le lot apparaît dans la liste
- [ ] Modifier le lot créé
- [ ] Vérifier que les modifications sont sauvegardées
- [ ] Tester validation champs obligatoires (numero, type)
- [ ] Tester avec champs optionnels vides
- [ ] Tester avec valeurs négatives (étage -1)
- [ ] Tester avec caractères spéciaux dans description
- [ ] Vérifier affichage dates création/modification
- [ ] Vérifier breadcrumb navigation
- [ ] Vérifier messages flash (succès/erreur)
- [ ] Tester bouton Annuler (retour à résidence)

---

**Version 1.0 - 10 décembre 2025**
