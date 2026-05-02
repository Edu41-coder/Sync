<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-truck',          'text' => 'Commandes',       'url' => BASE_URL . '/maintenance/commandes'],
    ['icon' => 'fas fa-plus',           'text' => 'Nouvelle',        'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <h1 class="h3 mb-4"><i class="fas fa-truck text-success me-2"></i>Nouvelle commande</h1>

    <form method="POST" action="<?= BASE_URL ?>/maintenance/commandeForm" id="formCommande">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Infos générales</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Résidence <span class="text-danger">*</span></label>
                        <select name="residence_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($residences as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="fournisseur_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date commande</label>
                        <input type="date" name="date_commande" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Livraison prévue</label>
                        <input type="date" name="date_livraison_prevue" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between">
                <strong><i class="fas fa-list me-2"></i>Lignes de commande</strong>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLigne"><i class="fas fa-plus me-1"></i>Ligne</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="tableLignes">
                        <thead class="table-light">
                            <tr><th>Produit</th><th>Désignation</th><th class="text-end">Quantité</th><th class="text-end">PU HT</th><th class="text-end">TVA %</th><th class="text-end">Total HT</th><th></th></tr>
                        </thead>
                        <tbody id="lignesBody">
                            <!-- 1 ligne par défaut JS -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr><td colspan="5" class="text-end"><strong>Total HT</strong></td><td class="text-end" id="totalHt">0,00 €</td><td></td></tr>
                            <tr><td colspan="5" class="text-end"><strong>TVA</strong></td><td class="text-end" id="totalTva">0,00 €</td><td></td></tr>
                            <tr class="table-success"><td colspan="5" class="text-end"><strong>Total TTC</strong></td><td class="text-end"><strong id="totalTtc">0,00 €</strong></td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between gap-2">
            <a href="<?= BASE_URL ?>/maintenance/commandes" class="btn btn-secondary">Annuler</a>
            <div>
                <button type="submit" name="statut" value="brouillon" class="btn btn-outline-secondary">Enregistrer en brouillon</button>
                <button type="submit" name="statut" value="envoyee" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i>Créer et envoyer</button>
            </div>
        </div>
    </form>
</div>

<script>
const PRODUITS = <?= json_encode(array_map(fn($p) => ['id'=>(int)$p['id'],'nom'=>$p['nom'],'unite'=>$p['unite'] ?? '','prix'=>(float)($p['prix_unitaire'] ?? 0)], $produits), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
let ligneIdx = 0;

function ajouterLigne() {
    const optsProduits = '<option value="">— Manuel —</option>' + PRODUITS.map(p => `<option value="${p.id}" data-prix="${p.prix}" data-unite="${p.unite}">${p.nom}</option>`).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="lignes[${ligneIdx}][produit_id]" class="form-select form-select-sm produit-select">${optsProduits}</select></td>
        <td><input type="text" name="lignes[${ligneIdx}][designation]" class="form-control form-control-sm designation" required></td>
        <td><input type="number" step="0.001" min="0" name="lignes[${ligneIdx}][quantite]" class="form-control form-control-sm text-end qte" required value="1"></td>
        <td><input type="number" step="0.01" min="0" name="lignes[${ligneIdx}][prix_unitaire_ht]" class="form-control form-control-sm text-end pu" required value="0"></td>
        <td><input type="number" step="0.01" min="0" name="lignes[${ligneIdx}][taux_tva]" class="form-control form-control-sm text-end tva" value="20"></td>
        <td class="text-end totalHt">0,00 €</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger removeLigne"><i class="fas fa-times"></i></button></td>
    `;
    document.getElementById('lignesBody').appendChild(tr);
    bindLigne(tr);
    ligneIdx++;
}

function bindLigne(tr) {
    const sel = tr.querySelector('.produit-select');
    const desig = tr.querySelector('.designation');
    const pu = tr.querySelector('.pu');
    sel.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (this.value) {
            desig.value = opt.text + (opt.dataset.unite ? ' (' + opt.dataset.unite + ')' : '');
            pu.value = opt.dataset.prix;
        }
        recalc();
    });
    ['input','change'].forEach(ev => {
        tr.querySelector('.qte').addEventListener(ev, recalc);
        tr.querySelector('.pu').addEventListener(ev, recalc);
        tr.querySelector('.tva').addEventListener(ev, recalc);
    });
    tr.querySelector('.removeLigne').addEventListener('click', function() {
        tr.remove();
        recalc();
    });
}

function recalc() {
    let ht = 0, tva = 0;
    document.querySelectorAll('#lignesBody tr').forEach(tr => {
        const q = parseFloat(tr.querySelector('.qte').value) || 0;
        const p = parseFloat(tr.querySelector('.pu').value) || 0;
        const t = parseFloat(tr.querySelector('.tva').value) || 0;
        const ligneHt = q * p;
        ht += ligneHt;
        tva += ligneHt * (t / 100);
        tr.querySelector('.totalHt').textContent = ligneHt.toFixed(2).replace('.', ',') + ' €';
    });
    document.getElementById('totalHt').textContent = ht.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('totalTva').textContent = tva.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('totalTtc').textContent = (ht + tva).toFixed(2).replace('.', ',') + ' €';
}

document.getElementById('btnAddLigne').addEventListener('click', ajouterLigne);
ajouterLigne(); // 1 ligne par défaut
</script>
