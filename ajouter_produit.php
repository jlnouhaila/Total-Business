<?php
require_once("connexion.php");

// Initialisation des variables et messages
$message = '';
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    // Nettoyage et validation des données
    $nom = trim($_POST['nom']);
    $reference = trim($_POST['reference']);
    $categorie = trim($_POST['categorie']);
    $quantite = intval($_POST['quantite']);
    $prix_achat = floatval($_POST['prix_achat']);
    $prix_vente = floatval($_POST['prix_vente']);
    $fournisseur_id = !empty($_POST['fournisseur_id']) ? intval($_POST['fournisseur_id']) : null;

    // Validation des champs obligatoires
    if (empty($nom) || empty($reference)) {
        $message = "Les champs 'Nom' et 'Référence' sont obligatoires";
    } elseif ($prix_vente <= 0 || $prix_achat <= 0) {
        $message = "Les prix doivent être supérieurs à zéro";
    } else {
        // Vérification de l'unicité de la référence
        try {
            $verif = $conn->prepare("SELECT COUNT(id) FROM produits WHERE reference = ?");
            $verif->bind_param("s", $reference);
            $verif->execute();
            $verif->bind_result($count);
            $verif->fetch();
            $verif->close();

            if ($count > 0) {
                $message = "La référence '$reference' est déjà utilisée";
            } else {
                // Insertion sécurisée avec requête préparée
                $stmt = $conn->prepare("INSERT INTO produits 
                    (nom, reference, categorie, quantite, prix_achat, prix_vente, fournisseur_id, date_ajout)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

                $stmt->bind_param("sssiddi", $nom, $reference, $categorie, $quantite, $prix_achat, $prix_vente, $fournisseur_id);

                if ($stmt->execute()) {
                    $success = true;
                    $message = "Produit ajouté avec succès";
                    // Réinitialisation des valeurs pour un nouvel ajout
                    $_POST = array();
                } else {
                    throw new Exception("Erreur lors de l'ajout du produit: " . $stmt->error);
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $message = "Erreur système: " . $e->getMessage();
        }
    }

    // Préparation du message pour l'affichage
    $alert_class = $success ? 'alert-success' : 'alert-danger';
    $message = $message ? "<div class='alert $alert_class alert-dismissible fade show' role='alert'>$message<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>" : '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des produits - Ajout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .required:after {
            content: " *";
            color: red;
        }
        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="form-container">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0"><i class="bi bi-plus-circle"></i> Ajouter un nouveau produit</h2>
                </div>
                
                <div class="card-body">
                    <?= $message ?>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="nom" class="form-label required">Nom du produit</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                                <div class="invalid-feedback">Veuillez saisir un nom valide</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="reference" class="form-label required">Référence</label>
                                <input type="text" class="form-control" id="reference" name="reference" 
                                       value="<?= htmlspecialchars($_POST['reference'] ?? '') ?>" required>
                                <div class="invalid-feedback">Veuillez saisir une référence</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="categorie" class="form-label">Catégorie</label>
                                <input type="text" class="form-control" id="categorie" name="categorie" 
                                       value="<?= htmlspecialchars($_POST['categorie'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fournisseur_id" class="form-label">Fournisseur</label>
                                <select class="form-select" id="fournisseur_id" name="fournisseur_id">
                                    <option value="">-- Sélectionnez un fournisseur --</option>
                                    <?php
                                    $res = $conn->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                    while ($f = $res->fetch_assoc()) {
                                        $selected = ($_POST['fournisseur_id'] ?? '') == $f['id'] ? 'selected' : '';
                                        echo "<option value='{$f['id']}' $selected>{$f['nom']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="quantite" class="form-label">Quantité en stock</label>
                                <input type="number" class="form-control" id="quantite" name="quantite" 
                                       min="0" value="<?= htmlspecialchars($_POST['quantite'] ?? 0) ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="prix_achat" class="form-label">Prix d'achat (DH)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" id="prix_achat" 
                                           name="prix_achat" value="<?= htmlspecialchars($_POST['prix_achat'] ?? '') ?>">
                                    <span class="input-group-text">DH</span>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="prix_vente" class="form-label">Prix de vente (DH)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" id="prix_vente" 
                                           name="prix_vente" value="<?= htmlspecialchars($_POST['prix_vente'] ?? '') ?>">
                                    <span class="input-group-text">DH</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="produits.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Retour à la liste
                            </a>
                            <button type="submit" name="ajouter" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer le produit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation côté client
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>