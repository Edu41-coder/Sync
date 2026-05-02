<?php
$isEdit = !empty($produit);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-box',            'text' => 'Catalogue',       'url' => BASE_URL . '/maintenance/produits'],
    ['icon' => 'fas fa-edit',           'text' => $isEdit ? 'Modifier #' . (int)$produit['id'] : 'Nouveau', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$action = BASE_URL . '/maintenance/produitForm' . ($isEdit ? '/' . (int)$produit['id'] : '');
$v = function($k, $def = '') use ($produit, $isEdit) { return $isEdit ? htmlspecialchars((string)($produit[$k] ?? $def)) : $def; };
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4"><i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> text-warning me-2"></i><?= $isEdit ? 'Modifier le produit' : 'Nouveau produit' ?></h1>

    <form method="POST" action="<?= $action ?>" class="card shadow-sm">
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" required maxlength="200" value="<?= $v('nom') ?>" placeholder="Ex: Disjoncteur 16A">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <?php foreach ($types as $t): ?>
                        <option value="<?= $t ?>" <?= $isEdit && $produit['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Spécialité</label>
                    <select name="specialite_id" class="form-select">
                        <option value="">— Générique —</option>
                        <?php foreach ($specialites as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= $isEdit && (int)($produit['specialite_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-select">
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c ?>" <?= $isEdit && $produit['categorie'] === $c ? 'selected' : '' ?>><?= str_replace('_', ' ', $c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unité</label>
                    <input type="text" name="unite" class="form-control" maxlength="20" value="<?= $v('unite') ?>" placeholder="ex: pièce, m, L, kg">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prix unitaire HT (€)</label>
                    <input type="number" step="0.01" min="0" name="prix_unitaire" class="form-control" value="<?= $v('prix_unitaire') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Fiche de sécurité (FDS)</label>
                    <input type="text" name="fiche_securite" class="form-control" maxlength="512" value="<?= $v('fiche_securite') ?>" placeholder="Lien ou référence vers la FDS si produit chimique">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" name="actif" id="actif" value="1" <?= !$isEdit || !empty($produit['actif']) ? 'checked' : '' ?>>
                        <label for="actif" class="form-check-label">Produit actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= $v('notes') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/maintenance/produits" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer' ?></button>
        </div>
    </form>
</div>
