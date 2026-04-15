<?php
$title = "Déclaration Fiscale " . $annee;
$typeDocLabels = [
    'tableau_amortissement' => 'Tableau d\'amortissement du prêt',
    'releve_interets' => 'Relevé des intérêts payés',
    'taxe_fonciere' => 'Avis de taxe foncière',
    'facture_travaux' => 'Facture de travaux',
    'assurance_pno' => 'Assurance PNO (propriétaire non occupant)',
    'charges_copropriete' => 'Relevé de charges de copropriété',
    'facture_comptable' => 'Honoraires expert-comptable',
    'bail_commercial' => 'Bail commercial Domitys',
    'releve_loyers' => 'Relevé annuel des loyers perçus',
    'autre' => 'Autre document',
];
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator', 'text' => 'Comptabilité', 'url' => BASE_URL . '/coproprietaire/comptabilite'],
    ['icon' => 'fas fa-file-invoice', 'text' => 'Déclaration ' . $annee, 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <?php if (!$proprietaire): ?>
    <div class="alert alert-warning">Aucun profil propriétaire associé.</div>
    <?php else: ?>

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">
                <i class="fas fa-file-invoice text-dark"></i>
                Déclaration Fiscale <?= $annee ?>
            </h1>
            <div>
                <select class="form-select form-select-sm d-inline-block" style="width:auto" onchange="window.location='<?= BASE_URL ?>/coproprietaire/declarationFiscale?annee='+this.value">
                    <?php for ($y = date('Y') - 1; $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $annee ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <a href="<?= BASE_URL ?>/coproprietaire/comptabilite" class="btn btn-secondary btn-sm ms-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Résumé contrats -->
    <div class="row g-3 mb-4">
        <?php
        $totalLoyer = array_sum(array_column($contrats, 'loyer_mensuel_garanti'));
        $dispositif = $contrats[0]['dispositif_fiscal'] ?? 'LMNP';
        ?>
        <div class="col-12 col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Revenus annuels</h6>
                    <h3 class="mb-0"><?= number_format($totalLoyer * 12, 0, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary mb-1 fw-bold small">Dispositif fiscal</h6>
                    <h3 class="mb-0"><?= htmlspecialchars($dispositif) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Documents uploadés</h6>
                    <h3 class="mb-0"><?= count($documents) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne documents -->
        <div class="col-12 col-lg-4 mb-4">
            <!-- Upload -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Ajouter un document</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">Type de document</label>
                        <select class="form-select form-select-sm" id="uploadType">
                            <?php foreach ($typeDocLabels as $key => $label): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Fichier (PDF, JPG, PNG, XLSX — max 10 Mo)</label>
                        <input type="file" class="form-control form-control-sm" id="uploadFile"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls">
                    </div>
                    <button class="btn btn-danger btn-sm w-100" id="uploadBtn">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Uploader
                    </button>
                    <div id="uploadProgress" class="mt-2 d-none">
                        <div class="progress" style="height:5px">
                            <div class="progress-bar bg-danger" style="width:0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents uploadés -->
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Mes documents (<span id="docCount"><?= count($documents) ?></span>)</h5>
                </div>
                <div class="card-body p-0" id="documentsList">
                    <?php if (empty($documents)): ?>
                    <div class="text-center py-4 text-muted" id="noDocsMsg">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <small>Aucun document. Commencez par uploader vos justificatifs.</small>
                    </div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($documents as $doc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center doc-item" data-id="<?= $doc['id'] ?>">
                            <div class="small">
                                <i class="fas fa-<?= in_array(pathinfo($doc['nom_fichier'], PATHINFO_EXTENSION), ['pdf']) ? 'file-pdf text-danger' : 'file-image text-primary' ?> me-1"></i>
                                <strong><?= $typeDocLabels[$doc['type_document']] ?? $doc['type_document'] ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($doc['nom_fichier']) ?></small>
                            </div>
                            <div>
                                <span class="badge bg-<?= $doc['statut'] === 'analyse' ? 'success' : ($doc['statut'] === 'uploade' ? 'warning' : 'secondary') ?>">
                                    <?= $doc['statut'] === 'analyse' ? 'Analysé' : ($doc['statut'] === 'uploade' ? 'En attente' : $doc['statut']) ?>
                                </span>
                                <button class="btn btn-sm btn-outline-primary ms-1 analyze-btn" data-id="<?= $doc['id'] ?>" title="Analyser">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colonne chat IA -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#6610f2,#8b5cf6)">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Assistant Déclaration Fiscale</h5>
                    <span class="badge bg-light text-dark"><i class="fas fa-brain me-1"></i>Claude AI</span>
                </div>
                <div class="card-body p-0">
                    <!-- Messages -->
                    <div id="chatMessages" style="height:500px;overflow-y:auto;padding:20px;background:#f8f9fa">
                        <div class="d-flex mb-3">
                            <div class="me-2">
                                <div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div>
                            </div>
                            <div class="bg-white rounded p-3 shadow-sm" style="max-width:85%">
                                <strong class="text-primary d-block mb-1">Assistant Fiscal</strong>
                                <p class="mb-1">Bonjour ! Je vais vous guider dans votre déclaration de revenus <?= $annee ?> pour vos biens Domitys.</p>
                                <p class="mb-1"><strong>Étapes :</strong></p>
                                <ol class="small mb-2">
                                    <li>Uploadez vos documents dans le panneau de gauche</li>
                                    <li>Cliquez sur <i class="fas fa-search"></i> pour que j'analyse chaque document</li>
                                    <li>Je vais extraire les montants et vous dire dans quelles cases les reporter</li>
                                    <li>À la fin, je génère votre récapitulatif fiscal complet</li>
                                </ol>
                                <p class="mb-0 small"><strong>Documents nécessaires :</strong></p>
                                <ul class="small mb-0">
                                    <li>Relevé annuel des loyers Domitys</li>
                                    <li>Tableau d'amortissement du prêt</li>
                                    <li>Avis de taxe foncière</li>
                                    <li>Attestation d'assurance PNO</li>
                                    <li>Factures de travaux (si applicable)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Saisie -->
                    <div class="border-top p-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="chatInput"
                                   placeholder="Posez une question ou demandez d'analyser un document..."
                                   autocomplete="off">
                            <button class="btn btn-primary" id="chatSendBtn" type="button">
                                <i class="fas fa-paper-plane me-1"></i>Envoyer
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                Informations à titre indicatif.
                            </small>
                            <button class="btn btn-success btn-sm" id="generateRecapBtn" type="button">
                                <i class="fas fa-file-alt me-1"></i>Générer le récapitulatif fiscal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Récapitulatif fiscal (caché par défaut, affiché après génération) -->
    <div id="recapSection" class="d-none">
        <div class="card shadow mb-4">
            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#198754,#0d6832)">
                <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Récapitulatif Fiscal <?= $annee ?></h5>
                <button class="btn btn-sm btn-light" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Imprimer
                </button>
            </div>
            <div class="card-body" id="recapContent">
                <!-- Contenu généré dynamiquement -->
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<style>
@media print {
    .navbar, .breadcrumb, #chatMessages, .border-top, .card-header button,
    #uploadBtn, #chatSendBtn, #generateRecapBtn, .btn, footer { display: none !important; }
    #recapSection { display: block !important; }
    #recapSection .card { border: none !important; box-shadow: none !important; }
    #recapSection .card-header { background: #198754 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<script>
(function() {
    const BASE = '<?= BASE_URL ?>';
    const DECL_ID = <?= $declaration['id'] ?? 0 ?>;
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatSendBtn = document.getElementById('chatSendBtn');
    let history = [];
    let isLoading = false;
    let pendingDocIds = [];

    // === UPLOAD ===
    document.getElementById('uploadBtn').addEventListener('click', async function() {
        const file = document.getElementById('uploadFile').files[0];
        const type = document.getElementById('uploadType').value;
        if (!file) { alert('Sélectionnez un fichier.'); return; }

        const formData = new FormData();
        formData.append('document', file);
        formData.append('declaration_id', DECL_ID);
        formData.append('type_document', type);

        const progress = document.getElementById('uploadProgress');
        progress.classList.remove('d-none');
        progress.querySelector('.progress-bar').style.width = '50%';

        try {
            const resp = await fetch(BASE + '/coproprietaire/uploadDocument', { method: 'POST', body: formData });
            const data = await resp.json();
            progress.querySelector('.progress-bar').style.width = '100%';

            if (data.success) {
                // Ajouter le document à la liste
                const list = document.getElementById('documentsList');
                const noMsg = document.getElementById('noDocsMsg');
                if (noMsg) noMsg.remove();

                let ul = list.querySelector('ul');
                if (!ul) { ul = document.createElement('ul'); ul.className = 'list-group list-group-flush'; list.appendChild(ul); }

                const typeLabels = <?= json_encode($typeDocLabels) ?>;
                const ext = data.document.ext;
                const icon = ext === 'pdf' ? 'file-pdf text-danger' : 'file-image text-primary';

                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center doc-item';
                li.setAttribute('data-id', data.document.id);
                li.innerHTML = `
                    <div class="small">
                        <i class="fas fa-${icon} me-1"></i>
                        <strong>${typeLabels[type] || type}</strong>
                        <br><small class="text-muted">${data.document.nom}</small>
                    </div>
                    <div>
                        <span class="badge bg-warning">En attente</span>
                        <button class="btn btn-sm btn-outline-primary ms-1 analyze-btn" data-id="${data.document.id}" title="Analyser"><i class="fas fa-search"></i></button>
                    </div>
                `;
                ul.appendChild(li);

                // Update count
                document.getElementById('docCount').textContent = list.querySelectorAll('.doc-item').length;
                document.getElementById('uploadFile').value = '';
                addMessage('assistant', `✅ Document **${typeLabels[type]}** uploadé avec succès. Cliquez sur 🔍 pour que je l'analyse, ou uploadez d'autres documents.`);
            } else {
                addMessage('assistant', '⚠️ ' + data.message);
            }
        } catch(e) {
            addMessage('assistant', '⚠️ Erreur d\'upload.');
        }
        setTimeout(() => progress.classList.add('d-none'), 1000);
    });

    // === ANALYSE DOCUMENT ===
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.analyze-btn');
        if (!btn) return;
        const docId = btn.getAttribute('data-id');
        pendingDocIds = [parseInt(docId)];
        chatInput.value = 'Analyse ce document et extrais les montants importants pour ma déclaration fiscale ' + <?= $annee ?> + '.';
        sendMessage();
    });

    // === CHAT ===
    function addMessage(role, content) {
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = 'd-flex mb-3 ' + (isUser ? 'justify-content-end' : '');

        let html = content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/^- (.*)/gm, '<li>$1</li>')
            .replace(/^• (.*)/gm, '<li>$1</li>')
            .replace(/^(\d+)\. (.*)/gm, '<li>$2</li>')
            .replace(/\n/g, '<br>');

        const avatar = isUser ? '' : '<div class="me-2"><div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div></div>';
        const bg = isUser ? 'bg-primary text-white' : 'bg-white shadow-sm';
        const label = isUser ? '' : '<strong class="text-primary d-block mb-1">Assistant Fiscal</strong>';

        div.innerHTML = `${avatar}<div class="${bg} rounded p-3" style="max-width:85%">${label}<div class="small">${html}</div></div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || isLoading) return;
        isLoading = true;
        chatInput.value = '';
        chatSendBtn.disabled = true;

        addMessage('user', message);

        // Loading
        const loading = document.createElement('div');
        loading.id = 'chatLoading';
        loading.className = 'd-flex mb-3';
        loading.innerHTML = '<div class="me-2"><div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div></div><div class="bg-white rounded p-3 shadow-sm"><div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted small">Analyse en cours...</span></div></div>';
        chatMessages.appendChild(loading);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        try {
            const resp = await fetch(BASE + '/coproprietaire/chatDeclaration', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message,
                    history: history,
                    documentIds: pendingDocIds,
                    declarationId: DECL_ID
                })
            });
            const data = await resp.json();
            loading.remove();

            if (data.success) {
                addMessage('assistant', data.message);
                history.push({ role: 'user', content: message });
                history.push({ role: 'assistant', content: data.message });
                if (history.length > 20) history = history.slice(-20);

                // Marquer les docs comme analysés
                if (pendingDocIds.length) {
                    pendingDocIds.forEach(id => {
                        const item = document.querySelector(`.doc-item[data-id="${id}"]`);
                        if (item) {
                            const badge = item.querySelector('.badge');
                            if (badge) { badge.className = 'badge bg-success'; badge.textContent = 'Analysé'; }
                        }
                    });
                }
            } else {
                addMessage('assistant', '⚠️ ' + (data.message || 'Erreur'));
            }
        } catch(e) {
            loading.remove();
            addMessage('assistant', '⚠️ Erreur de connexion.');
        }

        pendingDocIds = [];
        isLoading = false;
        chatSendBtn.disabled = false;
        chatInput.focus();
    }

    chatSendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });

    // === RÉCAPITULATIF ===
    document.getElementById('generateRecapBtn').addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Génération en cours...';

        // Demander à Claude de générer le récapitulatif complet
        const recapMessage = "Génère mon récapitulatif fiscal complet pour l'année " + <?= $annee ?> + ". " +
            "Présente un tableau avec : 1) Tous les revenus, 2) Toutes les charges déductibles avec montants, " +
            "3) Le calcul du résultat fiscal, 4) Les cases exactes à remplir sur les formulaires 2042-C-PRO et/ou 2031/2033 " +
            "avec les numéros de cases et montants. Présente sous forme de tableau HTML propre.";

        try {
            const resp = await fetch(BASE + '/coproprietaire/chatDeclaration', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: recapMessage,
                    history: history,
                    documentIds: [],
                    declarationId: DECL_ID
                })
            });
            const data = await resp.json();

            if (data.success) {
                // Convertir markdown en HTML
                let html = data.message
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/^### (.*)/gm, '<h5 class="mt-3">$1</h5>')
                    .replace(/^## (.*)/gm, '<h4 class="mt-3">$1</h4>')
                    .replace(/^# (.*)/gm, '<h3 class="mt-3">$1</h3>')
                    .replace(/^- (.*)/gm, '<li>$1</li>')
                    .replace(/\n/g, '<br>');

                // Si la réponse contient déjà du HTML (tables), le garder
                if (data.message.includes('<table') || data.message.includes('<tr')) {
                    html = data.message;
                }

                const recapSection = document.getElementById('recapSection');
                const recapContent = document.getElementById('recapContent');

                recapContent.innerHTML = `
                    <div class="mb-3 text-end small text-muted">
                        <i class="fas fa-calendar me-1"></i>Généré le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}
                    </div>
                    <div class="mb-3">
                        <strong>Propriétaire :</strong> <?= htmlspecialchars(($proprietaire['prenom'] ?? '') . ' ' . ($proprietaire['nom'] ?? '')) ?><br>
                        <strong>Année fiscale :</strong> <?= $annee ?><br>
                        <strong>Dispositif :</strong> <?= htmlspecialchars($contrats[0]['dispositif_fiscal'] ?? 'LMNP') ?>
                    </div>
                    <hr>
                    <div>${html}</div>
                    <hr>
                    <div class="alert alert-warning alert-permanent small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Avertissement :</strong> Ce récapitulatif est généré automatiquement à titre indicatif.
                        Les montants et cases fiscales doivent être vérifiés par un expert-comptable avant soumission aux impôts.
                    </div>
                `;

                recapSection.classList.remove('d-none');
                recapSection.scrollIntoView({ behavior: 'smooth' });

                // Aussi ajouter dans le chat
                addMessage('assistant', '✅ **Récapitulatif généré !** Faites défiler vers le bas pour le voir. Vous pouvez l\'imprimer avec le bouton dédié.');
                history.push({ role: 'user', content: recapMessage });
                history.push({ role: 'assistant', content: data.message });
            } else {
                addMessage('assistant', '⚠️ ' + (data.message || 'Erreur lors de la génération.'));
            }
        } catch(e) {
            addMessage('assistant', '⚠️ Erreur de connexion.');
        }

        this.disabled = false;
        this.innerHTML = '<i class="fas fa-file-alt me-1"></i>Générer le récapitulatif fiscal';
    });
})();
</script>
