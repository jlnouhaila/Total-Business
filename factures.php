<?php include("connexion.php"); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Factures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn i {
            vertical-align: middle;
            margin-right: 5px;
        }
        ul {
            padding-left: 1.2rem;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="bi bi-receipt"></i> Historique des Factures / Bons de Commande</h2>
        <a href="ajouter_facture.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Ajouter une facture
        </a>
    </div>

    <!-- Messages -->
    <?php if (isset($_GET['ajoute'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> Facture ajoutée avec succès.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['modifie'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-pencil-fill"></i> Facture modifiée avec succès.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['supprime'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-trash-fill"></i> Facture supprimée avec succès.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tableau -->
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-bordered table-hover align-middle text-center bg-white">
            <thead class="table-primary">
                <tr>
                    <th>Numéro</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Fournisseur</th>
                    <th>Produits commandés</th>
                    <th>Total (DH)</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT c.*, f.nom AS fournisseur
                        FROM commandes c
                        JOIN fournisseurs f ON c.fournisseur_id = f.id
                        ORDER BY c.date_commande DESC";
                $res = $conn->query($sql);

                if ($res->num_rows === 0) {
                    echo "<tr><td colspan='8' class='text-muted'>Aucune facture trouvée.</td></tr>";
                } else {
                    while ($cmd = $res->fetch_assoc()) {
                        $id           = (int)$cmd['id'];
                        $numero       = htmlspecialchars($cmd['numero_commande']);
                        $date         = date("d/m/Y", strtotime($cmd['date_commande']));
                        $client_nom   = htmlspecialchars($cmd['client_nom']);
                        $fournisseur  = htmlspecialchars($cmd['fournisseur']);
                        $total        = number_format($cmd['total'], 2, ',', ' ') . " DH";
                        $statut_value = strtolower($cmd['statut']);

                        // Badge du statut
                        $badge = match ($statut_value) {
                            'payée'      => "<span class='badge bg-success'><i class='bi bi-check-circle'></i> Payée</span>",
                            'en attente' => "<span class='badge bg-warning text-dark'><i class='bi bi-hourglass-split'></i> En attente</span>",
                            'annulée'    => "<span class='badge bg-danger'><i class='bi bi-x-circle'></i> Annulée</span>",
                            default      => "<span class='badge bg-secondary'>" . ucfirst($statut_value) . "</span>"
                        };

                        // Récupérer les produits commandés
                        $produits = [];
                        $sqlProd = "SELECT p.nom, cp.quantite
                                    FROM commande_produits cp
                                    JOIN produits p ON cp.produit_id = p.id
                                    WHERE cp.commande_id = $id";
                        $resProd = $conn->query($sqlProd);
                        while ($prod = $resProd->fetch_assoc()) {
                            $produits[] = "<li>" . htmlspecialchars($prod['nom']) . " <strong>(x" . intval($prod['quantite']) . ")</strong></li>";
                        }
                        $liste_produits = !empty($produits) ? "<ul>" . implode('', $produits) . "</ul>" : "<em class='text-muted'>Aucun produit</em>";

                        // Affichage de la ligne
                        echo "
                        <tr>
                            <td>$numero</td>
                            <td>$date</td>
                            <td>$client_nom</td>
                            <td>$fournisseur</td>
                            <td>$liste_produits</td>
                            <td>$total</td>
                            <td>$badge</td>
                            <td>
                                <a href='bon_commande.php?id=$id' target='_blank' class='btn btn-outline-secondary btn-sm me-1' title='PDF'>
                                    <i class='bi bi-file-earmark-pdf'></i>
                                </a>
                                <a href='modifier_facture.php?id=$id' class='btn btn-outline-warning btn-sm me-1' title='Modifier'>
                                    <i class='bi bi-pencil-square'></i>
                                </a>
                                <a href='supprimer_facture.php?id=$id' class='btn btn-outline-danger btn-sm' title='Supprimer'
                                   onclick=\"return confirm('Voulez-vous vraiment supprimer cette facture ?');\">
                                    <i class='bi bi-trash'></i>
                                </a>
                            </td>
                        </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JS Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
