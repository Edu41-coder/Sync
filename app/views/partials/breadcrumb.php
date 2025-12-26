<?php
/**
 * Composant Fil d'Ariane (Breadcrumb)
 * 
 * Usage dans une vue :
 * <?php
 * $breadcrumb = [
 *     ['icon' => 'fas fa-home', 'text' => 'Accueil', 'url' => BASE_URL],
 *     ['icon' => 'fas fa-building', 'text' => 'Copropriétés', 'url' => BASE_URL . '/copropriete/index'],
 *     ['icon' => 'fas fa-plus', 'text' => 'Nouvelle copropriété', 'url' => null] // null = page actuelle
 * ];
 * include '../app/views/partials/breadcrumb.php';
 * ?>
 */

if (!isset($breadcrumb) || empty($breadcrumb)) {
    return;
}
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-transparent mb-0 px-0">
        <?php foreach ($breadcrumb as $index => $item): ?>
            <?php if ($index === count($breadcrumb) - 1 || !isset($item['url']) || $item['url'] === null): ?>
                <!-- Dernier élément (page actuelle) -->
                <li class="breadcrumb-item active text-dark" aria-current="page">
                    <?php if (isset($item['icon'])): ?>
                        <i class="<?= htmlspecialchars($item['icon']) ?> me-1"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['text']) ?>
                </li>
            <?php else: ?>
                <!-- Élément avec lien -->
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="text-dark text-decoration-none">
                        <?php if (isset($item['icon'])): ?>
                            <i class="<?= htmlspecialchars($item['icon']) ?> me-1"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['text']) ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
