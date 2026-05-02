<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-elevator',       'text' => 'Ascenseurs',      'url' => BASE_URL . '/maintenance/ascenseurs'],
    ['icon' => 'fas fa-eye',            'text' => $entree['ascenseur_nom'], 'url' => BASE_URL . '/maintenance/ascenseurShow/' . (int)$entree['ascenseur_id']],
    ['icon' => 'fas fa-edit',           'text' => 'Modifier entrée #' . (int)$entree['id'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <h1 class="h3 mb-4">
        <i class="fas fa-edit text-secondary me-2"></i>
        Modifier l'entrée journal — <?= htmlspecialchars($entree['ascenseur_nom']) ?>
    </h1>

    <form method="POST" action="<?= BASE_URL ?>/maintenance/ascenseurEntreeEdit/<?= (int)$entree['id'] ?>" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Informations générales</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type d'entrée <span class="text-danger">*</span></label>
                            <select name="type_entree" id="ascTypeEntree" class="form-select" required>
                                <?php foreach (Ascenseur::TYPES_JOURNAL as $slug => $lbl): ?>
                                <option value="<?= $slug ?>" <?= $entree['type_entree'] === $slug ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date_event" class="form-control" required
                                   value="<?= !empty($entree['date_event']) ? date('Y-m-d\\TH:i', strtotime($entree['date_event'])) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Organisme</label>
                            <input type="text" name="organisme" class="form-control" value="<?= htmlspecialchars($entree['organisme'] ?? '') ?>" placeholder="Ex: APAVE, ascensoriste...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Technicien intervenant</label>
                            <input type="text" name="technicien_intervenant" class="form-control" value="<?= htmlspecialchars($entree['technicien_intervenant'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conformité -->
        <div class="col-12" id="ascBlocConformite">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-stamp me-2"></i>Conformité &amp; PV</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">N° PV</label>
                            <input type="text" name="numero_pv" class="form-control" value="<?= htmlspecialchars($entree['numero_pv'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Conformité</label>
                            <select name="conformite" class="form-select">
                                <option value="">—</option>
                                <?php foreach (['conforme'=>'Conforme','avec_reserves'=>'Avec réserves','non_conforme'=>'Non conforme'] as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($entree['conformite'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fichier PV (PDF/image)</label>
                            <?php if (!empty($entree['fichier_pv'])): ?>
                            <div class="mb-2">
                                <a href="<?= BASE_URL ?>/maintenance/ascenseurPv/<?= (int)$entree['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
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

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-calendar me-2"></i>Échéance &amp; coût</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Prochaine échéance</label>
                            <input type="date" name="prochaine_echeance" class="form-control"
                                   value="<?= !empty($entree['prochaine_echeance']) ? htmlspecialchars($entree['prochaine_echeance']) : '' ?>">
                            <small class="text-muted">Si vide ET type récurrent → recalculée automatiquement.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Coût (€)</label>
                            <input type="number" step="0.01" min="0" name="cout" class="form-control" value="<?= htmlspecialchars($entree['cout'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-comment me-2"></i>Observations</strong></div>
                <div class="card-body">
                    <textarea name="observations" class="form-control" rows="5"><?= htmlspecialchars($entree['observations'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-between gap-2">
            <a href="<?= BASE_URL ?>/maintenance/ascenseurShow/<?= (int)$entree['ascenseur_id'] ?>" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save me-1"></i>Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<script>
(function(){
    const sel = document.getElementById('ascTypeEntree');
    const blocConf = document.getElementById('ascBlocConformite');
    const dateInput = document.querySelector('input[name="date_event"]');
    const echInput = document.querySelector('input[name="prochaine_echeance"]');

    // Périodicités (jours) — alignées avec Ascenseur::PERIODICITES côté PHP
    const PERIODICITES = {
        'maintenance_preventive': 30,
        'visite_annuelle': 365,
        'controle_quinquennal': 1825
    };

    function refreshConformite() {
        blocConf.classList.toggle('d-none', !['visite_annuelle','controle_quinquennal','autre'].includes(sel.value));
    }

    function recalcEcheance() {
        const type = sel.value;
        const date = dateInput.value;
        if (!PERIODICITES[type] || !date) return;
        const d = new Date(date);
        d.setDate(d.getDate() + PERIODICITES[type]);
        // Format YYYY-MM-DD pour input type="date"
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        echInput.value = `${yyyy}-${mm}-${dd}`;
    }

    sel.addEventListener('change', () => { refreshConformite(); recalcEcheance(); });
    dateInput.addEventListener('change', recalcEcheance);

    refreshConformite();
})();
</script>
