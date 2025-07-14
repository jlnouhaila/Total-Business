<?php

ob_start(); // ➕ active le buffering de sortie
require_once("connexion.php");



// Initialisation des variables
$fournisseur_id = $numero = $date = $total = $statut = $client_nom = '';
$produits = [];
$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer'])) {
    // Récupération et validation des données
    $fournisseur_id = (int)$_POST['fournisseur_id'];
    $numero = trim($_POST['numero_commande']);
    $date = $_POST['date_commande'];
    $total = (float)$_POST['total'];
    $statut = $_POST['statut'];
    $client_nom = trim($_POST['client_nom']);
    $produits = $_POST['produits'] ?? [];

    // Validation des données
    if (empty($fournisseur_id) || empty($numero) || empty($date) || empty($client_nom)) {
        $erreur = "Tous les champs obligatoires doivent être remplis";
    } elseif (count($produits) === 0) {
        $erreur = "Au moins un produit doit être sélectionné";
    } else {
        $conn->begin_transaction();
        try {
            // Enregistrer la commande
            $stmt = $conn->prepare("INSERT INTO commandes 
                                  (fournisseur_id, numero_commande, date_commande, client_nom, total, statut)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssds", $fournisseur_id, $numero, $date, $client_nom, $total, $statut);
            $stmt->execute();
            $commande_id = $conn->insert_id;

            // Enregistrer les produits
            foreach ($produits as $prod) {
                $produit_id = (int)$prod['id'];
                $quantite = (int)$prod['quantite'];
                $prix_unitaire = (float)$prod['prix'] / $quantite;

                $stmt_prod = $conn->prepare("INSERT INTO commande_produits 
                                            (commande_id, produit_id, quantite, prix_unitaire)
                                            VALUES (?, ?, ?, ?)");
                $stmt_prod->bind_param("iiid", $commande_id, $produit_id, $quantite, $prix_unitaire);
                $stmt_prod->execute();
            }

            $conn->commit();
            header("Location: factures.php?ajoute=1");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $erreur = "Erreur lors de l'enregistrement : " . $e->getMessage();
            error_log("Erreur facture: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Facture | Gestion de Stock</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .produit-row {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid var(--primary-color);
        }
        
        .total-section {
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .btn-action {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-file-earmark-plus"></i> Nouvelle Facture</h1>
                    <p class="mb-0">Système de gestion de stock</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Messages d'erreur -->
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($erreur) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" class="form-container">
            <!-- Section Informations de base -->
            <h4 class="mb-4 text-primary"><i class="bi bi-info-circle"></i> Informations de base</h4>
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="fournisseur_id" class="form-label">Fournisseur <span class="text-danger">*</span></label>
                    <select name="fournisseur_id" id="fournisseur_id" class="form-select" required>
                        <option value="">-- Sélectionnez un fournisseur --</option>
                        <?php
                        $query = $conn->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                        while ($f = $query->fetch_assoc()) {
                            $selected = ($f['id'] == $fournisseur_id) ? 'selected' : '';
                            echo "<option value='{$f['id']}' $selected>{$f['nom']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="client_nom" class="form-label">Client <span class="text-danger">*</span></label>
                    <input type="text" name="client_nom" id="client_nom" class="form-control" 
                           value="<?= htmlspecialchars($client_nom) ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label for="numero_commande" class="form-label">Numéro de facture <span class="text-danger">*</span></label>
                    <input type="text" name="numero_commande" id="numero_commande" class="form-control" 
                           value="<?= htmlspecialchars($numero) ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label for="date_commande" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date_commande" id="date_commande" class="form-control" 
                           value="<?= $date ?: date('Y-m-d') ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="En attente" <?= ($statut === 'En attente') ? 'selected' : '' ?>>En attente</option>
                        <option value="Payée" <?= ($statut === 'Payée') ? 'selected' : '' ?>>Payée</option>
                        <option value="Annulée" <?= ($statut === 'Annulée') ? 'selected' : '' ?>>Annulée</option>
                    </select>
                </div>
            </div>
            
            <!-- Section Produits -->
            <h4 class="mb-4 text-primary"><i class="bi bi-cart"></i> Produits commandés</h4>
            
            <div id="produits-container">
                <div class="produit-row row g-2">
                    <div class="col-md-5">
                        <select name="produits[0][id]" class="form-select produit-select" required>
                            <option value="">-- Sélectionnez un produit --</option>
                            <?php
                            $query = $conn->query("SELECT id, nom, prix_vente FROM produits ORDER BY nom");
                            while ($p = $query->fetch_assoc()) {
                                echo "<option value='{$p['id']}' data-prix='{$p['prix_vente']}'>{$p['nom']} ({$p['prix_vente']} DH)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="produits[0][quantite]" class="form-control quantite" min="1" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="produits[0][prix]" class="form-control prix" step="0.01" readonly>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-danger btn-action supprimer-produit" disabled>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="button" id="ajouter-produit" class="btn btn-outline-primary mt-3">
                <i class="bi bi-plus-circle"></i> Ajouter un produit
            </button>
            
            <!-- Section Total -->
            <div class="total-section mt-4 row">
                <div class="col-md-6">
                    <label for="total" class="form-label fw-bold">Montant total (DH)</label>
                    <input type="number" step="0.01" name="total" id="total" class="form-control" readonly required>
                </div>
            </div>
            
            <!-- Boutons d'action -->
            <div class="d-flex justify-content-between mt-5">
                <a href="factures.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
                <button type="submit" name="enregistrer" class="btn btn-primary">
                    <i class="bi bi-save"></i> Enregistrer la facture
                </button>
            </div>
        </form>
    </div>

    <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let produitIndex = 1;
        
        // Ajouter un produit
        $('#ajouter-produit').click(function() {
            const newRow = `
                <div class="produit-row row g-2">
                    <div class="col-md-5">
                        <select name="produits[${produitIndex}][id]" class="form-select produit-select" required>
                            <option value="">-- Sélectionnez un produit --</option>
                            <?php
                            $query = $conn->query("SELECT id, nom, prix_vente FROM produits ORDER BY nom");
                            while ($p = $query->fetch_assoc()) {
                                echo "<option value='{$p['id']}' data-prix='{$p['prix_vente']}'>{$p['nom']} ({$p['prix_vente']} DH)</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="produits[${produitIndex}][quantite]" class="form-control quantite" min="1" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="produits[${produitIndex}][prix]" class="form-control prix" step="0.01" readonly>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-danger btn-action supprimer-produit">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>`;
            
            $('#produits-container').append(newRow);
            produitIndex++;
            
            // Activer tous les boutons de suppression sauf le premier
            $('.supprimer-produit').not(':first').prop('disabled', false);
        });
        
        // Supprimer un produit
        $(document).on('click', '.supprimer-produit:not(:disabled)', function() {
            $(this).closest('.produit-row').remove();
            calculerTotal();
            
            // Si un seul produit reste, désactiver son bouton de suppression
            if ($('.produit-row').length === 1) {
                $('.supprimer-produit').prop('disabled', true);
            }
        });
        
        // Mettre à jour le prix quand le produit change
        $(document).on('change', '.produit-select', function() {
            const prixUnitaire = $(this).find('option:selected').data('prix') || 0;
            const quantite = $(this).closest('.produit-row').find('.quantite').val() || 1;
            const prixTotal = (prixUnitaire * quantite).toFixed(2);
            
            $(this).closest('.produit-row').find('.prix').val(prixTotal);
            calculerTotal();
        });
        
        // Mettre à jour le prix quand la quantité change
        $(document).on('input', '.quantite', function() {
            const prixUnitaire = $(this).closest('.produit-row').find('.produit-select option:selected').data('prix') || 0;
            const quantite = $(this).val() || 1;
            const prixTotal = (prixUnitaire * quantite).toFixed(2);
            
            $(this).closest('.produit-row').find('.prix').val(prixTotal);
            calculerTotal();
        });
        
        // Calculer le total général
        function calculerTotal() {
            let total = 0;
            $('.prix').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#total').val(total.toFixed(2));
        }
        
        // Initialisation
        $('.produit-select').trigger('change');
    });
    </script>
</body>
</html>
<?php ob_end_flush(); // 🔚 libère le contenu du buffer et l’envoie au navigateur ?>
