<?php $title = "Modifier l'occupation"; ?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-building', 'text' => $occupation['residence_nom'], 'url' => BASE_URL . '/admin/viewResidence/' . $occupation['residence_id']],
        ['icon' => 'fas fa-user-check', 'text' => 'Occupation', 'url' => BASE_URL . '/occupation/show/' . $occupation['id']],
        ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3">
                    <i class="fas fa-edit text-warning"></i>
                    Modifier l'occupation
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($occupation['resident_nom']) ?> - 
                    <i class="fas fa-door-open me-1"></i>Lot <?= htmlspecialchars($occupation['numero_lot']) ?>
                </p>
            </div>
            <a href="<?= BASE_URL ?>/occupation/show/<?= $occupation['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>
    
    <form method="POST" action="<?= BASE_URL ?>/occupation/edit/<?= $occupation['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
        
        <div class="row">
            <div class="col-12 col-lg-8">
                <!-- Résident occupant -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>
                            Résident occupant
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle avatar-sm bg-primary text-white me-2">
                                <?= strtoupper(substr($occupation['resident_nom'] ?? '?', 0, 2)) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($occupation['resident_nom'] ?? '') ?></strong>
                                <br><small class="text-muted">Résident actuel (ID: <?= $occupation['resident_id'] ?>)</small>
                            </div>
                        </div>
                        <label class="form-label small">Changer le résident</label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchResidentEdit" placeholder="Rechercher un résident disponible..." autocomplete="off">
                        </div>
                        <div class="border rounded" style="max-height:180px;overflow-y:auto" id="residentEditContainer">
                            <!-- Résident actuel (toujours en option) -->
                            <div class="form-check px-3 py-1 resident-edit-item"
                                 data-search="<?= htmlspecialchars(strtolower($occupation['resident_nom'] ?? '')) ?>">
                                <input class="form-check-input" type="radio" name="resident_id"
                                       value="<?= $occupation['resident_id'] ?>" id="res_current" checked>
                                <label class="form-check-label small w-100" for="res_current">
                                    <?= htmlspecialchars($occupation['resident_nom'] ?? '') ?> <span class="badge bg-primary ms-1">actuel</span>
                                </label>
                            </div>
                            <?php foreach ($availableResidents ?? [] as $res): ?>
                            <div class="form-check px-3 py-1 resident-edit-item"
                                 data-search="<?= htmlspecialchars(strtolower($res['nom'] . ' ' . $res['prenom'])) ?>">
                                <input class="form-check-input" type="radio" name="resident_id"
                                       value="<?= $res['id'] ?>" id="res_<?= $res['id'] ?>">
                                <label class="form-check-label small w-100" for="res_<?= $res['id'] ?>">
                                    <?= htmlspecialchars($res['civilite'] . ' ' . $res['prenom'] . ' ' . $res['nom']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($availableResidents)): ?>
                            <div class="text-muted small text-center py-2">Aucun autre résident disponible</div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            Seuls les résidents actifs sans occupation sont proposés.
                        </small>
                    </div>
                </div>

                <!-- Informations financières -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-euro-sign me-2"></i>
                            Informations financières
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Loyer mensuel -->
                            <div class="col-12 col-md-6">
                                <label for="loyer_mensuel_resident" class="form-label">
                                    <i class="fas fa-home text-dark me-1"></i>
                                    Loyer mensuel résident <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="loyer_mensuel_resident" 
                                           name="loyer_mensuel_resident" 
                                           step="0.01" 
                                           value="<?= $occupation['loyer_mensuel_resident'] ?>" 
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Charges mensuelles -->
                            <div class="col-12 col-md-6">
                                <label for="charges_mensuelles_resident" class="form-label">
                                    <i class="fas fa-file-invoice-dollar text-dark me-1"></i>
                                    Charges mensuelles
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="charges_mensuelles_resident" 
                                           name="charges_mensuelles_resident" 
                                           step="0.01" 
                                           value="<?= $occupation['charges_mensuelles_resident'] ?? 0 ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Type de forfait -->
                            <div class="col-12 col-md-6">
                                <label for="forfait_type" class="form-label">
                                    <i class="fas fa-tag text-dark me-1"></i>
                                    Type de forfait
                                </label>
                                <select class="form-select" id="forfait_type" name="forfait_type">
                                    <option value="essentiel" <?= $occupation['forfait_type'] === 'essentiel' ? 'selected' : '' ?>>Essentiel</option>
                                    <option value="confort" <?= $occupation['forfait_type'] === 'confort' ? 'selected' : '' ?>>Confort</option>
                                    <option value="premium" <?= $occupation['forfait_type'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                                </select>
                            </div>
                            
                            <!-- Services supplémentaires montant -->
                            <div class="col-12 col-md-6">
                                <label for="montant_services_sup" class="form-label">
                                    <i class="fas fa-plus-circle text-dark me-1"></i>
                                    Services supplémentaires
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant_services_sup" 
                                           name="montant_services_sup" 
                                           step="0.01" 
                                           value="<?= $occupation['montant_services_sup'] ?? 0 ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Mode de paiement -->
                            <div class="col-12 col-md-6">
                                <label for="mode_paiement" class="form-label">
                                    <i class="fas fa-credit-card text-dark me-1"></i>
                                    Mode de paiement
                                </label>
                                <select class="form-select" id="mode_paiement" name="mode_paiement">
                                    <option value="prelevement" <?= $occupation['mode_paiement'] === 'prelevement' ? 'selected' : '' ?>>Prélèvement</option>
                                    <option value="virement" <?= $occupation['mode_paiement'] === 'virement' ? 'selected' : '' ?>>Virement</option>
                                    <option value="cheque" <?= $occupation['mode_paiement'] === 'cheque' ? 'selected' : '' ?>>Chèque</option>
                                </select>
                            </div>
                            
                            <!-- Jour de prélèvement -->
                            <div class="col-12 col-md-6">
                                <label for="jour_prelevement" class="form-label">
                                    <i class="fas fa-calendar-day text-dark me-1"></i>
                                    Jour de prélèvement
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="jour_prelevement" 
                                       name="jour_prelevement" 
                                       min="1" 
                                       max="28" 
                                       value="<?= $occupation['jour_prelevement'] ?? 5 ?>">
                            </div>
                            
                            <!-- Services inclus -->
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="fas fa-check-circle text-dark me-1"></i>
                                    Services inclus
                                </label>
                                <div class="row g-2">
                                    <?php foreach ($services as $svc): ?>
                                    <?php if ($svc['categorie'] !== 'inclus') continue; ?>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input svc-checkbox" type="checkbox"
                                                   name="services[<?= $svc['id'] ?>]"
                                                   value="<?= $svc['prix_defaut'] ?>"
                                                   id="svc_<?= $svc['id'] ?>"
                                                   data-prix="<?= $svc['prix_defaut'] ?>"
                                                   <?= !empty($svc['souscrit']) ? 'checked' : '' ?>>
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
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="fas fa-plus-circle text-dark me-1"></i>
                                    Services supplémentaires
                                </label>
                                <div class="row g-2">
                                    <?php foreach ($services as $svc): ?>
                                    <?php if ($svc['categorie'] !== 'supplementaire') continue; ?>
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="form-check flex-grow-1">
                                                <input class="form-check-input svc-checkbox svc-sup" type="checkbox"
                                                       name="services[<?= $svc['id'] ?>]"
                                                       value="<?= !empty($svc['souscrit']) ? $svc['prix_applique'] : $svc['prix_defaut'] ?>"
                                                       id="svc_<?= $svc['id'] ?>"
                                                       data-prix="<?= $svc['prix_defaut'] ?>"
                                                       <?= !empty($svc['souscrit']) ? 'checked' : '' ?>>
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
                                    <strong>Total supplémentaires : <span id="totalSup">0.00</span> €/mois</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aides sociales -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-hands-helping me-2"></i>
                            Aides sociales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- APL -->
                            <div class="col-12 col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="beneficie_apl" 
                                           name="beneficie_apl" 
                                           <?= $occupation['beneficie_apl'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="beneficie_apl">
                                        <i class="fas fa-home text-dark me-1"></i>
                                        Bénéficie de l'APL
                                    </label>
                                </div>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant_apl" 
                                           name="montant_apl" 
                                           step="0.01" 
                                           value="<?= $occupation['montant_apl'] ?? '' ?>" 
                                           placeholder="Montant">
                                    <span class="input-group-text">€/mois</span>
                                </div>
                            </div>
                            
                            <!-- APA -->
                            <div class="col-12 col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="beneficie_apa" 
                                           name="beneficie_apa" 
                                           <?= $occupation['beneficie_apa'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="beneficie_apa">
                                        <i class="fas fa-heartbeat text-dark me-1"></i>
                                        Bénéficie de l'APA
                                    </label>
                                </div>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant_apa" 
                                           name="montant_apa" 
                                           step="0.01" 
                                           value="<?= $occupation['montant_apa'] ?? '' ?>" 
                                           placeholder="Montant">
                                    <span class="input-group-text">€/mois</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note text-dark me-2"></i>
                            Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4"><?= htmlspecialchars($occupation['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Statut -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle text-dark me-2"></i>
                            Statut
                        </h6>
                    </div>
                    <div class="card-body">
                        <label for="statut" class="form-label">Statut de l'occupation</label>
                        <select class="form-select" id="statut" name="statut">
                            <option value="actif" <?= $occupation['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="preavis" <?= $occupation['statut'] === 'preavis' ? 'selected' : '' ?>>Préavis</option>
                            <option value="termine" <?= $occupation['statut'] === 'termine' ? 'selected' : '' ?>>Terminé</option>
                        </select>
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle text-dark me-2"></i>
                            Informations
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Résident</small>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['resident_nom']) ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Lot</small>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['numero_lot']) ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Résidence</small>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['residence_nom']) ?></div>
                        </div>
                        <div>
                            <small class="text-muted">Date d'entrée</small>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($occupation['date_entree'])) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card shadow">
                    <div class="card-body">
                        <button type="submit" class="btn btn-warning w-100 mb-2">
                            <i class="fas fa-save me-1"></i>
                            Enregistrer les modifications
                        </button>
                        <a href="<?= BASE_URL ?>/occupation/show/<?= $occupation['id'] ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-1"></i>
                            Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Calcul total services supplémentaires
function calcTotalSup() {
    let total = 0;
    document.querySelectorAll('.svc-sup:checked').forEach(cb => {
        total += parseFloat(cb.value) || 0;
    });
    document.getElementById('totalSup').textContent = total.toFixed(2);
    const montantField = document.getElementById('montant_services_sup');
    if (montantField) montantField.value = total.toFixed(2);
}
document.querySelectorAll('.svc-checkbox').forEach(cb => cb.addEventListener('change', calcTotalSup));
calcTotalSup();

(function() {
    const search = document.getElementById('searchResidentEdit');
    if (!search) return;
    search.addEventListener('input', function() {
        const q = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        document.querySelectorAll('.resident-edit-item').forEach(item => {
            const text = item.getAttribute('data-search').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
            item.style.display = !q || text.includes(q) ? '' : 'none';
        });
    });
})();
</script>
