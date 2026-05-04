<?php
/**
 * Partial : champs du formulaire de sinistre (create + edit)
 *
 * Variables attendues :
 *   $sinistre        ?array  données existantes (null en création)
 *   $residences      array   liste des résidences accessibles
 *   $lotsByResidence array   lots groupés par residence_id
 *   $userRole        string
 */

$typeLabels = [
    'degat_eaux' => 'Dégât des eaux', 'incendie' => 'Incendie', 'vol_cambriolage' => 'Vol/Cambriolage',
    'bris_glace' => 'Bris de glace', 'catastrophe_naturelle' => 'Catastrophe naturelle',
    'vandalisme' => 'Vandalisme', 'chute_resident' => 'Chute résident',
    'panne_equipement' => 'Panne équipement', 'autre' => 'Autre',
];
$piecesLabels = [
    'parking' => 'Parking', 'ascenseur' => 'Ascenseur', 'hall' => 'Hall', 'couloir' => 'Couloir',
    'cage_escalier' => 'Cage d\'escalier', 'jardin' => 'Jardin', 'salle_commune' => 'Salle commune',
    'local_technique' => 'Local technique', 'toiture' => 'Toiture', 'facade' => 'Façade', 'autre' => 'Autre',
];
$graviteLabels = ['mineur' => 'Mineur', 'modere' => 'Modéré', 'majeur' => 'Majeur', 'catastrophe' => 'Catastrophe'];

$isEdit       = !empty($sinistre);
$isResident   = $userRole === 'locataire_permanent';
$residenceVal = $sinistre['residence_id'] ?? '';
$lotVal       = $sinistre['lot_id']       ?? '';
$pieceVal     = $sinistre['lieu_partie_commune'] ?? '';
$lieuMode     = $isEdit ? (!empty($sinistre['lot_id']) ? 'lot' : 'partie_commune') : 'lot';
?>

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label">Résidence *</label>
        <select name="residence_id" id="residence_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
            <option value="">— Sélectionner —</option>
            <?php foreach ($residences as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= (int)$residenceVal === (int)$r['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['nom']) ?> — <?= htmlspecialchars($r['ville']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($isEdit): ?>
            <input type="hidden" name="residence_id" value="<?= (int)$residenceVal ?>">
            <small class="text-muted">La résidence ne peut pas être modifiée après création.</small>
        <?php endif; ?>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Lieu *</label>
        <?php if ($isResident): ?>
            <input type="hidden" name="lieu_mode" value="lot">
            <input type="hidden" id="lieu_mode_lot" value="1">
        <?php else: ?>
            <div class="btn-group d-flex" role="group">
                <input type="radio" class="btn-check" name="lieu_mode" id="lieu_mode_lot" value="lot" <?= $lieuMode === 'lot' ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="lieu_mode_lot"><i class="fas fa-door-open me-1"></i>Lot</label>
                <input type="radio" class="btn-check" name="lieu_mode" id="lieu_mode_pc" value="partie_commune" <?= $lieuMode === 'partie_commune' ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="lieu_mode_pc"><i class="fas fa-building me-1"></i>Partie commune</label>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-md-6" id="zone_lot">
        <label class="form-label">Lot</label>
        <select name="lot_id" id="lot_id" class="form-select">
            <option value="">— Sélectionner un lot —</option>
        </select>
        <small class="text-muted">Choisissez d'abord la résidence ci-dessus.</small>
    </div>

    <div class="col-12 col-md-6" id="zone_partie_commune" style="display:none">
        <label class="form-label">Partie commune</label>
        <select name="lieu_partie_commune" id="lieu_partie_commune" class="form-select">
            <option value="">— Sélectionner —</option>
            <?php foreach ($piecesLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $pieceVal === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Précision sur le lieu <small class="text-muted">(optionnel)</small></label>
        <input type="text" name="description_lieu" class="form-control" maxlength="255"
               value="<?= htmlspecialchars($sinistre['description_lieu'] ?? '') ?>"
               placeholder="Ex: cuisine côté évier / ascenseur côté A">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Type de sinistre *</label>
        <select name="type_sinistre" class="form-select" required>
            <option value="">— Sélectionner —</option>
            <?php foreach ($typeLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($sinistre['type_sinistre'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Gravité</label>
        <select name="gravite" class="form-select">
            <?php foreach ($graviteLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($sinistre['gravite'] ?? 'modere') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Date de survenue *</label>
        <input type="datetime-local" name="date_survenue" class="form-control" required
               value="<?= !empty($sinistre['date_survenue']) ? date('Y-m-d\TH:i', strtotime($sinistre['date_survenue'])) : '' ?>">
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Date de constat <small class="text-muted">(optionnel)</small></label>
        <input type="datetime-local" name="date_constat" class="form-control"
               value="<?= !empty($sinistre['date_constat']) ? date('Y-m-d\TH:i', strtotime($sinistre['date_constat'])) : '' ?>">
    </div>

    <div class="col-12">
        <label class="form-label">Titre court *</label>
        <input type="text" name="titre" class="form-control" required maxlength="150"
               value="<?= htmlspecialchars($sinistre['titre'] ?? '') ?>"
               placeholder="Ex: Dégât des eaux dans la chambre 12">
    </div>

    <div class="col-12">
        <label class="form-label">Description détaillée *</label>
        <textarea name="description" class="form-control" rows="4" required placeholder="Décrivez ce qui s'est passé, les dégâts constatés, l'origine si connue..."><?= htmlspecialchars($sinistre['description'] ?? '') ?></textarea>
    </div>

    <!-- Section assurance -->
    <div class="col-12">
        <hr>
        <h6 class="text-muted mb-3"><i class="fas fa-file-contract me-2"></i>Informations assurance <small>(optionnelles à la déclaration)</small></h6>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label">Assureur</label>
        <input type="text" name="assureur_nom" class="form-control" maxlength="150"
               value="<?= htmlspecialchars($sinistre['assureur_nom'] ?? '') ?>" placeholder="Ex: Allianz, MAIF...">
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">N° contrat</label>
        <input type="text" name="numero_contrat_assurance" class="form-control" maxlength="100"
               value="<?= htmlspecialchars($sinistre['numero_contrat_assurance'] ?? '') ?>">
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">N° dossier sinistre</label>
        <input type="text" name="numero_dossier_sinistre" class="form-control" maxlength="100"
               value="<?= htmlspecialchars($sinistre['numero_dossier_sinistre'] ?? '') ?>" placeholder="Attribué par l'assureur">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label">Franchise (€)</label>
        <input type="number" step="0.01" min="0" name="franchise" class="form-control"
               value="<?= htmlspecialchars($sinistre['franchise'] ?? '') ?>">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label">Montant estimé des dégâts (€)</label>
        <input type="number" step="0.01" min="0" name="montant_estime" class="form-control"
               value="<?= htmlspecialchars($sinistre['montant_estime'] ?? '') ?>">
    </div>

    <div class="col-12">
        <label class="form-label">Notes internes <small class="text-muted">(non visibles par le résident/propriétaire)</small></label>
        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($sinistre['notes'] ?? '') ?></textarea>
    </div>
</div>

<script>
(function() {
    const lotsByResidence = <?= json_encode($lotsByResidence ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const initialLot = <?= json_encode((int)($lotVal ?? 0)) ?>;
    const isResident = <?= $isResident ? 'true' : 'false' ?>;

    const selResidence = document.getElementById('residence_id');
    const selLot       = document.getElementById('lot_id');
    const selPC        = document.getElementById('lieu_partie_commune');
    const zoneLot      = document.getElementById('zone_lot');
    const zonePC       = document.getElementById('zone_partie_commune');
    const radioLot     = document.getElementById('lieu_mode_lot');
    const radioPC      = document.getElementById('lieu_mode_pc');

    function refreshLots() {
        if (!selResidence || !selLot) return;
        const rid = parseInt(selResidence.value || '0', 10);
        const lots = lotsByResidence[rid] || [];
        const previousValue = selLot.value || initialLot;
        selLot.innerHTML = '<option value="">— Sélectionner un lot —</option>';
        lots.forEach(function(lot) {
            const opt = document.createElement('option');
            opt.value = lot.id;
            opt.textContent = 'Lot ' + lot.numero_lot + ' (' + lot.type + ')';
            if (parseInt(previousValue, 10) === lot.id) opt.selected = true;
            selLot.appendChild(opt);
        });
    }

    function refreshLieuMode() {
        const isLot = radioLot ? radioLot.checked : true;
        if (zoneLot) zoneLot.style.display = isLot ? '' : 'none';
        if (zonePC)  zonePC.style.display  = isLot ? 'none' : '';
        // Reset hidden side to avoid sending both
        if (isLot) {
            if (selPC) selPC.value = '';
        } else {
            if (selLot) selLot.value = '';
        }
    }

    if (selResidence) selResidence.addEventListener('change', refreshLots);
    if (radioLot) radioLot.addEventListener('change', refreshLieuMode);
    if (radioPC)  radioPC.addEventListener('change', refreshLieuMode);

    refreshLots();
    refreshLieuMode();
})();
</script>
