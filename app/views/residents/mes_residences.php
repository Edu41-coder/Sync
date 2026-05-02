<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',          'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-building',       'text' => 'Résidences Domitys',  'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

// Préparer données carte (résidences géolocalisées uniquement)
$mapPoints = [];
foreach ($residences as $r) {
    if (!empty($r['latitude']) && !empty($r['longitude'])) {
        $mapPoints[] = [
            'id'        => (int)$r['id'],
            'nom'       => (string)$r['nom'],
            'adresse'   => (string)($r['adresse'] ?? ''),
            'ville'     => (string)($r['ville'] ?? ''),
            'cp'        => (string)($r['code_postal'] ?? ''),
            'lat'       => (float)$r['latitude'],
            'lng'       => (float)$r['longitude'],
            'nb_lots'   => (int)($r['nb_lots'] ?? 0),
            'is_mine'   => in_array($r['id'], $mesResidencesIds, false),
        ];
    }
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-building fa-2x text-info me-3"></i>
        <h1 class="h3 mb-0">Résidences Domitys</h1>
        <small class="text-muted ms-3"><?= count($residences) ?> résidences seniors</small>
        <?php if (!empty($mesResidencesIds)): ?>
        <span class="badge bg-warning text-dark ms-3">
            <i class="fas fa-star me-1"></i><?= count($mesResidencesIds) ?> résidence<?= count($mesResidencesIds) > 1 ? 's' : '' ?> où je réside
        </span>
        <?php endif; ?>
    </div>

    <!-- Carte Leaflet -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Carte des résidences</h5>
        </div>
        <div class="card-body p-0">
            <div id="mapResidences" style="height: 480px; width: 100%;"></div>
            <div class="p-2 small text-muted bg-light border-top">
                <span class="me-3"><i class="fas fa-map-marker-alt text-warning"></i> Je réside ici</span>
                <span><i class="fas fa-map-marker-alt text-info"></i> Autres résidences Domitys</span>
            </div>
        </div>
    </div>

    <!-- Liste / table avec recherche & tri -->
    <div class="card shadow">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Liste détaillée</h5>
            <input type="text" id="searchResidences" class="form-control form-control-sm"
                   placeholder="Rechercher (nom, ville…)" style="min-width:240px;max-width:320px">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableResidences">
                    <thead class="table-light">
                        <tr>
                            <th>Résidence</th>
                            <th>Ville</th>
                            <th>Exploitant</th>
                            <th class="text-center">Lots</th>
                            <th class="text-center">Vous y résidez</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residences)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune résidence.</td></tr>
                        <?php else: foreach ($residences as $r): $isMine = in_array($r['id'], $mesResidencesIds, false); ?>
                        <tr class="<?= $isMine ? 'table-warning' : '' ?>">
                            <td><strong><?= htmlspecialchars($r['nom']) ?></strong>
                                <?php if (!empty($r['adresse'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($r['adresse']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars(($r['code_postal'] ?? '') . ' ' . ($r['ville'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($r['exploitant'] ?? 'Domitys') ?></td>
                            <td class="text-center" data-sort="<?= (int)($r['nb_lots'] ?? 0) ?>"><?= (int)($r['nb_lots'] ?? 0) ?></td>
                            <td class="text-center" data-mine="<?= $isMine ? 'oui' : 'non' ?>">
                                <?php if ($isMine): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Oui</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="tableInfoResidences" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationResidences"></ul>
            </div>
        </div>
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
(function() {
    const points = <?= json_encode($mapPoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    // Carte Leaflet
    const map = L.map('mapResidences');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    if (points.length > 0) {
        const bounds = [];
        points.forEach(p => {
            const color = p.is_mine ? '#fd7e14' : '#0dcaf0';
            const marker = L.circleMarker([p.lat, p.lng], {
                radius: p.is_mine ? 12 : 8,
                fillColor: color, color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.9
            }).addTo(map);
            const tag = p.is_mine ? '<span class="badge bg-warning text-dark">★ Je réside ici</span>' : '';
            marker.bindPopup(
                '<strong>' + p.nom + '</strong>' + (tag ? ' ' + tag : '') + '<br>' +
                p.adresse + '<br>' + p.cp + ' ' + p.ville + '<br>' +
                '<small>' + p.nb_lots + ' lot' + (p.nb_lots > 1 ? 's' : '') + '</small>'
            );
            bounds.push([p.lat, p.lng]);
        });
        map.fitBounds(bounds, { padding: [40, 40] });
    } else {
        map.setView([46.603354, 1.888334], 6); // Centre France
    }

    // DataTable (tri + recherche + pagination)
    if (typeof DataTableWithPagination !== 'undefined') {
        new DataTableWithPagination('tableResidences', {
            rowsPerPage: 10,
            searchInputId: 'searchResidences',
            paginationId: 'paginationResidences',
            infoId: 'tableInfoResidences',
            excludeColumns: [4]
        });
    }
})();
</script>
