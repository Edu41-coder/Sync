<?php
/**
 * Widget multi-select fournisseurs pour un produit.
 *
 * Attend en scope :
 *  - $produitFournisseurs : array des fournisseurs actuellement liés (via Fournisseur::getFournisseursDuProduit)
 *                           [] pour un nouveau produit
 *  - $fournisseursDisponibles : array de tous les fournisseurs actifs filtrés par type_service du module
 *  - $widgetId (optionnel) : préfixe ID unique si plusieurs widgets dans la même page (défaut: 'pfwidget')
 *
 * Génère des champs POST au format :
 *   fournisseurs[<fournisseur_id>][id]      → id du fournisseur (redondant pour parsing)
 *   fournisseurs[<fournisseur_id>][prix]    → prix unitaire spécifique (optionnel)
 *   fournisseurs[<fournisseur_id>][ref]     → référence côté fournisseur (optionnel)
 *   fournisseurs[<fournisseur_id>][notes]   → notes (optionnel)
 *   fournisseur_prefere_id                  → id du fournisseur préféré (radio)
 */
$pfWidgetId = $widgetId ?? 'pfwidget';
$pfExistants = $produitFournisseurs ?? [];
$pfDisponibles = $fournisseursDisponibles ?? [];
// Map id → nom pour le rendu et le JS
$pfDispoMap = [];
foreach ($pfDisponibles as $f) {
    $pfDispoMap[(int)$f['id']] = [
        'id'   => (int)$f['id'],
        'nom'  => $f['nom'],
        'type' => $f['type_service'] ?? '',
    ];
}
// Exclure les déjà présents du dropdown "Ajouter"
$pfIdsExistants = array_map('intval', array_column($pfExistants, 'id'));
$pfAjoutables = array_values(array_filter($pfDisponibles, fn($f) => !in_array((int)$f['id'], $pfIdsExistants, true)));
?>

<div class="border rounded p-2 bg-light" id="<?= htmlspecialchars($pfWidgetId) ?>-wrap">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Cochez la case <strong>⭐ Préféré</strong> pour désigner le fournisseur principal (un seul). Prix/référence optionnels.</small>
        <?php if (!empty($pfAjoutables)): ?>
        <div class="input-group input-group-sm" style="max-width:340px">
            <select class="form-select form-select-sm" id="<?= $pfWidgetId ?>-select">
                <option value="">— Ajouter un fournisseur —</option>
                <?php foreach ($pfAjoutables as $f): ?>
                <option value="<?= (int)$f['id'] ?>"
                        data-nom="<?= htmlspecialchars($f['nom']) ?>"
                        data-type="<?= htmlspecialchars($f['type_service'] ?? '') ?>">
                    <?= htmlspecialchars($f['nom']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-sm btn-success" onclick="<?= $pfWidgetId ?>_add()"><i class="fas fa-plus"></i></button>
        </div>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle" id="<?= $pfWidgetId ?>-table">
            <thead class="table-light">
                <tr>
                    <th style="width:40px" class="text-center">⭐</th>
                    <th>Fournisseur</th>
                    <th style="width:130px">Prix unitaire HT</th>
                    <th style="width:140px">Réf. fournisseur</th>
                    <th>Notes</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody id="<?= $pfWidgetId ?>-body">
                <?php if (empty($pfExistants)): ?>
                <tr id="<?= $pfWidgetId ?>-empty"><td colspan="6" class="text-center text-muted small py-3">Aucun fournisseur rattaché. Ajoutez-en via le sélecteur ci-dessus.</td></tr>
                <?php else: ?>
                    <?php foreach ($pfExistants as $pf): $fid = (int)$pf['id']; ?>
                    <tr data-fid="<?= $fid ?>">
                        <td class="text-center">
                            <input type="radio" name="fournisseur_prefere_id" value="<?= $fid ?>"
                                   class="form-check-input" <?= !empty($pf['fournisseur_prefere']) ? 'checked' : '' ?>>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($pf['nom']) ?></strong>
                            <input type="hidden" name="fournisseurs[<?= $fid ?>][id]" value="<?= $fid ?>">
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0"
                                       name="fournisseurs[<?= $fid ?>][prix]"
                                       class="form-control"
                                       value="<?= $pf['prix_unitaire_specifique'] !== null ? htmlspecialchars((string)$pf['prix_unitaire_specifique']) : '' ?>"
                                       placeholder="—">
                                <span class="input-group-text">€</span>
                            </div>
                        </td>
                        <td>
                            <input type="text" maxlength="100"
                                   name="fournisseurs[<?= $fid ?>][ref]"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($pf['reference_fournisseur'] ?? '') ?>"
                                   placeholder="Code article">
                        </td>
                        <td>
                            <input type="text"
                                   name="fournisseurs[<?= $fid ?>][notes]"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($pf['pivot_notes'] ?? '') ?>"
                                   placeholder="—">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="<?= $pfWidgetId ?>_remove(this)" title="Détacher"><i class="fas fa-times"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    const widgetId = <?= json_encode($pfWidgetId) ?>;
    const select = document.getElementById(widgetId + '-select');
    const tbody = document.getElementById(widgetId + '-body');

    window[widgetId + '_add'] = function() {
        if (!select) return;
        const fid = select.value;
        if (!fid) return;
        const opt = select.options[select.selectedIndex];
        const nom = opt.dataset.nom;

        // Retirer le placeholder "vide"
        const emptyRow = document.getElementById(widgetId + '-empty');
        if (emptyRow) emptyRow.remove();

        // Si aucun préféré défini, cocher celui-ci par défaut
        const hasPrefere = tbody.querySelector('input[name="fournisseur_prefere_id"]:checked');

        const tr = document.createElement('tr');
        tr.dataset.fid = fid;
        tr.innerHTML = `
            <td class="text-center">
                <input type="radio" name="fournisseur_prefere_id" value="${fid}"
                       class="form-check-input" ${hasPrefere ? '' : 'checked'}>
            </td>
            <td>
                <strong>${escapeHtml(nom)}</strong>
                <input type="hidden" name="fournisseurs[${fid}][id]" value="${fid}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" name="fournisseurs[${fid}][prix]" class="form-control" placeholder="—">
                    <span class="input-group-text">€</span>
                </div>
            </td>
            <td>
                <input type="text" maxlength="100" name="fournisseurs[${fid}][ref]" class="form-control form-control-sm" placeholder="Code article">
            </td>
            <td>
                <input type="text" name="fournisseurs[${fid}][notes]" class="form-control form-control-sm" placeholder="—">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="${widgetId}_remove(this)" title="Détacher"><i class="fas fa-times"></i></button>
            </td>`;
        tbody.appendChild(tr);

        // Retirer cette option du dropdown
        opt.remove();
        select.value = '';
    };

    window[widgetId + '_remove'] = function(btn) {
        const tr = btn.closest('tr');
        const fid = tr.dataset.fid;
        const wasPrefere = tr.querySelector('input[type=radio]').checked;
        // Re-ajouter dans le dropdown (utilise le nom du <strong>)
        const nom = tr.querySelector('strong').textContent;
        if (select) {
            const opt = document.createElement('option');
            opt.value = fid;
            opt.textContent = nom;
            opt.dataset.nom = nom;
            select.appendChild(opt);
        }
        tr.remove();

        // Si on vient de supprimer le préféré, le premier restant devient préféré
        if (wasPrefere) {
            const first = tbody.querySelector('input[type=radio]');
            if (first) first.checked = true;
        }

        // Afficher le placeholder si vide
        if (!tbody.querySelector('tr')) {
            const emptyTr = document.createElement('tr');
            emptyTr.id = widgetId + '-empty';
            emptyTr.innerHTML = '<td colspan="6" class="text-center text-muted small py-3">Aucun fournisseur rattaché. Ajoutez-en via le sélecteur ci-dessus.</td>';
            tbody.appendChild(emptyTr);
        }
    };

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
})();
</script>
