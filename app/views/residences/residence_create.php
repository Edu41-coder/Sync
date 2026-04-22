<?php
/**
 * Créer une nouvelle résidence senior - Admin
 */
?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-plus', 'text' => 'Créer une résidence', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-plus-circle text-dark"></i>
                Nouvelle Résidence Senior
            </h1>
        </div>
    </div>
    
    <!-- Formulaire -->
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Informations de la résidence</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/admin/createResidence">
                        <?= csrf_field() ?>
                        
                        <!-- Nom -->
                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nom de la résidence <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="nom"
                                   name="nom"
                                   value="Domitys - "
                                   required>
                        </div>
                        
                        <!-- Adresse -->
                        <div class="mb-3">
                            <label for="adresse" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Adresse <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="adresse" 
                                   name="adresse" 
                                   placeholder="Ex: 25 Avenue des Fleurs"
                                   required>
                        </div>
                        
                        <!-- Code postal et Ville -->
                        <div class="row">
                            <div class="col-12 col-md-4 mb-3">
                                <label for="code_postal" class="form-label">
                                    <i class="fas fa-hashtag me-1"></i>Code postal <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="code_postal"
                                       name="code_postal"
                                       placeholder="69001"
                                       maxlength="10"
                                       required>
                            </div>

                            <div class="col-12 col-md-8 mb-3">
                                <label for="ville" class="form-label">
                                    <i class="fas fa-city me-1"></i>Ville <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="ville"
                                       name="ville"
                                       placeholder="Lyon"
                                       required>
                            </div>
                        </div>

                        <input type="hidden" id="latitude" name="latitude" value="">
                        <input type="hidden" id="longitude" name="longitude" value="">
                        
                        <!-- Exploitants avec pourcentages -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-briefcase me-1"></i>Exploitants &amp; Pourcentages de gestion
                            </label>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-1" id="exploitants-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Société</th>
                                            <th style="width:130px">% de gestion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exploitants as $exp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($exp['raison_sociale']) ?></td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" class="form-control pct-input"
                                                           name="exploitant_pourcentages[<?= $exp['id'] ?>]"
                                                           value="<?= $exp['id'] == 1 ? '100' : '0' ?>"
                                                           min="0" max="100" step="0.01"
                                                           placeholder="0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <small class="text-muted">Total :</small>
                                <span id="pct-total" class="fw-bold">100%</span>
                                <small id="pct-warning" class="text-danger d-none"><i class="fas fa-exclamation-triangle"></i> Le total ne doit pas dépasser 100%</small>
                            </div>
                            <div class="form-text">Par défaut Domitys = 100%. Modifiez si cette résidence est gérée partiellement par plusieurs exploitants.</div>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="4"
                                      placeholder="Description de la résidence, équipements, services..."></textarea>
                        </div>

                        <!-- Apiculture (ruches) -->
                        <div class="mb-3 p-3 border rounded bg-light">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="ruches" name="ruches" value="1">
                                <label class="form-check-label" for="ruches">
                                    🐝 <strong>Cette résidence disposera de ruches (apiculture)</strong>
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Si coché, le module apiculture (ruches + carnet de visite) sera disponible dans Jardinage après la création.
                                La configuration détaillée (NAPI, référent…) se fera ensuite via le module Jardinage.
                            </small>
                        </div>

                        <hr class="my-4">
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-save"></i> Créer la résidence
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Aide -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle text-danger"></i> Informations
                    </h5>
                    <h6><i class="fas fa-check-circle text-success"></i> Après la création :</h6>
                    <ul class="small">
                        <li>Vous pourrez ajouter des lots (appartements)</li>
                        <li>Assigner des résidents aux lots</li>
                        <li>Gérer les occupations et loyers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function updatePctTotal() {
    const inputs = document.querySelectorAll('.pct-input');
    let total = 0;
    inputs.forEach(i => total += parseFloat(i.value) || 0);
    total = Math.round(total * 100) / 100;
    const el = document.getElementById('pct-total');
    const warn = document.getElementById('pct-warning');
    el.textContent = total + '%';
    el.className = 'fw-bold ' + (total > 100 ? 'text-danger' : total === 100 ? 'text-success' : 'text-warning');
    warn.classList.toggle('d-none', total <= 100);
}
document.querySelectorAll('.pct-input').forEach(i => i.addEventListener('input', updatePctTotal));
updatePctTotal();

// Validation pourcentages au submit
document.querySelector('form').addEventListener('submit', function(e) {
        const pctInputs = document.querySelectorAll('.pct-input');
        let total = 0;
        pctInputs.forEach(i => total += parseFloat(i.value) || 0);
        if (total > 100) {
            e.preventDefault();
            alert('Le total des pourcentages exploitants dépasse 100% (' + Math.round(total*100)/100 + '%). Veuillez corriger.');
        }
});
</script>
