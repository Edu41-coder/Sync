<?php
$agContext = $agContext ?? null;
$prefill   = $prefill   ?? ['sujet' => '', 'contenu' => '', 'destinataires_precoches' => [], 'tab_actif' => 'tabRes'];
$precoches = array_map('intval', $prefill['destinataires_precoches'] ?? []);

$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
];
if ($agContext) {
    $breadcrumb[] = ['icon' => 'fas fa-gavel', 'text' => 'AG ' . date('d/m/Y', strtotime($agContext['date_ag'])), 'url' => BASE_URL . '/assemblee/show/' . (int)$agContext['id']];
    $breadcrumb[] = ['icon' => 'fas fa-paper-plane', 'text' => 'Convocation', 'url' => null];
} else {
    $breadcrumb[] = ['icon' => 'fas fa-paper-plane', 'text' => 'Message groupé', 'url' => null];
}
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<?php if ($agContext): ?>
<div class="container-fluid pt-3">
    <div class="alert alert-primary d-flex justify-content-between align-items-center flex-wrap gap-2 mb-0">
        <div>
            <i class="fas fa-gavel me-2"></i>
            <strong>Convocation AG <?= $agContext['type'] === 'extraordinaire' ? 'extraordinaire' : 'ordinaire' ?></strong>
            du <?= date('d/m/Y à H\hi', strtotime($agContext['date_ag'])) ?>
            — Le formulaire est pré-rempli avec les <strong><?= count($precoches) ?> propriétaire(s)</strong> de la résidence et un message type.
        </div>
        <a href="<?= BASE_URL ?>/assemblee/show/<?= (int)$agContext['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Retour fiche AG
        </a>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-paper-plane text-info me-2"></i>Message groupé</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> · Sélectionner les destinataires puis rédiger le message</p>
            <?php endif; ?>
        </div>
        <?php if (count($residences) > 1): ?>
        <form method="GET" action="<?= BASE_URL ?>/accueil/messageGroupe">
            <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($residences as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/message/send" id="msgForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="type_envoi" value="individuel">
        <input type="hidden" name="residence_id" value="<?= (int)$residenceCourante['id'] ?>">
        <input type="hidden" name="priorite" value="normale">

        <div class="row g-3">
            <!-- Sélection destinataires -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <strong><i class="fas fa-users me-2"></i>Destinataires</strong>
                        <span class="badge bg-light text-dark"><span id="nbSelected">0</span> sélectionnés</span>
                    </div>
                    <div class="card-body p-0">
                        <input type="search" id="filterDest" class="form-control rounded-0 border-0 border-bottom" placeholder="🔍 Filtrer">

                        <ul class="nav nav-tabs nav-fill" role="tablist">
                            <li class="nav-item"><button class="nav-link <?= $prefill['tab_actif'] === 'tabRes'  ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabRes"  type="button">
                                <i class="fas fa-user me-1"></i>Résidents (<?= count($destinataires['residents']) ?>)
                            </button></li>
                            <li class="nav-item"><button class="nav-link <?= $prefill['tab_actif'] === 'tabStaff' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabStaff" type="button">
                                <i class="fas fa-user-tie me-1"></i>Staff (<?= count($destinataires['staff']) ?>)
                            </button></li>
                            <li class="nav-item"><button class="nav-link <?= $prefill['tab_actif'] === 'tabProp' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabProp" type="button">
                                <i class="fas fa-key me-1"></i>Proprio (<?= count($destinataires['proprietaires']) ?>)
                            </button></li>
                        </ul>

                        <div class="tab-content" style="max-height:480px;overflow-y:auto">
                            <!-- Résidents -->
                            <div class="tab-pane fade <?= $prefill['tab_actif'] === 'tabRes' ? 'show active' : '' ?>" id="tabRes">
                                <div class="p-2 border-bottom">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="selectAll('tabRes', true)"><i class="fas fa-check-square me-1"></i>Tout</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll('tabRes', false)"><i class="far fa-square me-1"></i>Aucun</button>
                                </div>
                                <?php foreach ($destinataires['residents'] as $u): ?>
                                <label class="d-flex align-items-center px-3 py-2 border-bottom dest-item">
                                    <input type="checkbox" name="destinataires[]" value="<?= (int)$u['id'] ?>" class="form-check-input me-2 dest-cb" <?= in_array((int)$u['id'], $precoches, true) ? 'checked' : '' ?>>
                                    <span class="flex-grow-1"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($u['email'] ?? '') ?></small>
                                </label>
                                <?php endforeach; ?>
                                <?php if (empty($destinataires['residents'])): ?>
                                <div class="text-muted text-center py-3 small">Aucun résident avec un compte.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Staff -->
                            <div class="tab-pane fade <?= $prefill['tab_actif'] === 'tabStaff' ? 'show active' : '' ?>" id="tabStaff">
                                <div class="p-2 border-bottom">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="selectAll('tabStaff', true)"><i class="fas fa-check-square me-1"></i>Tout</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll('tabStaff', false)"><i class="far fa-square me-1"></i>Aucun</button>
                                </div>
                                <?php foreach ($destinataires['staff'] as $u): ?>
                                <label class="d-flex align-items-center px-3 py-2 border-bottom dest-item">
                                    <input type="checkbox" name="destinataires[]" value="<?= (int)$u['id'] ?>" class="form-check-input me-2 dest-cb" <?= in_array((int)$u['id'], $precoches, true) ? 'checked' : '' ?>>
                                    <span class="flex-grow-1">
                                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                        <small class="text-muted">(<?= htmlspecialchars($u['role']) ?>)</small>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Propriétaires -->
                            <div class="tab-pane fade <?= $prefill['tab_actif'] === 'tabProp' ? 'show active' : '' ?>" id="tabProp">
                                <div class="p-2 border-bottom">
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="selectAll('tabProp', true)"><i class="fas fa-check-square me-1"></i>Tout</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll('tabProp', false)"><i class="far fa-square me-1"></i>Aucun</button>
                                </div>
                                <?php foreach ($destinataires['proprietaires'] as $u): ?>
                                <label class="d-flex align-items-center px-3 py-2 border-bottom dest-item">
                                    <input type="checkbox" name="destinataires[]" value="<?= (int)$u['id'] ?>" class="form-check-input me-2 dest-cb" <?= in_array((int)$u['id'], $precoches, true) ? 'checked' : '' ?>>
                                    <span class="flex-grow-1"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($u['email'] ?? '') ?></small>
                                </label>
                                <?php endforeach; ?>
                                <?php if (empty($destinataires['proprietaires'])): ?>
                                <div class="text-muted text-center py-3 small">Aucun propriétaire actif.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rédaction message -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <strong><i class="fas fa-pen me-2"></i>Rédiger le message</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" name="sujet" class="form-control" required maxlength="255"
                                   value="<?= htmlspecialchars($prefill['sujet']) ?>"
                                   placeholder="Ex: Animation jeudi annulée, Information importante…">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priorité</label>
                            <select name="priorite" class="form-select">
                                <option value="normale" <?= $agContext ? '' : 'selected' ?>>Normale</option>
                                <option value="haute"   <?= $agContext ? 'selected' : '' ?>>Haute</option>
                                <option value="urgente">Urgente</option>
                            </select>
                            <?php if ($agContext): ?>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Priorité « Haute » pré-sélectionnée pour la convocation AG.</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="contenu" class="form-control" rows="12" required
                                      placeholder="Bonjour,&#10;&#10;…"><?= htmlspecialchars($prefill['contenu']) ?></textarea>
                            <small class="text-muted">Le message sera envoyé individuellement à chaque destinataire sélectionné.</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/accueil/equipe?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-info text-white" id="btnSend" disabled>
                                <i class="fas fa-paper-plane me-1"></i>Envoyer à <span id="btnNb">0</span> destinataire(s)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateCount() {
    const n = document.querySelectorAll('.dest-cb:checked').length;
    document.getElementById('nbSelected').textContent = n;
    document.getElementById('btnNb').textContent = n;
    document.getElementById('btnSend').disabled = n === 0;
}
document.querySelectorAll('.dest-cb').forEach(cb => cb.addEventListener('change', updateCount));
updateCount(); // Initialiser avec les cases pré-cochées (préfill convocation AG)

function selectAll(tabId, value) {
    document.querySelectorAll('#' + tabId + ' .dest-cb').forEach(cb => {
        // Ne sélectionne que les cases visibles (filtre)
        if (cb.closest('.dest-item').style.display !== 'none') cb.checked = value;
    });
    updateCount();
}

// Filtre recherche
document.getElementById('filterDest').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.dest-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q) ? 'flex' : 'none';
    });
});

document.getElementById('msgForm').addEventListener('submit', function(e) {
    if (document.querySelectorAll('.dest-cb:checked').length === 0) {
        e.preventDefault();
        alert('Sélectionnez au moins un destinataire.');
    }
});
</script>
