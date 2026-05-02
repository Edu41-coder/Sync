<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',      'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-calculator',     'text' => 'Ma comptabilité', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-calculator fa-2x text-danger me-3"></i>
        <h1 class="h3 mb-0">Ma comptabilité</h1>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-start border-danger border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-danger mb-1 fw-bold small">Dépenses / mois</h6>
                    <h2 class="mb-0 text-danger"><?= number_format($budget['total_depenses'], 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-start border-success border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Aides perçues / mois</h6>
                    <h2 class="mb-0 text-success"><?= number_format($budget['total_aides'], 0, ',', ' ') ?> €</h2>
                    <small class="text-muted">APL + APA</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-start border-warning border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning mb-1 fw-bold small">Reste à charge / mois</h6>
                    <h2 class="mb-0 text-warning"><?= number_format($budget['reste_a_charge'], 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-start border-info border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Reste à charge / an</h6>
                    <h2 class="mb-0 text-info"><?= number_format($budget['reste_annuel'], 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Détail dépenses -->
        <div class="col-12 col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Ventilation des dépenses mensuelles</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Poste</th>
                                <th class="text-end">Montant / mois</th>
                                <th class="text-end">Montant / an</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-key text-muted me-2"></i>Loyers</td>
                                <td class="text-end"><?= number_format($budget['total_loyer'], 2, ',', ' ') ?> €</td>
                                <td class="text-end text-muted"><?= number_format($budget['total_loyer'] * 12, 2, ',', ' ') ?> €</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-bolt text-muted me-2"></i>Charges</td>
                                <td class="text-end"><?= number_format($budget['total_charges'], 2, ',', ' ') ?> €</td>
                                <td class="text-end text-muted"><?= number_format($budget['total_charges'] * 12, 2, ',', ' ') ?> €</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-concierge-bell text-muted me-2"></i>Services supplémentaires</td>
                                <td class="text-end"><?= number_format($budget['total_services'], 2, ',', ' ') ?> €</td>
                                <td class="text-end text-muted"><?= number_format($budget['total_services'] * 12, 2, ',', ' ') ?> €</td>
                            </tr>
                            <tr class="table-danger fw-bold">
                                <td>Total dépenses</td>
                                <td class="text-end"><?= number_format($budget['total_depenses'], 2, ',', ' ') ?> €</td>
                                <td class="text-end"><?= number_format($budget['depenses_annuelles'], 2, ',', ' ') ?> €</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hand-holding-heart text-success me-2"></i>APL</td>
                                <td class="text-end text-success">- <?= number_format($budget['total_apl'], 2, ',', ' ') ?> €</td>
                                <td class="text-end text-success">- <?= number_format($budget['total_apl'] * 12, 2, ',', ' ') ?> €</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-hand-holding-heart text-success me-2"></i>APA</td>
                                <td class="text-end text-success">- <?= number_format($budget['total_apa'], 2, ',', ' ') ?> €</td>
                                <td class="text-end text-success">- <?= number_format($budget['total_apa'] * 12, 2, ',', ' ') ?> €</td>
                            </tr>
                            <tr class="table-warning fw-bold">
                                <td>Reste à charge</td>
                                <td class="text-end"><?= number_format($budget['reste_a_charge'], 2, ',', ' ') ?> €</td>
                                <td class="text-end"><?= number_format($budget['reste_annuel'], 2, ',', ' ') ?> €</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Récap par lot -->
        <div class="col-12 col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                    <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Détail par lot</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($budget['occupations'])): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-home fa-2x mb-2 d-block opacity-50"></i>
                        Aucun lot actif.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($budget['occupations'] as $o): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><?= htmlspecialchars($o['residence_nom']) ?></strong>
                                <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($o['lot_type']) ?></span>
                            </div>
                            <small class="text-muted">Lot <?= htmlspecialchars($o['numero_lot']) ?></small>
                            <div class="row g-1 small mt-2">
                                <div class="col-6">
                                    <span class="text-muted">Loyer</span><br>
                                    <strong><?= number_format((float)$o['loyer_mensuel_resident'], 0, ',', ' ') ?> €</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">Charges</span><br>
                                    <strong><?= number_format((float)($o['charges_mensuelles_resident'] ?? 0), 0, ',', ' ') ?> €</strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lien déclaration fiscale -->
    <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <i class="fas fa-info-circle me-2"></i>
            Vous pouvez bénéficier de crédits/réductions d'impôt pour vos services à la personne et votre hébergement.
        </span>
        <a href="<?= BASE_URL ?>/resident/declarationFiscale" class="btn btn-sm btn-primary">
            <i class="fas fa-file-invoice me-1"></i>Préparer ma déclaration fiscale avec l'IA
        </a>
    </div>

    <!-- Assistant IA Budget -->
    <div class="card shadow mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#6610f2,#8b5cf6)">
            <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Assistant Budget IA</h5>
            <span class="badge bg-light text-dark"><i class="fas fa-brain me-1"></i>Propulsé par Claude AI</span>
        </div>
        <div class="card-body p-0">
            <div id="chatMessages" style="height:400px;overflow-y:auto;padding:20px;background:#f8f9fa">
                <div class="d-flex mb-3">
                    <div class="me-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem">
                            <i class="fas fa-robot"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                        <strong class="text-primary d-block mb-1">Assistant Budget</strong>
                        <p class="mb-1">Bonjour <?= htmlspecialchars($resident['prenom'] ?? '') ?> ! Je suis là pour vous aider à comprendre votre budget mensuel.</p>
                        <p class="mb-1">Vous pouvez me poser des questions sur :</p>
                        <ul class="small mb-1">
                            <li>Vos dépenses Domitys (loyer, charges, services)</li>
                            <li>Les aides au logement (APL) et autonomie (APA)</li>
                            <li>L'aide sociale à l'hébergement (ASH)</li>
                            <li>Comment optimiser votre reste à charge</li>
                        </ul>
                        <p class="mb-0 small text-muted"><em>Posez votre question ci-dessous.</em></p>
                    </div>
                </div>
            </div>
            <div class="border-top p-3 bg-white">
                <form id="chatForm" class="d-flex gap-2">
                    <input type="text" id="chatInput" class="form-control" placeholder="Ex : pourquoi mon reste à charge est-il élevé ce mois-ci ?" autocomplete="off">
                    <button type="submit" class="btn btn-primary" id="chatSubmit">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
(function() {
    const BASE = '<?= BASE_URL ?>';
    const history = [];

    function jsonHeaders() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return { 'Content-Type': 'application/json', 'X-CSRF-Token': meta ? meta.content : '' };
    }

    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatSubmit = document.getElementById('chatSubmit');

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
    function md(s) {
        let h = escapeHtml(s);
        h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        h = h.replace(/\*(.+?)\*/g, '<em>$1</em>');
        h = h.replace(/\n/g, '<br>');
        return h;
    }

    function appendUser(text) {
        const div = document.createElement('div');
        div.className = 'd-flex justify-content-end mb-3';
        div.innerHTML = `
            <div class="bg-primary text-white rounded p-3 shadow-sm" style="max-width:80%">
                ${md(text)}
            </div>
            <div class="ms-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#6610f2;color:#fff;width:35px;height:35px"><i class="fas fa-user"></i></div>
            </div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function appendBot(text) {
        const div = document.createElement('div');
        div.className = 'd-flex mb-3';
        div.innerHTML = `
            <div class="me-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#6610f2;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div>
            </div>
            <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                <strong class="text-primary d-block mb-1">Assistant Budget</strong>
                ${md(text)}
            </div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function appendTyping() {
        const div = document.createElement('div');
        div.id = 'typingIndicator';
        div.className = 'd-flex mb-3';
        div.innerHTML = `<div class="me-2"><div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#6610f2;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div></div>
            <div class="bg-white rounded p-3 shadow-sm"><em class="text-muted"><i class="fas fa-circle-notch fa-spin me-2"></i>L'assistant réfléchit…</em></div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;
        appendUser(msg);
        chatInput.value = '';
        chatSubmit.disabled = true;
        appendTyping();

        try {
            const r = await fetch(BASE + '/resident/chat', {
                method: 'POST', headers: jsonHeaders(),
                body: JSON.stringify({ message: msg, history: history, mode: 'budget' })
            });
            const data = await r.json();
            document.getElementById('typingIndicator')?.remove();
            if (data.success) {
                appendBot(data.message);
                history.push({ role: 'user', content: msg });
                history.push({ role: 'assistant', content: data.message });
                if (history.length > 12) history.splice(0, history.length - 12);
            } else {
                appendBot('⚠️ ' + (data.message || 'Erreur'));
            }
        } catch (err) {
            document.getElementById('typingIndicator')?.remove();
            appendBot('⚠️ Erreur réseau : ' + err.message);
        } finally {
            chatSubmit.disabled = false;
            chatInput.focus();
        }
    });
})();
</script>
