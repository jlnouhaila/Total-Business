<?php
include("connexion.php");

// Vérification de session sécurisée
if (!isset($_SESSION['connecte'])) {
    header("Location: login.php");
    exit();
}

// Récupération sécurisée des catégories
$categories = $conn->query("SELECT DISTINCT categorie FROM produits WHERE categorie IS NOT NULL ORDER BY categorie");

// Filtrage sécurisé
$categorie_filtre = isset($_GET['categorie']) ? $conn->real_escape_string($_GET['categorie']) : '';

// Requête préparée pour la sécurité
$sql = "SELECT *, 
        (prix_vente - prix_achat) AS marge,
        (prix_vente * quantite) AS valeur_stock 
        FROM produits";

if (!empty($categorie_filtre)) {
    $sql .= " WHERE categorie = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categorie_filtre);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Stock | Tableau de Bord</title>
    
    <!-- Intégration des assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: none;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .status-badge {
            padding: 0.5em 0.75em;
            font-size: 0.85em;
            border-radius: 50px;
            font-weight: 500;
        }
        
        .filter-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-card {
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-clipboard-data me-2"></i>Tableau de Bord Stock
            </h2>
            <div>
                <a href="export_stock.php" class="btn btn-outline-primary">
                    <i class="bi bi-download me-2"></i>Exporter
                </a>
            </div>
        </div>

        <!-- Cartes de synthèse -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card summary-card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-box-seam"></i> Produits Total</h5>
                        <p class="card-text display-6">
                            <?php 
                            $total = $conn->query("SELECT COUNT(*) as total FROM produits")->fetch_assoc();
                            echo $total['total'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card summary-card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-check-circle"></i> En Stock</h5>
                        <p class="card-text display-6">
                            <?php 
                            $en_stock = $conn->query("SELECT COUNT(*) as total FROM produits WHERE quantite > 0")->fetch_assoc();
                            echo $en_stock['total'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card summary-card bg-danger text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-exclamation-triangle"></i> Rupture</h5>
                        <p class="card-text display-6">
                            <?php 
                            $rupture = $conn->query("SELECT COUNT(*) as total FROM produits WHERE quantite <= 0")->fetch_assoc();
                            echo $rupture['total'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-card mb-4">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="categorie" class="form-label fw-bold">Filtrer par catégorie :</label>
                    <select name="categorie" id="categorie" class="form-select">
                        <option value="">Toutes les catégories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['categorie']) ?>" 
                                <?= $cat['categorie'] == $categorie_filtre ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['categorie']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="bi bi-funnel"></i> Appliquer
                    </button>
                    <?php if (!empty($categorie_filtre)): ?>
                    <a href="stock.php" class="btn btn-outline-secondary mt-4">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tableau -->
        <div class="table-container">
            <table id="stockTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Référence</th>
                        <th>Catégorie</th>
                        <th>Quantité</th>
                        <th>Prix Achat (MAD)</th>
                        <th>Prix Vente (MAD)</th>
                        <th>Marge (MAD)</th>
                        <th>Valeur Stock</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res->num_rows > 0): ?>
                        <?php while ($row = $res->fetch_assoc()): ?>
                            <?php
                            $statut_class = '';
                            $statut_text = '';
                            
                            if ($row['quantite'] <= 0) {
                                $statut_class = 'bg-danger';
                                $statut_text = 'Rupture';
                            } elseif ($row['quantite'] < 5) {
                                $statut_class = 'bg-warning';
                                $statut_text = 'Stock Faible';
                            } else {
                                $statut_class = 'bg-success';
                                $statut_text = 'Disponible';
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nom']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['reference']) ?></span></td>
                                <td><?= htmlspecialchars($row['categorie']) ?></td>
                                <td><?= $row['quantite'] ?></td>
                                <td><?= number_format($row['prix_achat'], 2) ?></td>
                                <td><?= number_format($row['prix_vente'], 2) ?></td>
                                <td class="<?= ($row['marge'] >= 0) ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($row['marge'], 2) ?>
                                </td>
                                <td><?= number_format($row['valeur_stock'], 2) ?></td>
                                <td>
                                    <span class="badge status-badge <?= $statut_class ?>">
                                        <?= $statut_text ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
                                Aucun produit trouvé
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
$(document).ready(function() {
    $('#stockTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
        },
        dom: '<"table-responsive"t>ip', // Configuration minimale
        paging: false, // Désactive la pagination
        searching: false, // Désactive la recherche
        info: false, // Masque les infos "Showing X of Y entries"
        responsive: true,
        order: [[3, 'desc']]
    });
});
</script>
</body>
</html>