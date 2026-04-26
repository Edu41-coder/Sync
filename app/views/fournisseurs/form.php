<?php
$isEdit = !empty($fournisseur);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => BASE_URL . '/fournisseur/index'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? htmlspecialchars($fournisseur['nom']) : 'Nouveau', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typesLabels = Fournisseur::TYPES_SERVICE;
$typesSelected = $isEdit && !empty($fournisseur['type_service'])
    ? explode(',', $fournisseur['type_service']) : [];
?>

<div class="container-fluid py-4" style="max-width:1000px">
    <h2 class="mb-4">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2 text-primary"></i>
        <?= $isEdit ? 'Modifier ' . htmlspecialchars($fournisseur['nom']) : 'Nouveau fournisseur' ?>
    </h2>

    <form method="POST" action="<?= BASE_URL ?>/fournisseur/<?= $action ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-building me-2"></i>Identité</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nom / Raison sociale <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required maxlength="200" value="<?= htmlspecialchars($fournisseur['nom'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SIRET</label>
                        <input type="text" name="siret" class="form-control" maxlength="14" pattern="[0-9]{14}" title="14 chiffres" value="<?= htmlspecialchars($fournisseur['siret'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" maxlength="255" value="<?= htmlspecialchars($fournisseur['adresse'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code postal</label>
                        <input type="text" name="code_postal" class="form-control" maxlength="10" value="<?= htmlspecialchars($fournisseur['code_postal'] ?? '') ?>">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Ville</label>
                        <input type="text" name="ville" class="form-control" maxlength="100" value="<?= htmlspecialchars($fournisseur['ville'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-tags me-2"></i>Services proposés</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-2">Cochez tous les modules auxquels ce fournisseur est rattaché. Il apparaîtra alors dans ces modules.</p>
                <div class="row g-2">
                    <?php foreach ($typesLabels as $k => $l): ?>
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="type_service[]" value="<?= $k ?>" id="type_<?= $k ?>"
                                   <?= in_array($k, $typesSelected) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type_<?= $k ?>"><?= $l ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact & paiement</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du contact</label>
                        <input type="text" name="contact_nom" class="form-control" maxlength="100" value="<?= htmlspecialchars($fournisseur['contact_nom'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" maxlength="20" value="<?= htmlspecialchars($fournisseur['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" maxlength="100" value="<?= htmlspecialchars($fournisseur['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control" maxlength="34" value="<?= htmlspecialchars($fournisseur['iban'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($fournisseur['notes'] ?? '') ?></textarea>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" value="1" id="fieldActif" <?= !empty($fournisseur['actif']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fieldActif">Fournisseur actif</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/fournisseur/<?= $isEdit ? 'show/' . (int)$fournisseur['id'] : 'index' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer' ?>
            </button>
        </div>
    </form>
</div>
