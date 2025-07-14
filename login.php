<?php
session_start();

// Configuration plus sécurisée
$mdp_correct = "admin123"; // À remplacer par un système de hachage en production
$temps_blocage = 300; // 5 minutes en secondes
$tentatives_max = 5;
$erreur = "";

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['connecte']) && $_SESSION['connecte'] === true) {
    header("Location: produits.php");
    exit();
}

// Gestion des tentatives de connexion
if (!isset($_SESSION['tentatives'])) {
    $_SESSION['tentatives'] = 0;
    $_SESSION['derniere_tentative'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifier le délai entre les tentatives
    $temps_ecoule = time() - $_SESSION['derniere_tentative'];
    
    if ($_SESSION['tentatives'] >= $tentatives_max && $temps_ecoule < $temps_blocage) {
        $temps_restant = $temps_blocage - $temps_ecoule;
        $erreur = "Trop de tentatives. Veuillez réessayer dans ".gmdate("i:s", $temps_restant)." minutes.";
    } else {
        if ($temps_ecoule >= $temps_blocage) {
            $_SESSION['tentatives'] = 0;
        }
        
        $saisi = $_POST['password'] ?? '';
        if ($saisi === $mdp_correct) {
            $_SESSION['connecte'] = true;
            $_SESSION['tentatives'] = 0;
            $_SESSION['derniere_tentative'] = 0;
            header("Location: produits.php");
            exit();
        } else {
            $_SESSION['tentatives']++;
            $_SESSION['derniere_tentative'] = time();
            $erreur = "Identifiants incorrects. Tentatives restantes : ".($tentatives_max - $_SESSION['tentatives']);
            
            if ($_SESSION['tentatives'] >= $tentatives_max) {
                $erreur = "Compte temporairement bloqué. Veuillez réessayer dans ".gmdate("i:s", $temps_blocage)." minutes.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Administratif | Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portail d'administration sécurisé">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            margin: 0;
            height: 100vh;
            background: linear-gradient(135deg, rgba(44,62,80,0.9) 0%, rgba(52,152,219,0.8) 100%), url('lolo.jpg') center/cover no-repeat fixed;
            font-family: 'Montserrat', sans-serif;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            animation: fadeIn 0.6s ease-in-out;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: none;
            backdrop-filter: blur(5px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header img {
            max-height: 80px;
            margin-bottom: 1.5rem;
        }

        .login-header h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(52,152,219,0.25);
            border-color: var(--secondary-color);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #95a5a6;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: var(--secondary-color);
        }

        .btn-login {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .btn-login:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--light-color);
            font-size: 0.8rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 1.5rem;
                margin: 0 1rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="logo.png" alt="Logo Entreprise" class="img-fluid">
            <h2>Portail Administratif</h2>
            <p>Veuillez vous authentifier pour continuer</p>
        </div>

        <?php if (!empty($erreur)) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($erreur) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off">
            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" class="form-control" required 
                           placeholder="Saisissez votre mot de passe" autocomplete="current-password">
                    <i class="bi bi-eye-slash toggle-password" onclick="togglePassword(this)"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                <i class="bi bi-lock-fill me-2"></i>Se connecter
            </button>
        </form>
    </div>
    <p class="footer-text">&copy; <?= date('Y') ?> Total Business. Tous droits réservés.</p>
</div>

<script>
function togglePassword(icon) {
    const input = document.getElementById("password");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    }
}

// Effet de focus sur le champ de mot de passe au chargement
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    if (passwordField) {
        setTimeout(() => {
            passwordField.focus();
        }, 300);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>