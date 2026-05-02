<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',          'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-users-cog',      'text' => 'Affectation spécialités', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-users-cog text-primary me-2"></i>Affectation des spécialités</h1>
            <p class="text-muted mb-0">Cochez les spécialités de chaque technicien. Cumul libre.</p>
        </div>
    </div>

    <?php if (empty($matrice)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun technicien actif.</div>
    <?php else: ?>
    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:220px">Technicien</th>
                            <?php foreach ($specialites as $s): ?>
                                <th class="text-center" style="min-width:140px">
                                    <i class="<?= htmlspecialchars($s['icone']) ?>" style="color:<?= htmlspecialchars($s['couleur']) ?>"></i>
                                    <small class="d-block"><?= htmlspecialchars($s['nom']) ?></small>
                                    <?php if (!empty($s['certif_obligatoire'])): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:0.6rem"
                                              title="Certif obligatoire — <?= htmlspecialchars($s['organisme_recommande'] ?? '') ?>">
                                            <i class="fas fa-certificate"></i>
                                        </span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matrice as $u): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong>
                                <br><small class="text-muted">
                                    @<?= htmlspecialchars($u['username']) ?>
                                    <span class="badge <?= $u['role'] === 'technicien_chef' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= $u['role'] === 'technicien_chef' ? 'Chef' : 'Technicien' ?>
                                    </span>
                                </small>
                                <br><a href="<?= BASE_URL ?>/maintenance/certifications/<?= (int)$u['user_id'] ?>" class="small">
                                    <i class="fas fa-certificate me-1"></i>Certifs
                                </a>
                            </td>
                            <?php foreach ($specialites as $s):
                                $coche = isset($u['specs'][$s['id']]);
                                $niveau = $coche ? $u['specs'][$s['id']] : null;
                            ?>
                            <td class="text-center">
                                <?php if ($coche): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/maintenance/affecterSpecialite" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="specialite_id" value="<?= (int)$s['id'] ?>">
                                        <input type="hidden" name="action" value="retirer">
                                        <select name="niveau-current" class="form-select form-select-sm d-inline-block w-auto"
                                                onchange="this.form.action='<?= BASE_URL ?>/maintenance/affecterSpecialite';
                                                         this.form.elements['action'].value='affecter';
                                                         this.form.elements['niveau'].value=this.value;
                                                         this.form.submit()">
                                            <?php foreach ($niveaux as $nv): ?>
                                                <option value="<?= $nv ?>" <?= $nv === $niveau ? 'selected' : '' ?>><?= $nv ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="niveau" value="<?= htmlspecialchars($niveau) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="Retirer">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= BASE_URL ?>/maintenance/affecterSpecialite" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="specialite_id" value="<?= (int)$s['id'] ?>">
                                        <input type="hidden" name="action" value="affecter">
                                        <input type="hidden" name="niveau" value="confirme">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Affecter">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="alert alert-info mt-3 small">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Légende :</strong> Bouton vert = affecter au niveau "confirmé" — sélecteur = changer le niveau (debutant / confirme / expert) — bouton rouge = retirer.
        <br>L'icône <i class="fas fa-certificate text-warning"></i> sur une colonne signale qu'une <strong>certification est légalement obligatoire</strong> pour cette spécialité.
    </div>

</div>
