<?php
/**
 * Composant de pagination réutilisable
 * 
 * @param int $currentPage Page actuelle
 * @param int $totalPages Nombre total de pages
 * @param int $totalRecords Nombre total d'enregistrements
 * @param int $perPage Nombre d'éléments par page
 * @param array $params Paramètres GET à conserver (search, filters, sort, etc.)
 */

$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalRecords = $totalRecords ?? 0;
$perPage = $perPage ?? 20;
$params = $params ?? $_GET;

// Fonction pour générer le lien de pagination
function buildPaginationUrl($page, $params) {
    $queryParams = $params;
    $queryParams['page'] = $page;
    return '?' . http_build_query($queryParams);
}
?>

<?php if ($totalPages > 1): ?>
<div class="card-footer bg-white border-top">
    <div class="row align-items-center">
        <!-- Info pagination -->
        <div class="col-12 col-md-5 mb-2 mb-md-0">
            <p class="mb-0 text-muted small">
                <i class="fas fa-info-circle"></i>
                Affichage de <strong><?= (($currentPage - 1) * $perPage) + 1 ?></strong> 
                à <strong><?= min($currentPage * $perPage, $totalRecords) ?></strong> 
                sur <strong><?= number_format($totalRecords, 0, ',', ' ') ?></strong> résultats
            </p>
        </div>
        
        <!-- Navigation -->
        <div class="col-12 col-md-7">
            <nav>
                <ul class="pagination justify-content-end mb-0 flex-wrap">
                    <!-- Première page -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl(1, $params) ?>" title="Première page" aria-label="Première page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Page précédente -->
                    <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $params) ?>" title="Page précédente" aria-label="Page précédente">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    // Calcul des pages à afficher
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    // Afficher la première page si on est loin
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl(1, $params) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Numéros de pages -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($i, $params) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Afficher la dernière page si on est loin -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= buildPaginationUrl($totalPages, $params) ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Page suivante -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $params) ?>" title="Page suivante" aria-label="Page suivante">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    
                    <!-- Dernière page -->
                    <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= buildPaginationUrl($totalPages, $params) ?>" title="Dernière page" aria-label="Dernière page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>
