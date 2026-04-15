<?php
/**
 * Modifier une résidence senior - Admin
 */
?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-edit text-warning"></i>
                Modifier <?= htmlspecialchars($residence['nom']) ?>
            </h1>
        </div>
    </div>
    
    <!-- Formulaire -->
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Informations de la résidence</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/admin/editResidence/<?= $residence['id'] ?>">
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
                                   value="<?= htmlspecialchars($residence['nom']) ?>"
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
                                   value="<?= htmlspecialchars($residence['adresse']) ?>"
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
                                       value="<?= htmlspecialchars($residence['code_postal']) ?>"
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
                                       value="<?= htmlspecialchars($residence['ville']) ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Coordonnées GPS -->
                        <div class="mb-3" id="geocode-result" style="<?= !empty($residence['latitude']) ? '' : 'display:none' ?>">
                            <div class="alert alert-success alert-permanent small mb-0 d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-map-pin me-1"></i>
                                    <strong>Coordonnées GPS :</strong>
                                    <span id="geocode-coords"><?= !empty($residence['latitude']) ? htmlspecialchars($residence['latitude'] . ', ' . $residence['longitude']) : '' ?></span>
                                </div>
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                        </div>
                        <?php if (empty($residence['latitude'])): ?>
                        <div class="mb-3">
                            <div class="alert alert-warning alert-permanent small mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <strong>Coordonnées GPS manquantes.</strong> Saisissez-les manuellement ou modifiez l'adresse pour déclencher la détection automatique.
                                <br><small class="text-muted">Astuce : Google Maps → clic droit sur l'adresse → coordonnées.</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row mb-3" id="geocode-manual" style="<?= !empty($residence['latitude']) ? 'display:none' : '' ?>">
                            <div class="col-12 col-md-6">
                                <label for="latitude" class="form-label small">
                                    <i class="fas fa-map-pin me-1"></i>Latitude
                                </label>
                                <input type="number" class="form-control form-control-sm"
                                       id="latitude" name="latitude" step="0.000001"
                                       min="-90" max="90" placeholder="Ex: 43.217126"
                                       value="<?= htmlspecialchars($residence['latitude'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="longitude" class="form-label small">
                                    <i class="fas fa-map-pin me-1"></i>Longitude
                                </label>
                                <input type="number" class="form-control form-control-sm"
                                       id="longitude" name="longitude" step="0.000001"
                                       min="-180" max="180" placeholder="Ex: 2.342808"
                                       value="<?= htmlspecialchars($residence['longitude'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Exploitants avec pourcentages -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-briefcase me-1"></i>Exploitants &amp; Pourcentages de gestion
                            </label>
                            <?php
                            // Indexer les pourcentages actuels par exploitant_id
                            $pctCourants = [];
                            foreach ($exploitantsResidence ?? [] as $er) {
                                $pctCourants[$er['id']] = $er['pourcentage_gestion'];
                            }
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-1">
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
                                                           value="<?= isset($pctCourants[$exp['id']]) ? htmlspecialchars($pctCourants[$exp['id']]) : '0' ?>"
                                                           min="0" max="100" step="0.01">
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
                                <span id="pct-total" class="fw-bold">—</span>
                                <small id="pct-warning" class="text-danger d-none"><i class="fas fa-exclamation-triangle"></i> Le total ne doit pas dépasser 100%</small>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?= htmlspecialchars($residence['description'] ?? '') ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Enregistrer les modifications
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
                        <i class="fas fa-info-circle text-info"></i> Informations
                    </h5>
                    <p class="card-text">
                        <strong>ID de la résidence :</strong> #<?= $residence['id'] ?><br>
                        <strong>Créée le :</strong> <?= date('d/m/Y', strtotime($residence['created_at'])) ?>
                    </p>
                    <hr>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention :</strong> La modification affectera toutes les informations liées à cette résidence.
                    </div>
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

// Submit géré dans le bloc géocodage ci-dessous

// === Géocodage automatique via API Adresse (gouv.fr) ===
(function() {
    const adresse = document.getElementById('adresse');
    const cp = document.getElementById('code_postal');
    const ville = document.getElementById('ville');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const resultDiv = document.getElementById('geocode-result');
    const manualDiv = document.getElementById('geocode-manual');
    const coordsSpan = document.getElementById('geocode-coords');
    let timer = null;

    function showFound(lat, lng, label) {
        latInput.value = lat;
        lngInput.value = lng;
        coordsSpan.textContent = lat + ', ' + lng;
        resultDiv.style.display = '';
        if (manualDiv) manualDiv.style.display = 'none';
    }

    function showNotFound() {
        latInput.value = '';
        lngInput.value = '';
        resultDiv.style.display = 'none';
        if (manualDiv) manualDiv.style.display = '';
    }

    function doGeocode() {
        const q = (adresse.value.trim() + ' ' + cp.value.trim() + ' ' + ville.value.trim()).trim();
        if (q.length < 10) return Promise.resolve();

        const params = new URLSearchParams({ q: q, limit: 1 });
        if (cp.value.trim()) params.set('postcode', cp.value.trim());

        return fetch('https://api-adresse.data.gouv.fr/search/?' + params)
            .then(r => r.json())
            .then(data => {
                if (data.features && data.features.length > 0) {
                    const f = data.features[0];
                    showFound(
                        f.geometry.coordinates[1].toFixed(6),
                        f.geometry.coordinates[0].toFixed(6),
                        f.properties.label
                    );
                } else {
                    showNotFound();
                }
            })
            .catch(() => showNotFound());
    }

    function geocode() {
        clearTimeout(timer);
        timer = setTimeout(doGeocode, 600);
    }

    [adresse, cp, ville].forEach(el => el.addEventListener('blur', geocode));
    ville.addEventListener('input', function() {
        if (adresse.value && cp.value && ville.value.length >= 3) geocode();
    });

    // Validation pourcentages au submit (géocodage fait côté serveur si nécessaire)
    document.querySelector('form').addEventListener('submit', function(e) {
        const pctInputs = document.querySelectorAll('.pct-input');
        let total = 0;
        pctInputs.forEach(i => total += parseFloat(i.value) || 0);
        if (total > 100) {
            e.preventDefault();
            alert('Le total des pourcentages exploitants dépasse 100% (' + Math.round(total*100)/100 + '%). Veuillez corriger.');
        }
    });
})();
</script>
