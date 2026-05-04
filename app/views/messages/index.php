<?php $title = "Messagerie"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-envelope', 'text' => 'Messagerie', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$prioriteIcons = [
    'urgente'    => 'exclamation-circle text-danger',
    'importante' => 'exclamation-triangle text-warning',
    'normale'    => '',
];
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">
                <i class="fas fa-envelope text-dark"></i> Messagerie
                <?php if ($nonLus > 0): ?>
                <span class="badge bg-danger ms-2"><?= $nonLus ?> non lu<?= $nonLus > 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </h1>
            <a href="<?= BASE_URL ?>/message/compose" class="btn btn-danger">
                <i class="fas fa-pen me-1"></i>Nouveau message
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" href="<?= BASE_URL ?>/message/index">
                <i class="fas fa-inbox me-1"></i>Reçus
                <?php if ($nonLus > 0): ?><span class="badge bg-danger ms-1"><?= $nonLus ?></span><?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/message/sent">
                <i class="fas fa-paper-plane me-1"></i>Envoyés
            </a>
        </li>
    </ul>

    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted"><?= count($messages) ?> message<?= count($messages) > 1 ? 's' : '' ?> dans votre boîte de réception</small>
            <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width:300px" placeholder="Rechercher (sujet, expéditeur)...">
        </div>
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                <h5>Aucun message</h5>
                <p>Votre boîte de réception est vide.</p>
            </div>
            <?php else: ?>
            <table class="table table-hover mb-0" id="messagesTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:40px">·</th>
                        <th>Sujet</th>
                        <th>Expéditeur</th>
                        <th style="width:140px">Date</th>
                        <th class="text-end no-sort" style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg):
                        $isUnread = !$msg['lu'];
                        $icon = $prioriteIcons[$msg['priorite']] ?? '';
                        $ts = strtotime($msg['created_at']);
                        $sujet = (string)($msg['sujet'] ?? '');
                        $expFullName = trim(($msg['exp_prenom'] ?? '') . ' ' . ($msg['exp_nom'] ?? ''));
                    ?>
                    <tr class="<?= $isUnread ? 'fw-semibold' : '' ?>">
                        <td class="text-center align-middle" data-sort="<?= $isUnread ? 1 : 0 ?>">
                            <?php if ($isUnread): ?>
                            <span class="badge bg-primary" style="width:10px;height:10px;padding:0;border-radius:50%" title="Non lu"></span>
                            <?php endif; ?>
                        </td>
                        <td data-sort="<?= htmlspecialchars(strtolower($sujet)) ?>">
                            <a href="<?= BASE_URL ?>/message/show/<?= (int)$msg['id'] ?>" class="text-decoration-none text-dark">
                                <?php if ($icon): ?><i class="fas fa-<?= $icon ?> me-1"></i><?php endif; ?>
                                <?= htmlspecialchars($sujet) ?>
                                <?php if (!empty($msg['nb_reponses']) && $msg['nb_reponses'] > 0): ?>
                                <span class="badge bg-secondary ms-1"><?= (int)$msg['nb_reponses'] ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="small text-muted text-truncate" style="max-width:520px">
                                <?= htmlspecialchars(substr((string)($msg['contenu'] ?? ''), 0, 110)) ?><?= strlen((string)($msg['contenu'] ?? '')) > 110 ? '…' : '' ?>
                            </div>
                        </td>
                        <td data-sort="<?= htmlspecialchars(strtolower($expFullName)) ?>">
                            <?= htmlspecialchars($expFullName) ?>
                            <?php if (!empty($msg['exp_role'])): ?>
                            <br><span class="badge bg-light text-dark"><?= htmlspecialchars($msg['exp_role']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted" data-sort="<?= (int)$ts ?>">
                            <?= date('d/m/Y H:i', $ts) ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/message/show/<?= (int)$msg['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer"
                                    onclick="confirmDeleteMessage(<?= (int)$msg['id'] ?>, <?= htmlspecialchars(json_encode($sujet), ENT_QUOTES) ?>)">
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

<!-- Modal de confirmation suppression (partagée — JS update form action et sujet) -->
<div class="modal fade" id="modalDeleteMessage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Supprimer ce message ?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Le message suivant va être retiré de votre boîte de réception :</p>
                <p class="mb-3"><strong>«&nbsp;<span id="deleteMessageSubject"></span>&nbsp;»</strong></p>
                <div class="alert alert-info small mb-0">
                    <i class="fas fa-info-circle me-1"></i>Le message reste accessible pour l'expéditeur et les autres destinataires éventuels. Cette action n'est pas réversible côté votre boîte.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="" id="formDeleteMessage" class="d-inline">
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
function confirmDeleteMessage(id, sujet) {
    document.getElementById('formDeleteMessage').action = '<?= BASE_URL ?>/message/delete/' + id;
    document.getElementById('deleteMessageSubject').textContent = sujet || '(sans sujet)';
    new bootstrap.Modal(document.getElementById('modalDeleteMessage')).show();
}
</script>
