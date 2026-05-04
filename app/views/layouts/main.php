<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="<?php echo APP_NAME; ?> - Plateforme de gestion de résidences seniors">
    <meta name="theme-color" content="#dc3545">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>">
    <title><?php echo $title ?? APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    
    <!-- Dark Mode CSS (complément Bootstrap) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style_dark.css">
    
    <!-- Mobile Responsive CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/mobile.css">
    
    <?php
    // Charger les préférences utilisateur pour appliquer le thème
    $userPrefs = [];
    if (isset($_SESSION['user_id'])) {
        try {
            // Connexion directe sans require database.php
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = ? LIMIT 1");
            $stmt->execute(['user_' . $_SESSION['user_id'] . '_prefs']);
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            if ($row && $row->valeur) {
                $userPrefs = json_decode($row->valeur, true) ?? [];
            }
        } catch (Exception $e) {
            // Ignorer les erreurs (table parametres n'existe pas encore, etc.)
        }
    }
    
    $theme = $userPrefs['theme'] ?? 'light';
    $density = $userPrefs['density'] ?? 'comfortable';
    $showAnimations = !empty($userPrefs['show_animations']);
    
    // Classes CSS pour le body
    $bodyClasses = [$bodyClass ?? ''];
    if (!$showAnimations) {
        $bodyClasses[] = 'no-animations';
    }
    $bodyClassAttr = implode(' ', array_filter($bodyClasses));
    ?>
</head>
<body class="<?php echo $bodyClassAttr; ?>" data-bs-theme="<?php echo $theme; ?>" data-density="<?php echo $density; ?>">

<script>
// Appliquer le thème sur HTML aussi pour Bootstrap 5.3
document.documentElement.setAttribute('data-bs-theme', '<?php echo $theme; ?>');
</script>
    
    <!-- Navbar (si utilisateur connecté) -->
    <?php if (isset($showNavbar) && $showNavbar === true): ?>
        <?php require_once '../app/views/partials/navbar.php'; ?>
    <?php endif; ?>
    
    <!-- Messages Flash -->
    <?php if (isset($flash) && $flash): ?>
        <?php $flashDuration = isset($flash['duration']) ? (int)$flash['duration'] : 5000; ?>
        <?php $flashPermanentClass = $flashDuration <= 0 ? ' alert-permanent' : ''; ?>
        <div class="container-fluid mt-3">
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show<?php echo $flashPermanentClass; ?>" role="alert" data-flash-duration="<?php echo $flashDuration; ?>">
                <?php if ($flash['type'] === 'success'): ?>
                    <i class="fas fa-check-circle me-2"></i>
                <?php elseif ($flash['type'] === 'error' || $flash['type'] === 'danger'): ?>
                    <i class="fas fa-exclamation-circle me-2"></i>
                <?php elseif ($flash['type'] === 'warning'): ?>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                <?php else: ?>
                    <i class="fas fa-info-circle me-2"></i>
                <?php endif; ?>
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Contenu principal -->
    <main class="<?php echo isset($showNavbar) && $showNavbar ? 'pt-3' : ''; ?>">
        <?php echo $content ?? ''; ?>
    </main>
    
    <!-- jQuery (charger EN PREMIER) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle (charger APRÈS jQuery) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Session Timeout (si utilisateur connecté) -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <script>
        const IS_LOGGED_IN = true;
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/session-timeout.js?v=<?= time() ?>"></script>
    <script>
    // Badge messages non lus
    (function() {
        function checkUnread() {
            fetch(BASE_URL + '/message/unreadCount')
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('msgBadge');
                    if (badge && data.count > 0) {
                        badge.textContent = data.count;
                        badge.classList.remove('d-none');
                    } else if (badge) {
                        badge.classList.add('d-none');
                    }
                })
                .catch(() => {});
        }
        checkUnread();
        setInterval(checkUnread, 60000); // Vérifier toutes les minutes
    })();
    </script>
    <?php endif; ?>
    
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
