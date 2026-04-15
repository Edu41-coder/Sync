<?php $title = "Messagerie"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-envelope', 'text' => 'Messagerie', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
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

    <div class="card shadow">
        <div class="card-header bg-white">
            <input type="text" id="searchMsg" class="form-control form-control-sm" style="max-width:300px" placeholder="Rechercher...">
        </div>
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                <h5>Aucun message</h5>
                <p>Votre boîte de réception est vide.</p>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush" id="msgList">
                <?php foreach ($messages as $msg):
                    $isUnread = !$msg['lu'];
                    $prioriteIcons = ['urgente'=>'exclamation-circle text-danger','importante'=>'exclamation-triangle text-warning','normale'=>''];
                    $icon = $prioriteIcons[$msg['priorite']] ?? '';
                ?>
                <a href="<?= BASE_URL ?>/message/show/<?= $msg['id'] ?>"
                   class="list-group-item list-group-item-action <?= $isUnread ? 'bg-light' : '' ?> msg-item"
                   data-search="<?= htmlspecialchars(strtolower($msg['sujet'] . ' ' . $msg['exp_prenom'] . ' ' . $msg['exp_nom'])) ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-start">
                            <?php if ($isUnread): ?>
                            <span class="badge bg-primary me-2 mt-1" style="width:8px;height:8px;padding:0;border-radius:50%"></span>
                            <?php else: ?>
                            <span class="me-2 mt-1" style="width:8px;display:inline-block"></span>
                            <?php endif; ?>
                            <div>
                                <div class="<?= $isUnread ? 'fw-bold' : '' ?>">
                                    <?php if ($icon): ?><i class="fas fa-<?= $icon ?> me-1"></i><?php endif; ?>
                                    <?= htmlspecialchars($msg['sujet']) ?>
                                    <?php if ($msg['nb_reponses'] > 0): ?>
                                    <span class="badge bg-secondary ms-1"><?= $msg['nb_reponses'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    De <strong><?= htmlspecialchars($msg['exp_prenom'] . ' ' . $msg['exp_nom']) ?></strong>
                                    <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($msg['exp_role']) ?></span>
                                </small>
                            </div>
                        </div>
                        <small class="text-muted text-nowrap ms-3">
                            <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                        </small>
                    </div>
                    <p class="mb-0 mt-1 small text-muted text-truncate" style="max-width:600px">
                        <?= htmlspecialchars(substr($msg['contenu'], 0, 120)) ?><?= strlen($msg['contenu']) > 120 ? '...' : '' ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('searchMsg')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.msg-item').forEach(item => {
        item.style.display = !q || item.getAttribute('data-search').includes(q) ? '' : 'none';
    });
});
</script>
