<?php
/**
 * Partial Chatbox IA réutilisable
 *
 * Variables attendues :
 * - $chatId        : ID unique du chat (ex: 'fiscalChat', 'adminChat')
 * - $chatTitle     : Titre affiché (ex: 'Assistant Fiscal')
 * - $chatEndpoint  : URL de l'endpoint AJAX (ex: BASE_URL . '/coproprietaire/chatDeclaration')
 * - $chatHeight    : Hauteur zone messages (défaut: '500px')
 * - $chatWelcome   : HTML du message de bienvenue (optionnel)
 * - $chatPlaceholder: Placeholder du champ de saisie (optionnel)
 * - $chatExtraData : Données supplémentaires envoyées avec chaque message (JSON object)
 * - $showRecapBtn  : Afficher le bouton récapitulatif (bool, défaut: false)
 * - $recapEndpoint : URL du récap si différent du chat
 */
$chatId = $chatId ?? 'aiChat';
$chatTitle = $chatTitle ?? 'Assistant IA';
$chatHeight = $chatHeight ?? '500px';
$chatPlaceholder = $chatPlaceholder ?? 'Posez votre question...';
$chatExtraData = $chatExtraData ?? '{}';
$showRecapBtn = $showRecapBtn ?? false;
?>

<div class="card shadow" id="<?= $chatId ?>Container">
    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#6610f2,#8b5cf6)">
        <h5 class="mb-0"><i class="fas fa-robot me-2"></i><?= htmlspecialchars($chatTitle) ?></h5>
        <span class="badge bg-light text-dark"><i class="fas fa-brain me-1"></i>Claude AI</span>
    </div>
    <div class="card-body p-0">
        <!-- Messages -->
        <div id="<?= $chatId ?>Messages" style="height:<?= $chatHeight ?>;overflow-y:auto;padding:20px;background:#f8f9fa">
            <?php if (!empty($chatWelcome)): ?>
            <div class="d-flex mb-3">
                <div class="me-2">
                    <div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div>
                </div>
                <div class="bg-white rounded p-3 shadow-sm" style="max-width:85%">
                    <strong class="text-primary d-block mb-1"><?= htmlspecialchars($chatTitle) ?></strong>
                    <?= $chatWelcome ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Saisie -->
        <div class="border-top p-3">
            <div class="input-group">
                <input type="text" class="form-control" id="<?= $chatId ?>Input"
                       placeholder="<?= htmlspecialchars($chatPlaceholder) ?>"
                       autocomplete="off">
                <button class="btn btn-primary" id="<?= $chatId ?>SendBtn" type="button">
                    <i class="fas fa-paper-plane me-1"></i>Envoyer
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                    Informations à titre indicatif.
                </small>
                <?php if ($showRecapBtn): ?>
                <button class="btn btn-success btn-sm" id="<?= $chatId ?>RecapBtn" type="button">
                    <i class="fas fa-file-alt me-1"></i>Générer le récapitulatif
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const CHAT_ID = '<?= $chatId ?>';
    const ENDPOINT = '<?= $chatEndpoint ?>';
    const EXTRA_DATA = <?= $chatExtraData ?>;
    const messagesDiv = document.getElementById(CHAT_ID + 'Messages');
    const inputEl = document.getElementById(CHAT_ID + 'Input');
    const sendBtn = document.getElementById(CHAT_ID + 'SendBtn');
    let history = [];
    let isLoading = false;

    // Exposer les fonctions globalement pour que la page parente puisse les utiliser
    window[CHAT_ID] = {
        addMessage: addMessage,
        sendCustomMessage: sendCustomMessage,
        getHistory: () => history,
        setPendingFiles: (ids) => { window[CHAT_ID]._pendingDocIds = ids; },
    };
    window[CHAT_ID]._pendingDocIds = [];

    function addMessage(role, content) {
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = 'd-flex mb-3 ' + (isUser ? 'justify-content-end' : '');

        let html = content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/^### (.*)/gm, '<h5 class="mt-2">$1</h5>')
            .replace(/^## (.*)/gm, '<h4 class="mt-2">$1</h4>')
            .replace(/^- (.*)/gm, '<li>$1</li>')
            .replace(/^• (.*)/gm, '<li>$1</li>')
            .replace(/^(\d+)\. (.*)/gm, '<li>$2</li>')
            .replace(/\n/g, '<br>');

        // Si la réponse contient du HTML brut (tables), le garder
        if (content.includes('<table') || content.includes('<tr')) html = content;

        const avatar = isUser ? '' : '<div class="me-2"><div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div></div>';
        const bg = isUser ? 'bg-primary text-white' : 'bg-white shadow-sm';
        const label = isUser ? '' : '<strong class="text-primary d-block mb-1"><?= addslashes($chatTitle) ?></strong>';

        div.innerHTML = `${avatar}<div class="${bg} rounded p-3" style="max-width:85%">${label}<div class="small">${html}</div></div>`;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    async function sendCustomMessage(message) {
        inputEl.value = message;
        await sendMessage();
    }

    async function sendMessage() {
        const message = inputEl.value.trim();
        if (!message || isLoading) return;
        isLoading = true;
        inputEl.value = '';
        sendBtn.disabled = true;
        addMessage('user', message);

        // Loading indicator
        const loading = document.createElement('div');
        loading.id = CHAT_ID + 'Loading';
        loading.className = 'd-flex mb-3';
        loading.innerHTML = '<div class="me-2"><div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div></div><div class="bg-white rounded p-3 shadow-sm"><div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted small">Analyse en cours...</span></div></div>';
        messagesDiv.appendChild(loading);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        try {
            const body = {
                message: message,
                history: history,
                ...EXTRA_DATA,
            };
            // Ajouter les fichiers en attente
            const pendingIds = window[CHAT_ID]._pendingDocIds || [];
            if (pendingIds.length) body.documentIds = pendingIds;

            const resp = await fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await resp.json();
            loading.remove();

            if (data.success) {
                addMessage('assistant', data.message);
                history.push({ role: 'user', content: message });
                history.push({ role: 'assistant', content: data.message });
                if (history.length > 20) history = history.slice(-20);

                // Callback pour la page parente
                if (window[CHAT_ID].onResponse) window[CHAT_ID].onResponse(data, pendingIds);
            } else {
                addMessage('assistant', '⚠️ ' + (data.message || 'Erreur'));
            }
        } catch(e) {
            loading.remove();
            addMessage('assistant', '⚠️ Erreur de connexion.');
        }

        window[CHAT_ID]._pendingDocIds = [];
        isLoading = false;
        sendBtn.disabled = false;
        inputEl.focus();
    }

    sendBtn.addEventListener('click', sendMessage);
    inputEl.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });
})();
</script>
