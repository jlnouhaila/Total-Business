<?php
ob_start();
include("connexion.php");


// Vérification de l'ID du produit
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = $conn->query("SELECT * FROM produits WHERE id = $id");

if (!$result || $result->num_rows === 0) {
    die("<div class='alert alert-danger m-4'>❌ Produit introuvable.</div>");
}

$produit = $result->fetch_assoc();

// Traitement du formulaire
if (isset($_POST['update'])) {
    $nom     = $_POST['nom'];
    $ref     = $_POST['reference'];
    $cat     = $_POST['categorie'];
    $qte     = $_POST['quantite'];
    $achat   = $_POST['prix_achat'];
    $vente   = $_POST['prix_vente'];

  $update = $conn->prepare("UPDATE produits SET nom=?, reference=?, categorie=?, quantite=?, prix_achat=?, prix_vente=? WHERE id=?");
$update->bind_param("sssiddi", $nom, $ref, $cat, $qte, $achat, $vente, $id);


    if ($update->execute()) {
        header("Location: produits.php?modifie=1");
        exit();
    } else {
        echo "<div class='alert alert-danger mt-4 container'>❌ Erreur : " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le produit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">✏️ Modifier le produit</h2>

    <form method="post" class="bg-light p-4 rounded shadow-sm">
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($produit['nom']) ?>" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label>Référence</label>
                <input type="text" name="reference" value="<?= htmlspecialchars($produit['reference']) ?>" class="form-control" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Catégorie</label>
                <input type="text" name="categorie" value="<?= htmlspecialchars($produit['categorie']) ?>" class="form-control">
            </div>
            <div class="col-md-6">
                <label>Quantité</label>
                <input type="number" name="quantite" value="<?= $produit['quantite'] ?>" class="form-control" min="0" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label>Prix d'achat (DH)</label>
                <input type="number" step="0.01" name="prix_achat" value="<?= $produit['prix_achat'] ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Prix de vente (DH)</label>
                <input type="number" step="0.01" name="prix_vente" value="<?= $produit['prix_vente'] ?>" class="form-control">
            </div>
        </div>

        <button type="submit" name="update" class="btn btn-primary">💾 Enregistrer</button>
        <a href="produits.php" class="btn btn-secondary ms-2">↩️ Retour</a>
    </form>
</div>
</body>
</html>
<?php ob_end_flush(); ?>
