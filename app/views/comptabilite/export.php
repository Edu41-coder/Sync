<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-file-export',    'text' => 'Exports',         'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$annee = (int)date('Y');
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-file-export me-2 text-primary"></i>Exports comptables</h2>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Génération de fichiers conformes pour transmission à un cabinet comptable ou contrôle fiscal.
        Les écritures en statut <em>brouillon</em> sont automatiquement exclues.
    </div>

    <form id="exportForm" method="GET" class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-filter me-2"></i>Filtres de la période
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">— Toutes les résidences accessibles —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === $annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Mois (optionnel)</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Année complète</option>
                        <?php
                        $moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                        foreach ($moisLabels as $i => $lbl): ?>
                        <option value="<?= $i + 1 ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="my-3">

            <div class="row g-2">
                <div class="col-12">
                    <label class="form-label small fw-bold">Modules (vide = tous)</label>
                </div>
                <?php foreach ($modulesAll as $key => $label): ?>
                <div class="col-md-3 col-sm-4 col-6">
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" name="modules[]" value="<?= htmlspecialchars($key) ?>" id="mod_<?= htmlspecialchars($key) ?>">
                        <label class="form-check-label small" for="mod_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <!-- FEC DGFIP -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-shield-alt me-2"></i>FEC — DGFIP officiel
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small mb-2">
                        <strong>Format réglementaire</strong> obligatoire en cas de contrôle fiscal
                        (art. A.47 A-1 LPF). 18 colonnes normalisées, séparateur tabulation, UTF-8.
                    </p>
                    <ul class="small text-muted mb-3">
                        <li>Conforme art. L.47 A-I LPF</li>
                        <li>1 ligne par écriture (recette = crédit, dépense = débit)</li>
                        <li>Mapping module → code journal (JAR, MEN, RES…)</li>
                    </ul>
                    <button type="submit" form="exportForm" formaction="<?= BASE_URL ?>/comptabilite/exportFec" class="btn btn-danger mt-auto">
                        <i class="fas fa-download me-1"></i>Télécharger FEC (.txt)
                    </button>
                </div>
            </div>
        </div>

        <!-- CSV Excel -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-file-excel me-2"></i>CSV Excel
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small mb-2">
                        Format <strong>tableur français</strong> (séparateur <code>;</code>, BOM UTF-8).
                        Ouverture directe dans Excel/LibreOffice avec accents préservés.
                    </p>
                    <ul class="small text-muted mb-3">
                        <li>15 colonnes lisibles humainement</li>
                        <li>Date format JJ/MM/AAAA, montants format français</li>
                        <li>Idéal pour analyse Excel ou archivage</li>
                    </ul>
                    <button type="submit" form="exportForm" formaction="<?= BASE_URL ?>/comptabilite/exportCsv" class="btn btn-success mt-auto">
                        <i class="fas fa-download me-1"></i>Télécharger CSV (.csv)
                    </button>
                </div>
            </div>
        </div>

        <!-- Cegid -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-info">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-cogs me-2"></i>Cegid Quadratus
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="small mb-2">
                        Format CSV simplifié <strong>compatible import Cegid Quadratus / Loop</strong>.
                        Pour transmission au cabinet comptable.
                    </p>
                    <ul class="small text-muted mb-3">
                        <li>10 colonnes avec sens débit/crédit</li>
                        <li>Code journal 3 lettres + numéro de compte</li>
                        <li>À valider auprès du cabinet avant 1er import</li>
                    </ul>
                    <button type="submit" form="exportForm" formaction="<?= BASE_URL ?>/comptabilite/exportCegid" class="btn btn-info text-white mt-auto">
                        <i class="fas fa-download me-1"></i>Télécharger Cegid (.csv)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning small mt-4">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>Pilote — non contractuel.</strong>
        Les fichiers exportés sont destinés à des tests d'intégration. Avant d'utiliser en production
        (déclaration fiscale ou import comptable), faire valider le format par le cabinet comptable.
    </div>
</div>
