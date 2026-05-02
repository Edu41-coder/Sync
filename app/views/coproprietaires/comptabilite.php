<?php $title = "Ma Comptabilité"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-home', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator', 'text' => 'Comptabilité', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-calculator text-dark"></i> Ma Comptabilité</h1>
        </div>
    </div>

    <?php if (!$proprietaire): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>Aucun profil propriétaire associé à votre compte.
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Revenus / mois</h6>
                    <h2 class="mb-0 text-success"><?= number_format($totaux['revenus_mensuels'], 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary mb-1 fw-bold small">Revenus / an</h6>
                    <h2 class="mb-0 text-primary"><?= number_format($totaux['revenus_annuels'], 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-danger mb-1 fw-bold small">Charges déductibles</h6>
                    <h2 class="mb-0 text-danger"><?= number_format($totaux['total_charges'], 0, ',', ' ') ?> €</h2>
                    <small class="text-muted">dernière année</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Résultat fiscal</h6>
                    <h2 class="mb-0"><?= number_format($totaux['resultat_fiscal'], 0, ',', ' ') ?> €</h2>
                    <small class="text-muted">dernière année</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenus par contrat -->
        <div class="col-12 col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Revenus par lot (<?= count($contrats) ?> contrats)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Contrat</th>
                                    <th>Résidence / Lot</th>
                                    <th class="text-end">Loyer / mois</th>
                                    <th class="text-end">Loyer / an</th>
                                    <th class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contrats as $c):
                                    $sc = ['actif'=>'success','resilie'=>'danger','termine'=>'secondary','suspendu'=>'warning','projet'=>'info'];
                                ?>
                                <tr class="<?= $c['statut'] !== 'actif' ? 'table-secondary' : '' ?>">
                                    <td><strong><?= htmlspecialchars($c['numero_contrat'] ?? '-') ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($c['residence_nom'] ?? '-') ?>
                                        <br><small class="text-muted">Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?> (<?= $c['lot_type'] ?? '' ?>)</small>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($c['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €</td>
                                    <td class="text-end"><?= number_format(($c['loyer_mensuel_garanti'] ?? 0) * 12, 2, ',', ' ') ?> €</td>
                                    <td class="text-center"><span class="badge bg-<?= $sc[$c['statut']] ?? 'secondary' ?>"><?= ucfirst($c['statut']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (!empty($contrats)): ?>
                                <tr class="table-success fw-bold">
                                    <td colspan="2">Total contrats actifs</td>
                                    <td class="text-end"><?= number_format($totaux['revenus_mensuels'], 2, ',', ' ') ?> €</td>
                                    <td class="text-end"><?= number_format($totaux['revenus_annuels'], 2, ',', ' ') ?> €</td>
                                    <td></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique fiscal -->
        <div class="col-12 col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#198754,#0d6832)">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique fiscal</h5>
                </div>
                <?php if (empty($fiscalite)): ?>
                <div class="card-body text-center py-4">
                    <i class="fas fa-calculator fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted">Aucune donnée fiscale</h6>
                </div>
                <?php else: ?>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($fiscalite as $f):
                            $regimeLabels = ['micro_bic'=>'Micro-BIC','reel_simplifie'=>'Réel simplifié','reel_normal'=>'Réel normal'];
                            $tc = ($f['charges_deductibles'] ?? 0) + ($f['interets_emprunt'] ?? 0) + ($f['travaux_deductibles'] ?? 0)
                                + ($f['assurances_deductibles'] ?? 0) + ($f['taxe_fonciere_deductible'] ?? 0) + ($f['autres_charges_deductibles'] ?? 0);
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar me-1"></i><?= $f['annee_fiscale'] ?>
                                    <?php if ($f['numero_lot']): ?><small class="text-muted ms-1">(Lot <?= htmlspecialchars($f['numero_lot']) ?>)</small><?php endif; ?>
                                </h6>
                                <span class="badge bg-primary"><?= $regimeLabels[$f['regime_fiscal']] ?? $f['regime_fiscal'] ?></span>
                            </div>
                            <div class="row g-1 small">
                                <div class="col-4">
                                    <span class="text-muted">Bruts</span><br>
                                    <strong class="text-success"><?= number_format($f['revenus_bruts'] ?? 0, 0, ',', ' ') ?> €</strong>
                                </div>
                                <div class="col-4">
                                    <span class="text-muted">Charges</span><br>
                                    <strong class="text-danger"><?= number_format($tc, 0, ',', ' ') ?> €</strong>
                                </div>
                                <div class="col-4">
                                    <span class="text-muted">Résultat</span><br>
                                    <strong><?= number_format($f['resultat_fiscal'] ?? 0, 0, ',', ' ') ?> €</strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="card shadow mb-4">
        <div class="card-body small">
            <h6><i class="fas fa-info-circle text-info me-1"></i>Information</h6>
            <p class="mb-0">
                Les données fiscales sont renseignées par l'administration Domitys après la clôture de chaque exercice.
                <a href="<?= BASE_URL ?>/coproprietaire/declarationFiscale" class="btn btn-sm btn-primary ms-2">
                    <i class="fas fa-file-invoice me-1"></i>Préparer ma déclaration fiscale avec l'IA
                </a>
            </p>
        </div>
    </div>

    <!-- Assistant Fiscal IA -->
    <div class="card shadow mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#6610f2,#8b5cf6)">
            <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Assistant Fiscal IA</h5>
            <span class="badge bg-light text-dark"><i class="fas fa-brain me-1"></i>Propulsé par Claude AI</span>
        </div>
        <div class="card-body p-0">
            <!-- Zone de messages -->
            <div id="chatMessages" style="height:400px;overflow-y:auto;padding:20px;background:#f8f9fa">
                <!-- Message de bienvenue -->
                <div class="d-flex mb-3">
                    <div class="me-2">
                        <div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem">
                            <i class="fas fa-robot"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded p-3 shadow-sm" style="max-width:80%">
                        <strong class="text-primary d-block mb-1">Assistant Fiscal</strong>
                        <p class="mb-1">Bonjour ! Je suis votre assistant fiscal spécialisé dans les revenus locatifs Domitys.</p>
                        <p class="mb-1">Je peux vous aider avec :</p>
                        <ul class="small mb-1">
                            <li>Votre déclaration de revenus LMNP/LMP</li>
                            <li>Les formulaires à remplir (2042, 2042-C-PRO, 2031...)</li>
                            <li>Les charges déductibles et amortissements</li>
                            <li>Le dispositif Censi-Bouvard</li>
                            <li>La récupération de TVA</li>
                        </ul>
                        <p class="mb-0 small text-muted"><i class="fas fa-info-circle me-1"></i>Vos données fiscales sont automatiquement prises en compte dans mes réponses.</p>
                    </div>
                </div>
            </div>

            <!-- Zone de saisie -->
            <div class="border-top p-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="chatInput"
                           placeholder="Posez votre question fiscale..."
                           autocomplete="off">
                    <button class="btn btn-primary" id="chatSendBtn" type="button">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer
                    </button>
                </div>
                <small class="text-muted mt-1 d-block">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                    Informations à titre indicatif. Pour des cas complexes, consultez un expert-comptable.
                </small>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
(function() {
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatSendBtn = document.getElementById('chatSendBtn');
    const baseUrl = '<?= BASE_URL ?>';
    let history = [];
    let isLoading = false;

    function jsonHeaders() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return { 'Content-Type': 'application/json', 'X-CSRF-Token': meta ? meta.content : '' };
    }

    function addMessage(role, content) {
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = 'd-flex mb-3 ' + (isUser ? 'justify-content-end' : '');

        const avatar = isUser
            ? ''
            : `<div class="me-2"><div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div></div>`;

        // Convertir le markdown basique en HTML
        let html = content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/^- (.*)/gm, '<li>$1</li>')
            .replace(/^• (.*)/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            .replace(/\n/g, '<br>');

        const bgClass = isUser ? 'bg-primary text-white' : 'bg-white shadow-sm';
        const label = isUser
            ? ''
            : '<strong class="text-primary d-block mb-1">Assistant Fiscal</strong>';

        div.innerHTML = `
            ${avatar}
            <div class="${bgClass} rounded p-3" style="max-width:80%">
                ${label}
                <div class="small">${html}</div>
            </div>
        `;

        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function addLoadingIndicator() {
        const div = document.createElement('div');
        div.className = 'd-flex mb-3';
        div.id = 'chatLoading';
        div.innerHTML = `
            <div class="me-2"><div class="avatar-circle avatar-sm" style="background:#6610f2;color:#fff;width:35px;height:35px;font-size:0.8rem"><i class="fas fa-robot"></i></div></div>
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span class="text-muted small">Réflexion en cours...</span>
                </div>
            </div>
        `;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function removeLoadingIndicator() {
        const el = document.getElementById('chatLoading');
        if (el) el.remove();
    }

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || isLoading) return;

        isLoading = true;
        chatInput.value = '';
        chatSendBtn.disabled = true;

        // Afficher le message utilisateur
        addMessage('user', message);
        addLoadingIndicator();

        try {
            const response = await fetch(baseUrl + '/coproprietaire/chat', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({
                    message: message,
                    history: history
                })
            });

            const data = await response.json();
            removeLoadingIndicator();

            if (data.success) {
                addMessage('assistant', data.message);
                // Garder l'historique pour le contexte
                history.push({ role: 'user', content: message });
                history.push({ role: 'assistant', content: data.message });
                // Limiter l'historique à 20 messages
                if (history.length > 20) history = history.slice(-20);
            } else {
                addMessage('assistant', '⚠️ ' + (data.message || 'Erreur de communication.'));
            }
        } catch (error) {
            removeLoadingIndicator();
            addMessage('assistant', '⚠️ Erreur de connexion. Veuillez réessayer.');
        }

        isLoading = false;
        chatSendBtn.disabled = false;
        chatInput.focus();
    }

    chatSendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
})();
</script>
