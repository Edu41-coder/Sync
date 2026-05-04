<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-robot',          'text' => 'Assistant IA',    'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$libellePeriode = $mois ? ($moisLabels[$mois - 1] . ' ' . $annee) : 'Année ' . $annee;
$residenceLabel = $selectedResidence
    ? (function() use ($residences, $selectedResidence) {
        foreach ($residences as $r) if ((int)$r['id'] === $selectedResidence) return $r['nom'];
        return 'Résidence inconnue';
    })()
    : 'Toutes les résidences accessibles';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            <i class="fas fa-robot me-2 text-primary"></i>Assistant comptable IA
        </h2>
        <a href="<?= BASE_URL ?>/comptabilite/index" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Tableau de bord
        </a>
    </div>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        L'assistant analyse les écritures comptables réelles de la période sélectionnée.
        <strong>Pilote — non contractuel.</strong> Les analyses doivent être validées par un expert-comptable
        avant toute décision opérationnelle ou fiscale.
    </div>

    <!-- Filtres période -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes les résidences accessibles</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Mois (optionnel)</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Année complète</option>
                        <?php foreach ($moisLabels as $i => $lbl): ?>
                        <option value="<?= $i + 1 ?>" <?= ($i + 1) === (int)$mois ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-sync me-1"></i>Recharger</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Bandeau périmètre -->
    <div class="alert alert-secondary py-2 small d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-bullseye me-1"></i>
            <strong>Périmètre actif :</strong>
            <?= htmlspecialchars($residenceLabel) ?> · <?= htmlspecialchars($libellePeriode) ?>
        </span>
        <button type="button" id="btnResetChat" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-eraser me-1"></i>Réinitialiser
        </button>
    </div>

    <!-- Suggestions de questions -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="small text-muted mb-2"><i class="fas fa-lightbulb me-1 text-warning"></i>Suggestions :</div>
            <div class="d-flex flex-wrap gap-2" id="suggestions">
                <button class="btn btn-sm btn-outline-primary suggest" data-q="Donne-moi une analyse synthétique de la période. Quels sont les points marquants ?">📊 Vue d'ensemble</button>
                <button class="btn btn-sm btn-outline-primary suggest" data-q="Quel module a le plus dépensé sur cette période et pourquoi ? Comment optimiser ?">💸 Analyse dépenses par module</button>
                <button class="btn btn-sm btn-outline-primary suggest" data-q="Compare avec l'année précédente. Quelle évolution observes-tu ?">📈 Comparaison N vs N-1</button>
                <button class="btn btn-sm btn-outline-primary suggest" data-q="Y a-t-il des anomalies dans les écritures ? Y a-t-il des dépenses suspectes ou des doublons ?">🔍 Détection d'anomalies</button>
                <button class="btn btn-sm btn-outline-primary suggest" data-q="Aide-moi à préparer la déclaration TVA pour cette période. Quels sont les points d'attention ?">🧾 Préparation TVA</button>
                <button class="btn btn-sm btn-outline-primary suggest" data-q="Quelles sont les pistes pour améliorer le résultat net ?">🎯 Optimisation budgétaire</button>
            </div>
        </div>
    </div>

    <!-- Chatbox -->
    <div class="card shadow-sm" style="background:#f8f9fc;">
        <div class="card-body" style="height: 500px; overflow-y: auto;" id="chatMessages">
            <div class="d-flex mb-3">
                <div class="me-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div>
                </div>
                <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                    <strong class="text-primary d-block mb-1">Assistant comptable</strong>
                    Bonjour. Je suis votre assistant comptable. J'ai accès aux écritures réelles de
                    <strong><?= htmlspecialchars($residenceLabel) ?></strong> sur <strong><?= htmlspecialchars($libellePeriode) ?></strong>.
                    <br><br>
                    Posez-moi une question, ou cliquez sur une suggestion ci-dessus pour démarrer.
                </div>
            </div>
        </div>
        <div class="card-footer bg-white">
            <form id="chatForm" class="d-flex gap-2">
                <input type="text" id="chatInput" class="form-control" placeholder="Posez votre question comptable..." autocomplete="off" required>
                <button type="submit" id="chatSubmit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-1"></i>Envoyer
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const BASE        = '<?= BASE_URL ?>';
    const RESIDENCE   = <?= (int)$selectedResidence ?>;
    const ANNEE       = <?= (int)$annee ?>;
    const MOIS        = <?= $mois ? (int)$mois : 'null' ?>;
    let history       = [];

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
        // Listes simples
        h = h.replace(/^[-•]\s+(.+)$/gm, '<li>$1</li>');
        h = h.replace(/(<li>.+<\/li>(?:<br>)?)+/gs, m => '<ul class="mb-2">' + m.replace(/<br>/g, '') + '</ul>');
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
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div>
            </div>
            <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                <strong class="text-primary d-block mb-1">Assistant comptable</strong>
                ${md(text)}
            </div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function appendTyping() {
        const div = document.createElement('div');
        div.id = 'typingIndicator';
        div.className = 'd-flex mb-3';
        div.innerHTML = `<div class="me-2"><div class="rounded-circle d-flex align-items-center justify-content-center" style="background:#0d6efd;color:#fff;width:35px;height:35px"><i class="fas fa-robot"></i></div></div>
            <div class="bg-white rounded p-3 shadow-sm"><em class="text-muted"><i class="fas fa-circle-notch fa-spin me-2"></i>L'assistant analyse les écritures…</em></div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function sendMessage(msg) {
        appendUser(msg);
        chatSubmit.disabled = true;
        appendTyping();

        try {
            const r = await fetch(BASE + '/comptabilite/chat', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({
                    message: msg,
                    history: history,
                    residence_id: RESIDENCE,
                    annee: ANNEE,
                    mois: MOIS
                })
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
