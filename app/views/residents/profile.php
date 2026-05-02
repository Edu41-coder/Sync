<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',      'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-user-circle',    'text' => 'Mon profil',      'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-user-circle me-2"></i>Mon profil</h1>
            <p class="text-muted mb-0">Vos informations personnelles. Pour toute modification, contactez la direction.</p>
        </div>
    </div>

    <div class="row">
        <!-- Carte profil -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (!empty($user->photo_profil)): ?>
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($user->photo_profil) ?>" alt="Photo"
                                 class="rounded-circle" style="width:110px;height:110px;object-fit:cover;border:3px solid #6610f2">
                        <?php else: ?>
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                 style="width:110px;height:110px;background:#6610f2;color:#fff;font-size:2.5rem">
                                <?= strtoupper(substr($user->prenom ?? 'R', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Upload / changer photo -->
                    <div class="mb-3">
                        <form method="POST" action="<?= BASE_URL ?>/user/uploadPhoto" enctype="multipart/form-data" class="d-inline">
                            <?= csrf_field() ?>
                            <label class="btn btn-sm btn-outline-primary" style="cursor:pointer">
                                <i class="fas fa-camera me-1"></i><?= !empty($user->photo_profil) ? 'Changer' : 'Ajouter' ?>
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="d-none" onchange="this.form.submit()">
                            </label>
                        </form>
                        <?php if (!empty($user->photo_profil)): ?>
                        <form method="POST" action="<?= BASE_URL ?>/user/deletePhoto" class="d-inline" onsubmit="return confirm('Supprimer la photo ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger ms-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <br><small class="text-muted">JPG, PNG, WEBP — max 2 Mo</small>
                    </div>

                    <h4 class="mb-1"><?= htmlspecialchars($user->prenom . ' ' . $user->nom) ?></h4>
                    <p class="text-muted mb-2">@<?= htmlspecialchars($user->username) ?></p>
                    <span class="badge mb-3" style="background:#6610f2">
                        <i class="fas fa-user-circle me-1"></i>Résident Senior
                    </span>

                    <div class="mb-3">
                        <?php if ($user->actif): ?>
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Compte actif</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Compte désactivé</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($user->last_login): ?>
                    <p class="text-muted small mb-0">
                        <i class="far fa-clock me-1"></i>Dernière connexion : <?= date('d/m/Y à H:i', strtotime($user->last_login)) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-link me-2"></i>Liens rapides</strong>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>/resident/show/<?= (int)($resident['id'] ?? 0) ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-id-card me-2 text-info"></i>Ma fiche résident détaillée
                    </a>
                    <a href="<?= BASE_URL ?>/message/index" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2 text-primary"></i>Ma messagerie
                    </a>
                    <a href="<?= BASE_URL ?>/resident/calendrier" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2 text-success"></i>Mon calendrier
                    </a>
                </div>
            </div>
        </div>

        <!-- Détails + sécurité -->
        <div class="col-md-8">

            <!-- Infos compte (lecture seule) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-id-card me-2"></i>Informations du compte</strong>
                    <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Lecture seule</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user->prenom ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user->nom ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Identifiant</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user->username ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user->email ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user->telephone ?? '') ?>" disabled>
                        </div>
                        <?php if ($resident): ?>
                        <div class="col-md-6">
                            <label class="form-label">Niveau d'autonomie</label>
                            <input type="text" class="form-control text-uppercase" value="<?= htmlspecialchars($resident['niveau_autonomie'] ?? '-') ?>" disabled>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Pour modifier vos informations personnelles (nom, email, téléphone, etc.),
                        contactez la direction de votre résidence via la
                        <a href="<?= BASE_URL ?>/message/index">messagerie interne</a>.
                    </div>
                </div>
            </div>

            <!-- Sécurité : mot de passe -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-shield-alt me-2"></i>Sécurité</strong>
                </div>
                <div class="card-body">

                    <!-- Aide-mémoire mot de passe (visible) -->
                    <?php if (!empty($user->password_plain)): ?>
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-key text-warning me-1"></i>Mon mot de passe actuel
                            <small class="text-muted">(aide-mémoire)</small>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="passwordPlainInput"
                                   value="<?= htmlspecialchars($user->password_plain) ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                    title="Afficher / masquer">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="copyPassword" title="Copier">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Cliquez sur l'œil pour afficher le mot de passe. Ne le partagez avec personne.
                        </small>
                    </div>
                    <hr>
                    <?php endif; ?>

                    <!-- Formulaire changement mot de passe -->
                    <form method="POST" action="<?= BASE_URL ?>/resident/changePassword">
                        <?= csrf_field() ?>
                        <h6 class="mb-3"><i class="fas fa-edit text-primary me-1"></i>Changer mon mot de passe</h6>

                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control" required minlength="6" autocomplete="new-password">
                                <small class="text-muted">6 caractères minimum</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmer <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6" autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="fas fa-save me-1"></i>Modifier mon mot de passe
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function() {
    const inp = document.getElementById('passwordPlainInput');
    const tgl = document.getElementById('togglePassword');
    const ico = document.getElementById('togglePasswordIcon');
    const cpy = document.getElementById('copyPassword');

    if (tgl && inp) {
        tgl.addEventListener('click', () => {
            const isPwd = inp.type === 'password';
            inp.type = isPwd ? 'text' : 'password';
            ico.classList.toggle('fa-eye', !isPwd);
            ico.classList.toggle('fa-eye-slash', isPwd);
        });
    }
    if (cpy && inp) {
        cpy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(inp.value);
                const orig = cpy.innerHTML;
                cpy.innerHTML = '<i class="fas fa-check text-success"></i>';
                setTimeout(() => cpy.innerHTML = orig, 1500);
            } catch (e) { alert('Copie impossible — sélectionnez et copiez manuellement.'); }
        });
    }
})();
</script>
