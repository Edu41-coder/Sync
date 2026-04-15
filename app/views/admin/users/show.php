<?php $title = $user['prenom'] . ' ' . $user['nom']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-users', 'text' => 'Utilisateurs', 'url' => BASE_URL . '/admin/users'],
    ['icon' => 'fas fa-eye', 'text' => $user['prenom'] . ' ' . $user['nom'], 'url' => null]
];
include __DIR__ . '/../../partials/breadcrumb.php';

$roleColor = $roleInfo['couleur'] ?? '#6c757d';
$roleIcon = $roleInfo['icone'] ?? 'fas fa-user';
$roleLabel = $roleInfo['nom_affichage'] ?? $user['role'];
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="avatar-circle me-3" style="width:60px;height:60px;font-size:1.5rem;background:<?= $roleColor ?>;color:#fff">
                    <?= strtoupper(substr($user['prenom'] ?? '?', 0, 1) . substr($user['nom'] ?? '?', 0, 1)) ?>
                </div>
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-eye text-dark me-1"></i>
                        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                    </h1>
                    <span class="badge me-2" style="background-color:<?= $roleColor ?>">
                        <i class="<?= $roleIcon ?> me-1"></i><?= htmlspecialchars($roleLabel) ?>
                    </span>
                    <?php if ($user['actif']): ?>
                        <span class="badge bg-success">Actif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactif</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/admin/users/edit/<?= $user['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i>Modifier
                </a>
                <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-12 col-lg-8">
            <!-- Infos compte -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Informations du compte</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Nom complet</label>
                            <div class="fw-bold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Nom d'utilisateur</label>
                            <div><code><?= htmlspecialchars($user['username']) ?></code></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Email</label>
                            <div><a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Téléphone</label>
                            <div><?= htmlspecialchars($user['telephone'] ?? '-') ?: '-' ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Mot de passe</label>
                            <div>
                                <?php if (!empty($user['password_plain'])): ?>
                                <code id="pwdPlain" class="d-none"><?= htmlspecialchars($user['password_plain']) ?></code>
                                <span id="pwdMask">••••••••</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="
                                    const p=document.getElementById('pwdPlain'), m=document.getElementById('pwdMask'), i=this.querySelector('i');
                                    p.classList.toggle('d-none'); m.classList.toggle('d-none');
                                    i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
                                "><i class="fas fa-eye"></i></button>
                                <?php else: ?>
                                <span class="text-muted">Non disponible</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Dernière connexion</label>
                            <div><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Créé le</label>
                            <div><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Modifié le</label>
                            <div><?= $user['updated_at'] ? date('d/m/Y H:i', strtotime($user['updated_at'])) : '-' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($proprietaire): ?>
            <!-- Profil propriétaire -->
            <div class="card shadow mb-4">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#fd7e14,#e65100)">
                    <h5 class="mb-0"><i class="fas fa-home me-2"></i>Profil Propriétaire</h5>
                    <a href="<?= BASE_URL ?>/coproprietaire/show/<?= $proprietaire['id'] ?>?from=users" class="btn btn-sm btn-light">
                        <i class="fas fa-external-link-alt me-1"></i>Voir fiche complète
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="text-muted small">Civilité</label>
                            <div><?= htmlspecialchars($proprietaire['civilite'] ?? '-') ?></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="text-muted small">Date de naissance</label>
                            <div><?= $proprietaire['date_naissance'] ? date('d/m/Y', strtotime($proprietaire['date_naissance'])) : '-' ?></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="text-muted small">Profession</label>
                            <div><?= htmlspecialchars($proprietaire['profession'] ?? '-') ?: '-' ?></div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small">Adresse</label>
                            <div><?= htmlspecialchars(implode(', ', array_filter([
                                $proprietaire['adresse_principale'] ?? '',
                                $proprietaire['code_postal'] ?? '',
                                $proprietaire['ville'] ?? ''
                            ]))) ?: '-' ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Téléphone mobile</label>
                            <div><?= htmlspecialchars($proprietaire['telephone_mobile'] ?? '-') ?: '-' ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Téléphone fixe</label>
                            <div><?= htmlspecialchars($proprietaire['telephone'] ?? '-') ?: '-' ?></div>
                        </div>
                        <?php if (!empty($proprietaire['notes'])): ?>
                        <div class="col-12">
                            <label class="text-muted small">Notes</label>
                            <div class="small"><?= nl2br(htmlspecialchars($proprietaire['notes'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($contrats)): ?>
            <!-- Contrats -->
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Contrats de gestion (<?= count($contrats) ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>N° Contrat</th><th>Résidence / Lot</th><th class="text-end">Loyer garanti</th><th class="text-center">Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contrats as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['numero_contrat'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($c['residence_nom'] ?? '-') ?> — Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format($c['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €/mois</td>
                                <td class="text-center">
                                    <?php $sc = ['actif'=>'success','resilie'=>'danger','termine'=>'secondary','suspendu'=>'warning']; ?>
                                    <span class="badge bg-<?= $sc[$c['statut']] ?? 'secondary' ?>"><?= ucfirst($c['statut']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($resident): ?>
            <!-- Profil résident — résumé + lien vers fiche complète -->
            <div class="card shadow mb-4">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#6610f2,#8b5cf6)">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profil Résident Senior</h5>
                    <a href="<?= BASE_URL ?>/resident/show/<?= $resident['id'] ?>?from=users" class="btn btn-sm btn-light">
                        <i class="fas fa-external-link-alt me-1"></i>Voir fiche complète
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Civilité</label>
                            <div><?= htmlspecialchars($resident['civilite'] ?? '-') ?></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Date de naissance</label>
                            <div><?= $resident['date_naissance'] ? date('d/m/Y', strtotime($resident['date_naissance'])) : '-' ?></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Niveau autonomie</label>
                            <div><span class="badge bg-info"><?= htmlspecialchars($resident['niveau_autonomie'] ?? '-') ?></span></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Statut</label>
                            <div><?= $resident['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></div>
                        </div>
                        <?php if (!empty($occupations)): ?>
                        <div class="col-12">
                            <label class="text-muted small">Occupation(s) active(s)</label>
                            <div>
                                <?php foreach ($occupations as $o): ?>
                                    <?php if ($o['statut'] === 'actif'): ?>
                                    <span class="badge bg-success me-1">
                                        <?= htmlspecialchars($o['residence_nom'] ?? '') ?> — Lot <?= htmlspecialchars($o['numero_lot'] ?? '') ?>
                                        (<?= number_format($o['loyer_mensuel_resident'] ?? 0, 2, ',', ' ') ?> €/mois)
                                    </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (!array_filter($occupations, fn($o) => $o['statut'] === 'actif')): ?>
                                <span class="text-muted">Aucune occupation active</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end mt-3">
                        <a href="<?= BASE_URL ?>/resident/show/<?= $resident['id'] ?>?from=users" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Voir fiche complète (profil, CNI, urgence, occupations, historique)
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($exploitant): ?>
            <!-- Profil exploitant -->
            <div class="card shadow mb-4">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Profil Exploitant</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Raison sociale</label>
                            <div class="fw-bold"><?= htmlspecialchars($exploitant['raison_sociale'] ?? '-') ?></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Forme juridique</label>
                            <div><?= htmlspecialchars($exploitant['forme_juridique'] ?? '-') ?></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">SIRET</label>
                            <div><code><?= htmlspecialchars($exploitant['siret'] ?? '-') ?></code></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small">Adresse siège</label>
                            <div><?= htmlspecialchars(implode(', ', array_filter([
                                $exploitant['adresse_siege'] ?? '',
                                $exploitant['code_postal_siege'] ?? '',
                                $exploitant['ville_siege'] ?? ''
                            ]))) ?: '-' ?></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Téléphone</label>
                            <div><?= htmlspecialchars($exploitant['telephone'] ?? '-') ?: '-' ?></div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="text-muted small">Email</label>
                            <div><?= htmlspecialchars($exploitant['email'] ?? '-') ?: '-' ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Résidences assignées -->
            <?php if (!empty($userResidences)): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Résidences (<?= count($userResidences) ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($userResidences as $ur): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($ur['residence_nom']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($ur['ville'] ?? '') ?></small>
                            </div>
                            <span class="badge bg-<?= $ur['statut'] === 'actif' ? 'success' : 'secondary' ?>"><?= ucfirst($ur['statut']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info système -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations système</h6>
                </div>
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">ID</td><td>#<?= $user['id'] ?></td></tr>
                        <tr><td class="text-muted">Rôle</td><td><span class="badge" style="background-color:<?= $roleColor ?>"><?= htmlspecialchars($roleLabel) ?></span></td></tr>
                        <tr><td class="text-muted">Statut</td><td><?= $user['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td></tr>
                        <tr><td class="text-muted">Créé le</td><td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td></tr>
                        <tr><td class="text-muted">Modifié le</td><td><?= $user['updated_at'] ? date('d/m/Y H:i', strtotime($user['updated_at'])) : '-' ?></td></tr>
                        <tr><td class="text-muted">Dernière connexion</td><td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
