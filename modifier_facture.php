<?php
ob_start(); // 🔄 Active le buffering
session_start();
include("connexion.php");

// Vérification et sécurisation de l'ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: factures.php");
    exit();
}

$id = intval($_GET['id']);

// Chargement des données avec requête préparée
$stmt = $conn->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$commande = $stmt->get_result()->fetch_assoc();

if (!$commande) {
    header("Location: factures.php");
    exit();
}

// Chargement des produits de la commande
$stmt_prod = $conn->prepare("SELECT cp.*, p.nom, p.prix_vente 
                            FROM commande_produits cp
                            JOIN produits p ON cp.produit_id = p.id
                            WHERE cp.commande_id = ?");
$stmt_prod->bind_param("i", $id);
$stmt_prod->execute();
$produits_commande = $stmt_prod->get_result();

// Chargement des produits disponibles
$produits_disponibles = [];
$result = $conn->query("SELECT id, nom, reference, prix_vente FROM produits ORDER BY nom");
while ($row = $result->fetch_assoc()) {
    $produits_disponibles[] = $row;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer'])) {
    $fournisseur_id = intval($_POST['fournisseur_id']);
    $client_nom     = trim($_POST['client_nom']);
    $numero         = trim($_POST['numero_commande']);
    $date           = $_POST['date_commande'];
    $statut         = $_POST['statut'];
    $produits       = $_POST['produits'] ?? [];

    // Validation des données
    $errors = [];
    
    if (empty($client_nom)) $errors[] = "Le nom du client est requis";
    if (empty($numero)) $errors[] = "Le numéro de facture est requis";
    if (empty($date)) $errors[] = "La date est requise";
    if (empty($produits)) $errors[] = "Au moins un produit est requis";

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Mise à jour de la commande
            $stmt = $conn->prepare("UPDATE commandes SET 
                                  fournisseur_id = ?, 
                                  client_nom = ?, 
                                  numero_commande = ?, 
                                  date_commande = ?, 
                                  statut = ? 
                                  WHERE id = ?");
            $stmt->bind_param("issssi", $fournisseur_id, $client_nom, $numero, $date, $statut, $id);
            $stmt->execute();

            // Suppression des anciens produits
            $conn->query("DELETE FROM commande_produits WHERE commande_id = $id");

            // Insertion des nouveaux produits
            $total = 0;
            foreach ($produits as $prod) {
                $produit_id = intval($prod['id']);
                $quantite = intval($prod['quantite']);
                $prix = floatval($prod['prix']);
                
                $stmt_prod = $conn->prepare("INSERT INTO commande_produits 
                                            (commande_id, produit_id, quantite, prix_unitaire) 
                                            VALUES (?, ?, ?, ?)");
                $stmt_prod->bind_param("iiid", $id, $produit_id, $quantite, $prix);
                $stmt_prod->execute();
                
                $total += $prix;
            }

            // Mise à jour du total
            $conn->query("UPDATE commandes SET total = $total WHERE id = $id");

            $conn->commit();
            $_SESSION['success_message'] = "La facture a été mise à jour avec succès";
            header("Location: liste_commandes.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Facture #<?= htmlspecialchars($commande['numero_commande']) ?> | Gestion Factures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-top: 2rem;
        }
        .header-title {
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .produit-row {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .total-display {
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 4px;
            font-weight: bold;
        }
        .select2-container--bootstrap-5 .select2-selection {
            height: 38px;
            padding: 0.375rem 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">
                <i class="bi bi-pencil-square text-primary"></i> 
                Modifier Facture #<?= htmlspecialchars($commande['numero_commande']) ?>
            </h1>
            <a href="factures.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" id="facture-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="fournisseur_id" class="form-label">Fournisseur</label>
                        <select name="fournisseur_id" id="fournisseur_id" class="form-select" required>
                            <option value="">Sélectionnez un fournisseur</option>
                            <?php
                            $fournisseurs = $conn->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                            while ($f = $fournisseurs->fetch_assoc()) {
                                $selected = ($commande['fournisseur_id'] == $f['id']) ? "selected" : "";
                                echo "<option value='{$f['id']}' $selected>{$f['nom']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="client_nom" class="form-label">Client</label>
                        <input type="text" name="client_nom" id="client_nom" class="form-control" 
                               value="<?= htmlspecialchars($commande['client_nom']) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label for="numero_commande" class="form-label">Numéro de Facture</label>
                        <input type="text" name="numero_commande" id="numero_commande" class="form-control" 
                               value="<?= htmlspecialchars($commande['numero_commande']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="date_commande" class="form-label">Date</label>
                        <input type="date" name="date_commande" id="date_commande" class="form-control" 
                               value="<?= htmlspecialchars($commande['date_commande']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="statut" class="form-label">Statut</label>
                        <select name="statut" id="statut" class="form-select">
                            <option value="En attente" <?= ($commande['statut'] == 'En attente') ? 'selected' : '' ?>>En attente</option>
                            <option value="Payée" <?= ($commande['statut'] == 'Payée') ? 'selected' : '' ?>>Payée</option>
                            <option value="Annulée" <?= ($commande['statut'] == 'Annulée') ? 'selected' : '' ?>>Annulée</option>
                            <option value="Partiellement payée" <?= ($commande['statut'] == 'Partiellement payée') ? 'selected' : '' ?>>Partiellement payée</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3"><i class="bi bi-cart"></i> Produits</h5>
                <div id="produits-container">
                    <?php
                    $i = 0;
                    if ($produits_commande->num_rows > 0) {
                        while ($prod = $produits_commande->fetch_assoc()):
                    ?>
                        <div class="produit-row row g-2 mb-2">
                            <div class="col-md-5">
                                <select name="produits[<?= $i ?>][id]" class="form-select produit-select" required>
                                    <option value="">Sélectionnez un produit</option>
                                    <?php foreach ($produits_disponibles as $p): ?>
                                        <option value="<?= $p['id'] ?>" 
                                                data-prix="<?= $p['prix_vente'] ?>"
                                                data-ref="<?= htmlspecialchars($p['reference']) ?>"
                                                <?= ($p['id'] == $prod['produit_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nom']) ?> (<?= number_format($p['prix_vente'], 2) ?> DH)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="produits[<?= $i ?>][quantite]" 
                                       class="form-control quantite" min="1" 
                                       value="<?= $prod['quantite'] ?>" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="produits[<?= $i ?>][prix]" 
                                       class="form-control prix text-end" step="0.01" 
                                       value="<?= number_format($prod['prix_unitaire'], 2) ?>" readonly>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-danger supprimer-produit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            
                        </div>
                    <?php
                            $i++;
                        endwhile;
                    } else {
                        // Aucun produit existant - afficher une ligne vide
                    ?>
                        <div class="produit-row row g-2 mb-2">
                            <div class="col-md-5">
                                <select name="produits[0][id]" class="form-select produit-select" required>
                                    <option value="">Sélectionnez un produit</option>
                                    <?php foreach ($produits_disponibles as $p): ?>
                                        <option value="<?= $p['id'] ?>" 
                                                data-prix="<?= $p['prix_vente'] ?>"
                                                data-ref="<?= htmlspecialchars($p['reference']) ?>">
                                            <?= htmlspecialchars($p['nom']) ?> (<?= number_format($p['prix_vente'], 2) ?> DH)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="produits[0][quantite]" 
                                       class="form-control quantite" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="produits[0][prix]" 
                                       class="form-control prix text-end" step="0.01" readonly>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-outline-danger supprimer-produit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <input type="hidden" name="produits[0][reference]" class="produit-reference">
                        </div>
                    <?php
                    }
                    ?>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <button type="button" id="ajouter-produit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Ajouter un produit
                    </button>
                </div>

                <div class="row mt-4">
                   
                    <div class="col-md-6">
                        <div class="total-display text-end">
                            <div class="h5">Total: <span id="total-display"><?= number_format($commande['total'], 2) ?></span> DH</div>
                            <input type="hidden" name="total" id="total" value="<?= $commande['total'] ?>">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <button type="submit" name="enregistrer" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les modifications
                    </button>
                    <a href="factures.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        let produitIndex = <?= $i + 1 ?>;

        // Ajouter un nouveau produit
        $('#ajouter-produit').click(function() {
            const options = `<?php 
                foreach ($produits_disponibles as $p) {
                    echo '<option value="'.$p['id'].'" data-prix="'.$p['prix_vente'].'" data-ref="'.htmlspecialchars($p['reference']).'">'
                        .htmlspecialchars($p['nom']).' ('.number_format($p['prix_vente'], 2).' DH)</option>';
                }
            ?>`;

            const newRow = `
                <div class="produit-row row g-2 mb-2">
                    <div class="col-md-5">
                        <select name="produits[${produitIndex}][id]" class="form-select produit-select" required>
                            <option value="">Sélectionnez un produit</option>
                            ${options}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="produits[${produitIndex}][quantite]" 
                               class="form-control quantite" min="1" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="produits[${produitIndex}][prix]" 
                               class="form-control prix text-end" step="0.01" readonly>
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <button type="button" class="btn btn-outline-danger supprimer-produit">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <input type="hidden" name="produits[${produitIndex}][reference]" class="produit-reference">
                </div>`;
            
            $('#produits-container').append(newRow);
            produitIndex++;
        });

        // Supprimer un produit
        $(document).on('click', '.supprimer-produit', function() {
            if ($('.produit-row').length > 1) {
                $(this).closest('.produit-row').remove();
                calculerTotal();
            } else {
                alert("Une facture doit avoir au moins un produit.");
            }
        });

        // Changement de produit
        $(document).on('change', '.produit-select', function() {
            const selectedOption = $(this).find('option:selected');
            const prixUnitaire = parseFloat(selectedOption.data('prix')) || 0;
            const reference = selectedOption.data('ref') || '';
            
            const row = $(this).closest('.produit-row');
            const quantite = row.find('.quantite').val() || 1;
            const prixTotal = prixUnitaire * quantite;
            
            row.find('.prix').val(prixTotal.toFixed(2));
            row.find('.produit-reference').val(reference);
            
            calculerTotal();
        });

        // Changement de quantité
        $(document).on('input', '.quantite', function() {
            const row = $(this).closest('.produit-row');
            const prixUnitaire = parseFloat(row.find('.produit-select option:selected').data('prix')) || 0;
            const quantite = $(this).val() || 0;
            const prixTotal = prixUnitaire * quantite;
            
            row.find('.prix').val(prixTotal.toFixed(2));
            calculerTotal();
        });

        // Calcul du total
        function calculerTotal() {
            let total = 0;
            $('.prix').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            
            $('#total').val(total.toFixed(2));
            $('#total-display').text(total.toFixed(2));
        }

        // Initialisation des valeurs
        $('.produit-select').trigger('change');
    });
    </script>
</body>
</html>
<?php
ob_end_flush(); // 🔁 Vide le buffer et envoie les données
?>
