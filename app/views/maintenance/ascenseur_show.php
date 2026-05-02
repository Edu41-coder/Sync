<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-elevator',       'text' => 'Ascenseurs',      'url' => BASE_URL . '/maintenance/ascenseurs?residence_id=' . (int)$ascenseur['residence_id']],
    ['icon' => 'fas fa-eye',            'text' => $ascenseur['nom'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$badgeStatut = ['actif'=>'success','hors_service'=>'warning','depose'=>'dark'];
$badgeConf   = ['conforme'=>'success','non_conforme'=>'danger','avec_reserves'=>'warning'];

function ascTypeBadge(string $type): string {
    $cfg = [
        'maintenance_preventive' => ['Maintenance préventive', 'info'],
        'visite_annuelle'        => ['Visite annuelle',        'primary'],
        'controle_quinquennal'   => ['Contrôle quinquennal',   'danger'],
        'panne'                  => ['Panne',                  'warning'],
        'intervention'           => ['Intervention',           'secondary'],
        'autre'                  => ['Autre',                  'dark'],
    ];
    [$lbl, $color] = $cfg[$type] ?? ['?', 'secondary'];
    return '<span class="badge bg-' . $color . '">' . $lbl . '</span>';
}
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-elevator me-2 text-secondary"></i>
                <?= htmlspecialchars($ascenseur['nom']) ?>
                <span class="badge bg-<?= $badgeStatut[$ascenseur['statut']] ?? 'secondary' ?>"><?= $ascenseur['statut'] ?></span>
            </h1>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($ascenseur['residence_nom']) ?>
                <?php if (!empty($ascenseur['emplacement'])): ?>· <?= htmlspecialchars($ascenseur['emplacement']) ?><?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEntree">
                <i class="fas fa-plus me-1"></i>Nouvelle entrée journal
            </button>
            <?php if ($isManager): ?>
            <a href="<?= BASE_URL ?>/maintenance/ascenseurForm/<?= (int)$ascenseur['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
            <form method="POST" action="<?= BASE_URL ?>/maintenance/ascenseurDelete/<?= (int)$ascenseur['id'] ?>" class="d-inline"
                  onsubmit="return confirm('Supprimer cet ascenseur ? Toutes les entrées de journal seront aussi supprimées.')">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Détails -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-cogs me-2"></i>Caractéristiques</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-5">Marque</dt>
                                <dd class="col-7"><?= htmlspecialchars(ucfirst($ascenseur['marque'])) ?></dd>
                                <?php if (!empty($ascenseur['modele'])): ?>
                                <dt class="col-5">Modèle</dt>
                                <dd class="col-7"><?= htmlspecialchars($ascenseur['modele']) ?></dd>
                                <?php endif; ?>
                                <?php if (!empty($ascenseur['numero_serie'])): ?>
                                <dt class="col-5">N° série</dt>
                                <dd class="col-7"><?= htmlspecialchars($ascenseur['numero_serie']) ?></dd>
                                <?php endif; ?>
                                <?php if (!empty($ascenseur['date_mise_service'])): ?>
                                <dt class="col-5">Mise en service</dt>
                                <dd class="col-7"><?= date('d/m/Y', strtotime($ascenseur['date_mise_service'])) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <?php if ($ascenseur['capacite_personnes']): ?>
                                <dt class="col-5">Capacité</dt>
                                <dd class="col-7"><?= (int)$ascenseur['capacite_personnes'] ?> personnes
                                    <?php if ($ascenseur['capacite_kg']): ?>(<?= (int)$ascenseur['capacite_kg'] ?> kg)<?php endif; ?>
                                </dd>
                                <?php endif; ?>
                                <?php if ($ascenseur['nombre_etages']): ?>
                                <dt class="col-5">Nb étages</dt>
                                <dd class="col-7"><?= (int)$ascenseur['nombre_etages'] ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>
                    <?php if (!empty($ascenseur['notes'])): ?>
                    <hr>
                    <div class="small text-muted"><?= nl2br(htmlspecialchars($ascenseur['notes'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Journal -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-book me-2"></i>Journal de bord (<?= count($journal) ?> entrées)</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($journal)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-book fa-2x opacity-50 mb-2 d-block"></i>
                        Journal vide. Cliquez sur « Nouvelle entrée journal ».
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Organisme</th>
                                    <th>Conformité</th>
                                    <th>Prochaine échéance</th>
                                    <th class="text-end">Coût</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($journal as $e): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($e['date_event'])) ?></td>
                                    <td><?= ascTypeBadge($e['type_entree']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($e['organisme'] ?? '—') ?>
                                        <?php if (!empty($e['technicien_intervenant'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($e['technicien_intervenant']) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($e['intervention_id'])): ?>
                                        <br><a href="<?= BASE_URL ?>/maintenance/interventionShow/<?= (int)$e['intervention_id'] ?>" class="small">
                                            <i class="fas fa-link me-1"></i>Intervention #<?= (int)$e['intervention_id'] ?>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($e['conformite']): ?>
                                        <span class="badge bg-<?= $badgeConf[$e['conformite']] ?? 'secondary' ?>"><?= $e['conformite'] ?></span>
                                        <?php if (!empty($e['fichier_pv'])): ?>
                                        <a href="<?= BASE_URL ?>/maintenance/ascenseurPv/<?= (int)$e['id'] ?>" target="_blank" class="ms-1" title="Voir PV">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!empty($e['numero_pv'])): ?>
                                        <br><small class="text-muted">PV <?= htmlspecialchars($e['numero_pv']) ?></small>
                                        <?php endif; ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td>
                                        <?= !empty($e['prochaine_echeance']) ? date('d/m/Y', strtotime($e['prochaine_echeance'])) : '—' ?>
                                    </td>
                                    <td class="text-end">
                                        <?= $e['cout'] ? number_format((float)$e['cout'], 2, ',', ' ') . ' €' : '—' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/maintenance/ascenseurEntreeEdit/<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($isManager): ?>
                                        <form method="POST" action="<?= BASE_URL ?>/maintenance/ascenseurEntreeDelete/<?= (int)$e['id'] ?>" class="d-inline"
                                              onsubmit="return confirm('Supprimer cette entrée ?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if (!empty($e['observations'])): ?>
                                <tr>
                                    <td colspan="7" class="bg-light small">
                                        <em><?= nl2br(htmlspecialchars($e['observations'])) ?></em>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Contrat ascensoriste -->
            <?php if (!empty($ascenseur['contrat_ascensoriste_nom'])): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-file-contract me-2"></i>Contrat ascensoriste</strong></div>
                <div class="card-body small">
                    <div class="mb-1"><strong><?= htmlspecialchars($ascenseur['contrat_ascensoriste_nom']) ?></strong></div>
                    <?php if (!empty($ascenseur['contrat_numero'])): ?>
                    <div class="text-muted">N° <?= htmlspecialchars($ascenseur['contrat_numero']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ascenseur['contrat_ascensoriste_tel'])): ?>
                    <div><i class="fas fa-phone text-info me-1"></i><a href="tel:<?= htmlspecialchars($ascenseur['contrat_ascensoriste_tel']) ?>"><?= htmlspecialchars($ascenseur['contrat_ascensoriste_tel']) ?></a></div>
                    <?php endif; ?>
                    <?php if (!empty($ascenseur['contrat_ascensoriste_email'])): ?>
                    <div><i class="fas fa-envelope text-info me-1"></i><a href="mailto:<?= htmlspecialchars($ascenseur['contrat_ascensoriste_email']) ?>"><?= htmlspecialchars($ascenseur['contrat_ascensoriste_email']) ?></a></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-chart-bar me-2"></i>Statistiques</strong></div>
                <div class="card-body small">
                    <div class="d-flex justify-content-between"><span>Entrées journal</span><strong><?= (int)$stats['nb_entrees'] ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Dernière intervention</span><strong><?= $stats['derniere_pv'] ? date('d/m/Y', strtotime($stats['derniere_pv'])) : '—' ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Coût annuel</span><strong><?= number_format($stats['cout_total_an'], 2, ',', ' ') ?> €</strong></div>
                </div>
            </div>

            <!-- Aide-mémoire -->
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Réglementation</strong></div>
                <div class="card-body small">
                    <ul class="mb-0 ps-3">
                        <li><strong>Maintenance préventive</strong> : recommandée mensuelle</li>
                        <li><strong>Visite annuelle</strong> : 1×/an obligatoire (ascensoriste)</li>
                        <li><strong>Contrôle quinquennal</strong> : tous les 5 ans, par <em>bureau de contrôle agréé indépendant</em> (APAVE, Veritas, Socotec…)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal nouvelle entrée -->
<div class="modal fade" id="modalEntree" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/maintenance/ascenseurEntree" enctype="multipart/form-data">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouvelle entrée — <?= htmlspecialchars($ascenseur['nom']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="ascenseur_id" value="<?= (int)$ascenseur['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type d'entrée <span class="text-danger">*</span></label>
                            <select name="type_entree" id="ascTypeEntree" class="form-select" required>
                                <?php foreach (Ascenseur::TYPES_JOURNAL as $slug => $lbl): ?>
                                <option value="<?= $slug ?>"><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">L'échéance suivante sera auto-calculée pour les types récurrents.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date_event" class="form-control" required value="<?= date('Y-m-d\\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Organisme</label>
                            <input type="text" name="organisme" class="form-control" placeholder="Ex: APAVE, ascensoriste...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Technicien intervenant</label>
                            <input type="text" name="technicien_intervenant" class="form-control">
                        </div>

                        <!-- Conformité (visite annuelle / contrôle quinquennal) -->
                        <div class="col-12" id="ascBlocConformite">
                            <hr>
                            <h6 class="text-primary"><i class="fas fa-stamp me-1"></i>Conformité</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">N° PV</label>
                                    <input type="text" name="numero_pv" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Conformité</label>
                                    <select name="conformite" class="form-select">
                                        <option value="">—</option>
                                        <option value="conforme">Conforme</option>
                                        <option value="avec_reserves">Avec réserves</option>
                                        <option value="non_conforme">Non conforme</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fichier PV (PDF/image)</label>
                                    <input type="file" name="fichier_pv" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Prochaine échéance</label>
                            <input type="date" name="prochaine_echeance" class="form-control">
                            <small class="text-muted">Auto-calculée selon le type. Modifiable.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Coût (€)</label>
                            <input type="number" step="0.01" min="0" name="cout" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observations</label>
                            <textarea name="observations" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    const sel = document.getElementById('ascTypeEntree');
    const blocConf = document.getElementById('ascBlocConformite');
    function refresh() {
        const v = sel.value;
        // Afficher conformité pour visite annuelle, contrôle quinquennal, autre
        blocConf.classList.toggle('d-none', !['visite_annuelle','controle_quinquennal','autre'].includes(v));
    }
    sel.addEventListener('change', refresh);
    refresh();
})();
</script>
