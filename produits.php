<?php
session_start();
include("connexion.php");

// Vérification de connexion
if (!isset($_SESSION['connecte'])) {
    header("Location: login.php");
    exit();
}

// Récupération des produits
try {
    $result = $conn->query("SELECT * FROM produits ORDER BY nom ASC");
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des produits.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Produits</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body { background-color: #f5f7fa; }
        .table-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .table thead { background-color: #2c3e50; color: white; }
        .badge-stock { padding: .4em .75em; border-radius: 1rem; font-size: .85em; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-success { background-color: #28a745; }
        .action-btn { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam me-2"></i>Produits en stock</h2>
        <a href="ajouter_produit.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Ajouter</a>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <div class="table-container">
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-outline-secondary" onclick="exportTableToCSV('produits.csv')">
                <i class="bi bi-download me-1"></i>Exporter CSV
            </button>
        </div>

        <div class="table-responsive">
            <table id="productsTable" class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Référence</th>
                        <th>Catégorie</th>
                        <th>Quantité</th>
                        <th>Prix achat</th>
                        <th>Prix vente</th>
                        <th>Marge</th>
                        <th>État</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $marge = $row['prix_vente'] - $row['prix_achat'];
                        $badge_class = match (true) {
                            $row['quantite'] == 0 => 'badge-danger',
                            $row['quantite'] < 5 => 'badge-warning',
                            default => 'badge-success'
                        };
                        $etat = match (true) {
                            $row['quantite'] == 0 => 'Rupture',
                            $row['quantite'] < 5 => 'Faible',
                            default => 'Disponible'
                        };
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nom']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['reference']) ?></span></td>
                            <td><?= htmlspecialchars($row['categorie']) ?></td>
                            <td><?= (int)$row['quantite'] ?></td>
                            <td><?= number_format($row['prix_achat'], 2) ?> DH</td>
                            <td><?= number_format($row['prix_vente'], 2) ?> DH</td>
                            <td class="<?= $marge >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($marge, 2) ?> DH
                            </td>
                            <td><span class="badge badge-stock <?= $badge_class ?>"><?= $etat ?></span></td>
                            <td>
                                <a href="modifier_produit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary action-btn" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="supprimer_produit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger action-btn" title="Supprimer"
                                   onclick="return confirm('Confirmer la suppression ?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucun produit trouvé.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-3">
    <small>&copy; <?= date('Y') ?> - Maroc PC</small>
</footer>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    $('#productsTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json' },
        paging: false,
        info: false,
        responsive: true
    });
});

// Export CSV
function exportTableToCSV(filename) {
    const rows = document.querySelectorAll("table tr");
    let csv = [];
    rows.forEach(row => {
        let cols = row.querySelectorAll("td, th");
        let rowCsv = Array.from(cols).map(col => `"${col.innerText.replace(/"/g, '""')}"`).join(";");
        csv.push(rowCsv);
    });
    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

</body>
</html>
