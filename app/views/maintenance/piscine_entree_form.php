<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-swimming-pool',  'text' => 'Piscine',         'url' => BASE_URL . '/maintenance/piscine?residence_id=' . (int)$entree['residence_id']],
    ['icon' => 'fas fa-edit',           'text' => 'Modifier entrée #' . (int)$entree['id'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <h1 class="h3 mb-4">
        <i class="fas fa-edit text-info me-2"></i>
        Modifier l'entrée journal — <?= htmlspecialchars($entree['residence_nom']) ?>
    </h1>

    <form method="POST" action="<?= BASE_URL ?>/maintenance/piscineEntreeEdit/<?= (int)$entree['id'] ?>" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Informations générales</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type d'entrée <span class="text-danger">*</span></label>
                            <select name="type_entree" id="piscineTypeEntree" class="form-select" required>
                                <?php foreach (Piscine::TYPE_LABELS as $slug => $lbl): ?>
                                <option value="<?= $slug ?>" <?= $entree['type_entree'] === $slug ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date et heure <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date_mesure" class="form-control" required
                                   value="<?= !empty($entree['date_mesure']) ? date('Y-m-d\\TH:i', strtotime($entree['date_mesure'])) : '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mesures chimiques -->
        <div class="col-12" id="piscineBlocAnalyse">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-vial me-2"></i>Mesures chimiques</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">pH</label>
                            <input type="number" step="0.1" min="0" max="14" name="ph" class="form-control" value="<?= htmlspecialchars($entree['ph'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Chlore libre (mg/L)</label>
                            <input type="number" step="0.01" min="0" name="chlore_libre_mg_l" class="form-control" value="<?= htmlspecialchars($entree['chlore_libre_mg_l'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Chlore total (mg/L)</label>
                            <input type="number" step="0.01" min="0" name="chlore_total_mg_l" class="form-control" value="<?= htmlspecialchars($entree['chlore_total_mg_l'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Température (°C)</label>
                            <input type="number" step="0.1" name="temperature" class="form-control" value="<?= htmlspecialchars($entree['temperature'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Alcalinité TAC (mg/L)</label>
                            <input type="number" min="0" name="alcalinite_mg_l" class="form-control" value="<?= htmlspecialchars($entree['alcalinite_mg_l'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Stabilisant (mg/L)</label>
                            <input type="number" min="0" name="stabilisant_mg_l" class="form-control" value="<?= htmlspecialchars($entree['stabilisant_mg_l'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Produit utilisé</label>
                            <input type="text" name="produit_utilise" class="form-control" value="<?= htmlspecialchars($entree['produit_utilise'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantité (kg)</label>
                            <input type="number" step="0.01" min="0" name="quantite_produit_kg" class="form-control" value="<?= htmlspecialchars($entree['quantite_produit_kg'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contrôle ARS -->
        <div class="col-12" id="piscineBlocArs">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-stamp me-2"></i>Contrôle ARS</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">N° PV</label>
                            <input type="text" name="numero_pv" class="form-control" value="<?= htmlspecialchars($entree['numero_pv'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Conformité</label>
                            <select name="conformite_ars" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['conforme'=>'Conforme','avertissement'=>'Avertissement','non_conforme'=>'Non conforme'] as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($entree['conformite_ars'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fichier PV (PDF/image)</label>
                            <?php if (!empty($entree['fichier_pv'])): ?>
                            <div class="mb-2">
                                <a href="<?= BASE_URL ?>/maintenance/piscinePv/<?= (int)$entree['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-file-pdf me-1"></i>Voir le PV actuel
                                </a>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="fichier_pv" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Laissez vide pour conserver l'actuel.</small>
                            <?php if (!empty($entree['fichier_pv'])): ?>
                            <div class="form-check mt-2">
                                <input type="checkbox" name="supprimer_pv" id="supprimer_pv" value="1" class="form-check-input">
                                <label for="supprimer_pv" class="form-check-label small text-danger">Supprimer le PV existant</label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-comment me-2"></i>Notes</strong></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($entree['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-between gap-2">
            <a href="<?= BASE_URL ?>/maintenance/piscine?residence_id=<?= (int)$entree['residence_id'] ?>" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-info text-white">
                <i class="fas fa-save me-1"></i>Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
(function(){
    const sel = document.getElementById('piscineTypeEntree');
    const blocAnalyse = document.getElementById('piscineBlocAnalyse');
    const blocArs = document.getElementById('piscineBlocArs');
    function refresh() {
        const v = sel.value;
        blocArs.classList.toggle('d-none', v !== 'controle_ars');
        // Bloc analyse caché pour hivernage / mise en service (pas de mesures)
        blocAnalyse.classList.toggle('d-none', v === 'hivernage' || v === 'mise_en_service');
    }
    sel.addEventListener('change', refresh);
    refresh();
})();
</script>
