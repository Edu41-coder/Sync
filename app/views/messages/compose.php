<?php $title = "Nouveau message"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-envelope', 'text' => 'Messagerie', 'url' => BASE_URL . '/message/index'],
    ['icon' => 'fas fa-pen', 'text' => $replyData ? 'Répondre' : 'Nouveau message', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-pen text-dark"></i>
                <?= $replyData ? 'Répondre à : ' . htmlspecialchars($replyData['sujet']) : 'Nouveau message' ?>
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Rédiger</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/message/send">
                        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                        <?php if ($replyData): ?>
                        <input type="hidden" name="parent_id" value="<?= $replyData['parent_id'] ?? $replyData['id'] ?>">
                        <?php endif; ?>

                        <?php if ($currentRole === 'admin'): ?>
                        <!-- Admin : choix type d'envoi -->
                        <div class="mb-3">
                            <label class="form-label">Type d'envoi</label>
                            <select class="form-select" name="type_envoi" id="typeEnvoi">
                                <option value="individuel">Individuel</option>
                                <option value="tous_proprios">Tous les propriétaires</option>
                                <option value="tous_staff">Tout le staff</option>
                                <option value="residence">Par résidence</option>
                            </select>
                        </div>

                        <!-- Résidence (si envoi par résidence) -->
                        <div class="mb-3 d-none" id="residenceGroup">
                            <label class="form-label">Résidence</label>
                            <select class="form-select" name="residence_id">
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($residences as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="type_envoi" value="individuel">
                        <?php endif; ?>

                        <!-- Destinataires (individuel) -->
                        <div class="mb-3" id="destGroup">
                            <label class="form-label">Destinataire(s) <span class="text-danger">*</span></label>
                            <select class="form-select" name="destinataires[]" id="destSelect" multiple size="5">
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($preTo == $u['id'] || ($replyData && $replyData['expediteur_id'] == $u['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?> (<?= $u['role'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Maintenez Ctrl pour sélectionner plusieurs destinataires.</small>
                        </div>

                        <!-- Sujet -->
                        <div class="mb-3">
                            <label class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sujet" required
                                   value="<?= $replyData ? 'Re: ' . htmlspecialchars($replyData['sujet']) : '' ?>"
                                   placeholder="Objet du message">
                        </div>

                        <!-- Priorité -->
                        <div class="mb-3">
                            <label class="form-label">Priorité</label>
                            <select class="form-select" name="priorite">
                                <option value="normale">Normale</option>
                                <option value="importante">Importante</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>

                        <!-- Contenu -->
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="contenu" rows="8" required
                                      placeholder="Votre message..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/message/index" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Les destinataires recevront une notification</li>
                        <li>Le message sera visible dans leur boîte de réception</li>
                        <?php if ($currentRole === 'admin'): ?>
                        <li><strong>Envoi groupé</strong> : sélectionnez un type d'envoi pour envoyer à plusieurs destinataires</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($currentRole === 'admin'): ?>
<script>
document.getElementById('typeEnvoi').addEventListener('change', function() {
    const isIndividuel = this.value === 'individuel';
    const isResidence = this.value === 'residence';
    document.getElementById('destGroup').classList.toggle('d-none', !isIndividuel);
    document.getElementById('residenceGroup').classList.toggle('d-none', !isResidence);
});
</script>
<?php endif; ?>
