<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="Connexion à <?php echo APP_NAME; ?> - Plateforme de gestion immobilière et syndic">
    <meta name="theme-color" content="#667eea">
    <title><?php echo $title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .auth-header .logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .auth-header .logo img {
            max-width: 100%;
            height: auto;
        }
        .auth-body {
            padding: 2rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(102, 126, 234, 0.3);
        }
        #togglePassword {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        #togglePassword:hover {
            background-color: #e9ecef;
        }
        .input-group .form-control {
            border-right: none;
        }
        .input-group .btn-outline-secondary {
            border-left: none;
            background-color: white;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* RESPONSIVE MOBILE */
        @media (max-width: 575.98px) {
            body {
                padding: 0;
            }
            .auth-card {
                border-radius: 0;
                box-shadow: none;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            .auth-header {
                padding: 1.5rem 1rem;
            }
            .auth-header .logo {
                font-size: 2.5rem;
            }
            .auth-header .logo img {
                height: 60px;
            }
            .auth-header h1 {
                font-size: 1.5rem;
                margin-bottom: 0.25rem;
            }
            .auth-header p {
                font-size: 0.9rem;
            }
            .auth-body {
                padding: 1.5rem 1rem;
                flex: 1;
            }
            .auth-body h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            .form-control, .btn {
                min-height: 48px;
                font-size: 1rem;
            }
            .form-label {
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 379.98px) {
            .auth-header .logo img {
                height: 50px;
            }
            .auth-header h1 {
                font-size: 1.25rem;
            }
            .auth-body h2 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>

<div class="auth-card">
    <!-- Header -->
    <div class="auth-header">
        <div class="logo">
            <img src="<?= BASE_URL ?>/assets/images/domitys-logo.svg" alt="Domitys Logo" style="height: 80px; filter: brightness(0) invert(1);">
        </div>
        <h1><?php echo APP_NAME; ?></h1>
        <p>Gestion Immobilière & Syndic</p>
    </div>
    
    <!-- Body -->
    <div class="auth-body">
        <h2 class="text-center mb-4">Connexion</h2>
        
        <!-- Message flash -->
        <?php include '../app/views/partials/flash.php'; ?>
        
        <!-- Formulaire de connexion -->
        <form method="POST" action="<?php echo BASE_URL; ?>/auth/login">
            <!-- Protection CSRF -->
            <?= csrf_field() ?>
            
            <!-- Nom d'utilisateur -->
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-1"></i> Nom d'utilisateur
                </label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Entrez votre nom d'utilisateur"
                    required
                    autofocus
                >
            </div>
            
            <!-- Mot de passe -->
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-1"></i> Mot de passe
                </label>
                <div class="input-group">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password"
                        placeholder="Entrez votre mot de passe"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            
            <!-- Bouton de connexion -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Footer -->
    <div class="text-center p-3 bg-light border-top">
        <small class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></small>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script pour afficher/masquer le mot de passe -->
<script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        // Toggle le type de l'input
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });
    
    // Focus sur le champ username au chargement
    document.getElementById('username').focus();
</script>

</body>
</html>
