# 🗺️ Documentation - ResidenceMap.js

## Vue d'ensemble

La classe `ResidenceMap` permet de créer des cartes interactives Leaflet.js pour afficher les résidences Domitys avec des marqueurs personnalisés, des popups d'informations et des contrôles de zoom.

---

## Installation

### 1. Inclure les dépendances

```html
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Styles personnalisés -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/map.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Script personnalisé -->
<script src="<?= BASE_URL ?>/assets/js/map.js"></script>
```

### 2. Créer un conteneur pour la carte

```html
<div id="map" style="height: 600px;"></div>
```

### 3. Ajouter les boutons de contrôle (optionnel)

```html
<div class="btn-group" role="group">
    <button type="button" class="btn btn-outline-secondary" id="btnZoomIn">
        <i class="fas fa-plus"></i>
    </button>
    <button type="button" class="btn btn-outline-secondary" id="btnZoomOut">
        <i class="fas fa-minus"></i>
    </button>
    <button type="button" class="btn btn-outline-secondary" id="btnResetView">
        <i class="fas fa-sync"></i> Réinitialiser
    </button>
</div>
```

---

## Utilisation de base

### Initialisation simple

```javascript
// Données des résidences (depuis PHP)
const residences = <?= json_encode($residences) ?>;
const baseUrl = '<?= BASE_URL ?>';

// Créer la carte
const residenceMap = new ResidenceMap('map', residences, baseUrl);
```

### Avec DOMContentLoaded

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const residences = <?= json_encode($residences) ?>;
    const baseUrl = '<?= BASE_URL ?>';
    
    const residenceMap = new ResidenceMap('map', residences, baseUrl);
    
    // Optionnel : rendre accessible globalement
    window.residenceMap = residenceMap;
});
```

---

## Format des données

La classe attend un tableau d'objets résidences avec les propriétés suivantes :

```javascript
[
    {
        id: 61,                           // ID unique
        nom: "Domitys - La Badiane",      // Nom de la résidence
        adresse: "45 Bd de la Corderie",  // Adresse
        code_postal: "13007",             // Code postal
        ville: "Marseille",               // Ville
        latitude: 43.2890,                // Latitude GPS (DECIMAL)
        longitude: 5.3640,                // Longitude GPS (DECIMAL)
        exploitant: "Domitys",            // Nom de l'exploitant
        nb_lots: 45,                      // Nombre de lots
        nb_occupations: 38,               // Nombre d'occupations
        taux_occupation: 84.4             // Taux en pourcentage
    },
    // ... autres résidences
]
```

---

## Méthodes disponibles

### Contrôles de base

```javascript
// Zoom avant
residenceMap.zoomIn();

// Zoom arrière
residenceMap.zoomOut();

// Réinitialiser la vue
residenceMap.resetView();

// Ajuster la vue sur tous les marqueurs
residenceMap.fitBounds();
```

### Navigation

```javascript
// Centrer sur une résidence spécifique (zoom niveau 15)
residenceMap.centerOnResidence(61);
```

### Filtres

```javascript
// Filtrer par ville
const count = residenceMap.filterMarkers({
    ville: 'Marseille'
});
console.log(`${count} résidences affichées`);

// Filtrer par taux d'occupation
residenceMap.filterMarkers({
    tauxMin: 80  // Seulement taux >= 80%
});

// Filtrer par exploitant
residenceMap.filterMarkers({
    exploitant: 'Domitys'
});

// Combinaison de filtres
residenceMap.filterMarkers({
    ville: 'Lyon',
    tauxMin: 50,
    tauxMax: 90,
    exploitant: 'Domitys'
});

// Réinitialiser les filtres (afficher tout)
residenceMap.resetFilters();
```

---

## Personnalisation des couleurs

Les marqueurs sont automatiquement colorés selon le taux d'occupation :

| Taux d'occupation | Couleur | Code     |
|-------------------|---------|----------|
| >= 80%            | Vert    | #198754  |
| 50% - 79%         | Jaune   | #ffc107  |
| < 50%             | Rouge   | #dc3545  |

Pour modifier les seuils, éditez la méthode `getMarkerColor()` dans `map.js` :

```javascript
getMarkerColor(taux) {
    if (taux >= 90) return '#198754'; // Nouveau seuil : 90%
    if (taux >= 60) return '#ffc107'; // Nouveau seuil : 60%
    return '#dc3545';
}
```

---

## Personnalisation du popup

Pour modifier le contenu du popup, éditez la méthode `createPopupContent()` :

```javascript
createPopupContent(residence) {
    return `
        <div style="min-width: 250px;">
            <h6>${residence.nom}</h6>
            <!-- Votre contenu personnalisé -->
        </div>
    `;
}
```

---

## Exemples avancés

### 1. Carte avec filtres dynamiques

```html
<select id="filterVille" class="form-select">
    <option value="">Toutes les villes</option>
    <option value="Marseille">Marseille</option>
    <option value="Lyon">Lyon</option>
</select>

<script>
document.getElementById('filterVille').addEventListener('change', function(e) {
    if (e.target.value) {
        residenceMap.filterMarkers({ ville: e.target.value });
    } else {
        residenceMap.resetFilters();
    }
});
</script>
```

### 2. Afficher le nombre de résidences filtrées

```javascript
const count = residenceMap.filterMarkers({ tauxMin: 80 });
document.getElementById('resultCount').textContent = 
    `${count} résidence(s) avec taux >= 80%`;
```

### 3. Centrer sur une résidence au clic

```javascript
document.querySelectorAll('.residence-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const residenceId = this.dataset.id;
        residenceMap.centerOnResidence(residenceId);
    });
});
```

### 4. Exporter la carte en image

```javascript
// Utiliser leaflet-image plugin (à ajouter séparément)
leafletImage(residenceMap.map, function(err, canvas) {
    const img = canvas.toDataURL();
    const link = document.createElement('a');
    link.download = 'carte-residences.png';
    link.href = img;
    link.click();
});
```

---

## Propriétés publiques

| Propriété       | Type          | Description                          |
|-----------------|---------------|--------------------------------------|
| `map`           | L.Map         | Instance Leaflet.js                  |
| `markersGroup`  | L.FeatureGroup| Groupe contenant tous les marqueurs  |
| `residences`    | Array         | Tableau des données résidences       |
| `baseUrl`       | String        | URL de base de l'application         |
| `defaultCenter` | [lat, lng]    | Centre par défaut (France)           |
| `defaultZoom`   | Number        | Niveau de zoom par défaut (6)        |

---

## Responsive

La carte s'adapte automatiquement aux petits écrans :

- **Desktop** : 600px de hauteur
- **Tablet (< 767px)** : 400px de hauteur
- **Mobile (< 575px)** : 350px de hauteur, boutons empilés

Styles définis dans `map.css`.

---

## Débogage

### Vérifier l'initialisation

```javascript
console.log(residenceMap.residences.length); // Nombre de résidences
console.log(residenceMap.markersGroup.getLayers().length); // Nombre de marqueurs
```

### Logs automatiques

La classe affiche automatiquement :
```
Carte initialisée avec 20 résidences
```

### Accès depuis la console

Si vous avez défini `window.residenceMap`, testez depuis la console :
```javascript
// Console du navigateur
residenceMap.zoomIn();
residenceMap.centerOnResidence(61);
residenceMap.filterMarkers({ ville: 'Nice' });
```

---

## Compatibilité

- **Leaflet.js** : 1.9.4+
- **Navigateurs** : Chrome, Firefox, Safari, Edge (dernières versions)
- **Mobile** : iOS 12+, Android 8+
- **Bootstrap** : 5.3+ (pour les styles de popup)
- **Font Awesome** : 6.4+ (pour les icônes)

---

## Troubleshooting

### La carte ne s'affiche pas

1. Vérifier que Leaflet CSS est chargé avant `map.css`
2. Vérifier que `#map` a une hauteur définie (`height: 600px`)
3. Vérifier que Leaflet JS est chargé avant `map.js`
4. Vérifier la console pour les erreurs

### Les marqueurs ne s'affichent pas

1. Vérifier que les données ont `latitude` et `longitude` non NULL
2. Vérifier le format : DECIMAL(10,8) et DECIMAL(11,8)
3. Vérifier que les coordonnées sont valides (France : ~42-51°N, -5 à 8°E)

### Popup ne fonctionne pas

1. Vérifier que Bootstrap 5 est chargé
2. Vérifier que Font Awesome est chargé
3. Vérifier que `BASE_URL` est correctement défini

---

## Améliorations futures

- [ ] Clustering des marqueurs (leaflet.markercluster)
- [ ] Heatmap des taux d'occupation
- [ ] Mode fullscreen
- [ ] Export de la carte en PDF/Image
- [ ] Géolocalisation de l'utilisateur
- [ ] Calcul d'itinéraire vers une résidence
- [ ] Animations de transition entre marqueurs
- [ ] Tooltips au survol (sans clic)

---

## Support

Pour toute question ou bug, consulter :
- Documentation Leaflet.js : https://leafletjs.com/reference.html
- Issues GitHub : (votre repo)
- Contact : (votre email)

---

**Version** : 1.0  
**Dernière mise à jour** : 1er décembre 2024  
**Auteur** : Synd_Gest - Plateforme Domitys
