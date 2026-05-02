<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-hammer',         'text' => 'Chantiers',       'url' => BASE_URL . '/chantier/index'],
    ['icon' => 'fas fa-eye',            'text' => $chantier['titre'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$badgePhase  = ['diagnostic'=>'secondary','cahier_charges'=>'secondary','devis'=>'info','decision'=>'warning','commande'=>'primary','execution'=>'warning','reception'=>'info','garantie'=>'success','cloture'=>'dark'];
$badgeStatut = ['actif'=>'success','suspendu'=>'warning','termine'=>'dark','annule'=>'danger'];
$badgePrio   = ['basse'=>'secondary','normale'=>'primary','haute'=>'warning','urgente'=>'danger'];
$badgeDevis  = ['recu'=>'secondary','analyse'=>'info','retenu'=>'success','refuse'=>'danger'];

$today = new DateTime();
?>

<div class="container-fluid py-4">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-hammer text-warning me-2"></i><?= htmlspecialchars($chantier['titre']) ?>
            </h1>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($chantier['residence_nom']) ?>
                <?php if ($chantier['specialite_nom']): ?>
                · <span class="badge" style="background:<?= htmlspecialchars($chantier['specialite_couleur']) ?>;color:#fff;font-size:0.75rem">
                    <i class="<?= htmlspecialchars($chantier['specialite_icone']) ?> me-1"></i><?= htmlspecialchars($chantier['specialite_nom']) ?>
                </span>
                <?php endif; ?>
                · <span class="badge bg-<?= $badgePrio[$chantier['priorite']] ?? 'secondary' ?>"><?= $chantier['priorite'] ?></span>
                · <span class="badge bg-<?= $badgeStatut[$chantier['statut']] ?? 'secondary' ?>"><?= $chantier['statut'] ?></span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($isManager): ?>
            <a href="<?= BASE_URL ?>/chantier/form/<?= (int)$chantier['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
            <form method="POST" action="<?= BASE_URL ?>/chantier/delete/<?= (int)$chantier['id'] ?>" class="d-inline"
                  onsubmit="return confirm('Supprimer ce chantier ? TOUTES les données associées (devis, jalons, documents, garanties) seront perdues.')">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Workflow phases -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <strong class="me-2">Phase :</strong>
                <?php foreach ($phases as $p):
                    $idx = array_search($p, $phases);
                    $current = array_search($chantier['phase'], $phases);
                    $cls = $idx < $current ? 'bg-success' : ($idx === $current ? 'bg-' . ($badgePhase[$p] ?? 'primary') : 'bg-light text-muted border');
                ?>
                    <span class="badge <?= $cls ?>" style="font-size:0.75rem"><?= htmlspecialchars($phasesLabels[$p] ?? $p) ?></span>
                    <?php if ($p !== end($phases)): ?><i class="fas fa-chevron-right text-muted small"></i><?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($isManager): ?>
            <hr class="my-3">
            <form method="POST" action="<?= BASE_URL ?>/chantier/phase/<?= (int)$chantier['id'] ?>" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <label class="small text-muted">Changer la phase :</label>
                <select name="phase" class="form-select form-select-sm w-auto">
                    <?php foreach ($phases as $p): ?>
                    <option value="<?= $p ?>" <?= $chantier['phase'] === $p ? 'selected' : '' ?>><?= htmlspecialchars($phasesLabels[$p] ?? $p) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-arrow-right me-1"></i>Appliquer</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Détails -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Détails</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Catégorie</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($chantier['categorie']) ?></dd>
                        <?php if (!empty($chantier['description'])): ?>
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9"><?= nl2br(htmlspecialchars($chantier['description'])) ?></dd>
                        <?php endif; ?>
                        <dt class="col-sm-3">Dates prévues</dt>
                        <dd class="col-sm-9">
                            <?= !empty($chantier['date_debut_prevue']) ? date('d/m/Y', strtotime($chantier['date_debut_prevue'])) : '—' ?>
                            →
                            <?= !empty($chantier['date_fin_prevue']) ? date('d/m/Y', strtotime($chantier['date_fin_prevue'])) : '—' ?>
                        </dd>
                        <?php if (!empty($chantier['date_debut_reelle']) || !empty($chantier['date_fin_reelle'])): ?>
                        <dt class="col-sm-3">Dates réelles</dt>
                        <dd class="col-sm-9">
                            <?= !empty($chantier['date_debut_reelle']) ? date('d/m/Y', strtotime($chantier['date_debut_reelle'])) : '—' ?>
                            →
                            <?= !empty($chantier['date_fin_reelle']) ? date('d/m/Y', strtotime($chantier['date_fin_reelle'])) : '—' ?>
                        </dd>
                        <?php endif; ?>
                        <?php if (!empty($chantier['notes'])): ?>
                        <dt class="col-sm-3">Notes</dt>
                        <dd class="col-sm-9"><?= nl2br(htmlspecialchars($chantier['notes'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Sidebar : Budget + AG -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-euro-sign me-2"></i>Budget</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1"><span>Estimé HT</span><strong><?= $chantier['montant_estime'] ? number_format((float)$chantier['montant_estime'], 0, ',', ' ') . ' €' : '—' ?></strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>Engagé</span><strong class="text-warning"><?= number_format((float)$chantier['montant_engage'], 0, ',', ' ') ?> €</strong></div>
                    <div class="d-flex justify-content-between"><span>Payé</span><strong class="text-success"><?= number_format((float)$chantier['montant_paye'], 0, ',', ' ') ?> €</strong></div>
                    <?php if ($chantier['montant_estime'] > 0):
                        $pctEng = min(100, round(($chantier['montant_engage'] / $chantier['montant_estime']) * 100));
                        $pctPaye = min(100, round(($chantier['montant_paye'] / $chantier['montant_estime']) * 100));
                    ?>
                    <hr>
                    <div class="small text-muted mb-1">Progression budget</div>
                    <div class="progress" style="height:14px">
                        <div class="progress-bar bg-success" style="width:<?= $pctPaye ?>%" title="Payé"><?= $pctPaye ?>%</div>
                        <div class="progress-bar bg-warning" style="width:<?= max(0, $pctEng - $pctPaye) ?>%" title="Engagé"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong>⚖️ Vote AG</strong></div>
                <div class="card-body">
                    <?php if ($chantier['necessite_ag']): ?>
                        <?php if ($chantier['ag_id']): ?>
                            <div class="alert alert-success py-2 mb-0 small">
                                <i class="fas fa-check-circle me-1"></i>
                                AG <?= htmlspecialchars($chantier['ag_type']) ?> du <strong><?= date('d/m/Y', strtotime($chantier['date_ag'])) ?></strong>
                                <br><span class="badge bg-secondary mt-1"><?= htmlspecialchars($chantier['ag_statut']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 mb-0 small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Vote AG requis (montant > 5 000 € HT) — aucune AG associée.
                                Modifier le chantier pour lier une AG.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <small class="text-muted">Vote AG non requis.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lots impactés / Quote-part propriétaires -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-home me-2"></i>Lots impactés &amp; quote-part propriétaires</strong>
            <?php if ($isManager): ?>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalLots">
                <i class="fas fa-edit me-1"></i>Configurer
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($lotsImpactes)): ?>
            <div class="text-center py-3 text-muted small">Aucun lot impacté défini.</div>
            <?php else: ?>
            <div class="row g-0">
                <div class="col-md-6">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Lot</th><th>Type</th><th class="text-end">Quote-part</th></tr></thead>
                        <tbody>
                            <?php foreach ($lotsImpactes as $li): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($li['numero_lot']) ?></strong></td>
                                <td><small><?= htmlspecialchars($li['lot_type']) ?></small></td>
                                <td class="text-end"><strong><?= number_format((float)$li['quote_part_pourcentage'], 2, ',', ' ') ?> %</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 border-start">
                    <div class="p-2"><strong class="small text-muted">Coût par propriétaire :</strong></div>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Propriétaire</th><th>Lots</th><th class="text-end">Quote-part</th><th class="text-end">Coût estimé</th></tr></thead>
                        <tbody>
                            <?php foreach ($quoteParts as $qp):
                                $cout = $chantier['montant_estime'] ? round(((float)$qp['quote_part_totale'] / 100) * (float)$chantier['montant_estime'], 2) : 0;
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($qp['proprietaire_prenom'] . ' ' . $qp['proprietaire_nom']) ?></strong></td>
                                <td><small><?= htmlspecialchars($qp['lots']) ?></small></td>
                                <td class="text-end"><?= number_format((float)$qp['quote_part_totale'], 2, ',', ' ') ?> %</td>
                                <td class="text-end"><strong><?= number_format($cout, 2, ',', ' ') ?> €</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Devis -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-file-invoice-dollar me-2"></i>Devis (<?= count($devis) ?>)</strong>
            <?php if ($isManager): ?>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalDevis">
                <i class="fas fa-plus me-1"></i>Ajouter un devis
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($devis)): ?>
            <div class="text-center py-3 text-muted small">Aucun devis.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Fournisseur</th><th>Réf</th><th>Date</th><th>Validité</th><th class="text-end">HT</th><th class="text-end">TTC</th><th>Délai</th><th>Statut</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devis as $d): ?>
                        <tr class="<?= $d['statut'] === 'retenu' ? 'table-success' : ($d['statut'] === 'refuse' ? 'text-muted' : '') ?>">
                            <td><strong><?= htmlspecialchars($d['fournisseur_nom']) ?></strong></td>
                            <td><small><?= htmlspecialchars($d['reference'] ?? '—') ?></small></td>
                            <td><small><?= date('d/m/Y', strtotime($d['date_devis'])) ?></small></td>
                            <td><small><?= !empty($d['date_validite']) ? date('d/m/Y', strtotime($d['date_validite'])) : '—' ?></small></td>
                            <td class="text-end"><?= number_format((float)$d['montant_ht'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><strong><?= number_format((float)$d['montant_ttc'], 2, ',', ' ') ?> €</strong></td>
                            <td><small><?= $d['delai_execution_jours'] ? (int)$d['delai_execution_jours'] . 'j' : '—' ?></small></td>
                            <td><span class="badge bg-<?= $badgeDevis[$d['statut']] ?? 'secondary' ?>"><?= $d['statut'] ?></span></td>
                            <td class="text-center">
                                <?php if ($isManager && $d['statut'] !== 'retenu'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/chantier/devisRetenir/<?= (int)$d['id'] ?>" class="d-inline" onsubmit="return confirm('Retenir ce devis et refuser les autres ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Retenir"><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($isManager): ?>
                                <form method="POST" action="<?= BASE_URL ?>/chantier/devisDelete/<?= (int)$d['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce devis ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Jalons -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-flag me-2"></i>Jalons (<?= count($jalons) ?>)</strong>
            <?php if ($isManager): ?>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalJalon">
                <i class="fas fa-plus me-1"></i>Ajouter un jalon
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($jalons)): ?>
            <div class="text-center py-3 text-muted small">Aucun jalon défini.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Nom</th><th>Date prévue</th><th>Date réalisée</th><th>Avancement</th><th class="text-center">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($jalons as $j): ?>
                        <tr>
                            <td><?= (int)$j['ordre'] ?></td>
                            <td><strong><?= htmlspecialchars($j['nom']) ?></strong>
                                <?php if ($j['description']): ?><br><small class="text-muted"><?= htmlspecialchars($j['description']) ?></small><?php endif; ?>
                            </td>
                            <td><small><?= !empty($j['date_prevue']) ? date('d/m/Y', strtotime($j['date_prevue'])) : '—' ?></small></td>
                            <td><small><?= !empty($j['date_realisee']) ? date('d/m/Y', strtotime($j['date_realisee'])) : '—' ?></small></td>
                            <td>
                                <div class="progress" style="height:18px">
                                    <div class="progress-bar bg-info" style="width:<?= (int)$j['pourcentage_avancement'] ?>%"><?= (int)$j['pourcentage_avancement'] ?>%</div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($isManager): ?>
                                <form method="POST" action="<?= BASE_URL ?>/chantier/jalonDelete/<?= (int)$j['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce jalon ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Documents -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-folder me-2"></i>Documents (<?= count($documents) ?>)</strong>
            <?php if ($isManager): ?>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalDocument">
                <i class="fas fa-upload me-1"></i>Uploader
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($documents)): ?>
            <div class="text-center py-3 text-muted small">Aucun document.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Nom</th><th>Type</th><th>Taille</th><th>Uploader</th><th>Date</th><th class="text-center">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($documents as $doc):
                            $taille = $doc['taille_octets'];
                            $tailleStr = $taille < 1024 ? "$taille o" : ($taille < 1048576 ? round($taille/1024,1) . ' Ko' : round($taille/1048576,1) . ' Mo');
                        ?>
                        <tr>
                            <td><i class="fas fa-file me-1 text-muted"></i><?= htmlspecialchars($doc['nom_fichier']) ?>
                                <?php if ($doc['description']): ?><br><small class="text-muted"><?= htmlspecialchars($doc['description']) ?></small><?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= $doc['type'] ?></span></td>
                            <td><small><?= $tailleStr ?></small></td>
                            <td><small><?= htmlspecialchars(($doc['uploader_prenom'] ?? '') . ' ' . ($doc['uploader_nom'] ?? '')) ?: '—' ?></small></td>
                            <td><small><?= date('d/m/Y H:i', strtotime($doc['uploaded_at'])) ?></small></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/chantier/documentDownload/<?= (int)$doc['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($isManager): ?>
                                <form method="POST" action="<?= BASE_URL ?>/chantier/documentDelete/<?= (int)$doc['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce document ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Réception -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-stamp me-2"></i>Réception (<?= count($receptions) ?>)</strong>
            <?php if ($isManager && empty($receptions)): ?>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalReception">
                <i class="fas fa-check me-1"></i>Procéder à la réception
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($receptions)): ?>
            <small class="text-muted">Pas encore de réception. À effectuer en fin d'exécution. Les 3 garanties seront créées automatiquement.</small>
            <?php else: foreach ($receptions as $r): ?>
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>Réception du <?= date('d/m/Y', strtotime($r['date_reception'])) ?></strong>
                        <?php if ($r['avec_reserves']): ?>
                            <span class="badge bg-warning text-dark ms-1">Avec réserves</span>
                            <?php if ($r['reserves_levees']): ?>
                                <span class="badge bg-success ms-1">Réserves levées le <?= date('d/m/Y', strtotime($r['reserves_levees'])) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-success ms-1">Sans réserves</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($r['signataire_prenom']): ?>
                    <small class="text-muted">Signé par <?= htmlspecialchars($r['signataire_prenom'] . ' ' . $r['signataire_nom']) ?></small>
                    <?php endif; ?>
                </div>
                <?php if ($r['reserves_description']): ?>
                <hr class="my-2">
                <small class="text-muted"><strong>Réserves :</strong> <?= nl2br(htmlspecialchars($r['reserves_description'])) ?></small>
                <?php if ($isManager && !$r['reserves_levees']): ?>
                <form method="POST" action="<?= BASE_URL ?>/chantier/reserveLevee/<?= (int)$r['id'] ?>" class="d-flex gap-2 mt-2 align-items-center">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                    <input type="date" name="date_levee" class="form-control form-control-sm w-auto" value="<?= date('Y-m-d') ?>">
                    <button type="submit" class="btn btn-sm btn-success">Lever les réserves</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Garanties -->
    <?php if (!empty($garanties)): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light"><strong><i class="fas fa-shield-alt me-2"></i>Garanties</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Type</th><th>Période</th><th>Fournisseur</th><th>État</th></tr></thead>
                <tbody>
                    <?php foreach ($garanties as $g):
                        $fin = new DateTime($g['date_fin']);
                        $diff = (int)$today->diff($fin)->format('%r%a');
                        $expirée = $diff < 0;
                        $proche = $diff >= 0 && $diff <= 180;
                    ?>
                    <tr class="<?= $expirée ? 'text-muted' : ($proche ? 'table-warning' : '') ?>">
                        <td><strong><?= htmlspecialchars(str_replace('_', ' ', $g['type'])) ?></strong></td>
                        <td><small><?= date('d/m/Y', strtotime($g['date_debut'])) ?> → <?= date('d/m/Y', strtotime($g['date_fin'])) ?></small></td>
                        <td><small><?= htmlspecialchars($g['fournisseur_nom'] ?? '—') ?></small></td>
                        <td>
                            <?php if ($expirée): ?>
                                <span class="badge bg-secondary">Expirée</span>
                            <?php elseif ($proche): ?>
                                <span class="badge bg-warning text-dark">Expire dans <?= $diff ?>j</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ─── MODALS ─────────────────────────────────────────────── -->

<!-- Modal lots impactés -->
<?php if ($isManager): ?>
<div class="modal fade" id="modalLots" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/chantier/lotsImpactes">
                <div class="modal-header bg-warning"><h5 class="modal-title">Lots impactés</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                    <p class="small text-muted">Cocher les lots impactés et saisir leur quote-part en %. Total habituellement 100%.</p>
                    <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                        <table class="table table-sm">
                            <thead class="table-light"><tr><th></th><th>Lot</th><th>Type</th><th class="text-end">Quote-part %</th></tr></thead>
                            <tbody>
                                <?php foreach ($lotsResidence as $idx => $l):
                                    $existant = null;
                                    foreach ($lotsImpactes as $li) {
                                        if ((int)$li['lot_id'] === (int)$l['id']) { $existant = $li; break; }
                                    }
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="lots[<?= $idx ?>][lot_id]" value="<?= (int)$l['id'] ?>" <?= $existant ? 'checked' : '' ?> class="form-check-input"></td>
                                    <td><?= htmlspecialchars($l['numero_lot']) ?></td>
                                    <td><small><?= htmlspecialchars($l['type']) ?></small></td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="lots[<?= $idx ?>][quote_part_pourcentage]" class="form-control form-control-sm text-end" value="<?= $existant ? (float)$existant['quote_part_pourcentage'] : '' ?>"></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal devis -->
<div class="modal fade" id="modalDevis" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/chantier/devisCreate">
                <div class="modal-header bg-warning"><h5 class="modal-title">Nouveau devis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                            <select name="fournisseur_id" class="form-select" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Référence</label><input type="text" name="reference" class="form-control" maxlength="100"></div>
                        <div class="col-md-4"><label class="form-label">Date du devis <span class="text-danger">*</span></label><input type="date" name="date_devis" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Validité jusqu'au</label><input type="date" name="date_validite" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Délai (jours)</label><input type="number" min="0" name="delai_execution_jours" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Montant HT <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" name="montant_ht" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">TVA %</label><input type="number" step="0.01" min="0" name="tva_pourcentage" class="form-control" value="20.00"></div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal jalon -->
<div class="modal fade" id="modalJalon" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/chantier/jalonSave">
                <div class="modal-header bg-warning"><h5 class="modal-title">Nouveau jalon</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Nom <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control" required maxlength="200" placeholder="Ex: Démolition"></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="col-md-4"><label class="form-label">Ordre</label><input type="number" min="0" name="ordre" class="form-control" value="<?= count($jalons) + 1 ?>"></div>
                        <div class="col-md-4"><label class="form-label">Date prévue</label><input type="date" name="date_prevue" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">% avancement</label><input type="number" min="0" max="100" name="pourcentage_avancement" class="form-control" value="0"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal document -->
<div class="modal fade" id="modalDocument" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/chantier/documentUpload" enctype="multipart/form-data">
                <div class="modal-header bg-warning"><h5 class="modal-title">Uploader un document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <?php foreach ($typesDoc as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier <span class="text-danger">*</span></label>
                        <input type="file" name="fichier" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.dwg,.zip">
                        <small class="text-muted">PDF, images, Office, DWG, ZIP — max 50 Mo</small>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Uploader</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal réception -->
<div class="modal fade" id="modalReception" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/chantier/receptionCreate">
                <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-stamp me-2"></i>Réception du chantier</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="chantier_id" value="<?= (int)$chantier['id'] ?>">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        À la validation, les <strong>3 garanties seront créées automatiquement</strong> (parfait achèvement 1 an, biennale 2 ans, décennale 10 ans) et la phase passera à <strong>« garantie »</strong>.
                    </div>
                    <div class="mb-3"><label class="form-label">Date de réception <span class="text-danger">*</span></label><input type="date" name="date_reception" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                    <?php if (!empty($fournisseurs)): ?>
                    <div class="mb-3">
                        <label class="form-label">Fournisseur (pour les garanties)</label>
                        <select name="fournisseur_id" class="form-select">
                            <option value="">— Aucun —</option>
                            <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="avec_reserves" id="avecReserves" value="1" class="form-check-input">
                        <label for="avecReserves" class="form-check-label">Réception avec réserves</label>
                    </div>
                    <div class="mb-3"><label class="form-label">Description des réserves (si applicable)</label><textarea name="reserves_description" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Procéder à la réception</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
