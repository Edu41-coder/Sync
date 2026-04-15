<?php $title = "Modifier Séjour #" . $hote['id']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Hôtes temporaires', 'url' => BASE_URL . '/hote/index'],
    ['icon' => 'fas fa-edit', 'text' => 'Modifier #' . $hote['id'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-edit text-warning"></i> Modifier le séjour</h1>
            <a href="<?= BASE_URL ?>/hote/show/<?= $hote['id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/hote/edit/<?= $hote['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">

        <div class="row">
            <div class="col-12 col-lg-8">
                <!-- Identité -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Identité</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-2">
                                <label class="form-label">Civilité</label>
                                <select class="form-select" name="civilite">
                                    <option value="M" <?= $hote['civilite'] === 'M' ? 'selected' : '' ?>>M.</option>
                                    <option value="Mme" <?= $hote['civilite'] === 'Mme' ? 'selected' : '' ?>>Mme</option>
                                    <option value="Mlle" <?= $hote['civilite'] === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($hote['nom']) ?>" required>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($hote['prenom']) ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="date_naissance" value="<?= $hote['date_naissance'] ?? '' ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Nationalité</label>
                                <input type="text" class="form-control" name="nationalite" value="<?= htmlspecialchars($hote['nationalite'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($hote['email'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Téléphone mobile</label>
                                <input type="text" class="form-control" name="telephone_mobile" value="<?= htmlspecialchars($hote['telephone_mobile'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Adresse domicile</label>
                                <input type="text" class="form-control" name="adresse_domicile" value="<?= htmlspecialchars($hote['adresse_domicile'] ?? '') ?>">
                            </div>
                            <input type="hidden" name="telephone" value="<?= htmlspecialchars($hote['telephone'] ?? '') ?>">

                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase">Pièce d'identité</h6></div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type_piece_identite">
                                    <option value="">— Non renseigné —</option>
                                    <?php foreach (['cni'=>'CNI','passeport'=>'Passeport','titre_sejour'=>'Titre de séjour','permis'=>'Permis','autre'=>'Autre'] as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= ($hote['type_piece_identite'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label">Numéro</label>
                                <input type="text" class="form-control" name="numero_piece_identite" value="<?= htmlspecialchars($hote['numero_piece_identite'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Séjour -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Séjour</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Résidence</label>
                                <select class="form-select" name="residence_id" id="residence_id">
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($residences as $res): ?>
                                    <option value="<?= $res['id'] ?>" <?= ($hote['residence_id'] ?? 0) == $res['id'] ? 'selected' : '' ?>><?= htmlspecialchars($res['nom']) ?> (<?= htmlspecialchars($res['ville']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Chambre / Lot</label>
                                <select class="form-select" name="lot_id" id="lot_id">
                                    <option value="">— Non assigné —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date d'arrivée <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_arrivee" id="date_arrivee" value="<?= $hote['date_arrivee'] ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date de départ prévue <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_depart_prevue" id="date_depart_prevue" value="<?= $hote['date_depart_prevue'] ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date de départ effective</label>
                                <input type="date" class="form-control" name="date_depart_effective" value="<?= $hote['date_depart_effective'] ?? '' ?>">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Nb personnes</label>
                                <input type="number" class="form-control" name="nb_personnes" value="<?= $hote['nb_personnes'] ?? 1 ?>" min="1" max="10">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Motif</label>
                                <select class="form-select" name="motif_sejour">
                                    <?php foreach (['vacances'=>'Vacances','famille'=>'Visite famille','medical'=>'Médical','affaires'=>'Affaires','convalescence'=>'Convalescence','autre'=>'Autre'] as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= ($hote['motif_sejour'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Prix / nuit (€)</label>
                                <input type="number" class="form-control" name="prix_nuit" id="prix_nuit" step="0.01" min="0" value="<?= $hote['prix_nuit'] ?? '' ?>">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Montant total</label>
                                <input type="text" class="form-control" id="montant_total" readonly value="<?= $hote['montant_total'] ? number_format($hote['montant_total'], 2) : '' ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($hote['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-between gap-2 mb-4">
                    <a href="<?= BASE_URL ?>/hote/show/<?= $hote['id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Annuler</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Statut -->
                <div class="card shadow mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Statut du séjour</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut">
                                <?php foreach (['reserve'=>'Réservé','en_cours'=>'En cours','termine'=>'Terminé','annule'=>'Annulé'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $hote['statut'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Paiement</label>
                            <select class="form-select" name="statut_paiement">
                                <?php foreach (['en_attente'=>'En attente','partiel'=>'Partiel','paye'=>'Payé','rembourse'=>'Remboursé'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= ($hote['statut_paiement'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const LOTS = <?= $lots ?>;
const currentLotId = <?= $hote['lot_id'] ?? 'null' ?>;

function loadLots(resId) {
    const sel = document.getElementById('lot_id');
    sel.innerHTML = '<option value="">— Non assigné —</option>';
    LOTS.filter(l => l.residence_id == resId).forEach(l => {
        const opt = document.createElement('option');
        opt.value = l.id;
        opt.textContent = 'Lot ' + l.numero_lot + ' (' + l.type + ')';
        if (l.id == currentLotId) opt.selected = true;
        sel.appendChild(opt);
    });
}
document.getElementById('residence_id').addEventListener('change', function() { loadLots(this.value); });
loadLots(<?= $hote['residence_id'] ?? 0 ?>);

function calcMontant() {
    const a = document.getElementById('date_arrivee').value;
    const d = document.getElementById('date_depart_prevue').value;
    const p = parseFloat(document.getElementById('prix_nuit').value) || 0;
    if (a && d && p > 0) {
        const n = Math.round((new Date(d) - new Date(a)) / 86400000);
        document.getElementById('montant_total').value = n > 0 ? (n * p).toFixed(2) : '';
    }
}
['date_arrivee','date_depart_prevue','prix_nuit'].forEach(id =>
    document.getElementById(id).addEventListener('input', calcMontant)
);
</script>
