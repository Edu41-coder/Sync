# 📝 Standards de Formulaires - Synd_Gest

## 🎨 Charte graphique unifiée

Tous les formulaires de création et d'édition suivent le même design avec le thème **ROUGE/ROSE**.

---

## 🏗️ Structure HTML standardisée

### Layout de base

```html
<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-icon', 'text' => 'Section', 'url' => BASE_URL . '/section'],
        ['icon' => 'fas fa-plus', 'text' => 'Créer', 'url' => null]  <!-- ou 'fas fa-edit' pour édition -->
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-plus-circle text-dark"></i>  <!-- text-dark pour création -->
                <!-- <i class="fas fa-edit text-warning"></i> pour édition -->
                Titre de la page
            </h1>
        </div>
    </div>
    
    <!-- Formulaire -->
    <div class="row">
        <div class="col-12 col-lg-8">
            <!-- Carte principale -->
        </div>
        
        <div class="col-12 col-lg-4 mt-3 mt-lg-0">
            <!-- Sidebar aide -->
        </div>
    </div>
</div>
```

---

## 🎴 Carte principale du formulaire

### Header - Pour création (rouge/rose)

```html
<div class="card shadow">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="fas fa-icon me-2"></i>
            Titre de la section
        </h5>
    </div>
    
    <div class="card-body">
        <!-- Formulaire -->
    </div>
</div>
```

### Header - Pour édition (jaune)

```html
<div class="card shadow">
    <div class="card-header bg-warning">
        <h5 class="mb-0">
            <i class="fas fa-icon me-2"></i>
            Titre de la section
        </h5>
    </div>
    
    <div class="card-body">
        <!-- Formulaire -->
    </div>
</div>
```

### Formulaire complet avec boutons

```html
<div class="card shadow">
    <div class="card-header bg-danger text-white">  <!-- ou bg-warning pour édition -->
        <h5 class="mb-0">
            <i class="fas fa-icon me-2"></i>
            Titre de la section
        </h5>
    </div>
    
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/controller/action">
            <?= csrf_field() ?>
            
            <!-- Champs du formulaire -->
            
            <!-- Zone de boutons -->
            <div class="row mt-4">
                <div class="col-12">
                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/retour" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
```

### Couleurs de headers

- **Principal (création)** : `bg-danger text-white` → Dégradé rouge/rose
- **Principal (édition)** : `bg-warning` → Dégradé jaune
- **Secondaire (info/sidebar)** : `bg-light border-bottom` → Fond gris clair

### Couleurs d'icônes dans les titres

- **Pages de création** : Icône noire `text-dark`
- **Pages d'édition** : Icône jaune `text-warning`

---

## 📋 Champs de formulaire standardisés

### Champ texte simple

```html
<div class="mb-3">
    <label for="nom" class="form-label">
        <i class="fas fa-tag me-1"></i>Nom <span class="text-danger">*</span>
    </label>
    <input type="text" 
           class="form-control" 
           id="nom" 
           name="nom" 
           placeholder="Exemple..."
           required>
</div>
```

### Champ avec input-group (icône)

```html
<div class="mb-3">
    <label for="telephone" class="form-label">
        Téléphone
    </label>
    <div class="input-group">
        <span class="input-group-text">
            <i class="fas fa-phone"></i>
        </span>
        <input type="tel" 
               class="form-control" 
               id="telephone" 
               name="telephone">
    </div>
</div>
```

### Ligne avec 2 colonnes

```html
<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <!-- Champ 1 -->
    </div>
    <div class="col-12 col-md-6 mb-3">
        <!-- Champ 2 -->
    </div>
</div>
```

### Select / Dropdown

```html
<div class="mb-3">
    <label for="type" class="form-label">
        <i class="fas fa-list me-1"></i>Type <span class="text-danger">*</span>
    </label>
    <select class="form-select" id="type" name="type" required>
        <option value="">-- Sélectionner --</option>
        <option value="option1">Option 1</option>
        <option value="option2">Option 2</option>
    </select>
</div>
```

### Textarea

```html
<div class="mb-3">
    <label for="description" class="form-label">
        Description
    </label>
    <textarea class="form-control" 
              id="description" 
              name="description" 
              rows="3"
              placeholder="Informations complémentaires..."></textarea>
</div>
```

---

## 🎯 Sidebar d'aide (colonne droite)

### Structure

```html
<div class="col-12 col-lg-4 mt-3 mt-lg-0">
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-lightbulb text-warning me-2"></i>
                Aide
            </h6>
        </div>
        <div class="card-body">
            <h6 class="fw-bold">Titre section</h6>
            <ul class="small">
                <li><strong>Champ 1 :</strong> Description</li>
                <li><strong>Champ 2 :</strong> Description</li>
            </ul>
            
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <small>Message d'information</small>
            </div>
        </div>
    </div>
</div>
```

---

## 🎨 Classes CSS appliquées automatiquement

### Headers de formulaires

```css
/* Header principal - Dégradé rouge/rose */
.card-header.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
}

/* Header secondaire - Fond clair */
.card-header.bg-light {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #ffcdd2;
}
```

### Champs de formulaire

```css
/* Focus sur input - Bordure rouge */
.form-control:focus,
.form-select:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

/* Input groups - Fond rose clair */
.input-group-text {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(232, 62, 140, 0.1) 100%);
    border-color: #ffcdd2;
    color: #dc3545;
}

/* Labels avec icônes rouges */
.form-label i {
    color: #dc3545;
    margin-right: 0.25rem;
}
```

### Boutons

```css
/* Bouton principal - Dégradé rouge/rose */
.btn-primary {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
    border: none !important;
    box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2);
}

/* Bouton Annuler - Gris */
.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}
```

---

## 📐 Règles de design

### Espacement

- **Padding card-body** : Standard Bootstrap (1rem)
- **Espacement entre champs** : `mb-3` (1rem)
- **Espacement avant boutons** : `mt-4` + `<hr>` (1.5rem)

### Largeurs

- **Formulaire principal** : `col-12 col-lg-8` (8/12 en desktop)
- **Sidebar** : `col-12 col-lg-4` (4/12 en desktop)

### Icônes

- **Dans labels** : Font Awesome 6.4, taille par défaut
- **Dans headers** : `me-2` (margin-right)
- **Dans boutons** : `me-1` (margin-right)

### Champs obligatoires

- Astérisque rouge : `<span class="text-danger">*</span>`
- Attribut HTML : `required`

---

## ✅ Checklist de conformité

Avant de valider un formulaire, vérifier :

- [ ] Header principal avec `bg-danger text-white`
- [ ] Fil d'Ariane avec icônes cohérentes
- [ ] Tous les labels ont des icônes Font Awesome
- [ ] Champs obligatoires marqués avec `*` rouge
- [ ] Token CSRF présent : `<?= csrf_field() ?>`
- [ ] Boutons alignés : Annuler (gauche) / Enregistrer (droite)
- [ ] Sidebar d'aide avec `bg-light`
- [ ] Layout responsive : `col-12 col-lg-8` + `col-12 col-lg-4`
- [ ] Espacement standardisé : `mb-3` entre champs
- [ ] `<hr>` avant la zone de boutons

---

## 🔄 Formulaires conformes

### ✅ Pages alignées (après uniformisation)

1. **Résidences**
   - `/admin/createResidence` ✅
   - `/admin/editResidence/{id}` ✅

2. **Utilisateurs**
   - `/admin/users/create` ✅
   - `/admin/users/edit/{id}` ✅

3. **Lots**
   - `/lot/create/{residenceId}` ✅
   - `/lot/edit/{id}` ✅

### 📋 Prochains formulaires à créer

Suivre ce standard pour :
- Occupations (création/édition)
- Résidents (création/édition)
- Contrats de gestion
- Documents
- Paiements

---

## 💡 Exemples de code complets

### Formulaire de création minimal

```php
<?php $title = "Créer un élément"; ?>

<div class="container-fluid py-4">
    
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-folder', 'text' => 'Éléments', 'url' => BASE_URL . '/elements'],
        ['icon' => 'fas fa-plus', 'text' => 'Créer', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-plus-circle text-dark"></i>
                Nouvel élément
            </h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations
                    </h5>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/element/store">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nom <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="<?= BASE_URL ?>/elements" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Créer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-4 mt-3 mt-lg-0">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Aide
                    </h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">Informations d'aide ici</p>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

**Version 1.0 - 11 décembre 2025**
