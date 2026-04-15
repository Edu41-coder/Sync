<?php $title = "Nouvelle Occupation"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-home', 'text' => 'Occupations', 'url' => BASE_URL . '/occupation/index'],
    ['icon' => 'fas fa-plus', 'text' => 'Nouvelle occupation', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-plus-circle text-dark"></i>
                Nouvelle Occupation
            </h1>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/occupation/create" method="POST" id="occupationForm">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">

        <div class="row">
            <!-- Colonne principale -->
            <div class="col-12 col-lg-8">

                <!-- Lot -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Lot</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($preselectedLot): ?>
                            <!-- Lot pré-sélectionné depuis lot/show -->
                            <input type="hidden" name="lot_id" value="<?= $preselectedLot['id'] ?>">
                            <div class="alert alert-success alert-permanent mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong><?= htmlspecialchars($preselectedLot['residence_nom']) ?></strong>
                                — Lot <?= htmlspecialchars($preselectedLot['numero_lot']) ?>
                                (<?= htmlspecialchars($preselectedLot['type']) ?>
                                <?php if (!empty($preselectedLot['surface'])): ?>, <?= $preselectedLot['surface'] ?> m²<?php endif; ?>
                                <?php if (!empty($preselectedLot['etage'])): ?>, étage <?= $preselectedLot['etage'] ?><?php endif; ?>)
                            </div>
                        <?php else: ?>
                            <!-- Sélection d'un lot libre -->
                            <label class="form-label">Lot disponible <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchLot" placeholder="Rechercher un lot..." autocomplete="off">
                            </div>
                            <div class="border rounded" style="max-height:250px;overflow-y:auto" id="lotContainer">
                                <?php
                                $lastRes = null;
                                foreach ($lots as $lot):
                                    if ($lot['residence_nom'] !== $lastRes):
                                        if ($lastRes !== null) echo '</div>';
                                        $lastRes = $lot['residence_nom'];
                                ?>
                                <div class="px-3 pt-2 pb-1 bg-light border-bottom">
                                    <strong class="small text-uppercase text-muted"><?= htmlspecialchars($lot['residence_nom']) ?></strong>
                                </div>
                                <div>
                                <?php endif; ?>
                                <div class="form-check px-3 py-1 lot-item"
                                     data-search="<?= htmlspecialchars(strtolower($lot['residence_nom'] . ' ' . $lot['numero_lot'] . ' ' . $lot['type'])) ?>">
                                    <input class="form-check-input" type="radio" name="lot_id"
                                           value="<?= $lot['id'] ?>" id="lot_<?= $lot['id'] ?>" required>
                                    <label class="form-check-label small w-100" for="lot_<?= $lot['id'] ?>">
                                        Lot <?= htmlspecialchars($lot['numero_lot']) ?>
                                        <span class="text-muted">
                                            (<?= htmlspecialchars($lot['type']) ?>
                                            <?php if ($lot['surface']): ?>, <?= $lot['surface'] ?> m²<?php endif; ?>
                                            <?php if ($lot['terrasse'] !== 'non'): ?>, <?= $lot['terrasse'] === 'loggia' ? 'loggia' : 'terrasse' ?><?php endif; ?>)
                                        </span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <?php if ($lastRes !== null) echo '</div>'; ?>
                                <?php if (empty($lots)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Aucun lot disponible
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Résident -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Résident Senior</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($residents)): ?>
                        <div class="alert alert-warning alert-permanent mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Aucun résident disponible. Tous les résidents actifs ont déjà une occupation.
                        </div>
                        <?php else: ?>
                        <label class="form-label">Résident disponible <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchResident" placeholder="Rechercher un résident..." autocomplete="off">
                        </div>
                        <div class="border rounded" style="max-height:220px;overflow-y:auto" id="residentContainer">
                            <?php foreach ($residents as $res): ?>
                            <div class="form-check px-3 py-1 resident-item"
                                 data-search="<?= htmlspecialchars(strtolower($res['nom'] . ' ' . $res['prenom'])) ?>">
                                <input class="form-check-input" type="radio" name="resident_id"
                                       value="<?= $res['id'] ?>" id="res_<?= $res['id'] ?>" required>
                                <label class="form-check-label small w-100" for="res_<?= $res['id'] ?>">
                                    <?= htmlspecialchars($res['nom_complet']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted mt-1 d-block"><?= count($residents) ?> résident(s) disponible(s)</small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Détails de l'occupation -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Détails</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date d'entrée <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_debut" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Loyer mensuel (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="loyer_mensuel_resident" step="0.01" min="0" placeholder="1450.00">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Charges mensuelles (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="charges_mensuelles" step="0.01" min="0" value="0" placeholder="0.00">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Forfait</label>
                                <select class="form-select" name="forfait_type">
                                    <option value="essentiel">Essentiel</option>
                                    <option value="serenite">Sérénité</option>
                                    <option value="confort">Confort</option>
                                    <option value="premium">Premium</option>
                                    <option value="personnalise">Personnalisé</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Dépôt de garantie (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="depot_garantie" step="0.01" min="0" value="0" placeholder="0.00">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Mode de paiement</label>
                                <select class="form-select" name="mode_paiement">
                                    <option value="prelevement">Prélèvement</option>
                                    <option value="virement">Virement</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="mandat_sepa">Mandat SEPA</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Remarques sur l'occupation..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Services -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-concierge-bell me-2"></i>Services</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Services inclus -->
                            <div class="col-12">
                                <label class="form-label"><i class="fas fa-check-circle text-success me-1"></i>Services inclus</label>
                                <div class="row g-2">
                                    <?php foreach ($services as $svc): ?>
                                    <?php if ($svc['categorie'] !== 'inclus') continue; ?>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="services[<?= $svc['id'] ?>]" value="0"
                                                   id="svc_<?= $svc['id'] ?>" checked>
                                            <label class="form-check-label" for="svc_<?= $svc['id'] ?>">
                                                <i class="<?= htmlspecialchars($svc['icone']) ?> me-1 text-muted"></i>
                                                <?= htmlspecialchars($svc['nom']) ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Services supplémentaires -->
                            <div class="col-12 mt-3">
                                <label class="form-label"><i class="fas fa-plus-circle text-warning me-1"></i>Services supplémentaires</label>
                                <div class="row g-2">
                                    <?php foreach ($services as $svc): ?>
                                    <?php if ($svc['categorie'] !== 'supplementaire') continue; ?>
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check flex-grow-1">
                                                <input class="form-check-input svc-sup-create" type="checkbox"
                                                       name="services[<?= $svc['id'] ?>]" value="<?= $svc['prix_defaut'] ?>"
                                                       id="svc_<?= $svc['id'] ?>">
                                                <label class="form-check-label" for="svc_<?= $svc['id'] ?>">
                                                    <i class="<?= htmlspecialchars($svc['icone']) ?> me-1 text-muted"></i>
                                                    <?= htmlspecialchars($svc['nom']) ?>
                                                </label>
                                            </div>
                                            <span class="badge bg-light text-dark ms-1"><?= number_format($svc['prix_defaut'], 2) ?> €</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2 small text-end">
                                    <strong>Total supplémentaires : <span id="totalSupCreate">0.00</span> €/mois</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-between gap-2 mb-4">
                    <?php if ($lotId): ?>
                    <a href="<?= BASE_URL ?>/lot/show/<?= $lotId ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour au lot
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/occupation/index" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary" <?= empty($residents) ? 'disabled' : '' ?>>
                        <i class="fas fa-save me-1"></i>Créer l'occupation
                    </button>
                </div>

            </div>

            <!-- Colonne aide -->
            <div class="col-12 col-lg-4">
                <div class="card shadow mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide</h5>
                    </div>
                    <div class="card-body small">
                        <ul class="mb-0">
                            <li>Seuls les <strong>lots libres</strong> (non occupés) sont affichés</li>
                            <li>Seuls les <strong>résidents sans occupation</strong> active sont proposés</li>
                            <li>La date d'entrée est pré-remplie à aujourd'hui</li>
                            <li>Le loyer mensuel correspond au montant payé par le résident</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Recherche lots
(function() {
    const search = document.getElementById('searchLot');
    if (!search) return;
    search.addEventListener('input', function() {
        const q = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        document.querySelectorAll('.lot-item').forEach(item => {
            const text = item.getAttribute('data-search').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
            item.style.display = !q || text.includes(q) ? '' : 'none';
        });
    });
})();

// Calcul total services supplémentaires
function calcTotalSupCreate() {
    let total = 0;
    document.querySelectorAll('.svc-sup-create:checked').forEach(cb => {
        total += parseFloat(cb.value) || 0;
    });
    document.getElementById('totalSupCreate').textContent = total.toFixed(2);
}
document.querySelectorAll('.svc-sup-create').forEach(cb => cb.addEventListener('change', calcTotalSupCreate));

// Recherche résidents
(function() {
    const search = document.getElementById('searchResident');
    if (!search) return;
    search.addEventListener('input', function() {
        const q = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        document.querySelectorAll('.resident-item').forEach(item => {
            const text = item.getAttribute('data-search').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
            item.style.display = !q || text.includes(q) ? '' : 'none';
        });
    });
})();
</script>
