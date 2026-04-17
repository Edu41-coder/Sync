<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => BASE_URL . '/restauration/commandes'],
    ['icon' => 'fas fa-plus', 'text' => 'Nouvelle', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <form method="POST" action="<?= BASE_URL ?>/restauration/commandes/store" id="commandeForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-truck me-2"></i>Nouvelle Commande</h5></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <select name="residence_id" class="form-select" required>
                                <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="mb-3"><label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                            <select name="fournisseur_id" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($fournisseurs as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="mb-3"><label class="form-label">Date commande</label><input type="date" name="date_commande" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                        <div class="mb-3"><label class="form-label">Livraison prévue</label><input type="date" name="date_livraison_prevue" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Statut</label>
                            <select name="statut" class="form-select"><option value="brouillon">Brouillon</option><option value="envoyee">Envoyée</option></select></div>
                        <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Produits à commander</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLigne()"><i class="fas fa-plus me-1"></i>Ajouter</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead><tr><th>Produit</th><th style="width:80px">Qté</th><th style="width:100px">Prix unit. HT</th><th style="width:80px">TVA %</th><th style="width:100px">Total HT</th><th style="width:40px"></th></tr></thead>
                            <tbody id="lignesBody"></tbody>
                            <tfoot><tr><td colspan="4" class="text-end"><strong>Total HT</strong></td><td class="text-end" id="totalHt">0,00 &euro;</td><td></td></tr></tfoot>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/restauration/commandes" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Annuler</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Créer la commande</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const produits = <?= json_encode($produits) ?>;
let ligneIdx = 0;

function addLigne() {
    const tbody = document.getElementById('lignesBody');
    let opts = '<option value="">-- Produit --</option>';
    produits.forEach(p => { opts += `<option value="${p.id}" data-prix="${p.prix_reference || 0}" data-unite="${p.unite}">${p.nom} (${p.unite})</option>`; });

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="lignes[${ligneIdx}][produit_id]" class="form-select form-select-sm sel-produit" required>${opts}</select></td>
        <td><input type="number" name="lignes[${ligneIdx}][quantite_commandee]" class="form-control form-control-sm ligne-qte" step="0.1" min="0.1" required onchange="calcTotal()"></td>
        <td><input type="number" name="lignes[${ligneIdx}][prix_unitaire_ht]" class="form-control form-control-sm ligne-prix" step="0.01" min="0" onchange="calcTotal()"></td>
        <td><input type="number" name="lignes[${ligneIdx}][taux_tva]" class="form-control form-control-sm" value="5.5" step="0.1"></td>
        <td class="text-end pt-2 ligne-total">0,00 &euro;</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();calcTotal()"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);

    // Auto-fill prix when product selected
    tr.querySelector('.sel-produit').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const prixInput = tr.querySelector('.ligne-prix');
        if (opt.dataset.prix) prixInput.value = opt.dataset.prix;
        calcTotal();
    });

    ligneIdx++;
}

function calcTotal() {
    let total = 0;
    document.querySelectorAll('#lignesBody tr').forEach(tr => {
        const qte = parseFloat(tr.querySelector('.ligne-qte')?.value || 0);
        const prix = parseFloat(tr.querySelector('.ligne-prix')?.value || 0);
        const sub = qte * prix;
        tr.querySelector('.ligne-total').textContent = sub.toFixed(2).replace('.',',') + ' €';
        total += sub;
    });
    document.getElementById('totalHt').textContent = total.toFixed(2).replace('.',',') + ' €';
}

addLigne(); // Première ligne par défaut
</script>
