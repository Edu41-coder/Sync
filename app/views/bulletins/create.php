<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-file-invoice',   'text' => 'Bulletins',       'url' => BASE_URL . '/bulletinPaie/index'],
    ['icon' => 'fas fa-plus',           'text' => 'Nouveau bulletin', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-plus me-2 text-success"></i>Nouveau bulletin de paie</h2>

    <form method="POST" action="<?= BASE_URL ?>/bulletinPaie/store">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="row g-4">
            <!-- Sélection salarié + période -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-user me-2"></i>Salarié et période</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Salarié <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required onchange="reloadHeures(this)">
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($staff as $s): ?>
                                <option value="<?= (int)$s['user_id'] ?>" <?= $preUserId == $s['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(trim($s['prenom'] . ' ' . $s['nom'])) ?> — <?= htmlspecialchars($s['type_contrat'] ?? '?') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Seuls les salariés avec une fiche RH active sont listés.</small>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Année <span class="text-danger">*</span></label>
                                <select name="annee" class="form-select" required onchange="this.form.submit()">
                                    <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 2; $y--): ?>
                                    <option value="<?= $y ?>" <?= $preAnnee == $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mois <span class="text-danger">*</span></label>
                                <select name="mois" class="form-select" required onchange="this.form.submit()">
                                    <?php foreach ($moisLabels as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $preMois == $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <p class="small text-muted mt-3 mb-0">
                            <i class="fas fa-info-circle me-1"></i>Les heures travaillées sont importées automatiquement depuis le planning du module concerné.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Heures -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-clock me-2"></i>Heures travaillées</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Heures normales</label>
                                <input type="number" step="0.01" min="0" name="heures_normales" class="form-control" value="<?= htmlspecialchars((string)($preHeures['heures_normales'] ?? 151.67)) ?>">
                                <small class="text-muted">Base 151,67h pour temps plein</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mode heures sup</label>
                                <select name="mode_heures_sup" class="form-select">
                                    <option value="paiement">Paiement</option>
                                    <option value="repos_compensateur">Repos compensateur</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Heures sup 25%</label>
                                <input type="number" step="0.01" min="0" name="heures_sup_25" class="form-control" value="<?= htmlspecialchars((string)($preHeures['heures_sup_25'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Heures sup 50%</label>
                                <input type="number" step="0.01" min="0" name="heures_sup_50" class="form-control" value="<?= htmlspecialchars((string)($preHeures['heures_sup_50'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Heures repos comp.</label>
                                <input type="number" step="0.01" min="0" name="heures_repos_compensateur" class="form-control" value="0">
                                <small class="text-muted">Si mode = repos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primes & indemnités -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-gift me-2"></i>Primes &amp; indemnités</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Primes (€)</label>
                                <input type="number" step="0.01" min="0" name="primes" class="form-control" value="0">
                                <small class="text-muted">Anciennete, performance, 13e mois...</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Indemnités (€)</label>
                                <input type="number" step="0.01" min="0" name="indemnites" class="form-control" value="0">
                                <small class="text-muted">Transport, repas, etc.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAS -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-percentage me-2"></i>Prélèvement à la source</h6></div>
                    <div class="card-body">
                        <label class="form-label">Taux PAS (%)</label>
                        <input type="number" step="0.001" min="0" max="50" name="taux_pas" class="form-control" value="0" placeholder="Ex: 5.5">
                        <small class="text-muted">Communiqué par l'administration fiscale (taux personnalisé du salarié).</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="<?= BASE_URL ?>/bulletinPaie/index" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Annuler</a>
            <button type="submit" class="btn btn-success"><i class="fas fa-calculator me-1"></i>Calculer et créer le brouillon</button>
        </div>
    </form>
</div>

<script>
function reloadHeures(sel) {
    // Recharger la page avec user_id pour import auto des heures planning
    if (sel.value) {
        const url = new URL(window.location);
        url.searchParams.set('user_id', sel.value);
        window.location = url;
    }
}
</script>
