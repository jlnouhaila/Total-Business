<?php if (isset($header_loaded)) return; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$header_loaded = true;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #3a7ca5;
            --accent-color: #2ec4b6;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #004080 100%);
        }
        
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand img {
            height: 50px;
            width: auto;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .nav-link {
            position: relative;
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.85);
            transition: all 0.3s ease;
            margin: 0 0.25rem;
            border-radius: 4px;
        }
        
        .nav-link:hover,
        .nav-link:focus {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link.active {
            color: white;
            font-weight: 600;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 1rem;
            right: 1rem;
            height: 2px;
            background-color: var(--accent-color);
        }
        
        .btn-logout {
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
            padding: 0.375rem 1rem;
        }
        
        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            color: white;
            margin-right: 1rem;
        }
        
        .user-info i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }
        
        @media (max-width: 992px) {
            .navbar-collapse {
                padding: 1rem 0;
            }
            
            .nav-link {
                margin: 0.25rem 0;
                padding: 0.5rem 1rem;
            }
            
            .navbar-nav {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
    <img src="logo.png" alt="Logo" class="me-2 rounded-circle" style="width: 80px; height: 75px; object-fit: cover;">
    <span class="fw-bold">Gestion Stock Total Business</span>
</a>

        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto">
                
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'fournisseurs.php' ? 'active' : '' ?>" href="fournisseurs.php">
                        <i class="bi bi-people me-1"></i>Fournisseurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'produits.php' ? 'active' : '' ?>" href="produits.php">
                        <i class="bi bi-box-seam me-1"></i>Produits
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : '' ?>" href="stock.php">
                        <i class="bi bi-clipboard-data me-1"></i>Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'liste_commandes.php' ? 'active' : '' ?>" href="liste_commandes.php">
                        <i class="bi bi-receipt me-1"></i>Factures & Bons
                    </a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center">
                
                <a href="logout.php" class="btn btn-light">
    <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
</a>

            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Ajoute une classe active au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = document.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>