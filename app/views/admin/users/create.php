<?php $title = "Créer un utilisateur"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-users',          'text' => 'Utilisateurs',    'url' => BASE_URL . '/admin/users'],
    ['icon' => 'fas fa-plus',           'text' => 'Créer un utilisateur', 'url' => null]
];
include __DIR__ . '/../../partials/breadcrumb.php';

// Groupes de rôles pour JS
$staffRoles    = ['directeur_residence','employe_residence','technicien',
                  'jardinier_manager','jardinier_employe','entretien_manager',
                  'menage_interieur','menage_exterieur',
                  'restauration_manager','restauration_serveur','restauration_cuisine',
                  'comptable','employe_laverie'];

// Lots JSON pour filtrage dynamique par résidence
$lotsJson = json_encode($lots ?? []);
$staffRolesJson = json_encode($staffRoles);
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-user-plus text-dark"></i> Créer un Utilisateur</h1>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/admin/users/store" method="POST" id="userForm">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">

        <div class="row g-4">

            <!-- ==============================
                 COLONNE PRINCIPALE
            ============================== -->
            <div class="col-12 col-xl-8">

                <!-- BLOC 1 : Informations de base -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Informations du compte</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="prenom" id="prenom" required placeholder="Jean" autocomplete="given-name">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="nom" id="nom" required placeholder="Dupont" autocomplete="family-name">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="email" required autocomplete="email">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" name="username" id="username" required minlength="3" autocomplete="username">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" class="form-control" name="telephone" id="telephone" placeholder="06 00 00 00 00">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Rôle <span class="text-danger">*</span></label>
                                <select class="form-select" name="role" id="roleSelect" required>
                                    <option value="">-- Sélectionner un rôle --</option>
                                    <?php
                                    $lastCat = null;
                                    $catLabels = ['admin'=>'Administration','direction'=>'Direction','proprietaire'=>'Propriétaire','staff'=>'Personnel','resident'=>'Résidents'];
                                    foreach ($roles as $r):
                                        if ($r['categorie'] !== $lastCat):
                                            if ($lastCat !== null) echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($catLabels[$r['categorie']] ?? ucfirst($r['categorie'])) . '">';
                                            $lastCat = $r['categorie'];
                                        endif;
                                    ?>
                                        <option value="<?= htmlspecialchars($r['slug']) ?>" <?= ($preselectedRole ?? '') === $r['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom_affichage']) ?></option>
                                    <?php endforeach; if ($lastCat !== null) echo '</optgroup>'; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="password" required minlength="8" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password',this)"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Confirmer mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password_confirm" id="password_confirm" required minlength="8" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd('password_confirm',this)"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="actif" id="actif" checked>
                                    <label class="form-check-label" for="actif">
                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Compte actif</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BLOC 2 : Section STAFF — résidence(s) -->
                <div class="card shadow mb-4 d-none" id="section-staff">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Affectation Résidence(s)</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Résidence(s) assignée(s)</label>
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchStaffRes" placeholder="Rechercher une résidence...">
                            <button type="button" class="btn btn-outline-secondary" id="btnSelectAllRes" title="Tout cocher">
                                <i class="fas fa-check-double"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnDeselectAllRes" title="Tout décocher">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="border rounded p-2" style="max-height:220px;overflow-y:auto" id="staffResContainer">
                            <?php foreach ($residences as $res): ?>
                            <div class="form-check res-item" data-search="<?= htmlspecialchars(strtolower($res['nom'] . ' ' . $res['ville'])) ?>">
                                <input class="form-check-input staff-res-cb" type="checkbox"
                                       name="staff_residence_ids[]"
                                       value="<?= $res['id'] ?>"
                                       id="staff_res_<?= $res['id'] ?>">
                                <label class="form-check-label small" for="staff_res_<?= $res['id'] ?>">
                                    <?= htmlspecialchars($res['nom']) ?>
                                    <span class="text-muted">(<?= htmlspecialchars($res['ville']) ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="form-text">Cochez les résidences à assigner.</small>
                            <small class="text-muted"><span id="staffResCount">0</span> sélectionnée(s)</small>
                        </div>
                    </div>
                </div>

                <!-- BLOC 2b : Section PROPRIÉTAIRE -->
                <div class="card shadow mb-4 d-none" id="section-proprietaire">
                    <div class="card-header text-white" style="background:linear-gradient(135deg,#fd7e14,#e65100);">
                        <h5 class="mb-0"><i class="fas fa-home me-2"></i>Profil Propriétaire</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Identité -->
                            <div class="col-12"><h6 class="text-muted fw-bold small text-uppercase">Identité</h6></div>
                            <div class="col-12 col-md-2">
                                <label class="form-label">Civilité <span class="text-danger">*</span></label>
                                <select class="form-select" name="prop_civilite">
                                    <option value="M">M.</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="prop_date_naissance">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Profession</label>
                                <input type="text" class="form-control" name="prop_profession" placeholder="Ex : Cadre, Retraité...">
                            </div>

                            <!-- Adresse -->
                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase"><i class="fas fa-map-marker-alt text-warning me-1"></i>Adresse principale <small class="text-muted fw-normal">(recommandé)</small></h6></div>
                            <div class="col-12">
                                <label class="form-label">Adresse <span class="text-warning">*</span></label>
                                <input type="text" class="form-control" name="prop_adresse" placeholder="25 rue des Lilas">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Code postal <span class="text-warning">*</span></label>
                                <input type="text" class="form-control" name="prop_code_postal" placeholder="75001" maxlength="10">
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label">Ville <span class="text-warning">*</span></label>
                                <input type="text" class="form-control" name="prop_ville" placeholder="Paris">
                            </div>

                            <!-- Contact -->
                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase"><i class="fas fa-phone text-warning me-1"></i>Contact</h6></div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Téléphone mobile <span class="text-warning">*</span></label>
                                <input type="text" class="form-control" name="prop_telephone_mobile" placeholder="06 00 00 00 00">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Téléphone fixe</label>
                                <input type="text" class="form-control" name="prop_telephone" placeholder="01 00 00 00 00">
                            </div>

                            <!-- Notes -->
                            <div class="col-12 mt-2">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="prop_notes" rows="2" placeholder="Informations complémentaires sur le propriétaire..."></textarea>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="alert alert-info mb-0 small alert-permanent">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span class="text-danger">*</span> Obligatoire —
                                    <span class="text-warning">*</span> Recommandé —
                                    Les lots et contrats seront associés après la création.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BLOC 3 : Section RÉSIDENT SENIOR -->
                <div class="card shadow mb-4 d-none" id="section-resident-senior">
                    <div class="card-header bg-purple text-white" style="background:linear-gradient(135deg,#6610f2,#8b5cf6);">
                        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profil Résident Senior</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Identité -->
                            <div class="col-12"><h6 class="text-muted fw-bold small text-uppercase">Identité</h6></div>
                            <div class="col-12 col-md-2">
                                <label class="form-label">Civilité <span class="text-danger">*</span></label>
                                <select class="form-select" name="rs_civilite" id="rs_civilite">
                                    <option value="M">M.</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="rs_date_naissance" id="rs_date_naissance">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Lieu de naissance</label>
                                <input type="text" class="form-control" name="rs_lieu_naissance" placeholder="Paris">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Situation familiale</label>
                                <select class="form-select" name="rs_situation_familiale">
                                    <option value="">— Non renseigné —</option>
                                    <option value="celibataire">Célibataire</option>
                                    <option value="marie">Marié(e)</option>
                                    <option value="veuf">Veuf/Veuve</option>
                                    <option value="divorce">Divorcé(e)</option>
                                    <option value="pacse">Pacsé(e)</option>
                                    <option value="concubinage">Concubinage</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Nombre d'enfants</label>
                                <input type="number" class="form-control" name="rs_nombre_enfants" min="0" value="0">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">N° Sécurité sociale</label>
                                <input type="text" class="form-control" name="rs_num_securite_sociale" placeholder="1 85 12 75 001 001" maxlength="15">
                            </div>

                            <!-- Séjour -->
                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase">Séjour</h6></div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date d'entrée</label>
                                <input type="date" class="form-control" name="rs_date_entree">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Résidence</label>
                                <input type="hidden" name="rs_residence_id" id="rs_residence_id" value="">
                                <div class="position-relative">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="rsResSearch"
                                               placeholder="Rechercher..." autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary" id="rsResClear" title="Effacer">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="border rounded bg-white shadow-sm position-absolute w-100 d-none"
                                         id="rsResDropdown" style="max-height:180px;overflow-y:auto;z-index:10">
                                        <?php foreach ($residences as $res): ?>
                                        <div class="px-3 py-2 rs-res-option" role="button"
                                             data-id="<?= $res['id'] ?>"
                                             data-search="<?= htmlspecialchars(strtolower($res['nom'] . ' ' . $res['ville'])) ?>"
                                             data-label="<?= htmlspecialchars($res['nom'] . ' — ' . $res['ville']) ?>">
                                            <?= htmlspecialchars($res['nom']) ?>
                                            <span class="text-muted small">(<?= htmlspecialchars($res['ville']) ?>)</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Chambre / Lot</label>
                                <select class="form-select form-select-sm" name="rs_lot_id" id="rs_lot_id">
                                    <option value="">— Choisir résidence d'abord —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Niveau d'autonomie</label>
                                <select class="form-select" name="rs_niveau_autonomie">
                                    <option value="autonome">Autonome</option>
                                    <option value="semi_autonome">Semi-autonome</option>
                                    <option value="dependant">Dépendant</option>
                                    <option value="gir1">GIR 1</option>
                                    <option value="gir2">GIR 2</option>
                                    <option value="gir3">GIR 3</option>
                                    <option value="gir4">GIR 4</option>
                                    <option value="gir5">GIR 5</option>
                                    <option value="gir6">GIR 6</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Besoin d'assistance</label>
                                <select class="form-select" name="rs_besoin_assistance">
                                    <option value="0">Non</option>
                                    <option value="1">Oui</option>
                                </select>
                            </div>

                            <!-- CNI -->
                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase">Carte Nationale d'Identité</h6></div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Numéro CNI</label>
                                <input type="text" class="form-control" name="rs_numero_cni" placeholder="Ex : 123456789012">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date de délivrance</label>
                                <input type="date" class="form-control" name="rs_date_delivrance_cni">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Lieu de délivrance</label>
                                <input type="text" class="form-control" name="rs_lieu_delivrance_cni" placeholder="Préfecture de Paris">
                            </div>

                            <!-- Contact d'urgence -->
                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase"><i class="fas fa-phone-alt text-danger me-1"></i>Contact d'urgence <small class="text-muted fw-normal">(recommandé)</small></h6></div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Nom complet <span class="text-warning">*</span></label>
                                <input type="text" class="form-control" name="rs_urgence_nom" placeholder="Marie Dupont">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Lien de parenté</label>
                                <input type="text" class="form-control" name="rs_urgence_lien" placeholder="Fille, Fils...">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Téléphone 1 <span class="text-warning">*</span></label>
                                <input type="text" class="form-control" name="rs_urgence_telephone" placeholder="06 00 00 00 00">
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label">Téléphone 2</label>
                                <input type="text" class="form-control" name="rs_urgence_telephone_2" placeholder="01 00 00 00">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Email urgence</label>
                                <input type="email" class="form-control" name="rs_urgence_email" placeholder="urgence@email.fr">
                            </div>

                            <!-- Informations complémentaires (collapsible) -->
                            <div class="col-12 mt-3">
                                <a class="text-decoration-none small fw-bold" data-bs-toggle="collapse" href="#rsOptionalFields" role="button" aria-expanded="false">
                                    <i class="fas fa-chevron-down me-1"></i>Informations complémentaires (santé, vie quotidienne, notes)
                                </a>
                            </div>
                            <div class="collapse" id="rsOptionalFields">
                                <div class="row g-3 mt-1">
                                    <!-- Santé -->
                                    <div class="col-12"><h6 class="text-muted fw-bold small text-uppercase"><i class="fas fa-heartbeat text-danger me-1"></i>Santé</h6></div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Médecin traitant</label>
                                        <input type="text" class="form-control" name="rs_medecin_traitant_nom" placeholder="Dr. Martin">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Tél. médecin</label>
                                        <input type="text" class="form-control" name="rs_medecin_traitant_tel" placeholder="01 00 00 00 00">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label">Régime alimentaire</label>
                                        <select class="form-select" name="rs_regime_alimentaire">
                                            <option value="normal">Normal</option>
                                            <option value="sans_sel">Sans sel</option>
                                            <option value="diabetique">Diabétique</option>
                                            <option value="vegetarien">Végétarien</option>
                                            <option value="sans_gluten">Sans gluten</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Allergies</label>
                                        <input type="text" class="form-control" name="rs_allergies" placeholder="Aucune, Pénicilline, Arachides...">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Mutuelle</label>
                                        <input type="text" class="form-control" name="rs_mutuelle" placeholder="Nom mutuelle">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">N° mutuelle</label>
                                        <input type="text" class="form-control" name="rs_num_mutuelle" placeholder="123456">
                                    </div>

                                    <!-- Vie quotidienne -->
                                    <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase"><i class="fas fa-heart text-danger me-1"></i>Vie quotidienne</h6></div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Animal de compagnie</label>
                                        <select class="form-select" name="rs_animal_compagnie">
                                            <option value="0">Non</option>
                                            <option value="1">Oui</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Type d'animal</label>
                                        <input type="text" class="form-control" name="rs_animal_type" placeholder="Chat, Chien...">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Nom de l'animal</label>
                                        <input type="text" class="form-control" name="rs_animal_nom" placeholder="Minou">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label">Centres d'intérêt</label>
                                        <input type="text" class="form-control" name="rs_centres_interet" placeholder="Lecture, jardinage...">
                                    </div>

                                    <!-- Notes -->
                                    <div class="col-12 mt-2">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="rs_notes" rows="2" placeholder="Informations complémentaires..."></textarea>
                                    </div>
                                </div>
                            </div><!-- /collapse -->
                            <div class="col-12 mt-2">
                                <div class="alert alert-info mb-0 small alert-permanent">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span class="text-danger">*</span> Obligatoire —
                                    <span class="text-warning">*</span> Recommandé —
                                    Les autres champs peuvent être complétés ultérieurement.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hôtes temporaires gérés via module dédié /hote/ -->

                <!-- ==============================
                     SECTION EXPLOITANT
                ============================== -->
                <div class="card shadow mb-4 d-none" id="section-exploitant">
                    <div class="card-header text-white" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Société Exploitante</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label">Raison sociale <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="exp_raison_sociale" placeholder="Ex : Domitys SAS">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Forme juridique</label>
                                <select class="form-select" name="exp_forme_juridique">
                                    <option value="SAS">SAS</option>
                                    <option value="SARL">SARL</option>
                                    <option value="SA">SA</option>
                                    <option value="SCI">SCI</option>
                                    <option value="EURL">EURL</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">SIRET <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="exp_siret" placeholder="14 chiffres" maxlength="14">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Email société</label>
                                <input type="email" class="form-control" name="exp_email" placeholder="contact@societe.fr">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse siège social</label>
                                <input type="text" class="form-control" name="exp_adresse" placeholder="25 avenue des Fleurs">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Code postal</label>
                                <input type="text" class="form-control" name="exp_code_postal" placeholder="75001" maxlength="10">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" name="exp_ville" placeholder="Paris">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="exp_telephone" placeholder="+33 1 23 45 67 89">
                            </div>

                            <!-- Résidences gérées -->
                            <div class="col-12 mt-2">
                                <h6 class="text-muted fw-bold small text-uppercase">Résidences gérées</h6>
                                <p class="small text-muted">Les pourcentages seront définis dans la fiche résidence.</p>
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchExpRes" placeholder="Rechercher une résidence...">
                                </div>
                                <div class="border rounded p-2" style="max-height:180px;overflow-y:auto" id="expResContainer">
                                    <?php foreach ($residencesForAssignment as $res): ?>
                                    <div class="form-check exp-res-item" data-search="<?= htmlspecialchars(strtolower($res['nom'] . ' ' . ($res['ville'] ?? ''))) ?>">
                                        <input class="form-check-input exp-res-cb" type="checkbox"
                                               name="staff_residence_ids[]"
                                               value="<?= $res['id'] ?>"
                                               id="exp_res_<?= $res['id'] ?>">
                                        <label class="form-check-label small" for="exp_res_<?= $res['id'] ?>">
                                            <?= htmlspecialchars($res['nom']) ?>
                                            <span class="text-muted">(<?= htmlspecialchars($res['ville'] ?? '') ?>)</span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted mt-1 d-block"><span id="expResCount">0</span> sélectionnée(s)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-between gap-2">
                    <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Créer l'utilisateur
                    </button>
                </div>
            </div>

            <!-- ==============================
                 COLONNE AIDE
            ============================== -->
            <div class="col-12 col-xl-4">
                <div class="card shadow mb-3" id="help-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide — <span id="help-role-name">Choisissez un rôle</span></h5>
                    </div>
                    <div class="card-body small" id="help-body">
                        <p class="text-muted">Sélectionnez un rôle pour voir les informations correspondantes.</p>
                    </div>
                </div>

                <div class="card shadow border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Sécurité</h5>
                    </div>
                    <div class="card-body small">
                        <ul class="mb-0">
                            <li>Mot de passe : minimum <strong>8 caractères</strong></li>
                            <li>Email et username doivent être <strong>uniques</strong></li>
                            <li>Mot de passe stocké en <strong>hash bcrypt</strong></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div><!-- /row -->
    </form>
</div>

<script>
const STAFF_ROLES  = <?= $staffRolesJson ?>;
const LOTS_DATA    = <?= $lotsJson ?>;

const ROLE_HELP = {
    admin:                 { icon:'fa-user-shield',       color:'#dc3545', desc:'Accès complet. Gestion des utilisateurs, configuration, logs système.' },
    directeur_residence:   { icon:'fa-building-user',     color:'#0d6efd', desc:'Gestion de sa résidence : personnel, résidents, budget, planning.' },
    proprietaire:          { icon:'fa-home',              color:'#fd7e14', desc:'Consultation de ses biens, contrats, loyers perçus, déclarations fiscales. Un profil propriétaire sera créé automatiquement.' },
    employe_residence:     { icon:'fa-id-badge',          color:'#6f42c1', desc:'Opérations courantes selon son service dans la résidence.' },
    technicien:            { icon:'fa-screwdriver-wrench',color:'#20c997', desc:'Maintenance : électricité, plomberie, ascenseur, chauffage, maçonnerie.' },
    jardinier_manager:     { icon:'fa-tree',              color:'#198754', desc:'Responsable du service jardinage et paysagisme.' },
    jardinier_employe:     { icon:'fa-leaf',              color:'#198754', desc:'Entretien des espaces verts et aménagements paysagers.' },
    entretien_manager:     { icon:'fa-broom',             color:'#0dcaf0', desc:'Supervision des équipes ménage intérieur et extérieur.' },
    menage_interieur:      { icon:'fa-bed',               color:'#0dcaf0', desc:'Nettoyage des chambres et espaces intérieurs.' },
    menage_exterieur:      { icon:'fa-spray-can',         color:'#0dcaf0', desc:'Nettoyage des espaces communs extérieurs.' },
    restauration_manager:  { icon:'fa-utensils',          color:'#ffc107', desc:'Gestion du restaurant, fournisseurs, facturation.' },
    restauration_serveur:  { icon:'fa-concierge-bell',    color:'#ffc107', desc:'Service en salle, prise de commandes.' },
    restauration_cuisine:  { icon:'fa-kitchen-set',       color:'#ffc107', desc:'Préparation des repas, gestion des denrées.' },
    locataire_permanent:   { icon:'fa-user-circle',       color:'#6610f2', desc:'Résident senior permanent. Un profil senior sera créé automatiquement.' },
    locataire_temporel:    { icon:'fa-calendar-check',    color:'#6610f2', desc:'Hôte court séjour. Géré via le module Hôtes Temporaires.' },
    exploitant:            { icon:'fa-briefcase',          color:'#f59e0b', desc:'Société exploitante. Un profil entreprise (SIRET, IBAN, assurance) sera créé.' },
    comptable:             { icon:'fa-calculator',         color:'#10b981', desc:'Gestion comptable : paiements, loyers, charges, déclarations fiscales.' },
    employe_laverie:       { icon:'fa-shirt',              color:'#06b6d4', desc:'Service laverie et gestion du linge des résidents.' },
};

function togglePwd(fieldId, btn) {
    const f = document.getElementById(fieldId);
    const ico = btn.querySelector('i');
    if (f.type === 'password') { f.type = 'text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
    else                       { f.type = 'password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
}

function onRoleChange() {
    const role = document.getElementById('roleSelect').value;

    // Masquer toutes les sections dynamiques
    ['section-staff','section-proprietaire','section-resident-senior','section-exploitant']
        .forEach(id => document.getElementById(id).classList.add('d-none'));

    // Afficher la section pertinente
    if (STAFF_ROLES.includes(role)) {
        document.getElementById('section-staff').classList.remove('d-none');
    } else if (role === 'proprietaire') {
        document.getElementById('section-proprietaire').classList.remove('d-none');
    } else if (role === 'locataire_permanent') {
        document.getElementById('section-resident-senior').classList.remove('d-none');
    } else if (role === 'exploitant') {
        document.getElementById('section-exploitant').classList.remove('d-none');
    }

    // Mettre à jour l'aide
    const help = ROLE_HELP[role];
    if (help) {
        document.getElementById('help-role-name').textContent = role.replace(/_/g,' ');
        document.getElementById('help-body').innerHTML =
            `<p><i class="fas ${help.icon} me-2" style="color:${help.color}"></i>${help.desc}</p>`;
    } else {
        document.getElementById('help-role-name').textContent = 'Choisissez un rôle';
        document.getElementById('help-body').innerHTML = '<p class="text-muted">Sélectionnez un rôle pour voir les informations correspondantes.</p>';
    }
}

// === Recherche résidence résident senior ===
(function() {
    const searchInput = document.getElementById('rsResSearch');
    const dropdown = document.getElementById('rsResDropdown');
    const hiddenInput = document.getElementById('rs_residence_id');
    const clearBtn = document.getElementById('rsResClear');
    const options = dropdown.querySelectorAll('.rs-res-option');

    function filterRsLots(rid) {
        const lotSelect = document.getElementById('rs_lot_id');
        lotSelect.innerHTML = '<option value="">— Non assigné —</option>';
        if (rid) {
            LOTS_DATA.filter(l => l.residence_id == rid).forEach(l => {
                lotSelect.innerHTML += `<option value="${l.id}">${l.numero_lot} (${l.type})</option>`;
            });
        }
    }

    searchInput.addEventListener('focus', () => dropdown.classList.remove('d-none'));
    searchInput.addEventListener('input', function() {
        dropdown.classList.remove('d-none');
        const q = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        options.forEach(opt => {
            const text = opt.getAttribute('data-search').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
            opt.style.display = !q || text.includes(q) ? '' : 'none';
        });
        hiddenInput.value = '';
    });

    options.forEach(opt => {
        opt.addEventListener('mousedown', function(e) {
            e.preventDefault();
            hiddenInput.value = this.getAttribute('data-id');
            searchInput.value = this.getAttribute('data-label');
            dropdown.classList.add('d-none');
            filterRsLots(parseInt(hiddenInput.value));
        });
        opt.addEventListener('mouseenter', function() { this.style.background = '#f0f0f0'; });
        opt.addEventListener('mouseleave', function() { this.style.background = ''; });
    });

    searchInput.addEventListener('blur', () => setTimeout(() => dropdown.classList.add('d-none'), 150));

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        hiddenInput.value = '';
        filterRsLots(0);
        options.forEach(opt => opt.style.display = '');
    });
})();

// Auto-générer username
function generateUsername() {
    const u = document.getElementById('username');
    if (u.value.length > 0) return;
    const p = document.getElementById('prenom').value.trim();
    const n = document.getElementById('nom').value.trim();
    if (p && n) {
        u.value = (p[0] + n).toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/[^a-z0-9]/g,'');
    }
}
document.getElementById('prenom').addEventListener('blur', generateUsername);
document.getElementById('nom').addEventListener('blur', generateUsername);

// Validation submit
document.getElementById('userForm').addEventListener('submit', function(e) {
    const pwd = document.getElementById('password').value;
    const cpwd = document.getElementById('password_confirm').value;
    if (pwd !== cpwd) { e.preventDefault(); alert('Les mots de passe ne correspondent pas !'); return; }
    if (pwd.length < 8) { e.preventDefault(); alert('Mot de passe : 8 caractères minimum.'); return; }
});

document.getElementById('roleSelect').addEventListener('change', onRoleChange);
// Déclencher au chargement si rôle pré-sélectionné
if (document.getElementById('roleSelect').value) onRoleChange();

// === Recherche résidences (staff) ===
function initResSearch(searchId, containerId, cbClass, countId) {
    const search = document.getElementById(searchId);
    const container = document.getElementById(containerId);
    if (!search || !container) return;
    search.addEventListener('input', function() {
        const q = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        container.querySelectorAll('[data-search]').forEach(item => {
            const text = item.getAttribute('data-search').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
            item.style.display = !q || text.includes(q) ? '' : 'none';
        });
    });
    if (countId) {
        function updateCount() {
            const n = container.querySelectorAll('.' + cbClass + ':checked').length;
            document.getElementById(countId).textContent = n;
        }
        container.addEventListener('change', updateCount);
        updateCount();
    }
}
initResSearch('searchStaffRes', 'staffResContainer', 'staff-res-cb', 'staffResCount');
initResSearch('searchExpRes', 'expResContainer', 'exp-res-cb', 'expResCount');

// Tout cocher / décocher (staff)
const btnAll = document.getElementById('btnSelectAllRes');
const btnNone = document.getElementById('btnDeselectAllRes');
if (btnAll) btnAll.addEventListener('click', () => {
    document.querySelectorAll('#staffResContainer .staff-res-cb').forEach(cb => { if (cb.closest('.res-item').style.display !== 'none') cb.checked = true; });
    document.getElementById('staffResCount').textContent = document.querySelectorAll('#staffResContainer .staff-res-cb:checked').length;
});
if (btnNone) btnNone.addEventListener('click', () => {
    document.querySelectorAll('#staffResContainer .staff-res-cb').forEach(cb => cb.checked = false);
    document.getElementById('staffResCount').textContent = '0';
});
</script>
