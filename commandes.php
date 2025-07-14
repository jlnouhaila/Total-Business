<?php include("connexion.php"); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4"><i class="bi bi-receipt"></i> Historique des commandes</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Commande ajoutée avec succès.</div>
    <?php endif; ?>

    <a href="ajouter_commande.php" class="btn btn-primary mb-3">+ Nouvelle commande</a>

    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Commande #</th>
                    <th>Date</th>
                    <th>Fournisseur</th>
                    <th>Total (DH)</th>
                    <th>Statut</th>
                    <th>PDF</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT c.*, f.nom AS fournisseur 
                                     FROM commandes c 
                                     JOIN fournisseurs f ON c.fournisseur_id = f.id 
                                     ORDER BY c.date_commande DESC");

                if ($res->num_rows === 0) {
                    echo "<tr><td colspan='7' class='text-muted'>Aucune commande enregistrée.</td></tr>";
                } else {
                    while ($cmd = $res->fetch_assoc()) {
                        $badge = match ($cmd['statut']) {
                            'En attente' => 'warning',
                            'Validée' => 'success',
                            'Annulée' => 'danger',
                            default => 'secondary'
                        };

                        echo "<tr>
                            <td>#{$cmd['id']}</td>
                            <td>{$cmd['date_commande']}</td>
                            <td>{$cmd['fournisseur']}</td>
                            <td>{$cmd['total']} DH</td>
                            <td><span class='badge bg-$badge'>{$cmd['statut']}</span></td>
                            <td><a href='bon_commande.php?id={$cmd['id']}' class='btn btn-outline-secondary btn-sm' target='_blank'>🧾 PDF</a></td>
                            <td>
                                <a href='modifier_commande.php?id={$cmd['id']}' class='btn btn-sm btn-primary'>✏️</a>
                                <a href='supprimer_commande.php?id={$cmd['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Supprimer cette commande ?');\">🗑️</a>
                            </td>
                        </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
