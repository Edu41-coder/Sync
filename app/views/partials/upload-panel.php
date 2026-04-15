<?php
/**
 * Partial Upload Panel réutilisable
 *
 * Variables attendues :
 * - $uploadId       : ID unique du panneau (ex: 'fiscalUpload')
 * - $uploadEndpoint : URL de l'endpoint upload (ex: BASE_URL . '/coproprietaire/uploadDocument')
 * - $uploadTypes    : Array [slug => label] des types de documents
 * - $uploadExtraData: Données supplémentaires (ex: ['declaration_id' => 5])
 * - $uploadedDocs   : Array des documents déjà uploadés (optionnel)
 * - $chatId         : ID du chatbox associé (pour les messages de feedback)
 */
$uploadId = $uploadId ?? 'fileUpload';
$uploadTypes = $uploadTypes ?? ['autre' => 'Document'];
$uploadExtraData = $uploadExtraData ?? [];
$uploadedDocs = $uploadedDocs ?? [];
$chatId = $chatId ?? 'aiChat';
?>

<!-- Upload -->
<div class="card shadow mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Ajouter un document</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label small">Type de document</label>
            <select class="form-select form-select-sm" id="<?= $uploadId ?>Type">
                <?php foreach ($uploadTypes as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label small">Fichier (PDF, JPG, PNG, XLSX — max 10 Mo)</label>
            <input type="file" class="form-control form-control-sm" id="<?= $uploadId ?>File"
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls">
        </div>
        <button class="btn btn-danger btn-sm w-100" id="<?= $uploadId ?>Btn">
            <i class="fas fa-cloud-upload-alt me-1"></i>Uploader
        </button>
        <div id="<?= $uploadId ?>Progress" class="mt-2 d-none">
            <div class="progress" style="height:5px">
                <div class="progress-bar bg-danger" style="width:0%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Documents uploadés -->
<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Documents (<span id="<?= $uploadId ?>Count"><?= count($uploadedDocs) ?></span>)</h5>
    </div>
    <div class="card-body p-0" id="<?= $uploadId ?>List">
        <?php if (empty($uploadedDocs)): ?>
        <div class="text-center py-4 text-muted" id="<?= $uploadId ?>NoMsg">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
            <small>Aucun document. Uploadez vos justificatifs.</small>
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($uploadedDocs as $doc):
                $docExt = pathinfo($doc['nom_fichier'], PATHINFO_EXTENSION);
                $docIcon = in_array($docExt, ['pdf']) ? 'file-pdf text-danger' : 'file-image text-primary';
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center <?= $uploadId ?>-item" data-id="<?= $doc['id'] ?>">
                <div class="small">
                    <i class="fas fa-<?= $docIcon ?> me-1"></i>
                    <strong><?= htmlspecialchars($uploadTypes[$doc['type_document']] ?? $doc['type_document']) ?></strong>
                    <br><small class="text-muted"><?= htmlspecialchars($doc['nom_fichier']) ?></small>
                </div>
                <div>
                    <span class="badge bg-<?= $doc['statut'] === 'analyse' ? 'success' : 'warning' ?>">
                        <?= $doc['statut'] === 'analyse' ? 'Analysé' : 'En attente' ?>
                    </span>
                    <button class="btn btn-sm btn-outline-primary ms-1 <?= $uploadId ?>-analyze" data-id="<?= $doc['id'] ?>" title="Analyser">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const UPLOAD_ID = '<?= $uploadId ?>';
    const ENDPOINT = '<?= $uploadEndpoint ?>';
    const EXTRA = <?= json_encode($uploadExtraData) ?>;
    const TYPE_LABELS = <?= json_encode($uploadTypes) ?>;
    const CHAT_ID = '<?= $chatId ?>';

    // Upload
    document.getElementById(UPLOAD_ID + 'Btn').addEventListener('click', async function() {
        const file = document.getElementById(UPLOAD_ID + 'File').files[0];
        const type = document.getElementById(UPLOAD_ID + 'Type').value;
        if (!file) { alert('Sélectionnez un fichier.'); return; }

        const formData = new FormData();
        formData.append('document', file);
        formData.append('type_document', type);
        Object.entries(EXTRA).forEach(([k, v]) => formData.append(k, v));

        const progress = document.getElementById(UPLOAD_ID + 'Progress');
        progress.classList.remove('d-none');
        progress.querySelector('.progress-bar').style.width = '50%';

        try {
            const resp = await fetch(ENDPOINT, { method: 'POST', body: formData });
            const data = await resp.json();
            progress.querySelector('.progress-bar').style.width = '100%';

            if (data.success) {
                const list = document.getElementById(UPLOAD_ID + 'List');
                const noMsg = document.getElementById(UPLOAD_ID + 'NoMsg');
                if (noMsg) noMsg.remove();

                let ul = list.querySelector('ul');
                if (!ul) { ul = document.createElement('ul'); ul.className = 'list-group list-group-flush'; list.appendChild(ul); }

                const ext = data.document.ext;
                const icon = ext === 'pdf' ? 'file-pdf text-danger' : 'file-image text-primary';
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center ' + UPLOAD_ID + '-item';
                li.setAttribute('data-id', data.document.id);
                li.innerHTML = `
                    <div class="small"><i class="fas fa-${icon} me-1"></i><strong>${TYPE_LABELS[type] || type}</strong><br><small class="text-muted">${data.document.nom}</small></div>
                    <div><span class="badge bg-warning">En attente</span><button class="btn btn-sm btn-outline-primary ms-1 ${UPLOAD_ID}-analyze" data-id="${data.document.id}" title="Analyser"><i class="fas fa-search"></i></button></div>
                `;
                ul.appendChild(li);
                document.getElementById(UPLOAD_ID + 'Count').textContent = list.querySelectorAll('.' + UPLOAD_ID + '-item').length;
                document.getElementById(UPLOAD_ID + 'File').value = '';

                if (window[CHAT_ID]) window[CHAT_ID].addMessage('assistant', '✅ Document **' + (TYPE_LABELS[type] || type) + '** uploadé. Cliquez 🔍 pour l\'analyser.');
            } else {
                if (window[CHAT_ID]) window[CHAT_ID].addMessage('assistant', '⚠️ ' + data.message);
            }
        } catch(e) {
            if (window[CHAT_ID]) window[CHAT_ID].addMessage('assistant', '⚠️ Erreur d\'upload.');
        }
        setTimeout(() => progress.classList.add('d-none'), 1000);
    });

    // Analyse
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.' + UPLOAD_ID + '-analyze');
        if (!btn) return;
        const docId = parseInt(btn.getAttribute('data-id'));
        if (window[CHAT_ID]) {
            window[CHAT_ID].setPendingFiles([docId]);
            window[CHAT_ID].sendCustomMessage('Analyse ce document et extrais les montants importants pour ma déclaration fiscale.');
        }
    });

    // Callback : marquer comme analysé quand le chat répond
    if (window[CHAT_ID]) {
        window[CHAT_ID].onResponse = function(data, pendingIds) {
            if (pendingIds && pendingIds.length) {
                pendingIds.forEach(id => {
                    const item = document.querySelector('.' + UPLOAD_ID + '-item[data-id="' + id + '"]');
                    if (item) {
                        const badge = item.querySelector('.badge');
                        if (badge) { badge.className = 'badge bg-success'; badge.textContent = 'Analysé'; }
                    }
                });
            }
        };
    }
})();
</script>
