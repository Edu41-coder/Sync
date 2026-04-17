<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-box', 'text' => 'Produits', 'url' => BASE_URL . '/menage/produits'],
    ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center"><div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-edit me-2 text-info"></i>Modifier : <?= htmlspecialchars($produit['nom']) ?></h5></div>
            <form method="POST" action="<?= BASE_URL ?>/menage/produits/update/<?= $produit['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nom <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($produit['nom']) ?>" required></div>
                        <div class="col-md-3"><label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <?php foreach (['nettoyant'=>'Nettoyant','desinfectant'=>'Désinfectant','lessive'=>'Lessive','materiel'=>'Matériel','sac_poubelle'=>'Sac poubelle','papier'=>'Papier','autre'=>'Autre'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $produit['categorie']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Section</label>
                            <select name="section" class="form-select">
                                <?php foreach (['interieur'=>'Intérieur','exterieur'=>'Extérieur','laverie'=>'Laverie','commun'=>'Commun'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($produit['section'] ?? '')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Unité</label>
                            <select name="unite" class="form-select">
                                <?php foreach (['litre','ml','kg','unite','carton','rouleau','sachet','bidon','boite'] as $u): ?><option value="<?= $u ?>" <?= $produit['unite']===$u?'selected':'' ?>><?= $u ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Prix réf.</label><input type="number" name="prix_reference" class="form-control" step="0.01" value="<?= $produit['prix_reference'] ?? '' ?>"></div>
                        <div class="col-md-3"><label class="form-label">Code-barres</label><input type="text" name="code_barre" class="form-control" value="<?= htmlspecialchars($produit['code_barre'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label">Marque</label><input type="text" name="marque" class="form-control" value="<?= htmlspecialchars($produit['marque'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Fournisseur</label>
                            <select name="fournisseur_id" class="form-select"><option value="">-- Aucun --</option>
                                <?php foreach ($fournisseurs as $f): ?><option value="<?= $f['id'] ?>" <?= ($produit['fournisseur_id'] ?? 0)==$f['id']?'selected':'' ?>><?= htmlspecialchars($f['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6"><label class="form-label">Conditionnement</label><input type="text" name="conditionnement" class="form-control" value="<?= htmlspecialchars($produit['conditionnement'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($produit['notes'] ?? '') ?>"></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="actif" value="1" <?= $produit['actif']?'checked':'' ?>><label class="form-check-label">Actif</label></div></div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/menage/produits" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save me-2"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div></div>
</div>
