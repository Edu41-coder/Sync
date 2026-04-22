<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => BASE_URL . '/jardinage/commandes'],
    ['icon' => 'fas fa-plus', 'text' => 'Nouvelle', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-plus me-2 text-success"></i>Nouvelle commande</h2>

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
        <a href="<?= BASE_URL ?>/jardinage/fournisseurs?residence_id=<?= (int)$selectedResidence ?>" class="alert-link">Lier un fournisseur d'abord →</a>
    </div>
    <?php else: ?>

    <form method="POST" action="<?= BASE_URL ?>/jardinage/commandes/store" id="formCommande">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations commande</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="fournisseur_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                <button type="button" class="btn btn-sm btn-success" onclick="addLigne()"><i class="fas fa-plus me-1"></i>Ajouter une ligne</button>
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
                            <td class="text-end fs-5 text-success"><strong id="totalTtc">0,00 €</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/jardinage/commandes" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Créer la commande</button>
        </div>
    </form>

    <!-- Template ligne -->
    <template id="templateLigne">
        <tr>
            <td>
                <select name="lignes[__INDEX__][produit_id]" class="form-select form-select-sm" required onchange="onProduitChange(this)">
                    <option value="">— Produit —</option>
                    <?php foreach ($produits as $p): ?>
                    <option value="<?= (int)$p['id'] ?>"
                            data-nom="<?= htmlspecialchars($p['nom']) ?>"
                            data-prix="<?= (float)($p['prix_unitaire'] ?? 0) ?>"
                            data-unite="<?= htmlspecialchars($p['unite']) ?>">
                        <?= htmlspecialchars($p['nom']) ?> <?= $p['marque'] ? '— ' . htmlspecialchars($p['marque']) : '' ?> (<?= htmlspecialchars($p['unite']) ?>)
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

    function addLigne() {
        const tpl = document.getElementById('templateLigne').innerHTML.replace(/__INDEX__/g, ligneIndex++);
        const wrap = document.createElement('tbody');
        wrap.innerHTML = tpl;
        document.getElementById('lignesBody').appendChild(wrap.firstElementChild);
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
        if (!prixInp.value && opt.dataset.prix && parseFloat(opt.dataset.prix) > 0) {
            prixInp.value = parseFloat(opt.dataset.prix).toFixed(2);
        }
        recalcTotaux();
    }

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

    // Ajouter une première ligne au chargement
    addLigne();
    </script>
    <?php endif; ?>
</div>
