<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-elevator',       'text' => 'Ascenseurs',      'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$badgeStatut = ['actif'=>'success','hors_service'=>'warning','depose'=>'dark'];

// Index échéances par ascenseur (pour affichage rapide dans cartes)
$echParAsc = [];
foreach ($echeances as $e) {
    if ($e['type_entree']) $echParAsc[$e['id']][$e['type_entree']] = $e;
}
$today = new DateTime();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-elevator text-secondary me-2"></i>Ascenseurs</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> — <?= htmlspecialchars($residenceCourante['ville']) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/maintenance/ascenseurs">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <?php if ($isManager): ?>
            <a href="<?= BASE_URL ?>/maintenance/ascenseurForm<?= $residenceCourante ? '?residence_id='.(int)$residenceCourante['id'] : '' ?>" class="btn btn-warning">
                <i class="fas fa-plus me-1"></i>Nouvel ascenseur
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
        <?php if ($isManager && !empty($residencesPourCreation)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Aucune résidence n'a encore d'ascenseur déclaré. Créez-en un pour commencer.
        </div>
        <a href="<?= BASE_URL ?>/maintenance/ascenseurForm" class="btn btn-warning">
            <i class="fas fa-plus me-1"></i>Créer un ascenseur
        </a>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Aucune résidence avec ascenseur ne vous est accessible.
        </div>
        <?php endif; ?>

    <?php elseif (empty($ascenseurs)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Aucun ascenseur sur cette résidence.
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/maintenance/ascenseurForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="alert-link">Créer le premier</a>.
        <?php endif; ?>
    </div>

    <?php else: ?>

    <div class="row g-3">
        <?php foreach ($ascenseurs as $a):
            $echAsc = $echParAsc[$a['id']] ?? [];
            $alertes = [];
            // Alertes par ascenseur
            foreach (['visite_annuelle' => 'Visite annuelle', 'controle_quinquennal' => 'Contrôle quinquennal'] as $t => $lbl) {
                $e = $echAsc[$t] ?? null;
                if (!$e) {
                    $alertes[] = ['niveau' => 'warning', 'msg' => "$lbl : aucune entrée"];
                } elseif (!empty($e['prochaine_echeance'])) {
                    $diff = (int)$today->diff(new DateTime($e['prochaine_echeance']))->format('%r%a');
                    if ($diff < 0) $alertes[] = ['niveau' => 'danger', 'msg' => "$lbl : EXPIRÉE depuis " . abs($diff) . "j"];
                    elseif ($diff <= 30) $alertes[] = ['niveau' => 'warning', 'msg' => "$lbl : dans {$diff}j"];
                }
            }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100 <?= $a['statut'] !== 'actif' ? 'opacity-50' : '' ?>">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong>
                        <i class="fas fa-elevator me-1"></i>
                        <?= htmlspecialchars($a['nom']) ?>
                    </strong>
                    <span class="badge bg-<?= $badgeStatut[$a['statut']] ?? 'secondary' ?>"><?= $a['statut'] ?></span>
                </div>
                <div class="card-body small">
                    <?php if (!empty($a['emplacement'])): ?>
                    <div><i class="fas fa-map-marker-alt text-muted me-1"></i><?= htmlspecialchars($a['emplacement']) ?></div>
                    <?php endif; ?>
                    <div><i class="fas fa-tag text-muted me-1"></i><strong><?= htmlspecialchars(ucfirst($a['marque'])) ?></strong>
                        <?php if (!empty($a['modele'])): ?> — <?= htmlspecialchars($a['modele']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($a['capacite_personnes']) || !empty($a['capacite_kg'])): ?>
                    <div><i class="fas fa-users text-muted me-1"></i>
                        <?php if ($a['capacite_personnes']): ?><?= (int)$a['capacite_personnes'] ?> pers.<?php endif; ?>
                        <?php if ($a['capacite_kg']): ?> · <?= (int)$a['capacite_kg'] ?> kg<?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($a['nombre_etages'])): ?>
                    <div><i class="fas fa-layer-group text-muted me-1"></i><?= (int)$a['nombre_etages'] ?> étages</div>
                    <?php endif; ?>
                    <?php if (!empty($a['contrat_ascensoriste_nom'])): ?>
                    <hr class="my-2">
                    <div><i class="fas fa-file-contract text-info me-1"></i><strong><?= htmlspecialchars($a['contrat_ascensoriste_nom']) ?></strong></div>
                    <?php if (!empty($a['contrat_ascensoriste_tel'])): ?>
                    <div class="text-muted">📞 <?= htmlspecialchars($a['contrat_ascensoriste_tel']) ?></div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($alertes)): ?>
                    <hr class="my-2">
                    <?php foreach ($alertes as $al): ?>
                    <div class="small text-<?= $al['niveau'] === 'danger' ? 'danger' : 'warning' ?>">
                        <i class="fas fa-exclamation-triangle me-1"></i><?= htmlspecialchars($al['msg']) ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/maintenance/ascenseurShow/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Détails
                    </a>
                    <?php if ($isManager): ?>
                    <a href="<?= BASE_URL ?>/maintenance/ascenseurForm/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
