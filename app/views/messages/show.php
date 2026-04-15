<?php $title = $message['sujet']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-envelope', 'text' => 'Messagerie', 'url' => BASE_URL . '/message/index'],
    ['icon' => 'fas fa-eye', 'text' => $message['sujet'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
$currentUserId = (int)$_SESSION['user_id'];
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-envelope-open text-dark"></i> <?= htmlspecialchars($message['sujet']) ?></h1>
            <a href="<?= BASE_URL ?>/message/index" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <!-- Fil de conversation -->
            <?php foreach ($thread as $msg):
                $isMe = (int)$msg['expediteur_id'] === $currentUserId;
            ?>
            <div class="card shadow-sm mb-3 <?= $isMe ? 'border-primary' : '' ?>">
                <div class="card-header bg-<?= $isMe ? 'primary text-white' : 'light' ?> d-flex justify-content-between align-items-center py-2">
                    <div>
                        <i class="fas fa-user me-1"></i>
                        <strong><?= htmlspecialchars($msg['exp_prenom'] . ' ' . $msg['exp_nom']) ?></strong>
                        <span class="badge bg-<?= $isMe ? 'light text-primary' : 'secondary' ?> ms-1"><?= htmlspecialchars($msg['exp_role']) ?></span>
                    </div>
                    <small><?= date('d/m/Y à H:i', strtotime($msg['created_at'])) ?></small>
                </div>
                <div class="card-body">
                    <div class="mb-0"><?= nl2br(htmlspecialchars($msg['contenu'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Formulaire de réponse -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-reply me-2"></i>Répondre</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/message/send">
                        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                        <input type="hidden" name="parent_id" value="<?= $threadId ?>">
                        <input type="hidden" name="sujet" value="Re: <?= htmlspecialchars($message['sujet']) ?>">
                        <input type="hidden" name="type_envoi" value="individuel">
                        <?php
                        // Répondre à l'expéditeur du dernier message (ou du message original)
                        $lastMsg = end($thread);
                        $replyToId = (int)$lastMsg['expediteur_id'] === $currentUserId
                            ? (int)$thread[0]['expediteur_id']
                            : (int)$lastMsg['expediteur_id'];
                        ?>
                        <input type="hidden" name="destinataires[]" value="<?= $replyToId ?>">
                        <input type="hidden" name="priorite" value="<?= $message['priorite'] ?>">

                        <div class="mb-3">
                            <textarea class="form-control" name="contenu" rows="3" placeholder="Votre réponse..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i>Envoyer la réponse
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar infos -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                </div>
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Sujet</td><td><?= htmlspecialchars($message['sujet']) ?></td></tr>
                        <tr><td class="text-muted">Priorité</td><td>
                            <?php $pColors = ['urgente'=>'danger','importante'=>'warning','normale'=>'secondary']; ?>
                            <span class="badge bg-<?= $pColors[$message['priorite']] ?? 'secondary' ?>"><?= ucfirst($message['priorite']) ?></span>
                        </td></tr>
                        <tr><td class="text-muted">Messages</td><td><?= count($thread) ?></td></tr>
                        <tr><td class="text-muted">Créé le</td><td><?= date('d/m/Y H:i', strtotime($thread[0]['created_at'])) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
