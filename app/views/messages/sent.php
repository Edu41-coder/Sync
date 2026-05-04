<?php $title = "Messages envoyés"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-envelope',       'text' => 'Messagerie',     'url' => BASE_URL . '/message/index'],
    ['icon' => 'fas fa-paper-plane',    'text' => 'Envoyés',         'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$prioriteIcons = [
    'urgente'    => 'exclamation-circle text-danger',
    'importante' => 'exclamation-triangle text-warning',
    'normale'    => '',
];
$typeEnvoiLabels = [
    'individuel'    => 'Individuel',
    'groupe'        => 'Groupe',
    'residence'     => 'Résidence',
    'tous_proprios' => 'Tous propriétaires',
    'tous_staff'    => 'Tout le staff',
];
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">
                <i class="fas fa-paper-plane text-dark"></i> Messages envoyés
            </h1>
            <a href="<?= BASE_URL ?>/message/compose" class="btn btn-danger">
                <i class="fas fa-pen me-1"></i>Nouveau message
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/message/index">
                <i class="fas fa-inbox me-1"></i>Reçus
                <?php if ($nonLus > 0): ?><span class="badge bg-danger ms-1"><?= $nonLus ?></span><?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="<?= BASE_URL ?>/message/sent">
                <i class="fas fa-paper-plane me-1"></i>Envoyés
            </a>
        </li>
    </ul>

    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted"><?= count($messages) ?> message<?= count($messages) > 1 ? 's' : '' ?> envoyé<?= count($messages) > 1 ? 's' : '' ?></small>
            <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width:300px" placeholder="Rechercher (sujet, destinataires)...">
        </div>
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-paper-plane fa-3x mb-3 d-block"></i>
                <h5>Aucun message envoyé</h5>
                <p>Les messages que vous envoyez apparaîtront ici.</p>
            </div>
            <?php else: ?>
            <table class="table table-hover mb-0" id="messagesTable">
                <thead class="table-light">
                    <tr>
                        <th>Sujet</th>
                        <th>Destinataires</th>
                        <th class="text-center" style="width:120px">Lectures</th>
                        <th style="width:140px">Date</th>
                        <th class="text-end no-sort" style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg):
                        $icon       = $prioriteIcons[$msg['priorite']] ?? '';
                        $ts         = strtotime($msg['created_at']);
                        $sujet      = (string)($msg['sujet'] ?? '');
                        $nbDest     = (int)($msg['nb_destinataires'] ?? 0);
                        $nbLus      = (int)($msg['nb_lus'] ?? 0);
                        $destinaires = (string)($msg['destinataires_noms'] ?? '');
                        $type       = $typeEnvoiLabels[$msg['type_envoi'] ?? 'individuel'] ?? $msg['type_envoi'];
                    ?>
                    <tr>
                        <td data-sort="<?= htmlspecialchars(strtolower($sujet)) ?>">
                            <a href="<?= BASE_URL ?>/message/show/<?= (int)$msg['id'] ?>" class="text-decoration-none text-dark">
                                <?php if ($icon): ?><i class="fas fa-<?= $icon ?> me-1"></i><?php endif; ?>
                                <?= htmlspecialchars($sujet) ?>
                            </a>
                            <div class="small text-muted text-truncate" style="max-width:520px">
                                <?= htmlspecialchars(substr((string)($msg['contenu'] ?? ''), 0, 110)) ?><?= strlen((string)($msg['contenu'] ?? '')) > 110 ? '…' : '' ?>
                            </div>
                        </td>
                        <td data-sort="<?= htmlspecialchars(strtolower($destinaires)) ?>" class="small">
                            <span class="badge bg-secondary me-1"><?= $type ?></span>
                            <?= htmlspecialchars(mb_strimwidth($destinaires, 0, 80, '…')) ?>
                            <div class="text-muted">(<?= $nbDest ?> destinataire<?= $nbDest > 1 ? 's' : '' ?>)</div>
                        </td>
                        <td class="text-center" data-sort="<?= $nbDest > 0 ? round($nbLus * 100 / $nbDest) : 0 ?>">
                            <?php if ($nbDest > 0): ?>
                                <?php $pct = round($nbLus * 100 / $nbDest); $cls = $pct === 100 ? 'success' : ($pct > 0 ? 'warning' : 'secondary'); ?>
                                <span class="badge bg-<?= $cls ?>" title="<?= $nbLus ?> sur <?= $nbDest ?> destinataires ont lu">
                                    <?= $nbLus ?> / <?= $nbDest ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted" data-sort="<?= (int)$ts ?>">
                            <?= date('d/m/Y H:i', $ts) ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/message/show/<?= (int)$msg['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer"
                                    onclick="confirmDeleteSent(<?= (int)$msg['id'] ?>, <?= htmlspecialchars(json_encode($sujet), ENT_QUOTES) ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php if (!empty($messages) && count($messages) > 20): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="messagesTableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="messagesPagination"></ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmation suppression (boîte d'envoi) -->
<div class="modal fade" id="modalDeleteSent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Supprimer ce message envoyé ?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Le message suivant va être retiré de votre boîte d'envoi :</p>
                <p class="mb-3"><strong>«&nbsp;<span id="deleteSentSubject"></span>&nbsp;»</strong></p>
                <div class="alert alert-info small mb-0">
                    <i class="fas fa-info-circle me-1"></i>La copie reçue par les destinataires reste intacte. Cette action ne supprime que la trace dans <strong>votre</strong> boîte d'envoi.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="" id="formDeleteSent" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($messages)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<?php if (count($messages) > 20): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('messagesTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    excludeColumns: [4],
    paginationId: 'messagesPagination',
    infoId: 'messagesTableInfo'
});
</script>
<?php else: ?>
<script>
new DataTable('messagesTable', { searchInputId: 'searchInput', excludeColumns: [4] });
</script>
<?php endif; ?>
<?php endif; ?>

<script>
function confirmDeleteSent(id, sujet) {
    document.getElementById('formDeleteSent').action = '<?= BASE_URL ?>/message/deleteSent/' + id;
    document.getElementById('deleteSentSubject').textContent = sujet || '(sans sujet)';
    new bootstrap.Modal(document.getElementById('modalDeleteSent')).show();
}
</script>
