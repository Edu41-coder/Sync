# 🎨 Guide : Changer le Thème de l'Application

## ✅ Oui, c'est TRÈS FACILE avec la structure actuelle !

L'application utilise **des variables CSS globales** qui permettent de changer toutes les couleurs en modifiant **UN SEUL FICHIER** : `public/assets/css/style.css`

---

## 🔴 Exemple : Passer au thème ROUGE/ROSE

### Option 1 : Modifier les variables CSS (Recommandé)

Ouvrez `public/assets/css/style.css` et modifiez les lignes 13-19 :

```css
:root {
    /* AVANT (Bleu Bootstrap) */
    --primary-color: #0d6efd;
    
    /* APRÈS (Rouge) */
    --primary-color: #dc3545;  /* Rouge vif */
    --primary-hover: #bb2d3b;  /* Rouge foncé au survol */
    --primary-rgb: 220, 53, 69; /* Pour les transparences */
    
    /* Optionnel : adapter les autres couleurs */
    --secondary-color: #e83e8c;  /* Rose */
    --info-color: #ff80ab;       /* Rose clair */
    --bg-body: #fff5f5;          /* Fond rose très léger */
}
```

**C'est tout ! Toute l'application devient rouge instantanément** 🎉

---

## 🎨 Exemples de palettes prêtes à l'emploi

### 🔴 Thème Rouge Corporate
```css
:root {
    --primary-color: #c41e3a;
    --primary-hover: #a01728;
    --primary-rgb: 196, 30, 58;
    --secondary-color: #8b0000;
}
```

### 🟣 Thème Violet/Pourpre
```css
:root {
    --primary-color: #6f42c1;
    --primary-hover: #5a32a3;
    --primary-rgb: 111, 66, 193;
    --secondary-color: #9b59b6;
}
```

### 🟢 Thème Vert Nature
```css
:root {
    --primary-color: #28a745;
    --primary-hover: #218838;
    --primary-rgb: 40, 167, 69;
    --secondary-color: #20c997;
}
```

### 🟠 Thème Orange/Mandarine
```css
:root {
    --primary-color: #fd7e14;
    --primary-hover: #e8590c;
    --primary-rgb: 253, 126, 20;
    --secondary-color: #ff6b6b;
}
```

### ⚫ Thème Sombre Élégant
```css
:root {
    --primary-color: #ffd700;  /* Or */
    --primary-hover: #ffed4e;
    --primary-rgb: 255, 215, 0;
    --bg-body: #1a1a1a;
    --bg-card: #2d2d2d;
    --text-primary: #e0e0e0;
}
```

---

## 🚀 Avantages de la structure actuelle

### ✅ **1 seul fichier à modifier**
- Pas besoin de toucher à 50 fichiers HTML/PHP
- Toutes les pages sont automatiquement mises à jour

### ✅ **Variables CSS intelligentes**
Le fichier `style.css` utilise déjà ces variables partout :
- Boutons : `.btn-primary` utilise `var(--primary-color)`
- Badges : `.badge.bg-primary` utilise `var(--primary-color)`
- Liens : `a` utilise `var(--primary-color)`
- Navbar : `.navbar.bg-primary` utilise `var(--primary-color)`
- Formulaires : `.form-control:focus` utilise `var(--primary-color)`
- Et 20+ autres éléments !

### ✅ **Compatible avec les préférences utilisateur**
Le système de thème sombre/clair dans `main.php` fonctionne avec n'importe quelle couleur primaire !

---

## 📝 Étapes pour changer le thème

1. **Ouvrir** : `public/assets/css/style.css`
2. **Modifier** les variables (lignes 13-19)
3. **Sauvegarder** le fichier
4. **Rafraîchir** le navigateur (Ctrl+F5)
5. **Terminé !** ✨ Toute l'application a changé de couleur

---

## 🎨 Option avancée : Thèmes multiples

Si vous voulez que les utilisateurs choisissent entre plusieurs thèmes :

1. Créer des fichiers CSS séparés :
   - `style-blue.css` (bleu par défaut)
   - `style-red.css` (rouge)
   - `style-green.css` (vert)

2. Modifier `main.php` pour charger dynamiquement :
```php
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style-<?= $userThemeColor ?? 'blue' ?>.css">
```

3. Ajouter une option dans Settings pour choisir la couleur

---

## 🔧 Personnalisation avancée

Le fichier `style.css` contient aussi :
- **Ombres** : `--shadow`, `--shadow-sm`, `--shadow-lg`
- **Bordures** : `--border-color`, `--border-radius`
- **Transitions** : `--transition-speed`
- **Typographie** : `--font-family`

Vous pouvez tout personnaliser facilement !

---

## ✅ Conclusion

**Réponse : OUI, c'est TRÈS FACILE !**

Pour passer au rouge/rose dans toute l'application :
1. Ouvrir `public/assets/css/style.css`
2. Changer `--primary-color: #0d6efd;` en `--primary-color: #dc3545;`
3. Sauvegarder
4. Rafraîchir le navigateur

**Et voilà ! Toute l'application est rouge !** 🔴✨

La structure MVC avec variables CSS globales rend le changement de thème **instantané et centralisé** ! 🎉
