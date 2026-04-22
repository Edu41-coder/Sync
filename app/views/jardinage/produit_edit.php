<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-book-open', 'text' => 'Catalogue', 'url' => BASE_URL . '/jardinage/produits'],
    ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$categorieLabels = [
    'engrais' => 'Engrais', 'terreau' => 'Terreau', 'semence' => 'Semence', 'plant' => 'Plant',
    'phytosanitaire' => 'Phytosanitaire', 'outillage_main' => 'Outil à main',
    'outillage_motorise' => 'Outil motorisé', 'arrosage' => 'Arrosage',
    'protection' => 'Protection', 'consommable' => 'Consommable', 'autre' => 'Autre'
];
$uniteLabels = ['kg','g','litre','ml','sac','piece','rouleau','bidon','autre'];
?>

<div class="container-fluid py-4" style="max-width:900px">
    <h2 class="mb-4"><i class="fas fa-edit me-2 text-success"></i>Modifier produit</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/jardinage/produits/update/<?= $produit['id'] ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required maxlength="200" value="<?= htmlspecialchars($produit['nom']) ?>"></div>
                    <div class="col-md-4"><label class="form-label">Marque</label>
                        <input type="text" name="marque" class="form-control" maxlength="100" value="<?= htmlspecialchars($produit['marque'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="produit" <?= $produit['type'] === 'produit' ? 'selected' : '' ?>>Produit</option>
                            <option value="outil" <?= $produit['type'] === 'outil' ? 'selected' : '' ?>>Outil</option>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Catégorie</label>
                        <select name="categorie" class="form-select">
                            <?php foreach ($categorieLabels as $k => $l): ?>
                            <option value="<?= $k ?>" <?= $produit['categorie'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-2"><label class="form-label">Unité</label>
                        <select name="unite" class="form-select">
                            <?php foreach ($uniteLabels as $u): ?>
                            <option value="<?= $u ?>" <?= $produit['unite'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-3"><label class="form-label">Prix unitaire (€)</label>
                        <input type="number" step="0.01" min="0" name="prix_unitaire" class="form-control" value="<?= $produit['prix_unitaire'] ?? '' ?>"></div>
                    <div class="col-md-6"><label class="form-label">Fournisseur</label>
                        <select name="fournisseur_id" class="form-select">
                            <option value="">— Aucun —</option>
                            <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= $produit['fournisseur_id'] == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check"><input type="checkbox" class="form-check-input" name="bio" value="1" id="fieldBio" <?= $produit['bio'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fieldBio">Produit BIO</label></div>
                    </div>
                    <div class="col-12"><label class="form-label">Danger / mentions sécurité</label>
                        <input type="text" name="danger" class="form-control" maxlength="255" value="<?= htmlspecialchars($produit['danger'] ?? '') ?>"></div>
                    <div class="col-12">
                        <label class="form-label">Photo <small class="text-muted">(JPG, PNG, WEBP · max 5 Mo)</small></label>
                        <?php if (!empty($produit['photo'])): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($produit['photo']) ?>" alt="" class="rounded me-2" style="width:120px;height:90px;object-fit:cover;cursor:zoom-in" ondblclick="showPhotoViewer('<?= BASE_URL . '/' . htmlspecialchars($produit['photo']) ?>', <?= htmlspecialchars(json_encode($produit['nom']), ENT_QUOTES) ?>)" title="Double-clic pour agrandir">
                            <a href="<?= BASE_URL ?>/jardinage/produits/photoDelete/<?= (int)$produit['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette photo ?')"><i class="fas fa-trash me-1"></i>Supprimer la photo</a>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-12"><label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($produit['notes'] ?? '') ?></textarea></div>
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="actif" value="1" id="fieldActif" <?= $produit['actif'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fieldActif">Actif</label></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/jardinage/produits" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal viewer photo -->
<div class="modal fade" id="photoViewer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoViewerTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="photoViewerImg" src="" alt="" style="max-width:100%;max-height:80vh;object-fit:contain">
            </div>
        </div>
    </div>
</div>
<script>
function showPhotoViewer(src, nom) {
    document.getElementById('photoViewerImg').src = src;
    document.getElementById('photoViewerTitle').textContent = nom;
    new bootstrap.Modal(document.getElementById('photoViewer')).show();
}
</script>
