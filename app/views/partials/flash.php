<?php
/**
 * Message Flash
 */
if (isset($flash) && $flash): 
    $typeMap = [
        'success' => 'success',
        'error' => 'danger',
        'warning' => 'warning',
        'info' => 'info'
    ];
    $alertType = $typeMap[$flash['type']] ?? 'info';
    $iconMap = [
        'success' => 'fa-check-circle',
        'danger' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
    $icon = $iconMap[$alertType] ?? 'fa-info-circle';
    $duration = $flash['duration'] ?? 5000; // Par défaut 5 secondes
?>
    <div class="alert alert-<?php echo $alertType; ?> alert-dismissible fade show" role="alert" id="flash-message">
        <i class="fas <?php echo $icon; ?> me-2"></i>
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <script>
        // Auto-dismiss après la durée spécifiée
        setTimeout(function() {
            var flashAlert = document.getElementById('flash-message');
            if (flashAlert) {
                var bsAlert = new bootstrap.Alert(flashAlert);
                bsAlert.close();
            }
        }, <?php echo $duration; ?>);
    </script>
<?php endif; ?>
