<?php $title = "Nouvelle Réservation"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Hôtes temporaires', 'url' => BASE_URL . '/hote/index'],
    ['icon' => 'fas fa-plus', 'text' => 'Nouvelle réservation', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-plus-circle text-dark"></i> Nouvelle Réservation</h1>
        </div>
    </div>

    <form action="<?= BASE_URL ?>/hote/create" method="POST">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">

        <div class="row">
            <div class="col-12 col-lg-8">
                <!-- Identité -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Identité de l'hôte</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-2">
                                <label class="form-label">Civilité</label>
                                <select class="form-select" name="civilite">
                                    <option value="M">M.</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nom" required>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="prenom" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="date_naissance">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Nationalité</label>
                                <input type="text" class="form-control" name="nationalite" value="Française">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="email@exemple.fr">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Téléphone mobile</label>
                                <input type="text" class="form-control" name="telephone_mobile" placeholder="06 00 00 00 00">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Adresse domicile</label>
                                <input type="text" class="form-control" name="adresse_domicile" placeholder="25 rue des Lilas, 75001 Paris">
                            </div>

                            <div class="col-12 mt-2"><h6 class="text-muted fw-bold small text-uppercase">Pièce d'identité</h6></div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type_piece_identite">
                                    <option value="">— Non renseigné —</option>
                                    <option value="cni">CNI</option>
                                    <option value="passeport">Passeport</option>
                                    <option value="titre_sejour">Titre de séjour</option>
                                    <option value="permis">Permis de conduire</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label">Numéro</label>
                                <input type="text" class="form-control" name="numero_piece_identite" placeholder="Ex : 123456789012">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Séjour -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Détails du séjour</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Résidence <span class="text-danger">*</span></label>
                                <select class="form-select" name="residence_id" id="residence_id" required>
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($residences as $res): ?>
                                    <option value="<?= $res['id'] ?>"><?= htmlspecialchars($res['nom']) ?> (<?= htmlspecialchars($res['ville']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Chambre / Lot</label>
                                <select class="form-select" name="lot_id" id="lot_id">
                                    <option value="">— Choisir résidence d'abord —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date d'arrivée <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_arrivee" id="date_arrivee" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Date de départ <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date_depart_prevue" id="date_depart_prevue" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Nb de personnes</label>
                                <input type="number" class="form-control" name="nb_personnes" value="1" min="1" max="10">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Motif du séjour</label>
                                <select class="form-select" name="motif_sejour">
                                    <option value="vacances">Vacances</option>
                                    <option value="famille">Visite famille</option>
                                    <option value="medical">Médical</option>
                                    <option value="affaires">Affaires</option>
                                    <option value="convalescence">Convalescence</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Prix / nuit (€)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="prix_nuit" id="prix_nuit" step="0.01" min="0" placeholder="0.00">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Montant total</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="montant_total" readonly placeholder="Calculé auto">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Informations complémentaires..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex justify-content-between gap-2 mb-4">
                    <a href="<?= BASE_URL ?>/hote/index" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Créer la réservation</button>
                </div>
            </div>

            <!-- Aide -->
            <div class="col-12 col-lg-4">
                <div class="card shadow mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide</h5>
                    </div>
                    <div class="card-body small">
                        <ul class="mb-0">
                            <li>Les hôtes temporaires ne sont <strong>pas des utilisateurs</strong> de la plateforme</li>
                            <li>Le montant total est calculé automatiquement (prix/nuit x nb nuits)</li>
                            <li>La disponibilité du lot est vérifiée automatiquement</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const LOTS = <?= $lots ?>;

document.getElementById('residence_id').addEventListener('change', function() {
    const rid = parseInt(this.value);
    const lotSelect = document.getElementById('lot_id');
    lotSelect.innerHTML = '<option value="">— Non assigné —</option>';
    LOTS.filter(l => l.residence_id == rid).forEach(l => {
        lotSelect.innerHTML += `<option value="${l.id}">Lot ${l.numero_lot} (${l.type})</option>`;
    });
});

function calcMontant() {
    const a = document.getElementById('date_arrivee').value;
    const d = document.getElementById('date_depart_prevue').value;
    const p = parseFloat(document.getElementById('prix_nuit').value) || 0;
    if (a && d && p > 0) {
        const n = Math.round((new Date(d) - new Date(a)) / 86400000);
        document.getElementById('montant_total').value = n > 0 ? (n * p).toFixed(2) : '';
    } else {
        document.getElementById('montant_total').value = '';
    }
}
['date_arrivee','date_depart_prevue','prix_nuit'].forEach(id =>
    document.getElementById(id).addEventListener('input', calcMontant)
);
</script>
