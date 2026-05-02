<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-swimming-pool',  'text' => 'Piscine',         'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$badgeNiveau = ['danger' => 'danger', 'warning' => 'warning', 'info' => 'info'];

// Helpers d'affichage
function piscineBadge(string $check): string {
    return match($check) {
        'normal'      => '<span class="badge bg-success">Normal</span>',
        'hors_norme'  => '<span class="badge bg-warning text-dark">Hors norme</span>',
        'critique'    => '<span class="badge bg-danger">Critique</span>',
        default       => '<span class="badge bg-secondary">—</span>',
    };
}
function piscineTypeBadge(string $type): string {
    $cfg = [
        'analyse'         => ['Analyse',         'info'],
        'controle_ars'    => ['Contrôle ARS',    'primary'],
        'hivernage'       => ['Hivernage',       'secondary'],
        'mise_en_service' => ['Mise en service', 'success'],
        'vidange'         => ['Vidange',         'warning'],
        'autre'           => ['Autre',           'dark'],
    ];
    [$lbl, $color] = $cfg[$type] ?? ['?', 'secondary'];
    return '<span class="badge bg-' . $color . '">' . $lbl . '</span>';
}
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-swimming-pool text-info me-2"></i>Piscine</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> — <?= htmlspecialchars($residenceCourante['ville']) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!empty($residences)): ?>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/maintenance/piscine">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalEntree">
                <i class="fas fa-plus me-1"></i>Nouvelle entrée
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Aucune résidence avec piscine ne vous est accessible.
        L'admin doit cocher l'option « piscine » sur la fiche résidence.
    </div>

    <?php else: ?>

    <!-- Alertes -->
    <?php if (!empty($alertes)): ?>
    <div class="row mb-3">
        <div class="col-12">
            <?php foreach ($alertes as $a): ?>
            <div class="alert alert-<?= $badgeNiveau[$a['niveau']] ?? 'info' ?> py-2 mb-2">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($a['msg']) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-info small fw-bold mb-1">État saisonnier</h6>
                    <h4 class="mb-0">
                        <?php if ($etatSaisonnier['etat'] === 'ouverte'): ?>
                            <span class="text-success"><i class="fas fa-water me-1"></i>Ouverte</span>
                        <?php elseif ($etatSaisonnier['etat'] === 'hivernage'): ?>
                            <span class="text-secondary"><i class="fas fa-snowflake me-1"></i>Hivernage</span>
                        <?php else: ?>
                            <span class="text-muted">Inconnu</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($etatSaisonnier['date']): ?>
                    <small class="text-muted">depuis le <?= date('d/m/Y', strtotime($etatSaisonnier['date'])) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary small fw-bold mb-1">Analyses (30 j)</h6>
                    <h4 class="mb-0"><?= (int)$stats['analyses_30j'] ?></h4>
                    <small class="text-muted">Idéal : 1 par jour en saison</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-success small fw-bold mb-1">Contrôles ARS (12 mois)</h6>
                    <h4 class="mb-0"><?= (int)$stats['controles_ars_an'] ?></h4>
                    <small class="text-muted">Réglementaire : mensuel</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-warning small fw-bold mb-1">Dernière mesure</h6>
                    <h4 class="mb-0">
                        <?= $stats['derniere_mesure'] ? date('d/m/Y', strtotime($stats['derniere_mesure'])) : '—' ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Dernière analyse + dernier contrôle ARS -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <strong><i class="fas fa-vial me-2"></i>Dernière analyse chimique</strong>
                </div>
                <?php if ($derniereAnalyse): ?>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($derniereAnalyse['date_mesure'])) ?>
                    </p>
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th style="width:50%">pH</th>
                                <td>
                                    <strong><?= $derniereAnalyse['ph'] ?: '—' ?></strong>
                                    <?= piscineBadge(Piscine::checkPh($derniereAnalyse['ph'])) ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Chlore libre</th>
                                <td>
                                    <strong><?= $derniereAnalyse['chlore_libre_mg_l'] ?: '—' ?> mg/L</strong>
                                    <?= piscineBadge(Piscine::checkChlore($derniereAnalyse['chlore_libre_mg_l'])) ?>
                                </td>
                            </tr>
                            <?php if (!empty($derniereAnalyse['chlore_total_mg_l'])): ?>
                            <tr><th>Chlore total</th><td><?= $derniereAnalyse['chlore_total_mg_l'] ?> mg/L</td></tr>
                            <?php endif; ?>
                            <?php if (!empty($derniereAnalyse['temperature'])): ?>
                            <tr><th>Température</th><td><?= $derniereAnalyse['temperature'] ?> °C</td></tr>
                            <?php endif; ?>
                            <?php if (!empty($derniereAnalyse['alcalinite_mg_l'])): ?>
                            <tr><th>Alcalinité (TAC)</th><td><?= $derniereAnalyse['alcalinite_mg_l'] ?> mg/L</td></tr>
                            <?php endif; ?>
                            <?php if (!empty($derniereAnalyse['stabilisant_mg_l'])): ?>
                            <tr><th>Stabilisant</th><td><?= $derniereAnalyse['stabilisant_mg_l'] ?> mg/L</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="card-body text-center text-muted py-4">
                    <i class="fas fa-vial fa-2x opacity-50 mb-2"></i><br>
                    Aucune analyse enregistrée
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                    <strong><i class="fas fa-stamp me-2"></i>Dernier contrôle ARS</strong>
                </div>
                <?php if ($dernierArs): ?>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        <i class="far fa-clock me-1"></i><?= date('d/m/Y', strtotime($dernierArs['date_mesure'])) ?>
                        <?php if ($dernierArs['numero_pv']): ?>
                            • PV <?= htmlspecialchars($dernierArs['numero_pv']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="mb-2">
                        <strong>Conformité :</strong>
                        <?php
                        $conf = $dernierArs['conformite_ars'] ?? '';
                        $confBadge = ['conforme' => 'success', 'non_conforme' => 'danger', 'avertissement' => 'warning'][$conf] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $confBadge ?>"><?= $conf ?: '—' ?></span>
                    </p>
                    <?php if (!empty($dernierArs['notes'])): ?>
                    <div class="alert alert-light border small mb-2"><?= nl2br(htmlspecialchars($dernierArs['notes'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($dernierArs['fichier_pv'])): ?>
                    <a href="<?= BASE_URL ?>/maintenance/piscinePv/<?= (int)$dernierArs['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-file-pdf me-1"></i>Voir le PV
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="card-body text-center text-muted py-4">
                    <i class="fas fa-stamp fa-2x opacity-50 mb-2"></i><br>
                    Aucun contrôle ARS enregistré
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Journal complet -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-book me-2"></i>Journal de bord</strong>
            <input type="text" id="searchJournal" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:240px">
        </div>
        <div class="card-body p-0">
            <?php if (empty($journal)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-book fa-3x opacity-50 mb-2 d-block"></i>
                Journal vide. Cliquez sur « Nouvelle entrée ».
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableJournal">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>pH</th>
                            <th>Cl libre</th>
                            <th>Conformité ARS</th>
                            <th>Mesuré par</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($journal as $e): ?>
                        <tr>
                            <td data-sort="<?= date('Y-m-d H:i', strtotime($e['date_mesure'])) ?>">
                                <?= date('d/m/Y H:i', strtotime($e['date_mesure'])) ?>
                            </td>
                            <td><?= piscineTypeBadge($e['type_entree']) ?></td>
                            <td>
                                <?php if ($e['ph']): ?>
                                <?= $e['ph'] ?>
                                <?= piscineBadge(Piscine::checkPh($e['ph'])) ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($e['chlore_libre_mg_l']): ?>
                                <?= $e['chlore_libre_mg_l'] ?> mg/L
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($e['conformite_ars']): ?>
                                <?php $cb = ['conforme'=>'success','non_conforme'=>'danger','avertissement'=>'warning'][$e['conformite_ars']] ?? 'secondary'; ?>
                                <span class="badge bg-<?= $cb ?>"><?= $e['conformite_ars'] ?></span>
                                <?php if ($e['fichier_pv']): ?>
                                <a href="<?= BASE_URL ?>/maintenance/piscinePv/<?= (int)$e['id'] ?>" target="_blank" class="ms-1" title="Voir PV">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php endif; ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars(($e['mesure_par_prenom'] ?? '') . ' ' . ($e['mesure_par_nom'] ?? '')) ?: '—' ?></small></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/maintenance/piscineEntreeEdit/<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($isManager): ?>
                                <form method="POST" action="<?= BASE_URL ?>/maintenance/piscineEntreeDelete/<?= (int)$e['id'] ?>" class="d-inline"
                                      onsubmit="return confirm('Supprimer cette entrée ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($journal) > 10): ?>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoJournal" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationJournal"></ul>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aide-mémoire normes -->
    <div class="alert alert-light border small mb-0">
        <strong><i class="fas fa-book-medical me-1"></i>Normes piscine collective :</strong>
        pH idéal <strong>7.0–7.6</strong> ·
        Chlore libre <strong>1–3 mg/L</strong> (impératif > 0.5 mg/L sinon risque sanitaire) ·
        Alcalinité TAC <strong>80–120 mg/L</strong> ·
        Contrôle ARS <strong>obligatoire mensuellement</strong> en période d'ouverture ·
        Hivernage généralement octobre, mise en service avril-mai.
    </div>

    <?php endif; ?>
</div>

<!-- Modal nouvelle entrée -->
<?php if (!empty($residences)): ?>
<div class="modal fade" id="modalEntree" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/maintenance/piscineEntree" enctype="multipart/form-data">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouvelle entrée journal piscine</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="residence_id" value="<?= (int)($residenceCourante['id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type d'entrée <span class="text-danger">*</span></label>
                            <select name="type_entree" id="typeEntree" class="form-select" required>
                                <?php foreach (Piscine::TYPE_LABELS as $slug => $lbl): ?>
                                <option value="<?= $slug ?>"><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date et heure <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date_mesure" class="form-control" required value="<?= date('Y-m-d\\TH:i') ?>">
                        </div>
                    </div>

                    <!-- Champs analyse -->
                    <div id="bloc-analyse" class="mt-3">
                        <h6 class="text-info"><i class="fas fa-vial me-2"></i>Mesures chimiques</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">pH</label>
                                <input type="number" step="0.1" min="0" max="14" name="ph" class="form-control" placeholder="7.2">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Chlore libre (mg/L)</label>
                                <input type="number" step="0.01" min="0" name="chlore_libre_mg_l" class="form-control" placeholder="1.5">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Chlore total (mg/L)</label>
                                <input type="number" step="0.01" min="0" name="chlore_total_mg_l" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Température (°C)</label>
                                <input type="number" step="0.1" name="temperature" class="form-control" placeholder="28">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Alcalinité TAC (mg/L)</label>
                                <input type="number" min="0" name="alcalinite_mg_l" class="form-control" placeholder="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stabilisant (mg/L)</label>
                                <input type="number" min="0" name="stabilisant_mg_l" class="form-control" placeholder="40">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Produit utilisé</label>
                                <input type="text" name="produit_utilise" class="form-control" placeholder="Ex: Chlore choc">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantité (kg)</label>
                                <input type="number" step="0.01" min="0" name="quantite_produit_kg" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Champs contrôle ARS -->
                    <div id="bloc-ars" class="mt-3 d-none">
                        <h6 class="text-primary"><i class="fas fa-stamp me-2"></i>Contrôle ARS</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">N° PV</label>
                                <input type="text" name="numero_pv" class="form-control" placeholder="Ex: ARS-2026-04-001">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Conformité</label>
                                <select name="conformite_ars" class="form-select">
                                    <option value="">—</option>
                                    <option value="conforme">Conforme</option>
                                    <option value="avertissement">Avertissement</option>
                                    <option value="non_conforme">Non conforme</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fichier PV (PDF/image)</label>
                                <input type="file" name="fichier_pv" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    const sel = document.getElementById('typeEntree');
    const blocAnalyse = document.getElementById('bloc-analyse');
    const blocArs = document.getElementById('bloc-ars');
    function refresh() {
        const v = sel.value;
        blocArs.classList.toggle('d-none', v !== 'controle_ars');
        // Bloc analyse visible pour 'analyse' uniquement (mais champs disponibles pour les autres)
        blocAnalyse.classList.toggle('d-none', v === 'hivernage' || v === 'mise_en_service');
    }
    sel.addEventListener('change', refresh);
    refresh();
})();
</script>
<?php endif; ?>

<?php if (!empty($journal) && count($journal) > 10): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableJournal', {
    rowsPerPage: 10,
    searchInputId: 'searchJournal',
    paginationId: 'paginationJournal',
    infoId: 'infoJournal',
    excludeColumns: [6]
});
</script>
<?php endif; ?>
