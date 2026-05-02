<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',          'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-calculator',     'text' => 'Ma comptabilité',     'url' => BASE_URL . '/resident/comptabilite'],
    ['icon' => 'fas fa-file-invoice',   'text' => "Déclaration {$annee}", 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$creditServices = round($budget['total_services'] * 12 * 0.5, 0); // 50% sur services à la personne
$reductionHebergement = round($budget['depenses_annuelles'] * 0.25, 0); // 25% sur hébergement (plafond 10k)
$reductionPlafonnee = min($reductionHebergement, 10000);
?>

<div class="container-fluid py-4">

    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-file-invoice fa-2x text-primary me-3"></i>
        <h1 class="h3 mb-0">Déclaration fiscale <?= (int)$annee ?></h1>
    </div>

    <!-- Résumé -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-start border-primary border-4 shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary mb-1 fw-bold small">Dépenses annuelles Domitys</h6>
                    <h3 class="mb-0"><?= number_format($budget['depenses_annuelles'], 0, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-start border-success border-4 shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-success mb-1 fw-bold small">Crédit d'impôt estimé services à la personne</h6>
                    <h3 class="mb-0 text-success"><?= number_format($creditServices, 0, ',', ' ') ?> €</h3>
                    <small class="text-muted">50% des services × 12 mois (plafond 12 000 €/an)</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-start border-warning border-4 shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-warning mb-1 fw-bold small">Réduction d'impôt résidence services</h6>
                    <h3 class="mb-0 text-warning"><?= number_format($reductionPlafonnee, 0, ',', ' ') ?> €</h3>
                    <small class="text-muted">25% des frais d'hébergement (plafond 10 000 €/an)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Aide-mémoire -->
        <div class="col-12 col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Aide-mémoire déclaration <?= (int)$annee ?></h5>
                </div>
                <div class="card-body">
                    <h6 class="text-primary"><i class="fas fa-money-bill-wave me-2"></i>À déclarer</h6>
                    <ul class="small">
                        <li><strong>Pension de retraite</strong> — case <strong>1AS</strong> (déclarant 1) ou <strong>1BS</strong> (déclarant 2) du formulaire 2042</li>
                        <li><strong>Autres revenus</strong> (placements, etc.) le cas échéant</li>
                    </ul>

                    <h6 class="text-success mt-3"><i class="fas fa-hand-holding-heart me-2"></i>Aides non imposables</h6>
                    <ul class="small">
                        <li><strong>APL</strong> (Aide Personnalisée au Logement)</li>
                        <li><strong>APA</strong> (Allocation Personnalisée d'Autonomie)</li>
                        <li><strong>ASH</strong> (Aide Sociale à l'Hébergement)</li>
                    </ul>
                    <p class="small text-muted mb-2"><em>Ces aides ne sont pas à déclarer comme revenus.</em></p>

                    <h6 class="text-warning mt-3"><i class="fas fa-percent me-2"></i>Crédits & réductions d'impôt</h6>
                    <ul class="small">
                        <li>
                            <strong>Crédit services à la personne</strong> (art. 199 sexdecies CGI)<br>
                            <small class="text-muted">50% des dépenses, plafond 12 000 € — case <strong>7DB</strong></small>
                        </li>
                        <li>
                            <strong>Réduction hébergement résidence services médicalisée</strong> (art. 199 quindecies)<br>
                            <small class="text-muted">25% des frais d'hébergement et de dépendance, plafond 10 000 € — case <strong>7CD</strong></small>
                        </li>
                    </ul>

                    <div class="alert alert-warning small mb-0 mt-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Ces estimations sont indicatives. Pour la déclaration officielle, consultez impots.gouv.fr
                        ou un travailleur social Domitys.
                    </div>
                </div>
            </div>
        </div>

        <!-- Assistant IA fiscal -->
        <div class="col-12 col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#0d6efd,#0a58ca)">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Assistant Fiscal IA</h5>
                    <span class="badge bg-light text-dark"><i class="fas fa-brain me-1"></i>Claude AI</span>
                </div>
                <div class="card-body p-0">
                    <div id="chatMessages" style="height:480px;overflow-y:auto;padding:20px;background:#f8f9fa">
                        <div class="d-flex mb-3">
                            <div class="me-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div>
                            </div>
                            <div class="bg-white rounded p-3 shadow-sm" style="max-width:85%">
                                <strong class="text-primary d-block mb-1">Assistant Fiscal</strong>
                                <p class="mb-1">Bonjour <?= htmlspecialchars($resident['prenom'] ?? '') ?>,
                                je peux vous aider à préparer votre déclaration de revenus <?= (int)$annee ?>.</p>
                                <p class="mb-1">Vous pouvez me demander :</p>
                                <ul class="small mb-1">
                                    <li>Quelles cases remplir sur le formulaire 2042</li>
                                    <li>Comment calculer votre crédit d'impôt services à la personne</li>
                                    <li>Si vous êtes éligible à la réduction "hébergement résidence services"</li>
                                    <li>Comment déclarer votre pension de retraite</li>
                                </ul>
                                <p class="mb-0 small text-muted"><em>Les conseils sont indicatifs. En cas de doute, contactez les impôts ou un travailleur social.</em></p>
                            </div>
                        </div>
                    </div>
                    <div class="border-top p-3 bg-white">
                        <form id="chatForm" class="d-flex gap-2">
                            <input type="text" id="chatInput" class="form-control"
                                   placeholder="Ex : suis-je éligible à la réduction d'impôt 25% pour ma résidence ?" autocomplete="off">
                            <button type="submit" class="btn btn-primary" id="chatSubmit">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Vous pouvez uploader vos justificatifs (avis d'imposition, attestations APL/APA) dans
                            <a href="<?= BASE_URL ?>/residentDocument/index">Mes Documents</a> pour les analyser dans le chat (à venir).
                        </small>
                    </div>
                </div>
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
            <div class="bg-primary text-white rounded p-3 shadow-sm" style="max-width:85%">${md(text)}</div>
            <div class="ms-2"><div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-user"></i></div></div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function appendBot(text) {
        const div = document.createElement('div');
        div.className = 'd-flex mb-3';
        div.innerHTML = `
            <div class="me-2"><div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div></div>
            <div class="bg-white rounded p-3 shadow-sm" style="max-width:85%"><strong class="text-primary d-block mb-1">Assistant Fiscal</strong>${md(text)}</div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function appendTyping() {
        const div = document.createElement('div');
        div.id = 'typingIndicator';
        div.className = 'd-flex mb-3';
        div.innerHTML = `<div class="me-2"><div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div></div>
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
            const r = await fetch(BASE + '/resident/chatDeclaration', {
                method: 'POST', headers: jsonHeaders(),
                body: JSON.stringify({ message: msg, history: history })
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
