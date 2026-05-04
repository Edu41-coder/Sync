<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',  'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',     'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-file-invoice',   'text' => 'Bulletins de paie','url' => BASE_URL . '/bulletinPaie/index'],
    ['icon' => 'fas fa-robot',          'text' => 'Assistant Paie IA','url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$libellePeriode = ($moisLabels[$mois] ?? '') . ' ' . $annee;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            <i class="fas fa-robot me-2 text-success"></i>Assistant Paie IA
        </h2>
        <a href="<?= BASE_URL ?>/bulletinPaie/index" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Liste bulletins
        </a>
    </div>

    <div class="alert alert-warning small">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>Pilote — non contractuel.</strong>
        L'assistant analyse les fiches RH, bulletins et plannings. Avant toute décision RH (embauche, sanction,
        rupture, transmission DSN, paiement de salaire), validez impérativement avec un cabinet de paie ou expert-comptable.
    </div>

    <!-- Filtres période -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Mois</label>
                    <select name="mois" class="form-select form-select-sm">
                        <?php foreach ($moisLabels as $m => $lbl): ?>
                        <option value="<?= (int)$m ?>" <?= (int)$m === (int)$mois ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-sync me-1"></i>Recharger</button>
                </div>
            </div>
        </div>
    </form>

    <div class="alert alert-secondary py-2 small d-flex justify-content-between align-items-center">
        <span><i class="fas fa-bullseye me-1"></i><strong>Période active :</strong> <?= htmlspecialchars($libellePeriode) ?></span>
        <button type="button" id="btnResetChat" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-eraser me-1"></i>Réinitialiser
        </button>
    </div>

    <!-- Suggestions -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="small text-muted mb-2"><i class="fas fa-lightbulb me-1 text-warning"></i>Suggestions :</div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-outline-success suggest" data-q="Donne-moi un état des lieux de la paie pour cette période. Combien de bulletins émis, combien à émettre, masse salariale ?">📊 État de la paie</button>
                <button class="btn btn-sm btn-outline-success suggest" data-q="Combien de salariés actifs n'ont pas encore de fiche RH ? Quels rôles concernés ?">👤 Couverture fiches RH</button>
                <button class="btn btn-sm btn-outline-success suggest" data-q="Y a-t-il des anomalies à corriger avant la paie de fin de mois ? Salaires sous SMIC, dépassement durée légale, etc.">⚠️ Anomalies à corriger</button>
                <button class="btn btn-sm btn-outline-success suggest" data-q="Simule le passage d'un brut à 2200€ vers le net pour un employé en convention Services à la personne (CDI temps plein).">🧮 Simulation brut → net</button>
                <button class="btn btn-sm btn-outline-success suggest" data-q="Quelles sont les heures supplémentaires plausibles ce mois ? Comment calcules-tu les majorations 25% / 50% ?">⏰ Heures supplémentaires</button>
                <button class="btn btn-sm btn-outline-success suggest" data-q="Quelles conventions collectives s'appliquent à mes salariés ? Y a-t-il des points spécifiques à connaître ?">📚 Conventions collectives</button>
                <button class="btn btn-sm btn-outline-success suggest" data-q="Que faut-il préparer pour la DSN du mois ? Quelles vérifications avant transmission ?">📤 Préparation DSN</button>
            </div>
        </div>
    </div>

    <!-- Chatbox -->
    <div class="card shadow-sm" style="background:#f8f9fc;">
        <div class="card-body" style="height: 500px; overflow-y: auto;" id="chatMessages">
            <div class="d-flex mb-3">
                <div class="me-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#198754;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div>
                </div>
                <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                    <strong class="text-success d-block mb-1">Assistant Paie</strong>
                    Bonjour. Je suis votre assistant RH/paie. J'ai accès à l'effectif, aux fiches RH, aux conventions collectives et aux bulletins de la période <strong><?= htmlspecialchars($libellePeriode) ?></strong>.
                    <br><br>
                    Posez-moi une question, ou cliquez sur une suggestion ci-dessus pour démarrer.
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <form id="chatForm" class="d-flex gap-2">
                <input type="text" id="chatInput" class="form-control" placeholder="Posez votre question RH/paie..." autocomplete="off" required>
                <button type="submit" id="chatSubmit" class="btn btn-success">
                    <i class="fas fa-paper-plane me-1"></i>Envoyer
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const BASE  = '<?= BASE_URL ?>';
    const ANNEE = <?= (int)$annee ?>;
    const MOIS  = <?= (int)$mois ?>;
    let history = [];

    function jsonHeaders() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return { 'Content-Type': 'application/json', 'X-CSRF-Token': meta ? meta.content : '' };
    }

    const chatMessages = document.getElementById('chatMessages');
    const chatForm     = document.getElementById('chatForm');
    const chatInput    = document.getElementById('chatInput');
    const chatSubmit   = document.getElementById('chatSubmit');

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
    function md(s) {
        let h = escapeHtml(s);
        h = h.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        h = h.replace(/\*(.+?)\*/g, '<em>$1</em>');
        h = h.replace(/`([^`]+)`/g, '<code>$1</code>');
        h = h.replace(/^[-•]\s+(.+)$/gm, '<li>$1</li>');
        h = h.replace(/(<li>.+<\/li>(?:<br>)?)+/gs, m => '<ul class="mb-2">' + m.replace(/<br>/g, '') + '</ul>');
        h = h.replace(/\n/g, '<br>');
        return h;
    }
    function appendUser(text) {
        const div = document.createElement('div');
        div.className = 'd-flex justify-content-end mb-3';
        div.innerHTML = `
            <div class="bg-success text-white rounded p-3 shadow-sm" style="max-width:80%">${md(text)}</div>
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
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#198754;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div>
            </div>
            <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                <strong class="text-success d-block mb-1">Assistant Paie</strong>${md(text)}
            </div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function appendTyping() {
        const div = document.createElement('div');
        div.id = 'typingIndicator';
        div.className = 'd-flex mb-3';
        div.innerHTML = `<div class="me-2"><div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#198754;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div></div>
            <div class="bg-white rounded p-3 shadow-sm"><em class="text-muted"><i class="fas fa-circle-notch fa-spin me-2"></i>L'assistant analyse les données paie…</em></div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function sendMessage(msg) {
        appendUser(msg);
        chatSubmit.disabled = true;
        appendTyping();
        try {
            const r = await fetch(BASE + '/bulletinPaie/chat', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ message: msg, history: history, annee: ANNEE, mois: MOIS })
            });
            const data = await r.json();
            document.getElementById('typingIndicator')?.remove();
            if (data.success) {
                appendBot(data.message);
                history.push({ role: 'user', content: msg });
                history.push({ role: 'assistant', content: data.message });
                if (history.length > 14) history.splice(0, history.length - 14);
            } else {
                appendBot('⚠️ ' + (data.message || 'Erreur inconnue'));
            }
        } catch (err) {
            document.getElementById('typingIndicator')?.remove();
            appendBot('⚠️ Erreur réseau : ' + err.message);
        } finally {
            chatSubmit.disabled = false;
            chatInput.focus();
        }
    }

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;
        chatInput.value = '';
        sendMessage(msg);
    });
    document.querySelectorAll('.suggest').forEach(btn => {
        btn.addEventListener('click', () => {
            const q = btn.getAttribute('data-q');
            if (q) sendMessage(q);
        });
    });
    document.getElementById('btnResetChat').addEventListener('click', () => {
        if (!confirm('Effacer la conversation ?')) return;
        history = [];
        chatMessages.innerHTML = '';
        appendBot("Conversation réinitialisée. Posez-moi une nouvelle question.");
    });
})();
</script>
