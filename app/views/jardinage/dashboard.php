<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeMouvementLabels = ['entree' => 'Entrée', 'sortie' => 'Sortie', 'ajustement' => 'Ajustement'];
$typeMouvementColors = ['entree' => 'success', 'sortie' => 'warning', 'ajustement' => 'info'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-seedling me-2 text-success"></i>Jardinage</h2>
        <?php if (count($residences) > 1): ?>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="text-muted small mb-0">Résidence :</label>
            <select name="residence_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="0">Toutes</option>
                <?php foreach ($residences as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['nom']) ?><?= !empty($r['ruches']) ? ' 🐝' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <!-- Stats principales -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Espaces jardin</h6>
                            <h3 class="mb-0"><?= $stats['espaces'] ?></h3>
                        </div>
                        <div class="text-success fs-2"><i class="fas fa-tree"></i></div>
                    </div>
                    <small class="text-muted"><?= $stats['espaces_ruchers'] ?> rucher(s)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Catalogue</h6>
                            <h3 class="mb-0"><?= $stats['produits'] + $stats['outils'] ?></h3>
                        </div>
                        <div class="text-info fs-2"><i class="fas fa-boxes-stacked"></i></div>
                    </div>
                    <small class="text-muted"><?= $stats['produits'] ?> produit(s) | <?= $stats['outils'] ?> outil(s)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Ruches actives</h6>
                            <h3 class="mb-0 text-warning"><?= $stats['ruches_actives'] ?></h3>
                        </div>
                        <div class="text-warning fs-2">🐝</div>
                    </div>
                    <small class="text-muted"><?= $stats['visites_mois'] ?> visite(s) / 30j · <?= number_format($stats['miel_annee_kg'], 1, ',', ' ') ?> kg miel <?= date('Y') ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Alertes stock</h6>
                            <h3 class="mb-0"><?= $stats['alertes_stock'] ?></h3>
                        </div>
                        <div class="text-danger fs-2"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                    <small class="text-muted">Produits sous seuil</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Accès rapides -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>/jardinage/espaces<?= $selectedResidence ? '?residence_id='.$selectedResidence : '' ?>" class="card shadow-sm text-decoration-none h-100 border-0" style="background-color:#d4edda">
                <div class="card-body text-center py-4">
                    <i class="fas fa-tree fa-2x text-success mb-2"></i>
                    <h6 class="mb-0 text-dark">Espaces jardin</h6>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>/jardinage/inventaire<?= $selectedResidence ? '?residence_id='.$selectedResidence : '' ?>" class="card shadow-sm text-decoration-none h-100 border-0" style="background-color:#cfe2ff">
                <div class="card-body text-center py-4">
                    <i class="fas fa-boxes-stacked fa-2x text-primary mb-2"></i>
                    <h6 class="mb-0 text-dark">Inventaire</h6>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>/jardinage/planning" class="card shadow-sm text-decoration-none h-100 border-0" style="background-color:#fff3cd">
                <div class="card-body text-center py-4">
                    <i class="fas fa-calendar-alt fa-2x text-warning mb-2"></i>
                    <h6 class="mb-0 text-dark">Planning</h6>
                </div>
            </a>
        </div>
        <?php if ($isManager): ?>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>/jardinage/produits" class="card shadow-sm text-decoration-none h-100 border-0" style="background-color:#e2d9f3">
                <div class="card-body text-center py-4">
                    <i class="fas fa-book-open fa-2x text-purple mb-2" style="color:#6f42c1"></i>
                    <h6 class="mb-0 text-dark">Catalogue</h6>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (!empty($hasRuches)): ?>
        <div class="col-md-6 col-lg-3">
            <a href="<?= BASE_URL ?>/jardinage/ruches<?= $selectedResidence ? '?residence_id='.$selectedResidence : '' ?>" class="card shadow-sm text-decoration-none h-100 border-0" style="background-color:#fff3cd">
                <div class="card-body text-center py-4">
                    <div style="font-size:2rem" class="mb-2">🐝</div>
                    <h6 class="mb-0 text-dark">Ruches <span class="badge bg-warning text-dark"><?= (int)$stats['ruches_actives'] ?></span></h6>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($ruchesSansVisite)): ?>
    <div class="alert alert-warning d-flex align-items-start">
        <i class="fas fa-exclamation-triangle fa-lg me-3 mt-1"></i>
        <div class="flex-grow-1">
            <strong><?= count($ruchesSansVisite) ?> ruche(s) active(s) sans visite depuis &gt; 30 jours.</strong>
            <div class="small mt-2">
                <?php foreach (array_slice($ruchesSansVisite, 0, 8) as $r): ?>
                <a href="<?= BASE_URL ?>/jardinage/ruches/show/<?= $r['id'] ?>" class="badge bg-light text-dark text-decoration-none me-1 mb-1 border">
                    🐝 <?= htmlspecialchars($r['numero']) ?> · <?= htmlspecialchars($r['residence_nom']) ?>
                    · <?= $r['derniere_visite'] ? 'dernière ' . date('d/m/Y', strtotime($r['derniere_visite'])) : 'jamais visitée' ?>
                </a>
                <?php endforeach; ?>
                <?php if (count($ruchesSansVisite) > 8): ?>
                <span class="text-muted">+ <?= count($ruchesSansVisite) - 8 ?> autre(s)…</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($alertesTraitements)):
        $priorColors = [1 => 'danger', 2 => 'warning', 3 => 'info'];
        $priorLabels = [1 => 'Critique', 2 => 'Recommandé', 3 => 'Optionnel'];
    ?>
    <div class="alert alert-danger d-flex align-items-start">
        <i class="fas fa-shield-virus fa-lg me-3 mt-1"></i>
        <div class="flex-grow-1">
            <strong>🚨 <?= count($alertesTraitements) ?> traitement(s) apicole(s) obligatoire(s) à effectuer cette période.</strong>
            <?php if ($isManager): ?>
            <a href="<?= BASE_URL ?>/jardinage/traitements" class="btn btn-sm btn-outline-danger ms-2"><i class="fas fa-list me-1"></i>Voir tout</a>
            <?php endif; ?>
            <div class="small mt-2">
                <?php foreach (array_slice($alertesTraitements, 0, 10) as $a): ?>
                <a href="<?= BASE_URL ?>/jardinage/ruches/show/<?= (int)$a['ruche_id'] ?>" class="badge bg-<?= $priorColors[$a['priorite']] ?> text-decoration-none me-1 mb-1">
                    <?= htmlspecialchars($a['traitement_nom']) ?> · 🐝 <?= htmlspecialchars($a['ruche_numero']) ?>
                    <?php if (!$selectedResidence): ?> · <?= htmlspecialchars($a['residence_nom']) ?><?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php if (count($alertesTraitements) > 10): ?>
                <span class="text-muted">+ <?= count($alertesTraitements) - 10 ?> autre(s)…</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Alertes stock -->
        <?php if ($isManager): ?>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertes stock</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($alertesStock)): ?>
                    <p class="text-center text-muted p-4 mb-0"><i class="fas fa-check-circle me-2"></i>Aucune alerte stock.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Produit</th><th>Résidence</th><th class="text-end">Stock</th><th class="text-end">Seuil</th></tr></thead>
                        <tbody>
                            <?php foreach ($alertesStock as $a): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($a['produit_nom']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($a['categorie']) ?></small></td>
                                <td><small><?= htmlspecialchars($a['residence_nom']) ?></small></td>
                                <td class="text-end text-danger"><strong><?= number_format($a['quantite_actuelle'], 2, ',', ' ') ?></strong> <?= htmlspecialchars($a['unite']) ?></td>
                                <td class="text-end text-muted"><?= number_format($a['seuil_alerte'], 2, ',', ' ') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mouvements récents -->
        <div class="col-lg-<?= $isManager ? '6' : '12' ?>">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Mouvements récents</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mouvements)): ?>
                    <p class="text-center text-muted p-4 mb-0">Aucun mouvement.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Produit</th><th>Type</th><th class="text-end">Qté</th><th>Espace</th><th>Par</th></tr></thead>
                        <tbody>
                            <?php foreach ($mouvements as $m): ?>
                            <tr>
                                <td class="small text-muted"><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($m['produit_nom']) ?></strong></td>
                                <td><span class="badge bg-<?= $typeMouvementColors[$m['type_mouvement']] ?? 'secondary' ?>"><?= $typeMouvementLabels[$m['type_mouvement']] ?? $m['type_mouvement'] ?></span></td>
                                <td class="text-end"><?= number_format($m['quantite'], 2, ',', ' ') ?> <?= htmlspecialchars($m['unite']) ?></td>
                                <td class="small"><?= $m['espace_nom'] ? htmlspecialchars($m['espace_nom']) : '—' ?></td>
                                <td class="small"><?= $m['user_prenom'] ? htmlspecialchars($m['user_prenom'].' '.$m['user_nom']) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Contacter -->
    <?php if (!empty($contactsRapides)): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Contacter rapidement</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($contactsRapides as $c):
                    $initiales = mb_strtoupper(mb_substr($c['prenom'] ?? '', 0, 1) . mb_substr($c['nom'] ?? '', 0, 1));
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100 d-flex align-items-center">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                             style="width:48px;height:48px;background-color:<?= htmlspecialchars($c['role_couleur'] ?? '#6c757d') ?>;color:white;font-weight:bold">
                            <?= htmlspecialchars($initiales ?: '?') ?>
                        </div>
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-bold text-truncate"><?= htmlspecialchars(trim($c['prenom'] . ' ' . $c['nom'])) ?></div>
                            <div class="small text-muted">
                                <i class="fas <?= $c['role_icone'] ?? 'fa-user' ?> me-1"></i><?= htmlspecialchars($c['role_nom'] ?? $c['role']) ?>
                            </div>
                            <?php if (!empty($c['residences_affectees'])): ?>
                            <div class="small text-muted text-truncate" title="<?= htmlspecialchars($c['residences_affectees']) ?>">
                                <i class="fas fa-building me-1"></i><?= htmlspecialchars(mb_strimwidth($c['residences_affectees'], 0, 40, '…')) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-column gap-1 ms-2">
                            <a href="<?= BASE_URL ?>/message/compose?to=<?= (int)$c['id'] ?>" class="btn btn-sm btn-primary" title="Envoyer un message interne">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <?php if (!empty($c['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="btn btn-sm btn-outline-secondary" title="Envoyer un email (<?= htmlspecialchars($c['email']) ?>)">
                                <i class="fas fa-at"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($c['telephone'])): ?>
                            <a href="tel:<?= htmlspecialchars($c['telephone']) ?>" class="btn btn-sm btn-outline-secondary" title="Appeler (<?= htmlspecialchars($c['telephone']) ?>)">
                                <i class="fas fa-phone"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
