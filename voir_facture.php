<?php
include("connexion.php");

// Vérification robuste de l'ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    die("Erreur : ID de facture invalide.");
}

$id = intval($_GET['id']);

// Préparation des requêtes pour éviter les injections SQL
$stmt = $conn->prepare("SELECT c.*, f.nom AS fournisseur, f.adresse AS fournisseur_adresse, f.telephone AS fournisseur_tel
                       FROM commandes c
                       JOIN fournisseurs f ON c.fournisseur_id = f.id
                       WHERE c.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    header("HTTP/1.1 404 Not Found");
    die("Erreur : Facture introuvable.");
}

$facture = $res->fetch_assoc();

// Récupération des produits avec requête préparée
$produits = [];
$stmtProd = $conn->prepare("SELECT p.nom, p.reference, cp.quantite, cp.prix_unitaire
                           FROM commande_produits cp
                           JOIN produits p ON cp.produit_id = p.id
                           WHERE cp.commande_id = ?");
$stmtProd->bind_param("i", $id);
$stmtProd->execute();
$resProd = $stmtProd->get_result();

while ($prod = $resProd->fetch_assoc()) {
    $produits[] = $prod;
}

// Calcul du total si non présent dans la facture
if (!isset($facture['total']) || empty($facture['total'])) {
    $facture['total'] = array_reduce($produits, function($carry, $item) {
        return $carry + ($item['quantite'] * $item['prix_unitaire']);
    }, 0);
}

// Formatage des dates
$dateCommande = new DateTime($facture['date_commande']);
$dateEcheance = isset($facture['date_echeance']) ? new DateTime($facture['date_echeance']) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($facture['numero_commande']) ?> | <?= htmlspecialchars($facture['client_nom']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .invoice-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .invoice-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-logo {
            max-height: 80px;
            margin-bottom: 15px;
        }
        .badge {
            font-size: 0.9rem;
            padding: 6px 10px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .text-muted-light {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="row">
                <div class="col-md-6">
                    <h1 class="text-primary mb-0">FACTURE</h1>
                    <p class="text-muted mb-2">N° <?= htmlspecialchars($facture['numero_commande']) ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-1"><strong>Date :</strong> <?= $dateCommande->format('d/m/Y') ?></p>
                    <?php if ($dateEcheance): ?>
                    <p class="mb-1"><strong>Échéance :</strong> <?= $dateEcheance->format('d/m/Y') ?></p>
                    <?php endif; ?>
                    <span class="badge <?= strtolower($facture['statut']) === 'payée' ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= htmlspecialchars($facture['statut']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="text-primary">Fournisseur</h5>
                <address>
                    <strong><?= htmlspecialchars(strtoupper($facture['fournisseur'])) ?></strong><br>
                    
                   
                </address>
            </div>
            <div class="col-md-6 text-end">
                <h5 class="text-primary">Client</h5>
                <address>
                    <strong><?= htmlspecialchars($facture['client_nom']) ?></strong><br>
                    <?php if (!empty($facture['client_adresse'])): ?>
                    <?= nl2br(htmlspecialchars($facture['client_adresse'])) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($facture['client_telephone'])): ?>
                    Tél: <?= htmlspecialchars($facture['client_telephone']) ?><br>
                    <?php endif; ?>
                </address>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="40%">Désignation</th>
                        <th width="15%" class="text-center">Référence</th>
                        <th width="10%" class="text-center">Qté</th>
                        <th width="15%" class="text-end">Prix unitaire</th>
                        <th width="15%" class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Aucun produit dans cette commande</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($produits as $index => $p): ?>
                        <?php
                            $total_ligne = $p['quantite'] * $p['prix_unitaire'];
                        ?>
                        <tr>
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($p['nom']) ?></td>
                            <td class="text-center"><?= !empty($p['reference']) ? htmlspecialchars($p['reference']) : '-' ?></td>
                            <td class="text-center"><?= $p['quantite'] ?></td>
                            <td class="text-end"><?= number_format($p['prix_unitaire'], 2, ',', ' ') ?> DH</td>
                            <td class="text-end"><?= number_format($total_ligne, 2, ',', ' ') ?> DH</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?php if (!empty($facture['notes'])): ?>
                <div class="card border-light mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?= nl2br(htmlspecialchars($facture['notes'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr class="total-row">
                                <td class="text-end"><strong>Total HT</strong></td>
                                <td class="text-end"><?= number_format($facture['total'], 2, ',', ' ') ?> DH</td>
                            </tr>
                            <?php if (isset($facture['tva']) && $facture['tva'] > 0): ?>
                            <tr>
                                <td class="text-end">TVA (<?= $facture['tva'] ?>%)</td>
                                <td class="text-end"><?= number_format($facture['total'] * $facture['tva'] / 100, 2, ',', ' ') ?> DH</td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-active">
                                <td class="text-end"><strong>Total TTC</strong></td>
                                <td class="text-end"><strong><?= number_format(isset($facture['tva']) ? $facture['total'] * (1 + $facture['tva'] / 100) : $facture['total'], 2, ',', ' ') ?> DH</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between">
                <a href="factures.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Retour à la liste
                </a>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-primary me-2">
                        <i class="bi bi-printer me-2"></i>Imprimer
                    </button>
                    <a href="bon_commande.php?id=<?= $id ?>" target="_blank" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Exporter en PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>