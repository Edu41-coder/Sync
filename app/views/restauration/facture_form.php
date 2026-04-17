<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-file-invoice', 'text' => 'Factures', 'url' => BASE_URL . '/restauration/factures'],
    ['icon' => 'fas fa-plus', 'text' => 'Nouvelle', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <form method="POST" action="<?= BASE_URL ?>/restauration/factures/store" id="factureForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row g-4">
            <!-- Info facture -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark"><h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Nouvelle Facture</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Résidence</label>
                            <select name="residence_id" class="form-select" id="selResidence" onchange="window.location='?residence_id='+this.value">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($residences as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type client <span class="text-danger">*</span></label>
                            <select name="type_client" class="form-select" id="selTypeClient" required>
                                <option value="resident">Résident</option>
                                <option value="hote">Hôte temporaire</option>
                                <option value="passage">Client de passage</option>
                            </select>
                        </div>
                        <div class="mb-3" id="divClientSelect">
                            <label class="form-label">Client</label>
                            <select name="client_id" class="form-select" id="selClient">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($residents as $r): ?>
                                <option value="<?= $r['id'] ?>" data-type="resident"><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?> — Lot <?= $r['numero_lot'] ?></option>
                                <?php endforeach; ?>
                                <?php foreach ($hotes as $h): ?>
                                <option value="<?= $h['id'] ?>" data-type="hote" class="d-none"><?= htmlspecialchars($h['prenom'] . ' ' . $h['nom']) ?> (hôte)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="divNomPassage">
                            <label class="form-label">Nom du client</label>
                            <input type="text" name="nom_passage" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">TVA (%)</label>
                            <input type="number" name="taux_tva" class="form-control" value="10" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mode de paiement</label>
                            <select name="mode_paiement" class="form-select">
                                <option value="">Non défini</option>
                                <option value="cb">Carte bancaire</option>
                                <option value="especes">Espèces</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement</option>
                                <option value="prelevement">Prélèvement</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="emise">Émise</option>
                                <option value="brouillon">Brouillon</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lignes de facture -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lignes de facture</h5>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="addLigne()"><i class="fas fa-plus me-1"></i>Ajouter ligne</button>
                    </div>
                    <div class="card-body">
                        <!-- Repas non facturés -->
                        <?php if (!empty($repasNonFactures)): ?>
                        <div class="alert alert-info small">
                            <strong><i class="fas fa-info-circle me-1"></i><?= count($repasNonFactures) ?> repas non facturés</strong> — cochez pour les inclure :
                            <div class="mt-2">
                            <?php foreach ($repasNonFactures as $rnf): ?>
                                <div class="form-check">
                                    <input class="form-check-input repas-check" type="checkbox" value="<?= $rnf['id'] ?>"
                                           data-designation="<?= htmlspecialchars(str_replace('_',' ',$rnf['type_service']) . ' du ' . date('d/m', strtotime($rnf['date_service']))) ?>"
                                           data-montant="<?= $rnf['montant'] ?>" data-type="<?= $rnf['type_service'] ?>">
                                    <label class="form-check-label">
                                        <?= date('d/m', strtotime($rnf['date_service'])) ?> — <?= str_replace('_',' ',$rnf['type_service']) ?> — <?= number_format($rnf['montant'],2,',',' ') ?> &euro;
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-info mt-2" onclick="importRepas()"><i class="fas fa-download me-1"></i>Importer sélection</button>
                        </div>
                        <?php endif; ?>

                        <table class="table table-sm" id="lignesTable">
                            <thead><tr><th>Désignation</th><th style="width:120px">Type</th><th style="width:60px">Qté</th><th style="width:100px">Prix unit.</th><th style="width:100px">Total</th><th style="width:40px"></th></tr></thead>
                            <tbody id="lignesBody">
                                <!-- Lignes dynamiques -->
                            </tbody>
                            <tfoot>
                                <tr><td colspan="4" class="text-end"><strong>Total HT</strong></td><td class="text-end" id="totalHt">0,00 &euro;</td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/restauration/factures" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Annuler</a>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Créer la facture</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let ligneIndex = 0;

function addLigne(designation = '', type = 'menu_complet', qte = 1, prix = 0, serviceRepasId = '') {
    const tbody = document.getElementById('lignesBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="lignes[${ligneIndex}][designation]" class="form-control form-control-sm" value="${designation}" required>
            <input type="hidden" name="lignes[${ligneIndex}][service_repas_id]" value="${serviceRepasId}"></td>
        <td><select name="lignes[${ligneIndex}][type_ligne]" class="form-select form-select-sm">
            <option value="menu_complet" ${type==='menu_complet'?'selected':''}>Menu complet</option>
            <option value="entree" ${type==='entree'?'selected':''}>Entrée</option>
            <option value="plat" ${type==='plat'?'selected':''}>Plat</option>
            <option value="dessert" ${type==='dessert'?'selected':''}>Dessert</option>
            <option value="boisson" ${type==='boisson'?'selected':''}>Boisson</option>
            <option value="supplement" ${type==='supplement'?'selected':''}>Supplément</option>
            <option value="autre" ${type==='autre'?'selected':''}>Autre</option>
        </select></td>
        <td><input type="number" name="lignes[${ligneIndex}][quantite]" class="form-control form-control-sm ligne-qte" value="${qte}" min="1" onchange="calcTotal()"></td>
        <td><input type="number" name="lignes[${ligneIndex}][prix_unitaire]" class="form-control form-control-sm ligne-prix" value="${prix}" step="0.01" min="0" onchange="calcTotal()"></td>
        <td class="text-end ligne-total pt-2">${(qte*prix).toFixed(2)} &euro;</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();calcTotal()"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(tr);
    ligneIndex++;
    calcTotal();
}

function calcTotal() {
    let total = 0;
    document.querySelectorAll('#lignesBody tr').forEach(tr => {
        const qte = parseFloat(tr.querySelector('.ligne-qte')?.value || 0);
        const prix = parseFloat(tr.querySelector('.ligne-prix')?.value || 0);
        const sub = qte * prix;
        tr.querySelector('.ligne-total').textContent = sub.toFixed(2) + ' €';
        total += sub;
    });
    document.getElementById('totalHt').textContent = total.toFixed(2).replace('.', ',') + ' €';
}

function importRepas() {
    document.querySelectorAll('.repas-check:checked').forEach(cb => {
        addLigne(cb.dataset.designation, 'menu_complet', 1, parseFloat(cb.dataset.montant), cb.value);
        cb.checked = false;
    });
}

// Filtrer les clients selon le type
document.getElementById('selTypeClient')?.addEventListener('change', function() {
    const type = this.value;
    document.getElementById('divClientSelect').classList.toggle('d-none', type === 'passage');
    document.getElementById('divNomPassage').classList.toggle('d-none', type !== 'passage');
    document.querySelectorAll('#selClient option[data-type]').forEach(opt => {
        opt.classList.toggle('d-none', opt.dataset.type !== type);
    });
    document.getElementById('selClient').value = '';
});

// Ajouter une ligne vide par défaut
addLigne();
</script>
