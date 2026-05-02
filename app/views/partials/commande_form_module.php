<?php
/**
 * Partial formulaire de création de commande.
 * Attend : $modulePath, $moduleLabel, $moduleColor, $selectedResidence, $residences, $fournisseurs, $produits
 */
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-plus me-2 text-<?= htmlspecialchars($moduleColor) ?>"></i>Nouvelle commande <?= htmlspecialchars($moduleLabel) ?></h2>

    <?php if (!$selectedResidence): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET">
                <label class="form-label">Sélectionnez d'abord une résidence :</label>
                <select name="residence_id" class="form-select" onchange="this.form.submit()">
                    <option value="0">—</option>
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
    <?php elseif (empty($fournisseurs)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>Aucun fournisseur actif lié à cette résidence.
        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/fournisseurs?residence_id=<?= (int)$selectedResidence ?>" class="alert-link">Lier un fournisseur d'abord →</a>
    </div>
    <?php else: ?>

    <form method="POST" action="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes/store" id="formCommande">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations commande</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="fournisseur_id" id="selectFournisseur" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">★ = fournisseur préféré pour ce produit · ✓ = produit référencé chez ce fournisseur</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date commande</label>
                        <input type="date" name="date_commande" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Livraison prévue</label>
                        <input type="date" name="date_livraison_prevue" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut initial</label>
                        <select name="statut" class="form-select">
                            <option value="brouillon" selected>Brouillon</option>
                            <option value="envoyee">Envoyée</option>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" maxlength="500" placeholder="Conditions, référence devis, transport…">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Lignes de commande</h6>
                <button type="button" class="btn btn-sm btn-<?= htmlspecialchars($moduleColor) ?>" onclick="addLigne()"><i class="fas fa-plus me-1"></i>Ajouter une ligne</button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="lignesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:35%">Produit</th>
                            <th style="width:10%">Quantité</th>
                            <th style="width:12%">Prix unitaire HT</th>
                            <th style="width:10%">TVA %</th>
                            <th class="text-end">Total HT</th>
                            <th style="width:60px"></th>
                        </tr>
                    </thead>
                    <tbody id="lignesBody"></tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total HT :</strong></td>
                            <td class="text-end"><strong id="totalHt">0,00 €</strong></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end"><strong>TVA :</strong></td>
                            <td class="text-end"><strong id="totalTva">0,00 €</strong></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end fs-5"><strong>Total TTC :</strong></td>
                            <td class="text-end fs-5 text-<?= htmlspecialchars($moduleColor) ?>"><strong id="totalTtc">0,00 €</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <button type="submit" class="btn btn-<?= htmlspecialchars($moduleColor) ?>"><i class="fas fa-save me-1"></i>Créer la commande</button>
        </div>
    </form>

    <!-- Template ligne -->
    <template id="templateLigne">
        <tr>
            <td>
                <select name="lignes[__INDEX__][produit_id]" class="form-select form-select-sm" required onchange="onProduitChange(this)">
                    <option value="">— Produit —</option>
                    <?php foreach ($produits as $p):
                        $prix = $p['prix_unitaire'] ?? $p['prix_reference'] ?? 0;
                    ?>
                    <option value="<?= (int)$p['id'] ?>"
                            data-nom="<?= htmlspecialchars($p['nom']) ?>"
                            data-prix="<?= (float)$prix ?>"
                            data-unite="<?= htmlspecialchars($p['unite'] ?? '') ?>">
                        <?= htmlspecialchars($p['nom']) ?><?= !empty($p['marque']) ? ' — ' . htmlspecialchars($p['marque']) : '' ?> (<?= htmlspecialchars($p['unite'] ?? '') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="lignes[__INDEX__][designation]" class="ligne-designation">
            </td>
            <td>
                <input type="number" step="0.001" min="0.001" name="lignes[__INDEX__][quantite_commandee]" class="form-control form-control-sm ligne-qte" required oninput="recalcTotaux()">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="lignes[__INDEX__][prix_unitaire_ht]" class="form-control form-control-sm ligne-prix" required oninput="recalcTotaux()">
            </td>
            <td>
                <input type="number" step="0.01" min="0" max="100" name="lignes[__INDEX__][taux_tva]" class="form-control form-control-sm ligne-tva" value="20" oninput="recalcTotaux()">
            </td>
            <td class="text-end align-middle"><span class="ligne-total">0,00 €</span></td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLigne(this)"><i class="fas fa-times"></i></button>
            </td>
        </tr>
    </template>

    <script>
    let ligneIndex = 0;
    const BASE_URL = '<?= BASE_URL ?>';
    const MODULE_PATH = <?= json_encode($modulePath) ?>;
    // Map produit_id -> {prix, prefere, ref} pour le fournisseur actuellement sélectionné
    window.PRIX_NEGOCIES = {};

    function addLigne() {
        const tpl = document.getElementById('templateLigne').innerHTML.replace(/__INDEX__/g, ligneIndex++);
        const wrap = document.createElement('tbody');
        wrap.innerHTML = tpl;
        const tr = wrap.firstElementChild;
        document.getElementById('lignesBody').appendChild(tr);
        // Marquer la nouvelle ligne avec les prix négociés en cours
        applyPrixNegociesToSelect(tr.querySelector('select[name^="lignes["]'));
    }

    function removeLigne(btn) {
        btn.closest('tr').remove();
        recalcTotaux();
    }

    function onProduitChange(sel) {
        const opt = sel.options[sel.selectedIndex];
        const tr = sel.closest('tr');
        tr.querySelector('.ligne-designation').value = opt.dataset.nom || '';
        const prixInp = tr.querySelector('.ligne-prix');
        if (!prixInp.value) {
            // Prix négocié si dispo, sinon prix catalogue
            const prixNeg = parseFloat(opt.dataset.prixNegocie);
            const prixCat = parseFloat(opt.dataset.prix);
            const prix = (!isNaN(prixNeg) && prixNeg > 0) ? prixNeg : prixCat;
            if (!isNaN(prix) && prix > 0) prixInp.value = prix.toFixed(2);
        }
        recalcTotaux();
    }

    /**
     * Applique les marqueurs visuels (★/✓) et le data-prix-negocie sur un <select> produit
     * en fonction de window.PRIX_NEGOCIES.
     */
    function applyPrixNegociesToSelect(sel) {
        if (!sel) return;
        Array.from(sel.options).forEach(opt => {
            if (!opt.value) return;
            const pid = parseInt(opt.value);
            if (!opt.dataset.baseLabel) opt.dataset.baseLabel = opt.textContent;
            const info = window.PRIX_NEGOCIES[pid];
            if (info && info.prix !== null && info.prix > 0) {
                const marker = info.prefere ? '★' : '✓';
                opt.textContent = marker + ' ' + opt.dataset.baseLabel + ' — ' + info.prix.toFixed(2).replace('.', ',') + ' €';
                opt.dataset.prixNegocie = info.prix;
            } else {
                opt.textContent = opt.dataset.baseLabel;
                delete opt.dataset.prixNegocie;
            }
        });
    }

    function redrawAllProduitSelects() {
        document.querySelectorAll('#lignesBody select[name^="lignes["]').forEach(applyPrixNegociesToSelect);
    }

    async function loadFournisseurProduits() {
        const fid = parseInt(document.getElementById('selectFournisseur').value);
        if (!fid) {
            window.PRIX_NEGOCIES = {};
            redrawAllProduitSelects();
            return;
        }
        try {
            const r = await fetch(BASE_URL + '/fournisseur/produitsForCommande/' + fid + '?module=' + MODULE_PATH);
            const data = await r.json();
            window.PRIX_NEGOCIES = (data && data.success) ? data.produits : {};
        } catch (e) {
            window.PRIX_NEGOCIES = {};
        }
        redrawAllProduitSelects();
    }

    document.getElementById('selectFournisseur').addEventListener('change', loadFournisseurProduits);

    function recalcTotaux() {
        let totalHt = 0, totalTva = 0;
        document.querySelectorAll('#lignesBody tr').forEach(tr => {
            const q = parseFloat(tr.querySelector('.ligne-qte').value) || 0;
            const p = parseFloat(tr.querySelector('.ligne-prix').value) || 0;
            const t = parseFloat(tr.querySelector('.ligne-tva').value) || 0;
            const ht = q * p;
            const tva = ht * (t / 100);
            tr.querySelector('.ligne-total').textContent = ht.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            totalHt += ht;
            totalTva += tva;
        });
        const fmt = n => n.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
        document.getElementById('totalHt').textContent = fmt(totalHt);
        document.getElementById('totalTva').textContent = fmt(totalTva);
        document.getElementById('totalTtc').textContent = fmt(totalHt + totalTva);
    }

    addLigne();
    </script>
    <?php endif; ?>
</div>
