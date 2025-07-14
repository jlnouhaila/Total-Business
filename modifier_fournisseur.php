<?php
include("connexion.php");

// Vérifie qu’un ID est passé
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID fournisseur manquant.</div>";
    exit;
}

$id = intval($_GET['id']);

// Récupérer les infos du fournisseur
$result = $conn->query("SELECT * FROM fournisseurs WHERE id = $id");

if ($result->num_rows === 0) {
    echo "<div class='alert alert-warning'>Fournisseur introuvable.</div>";
    exit;
}

$fournisseur = $result->fetch_assoc();

// Traitement du formulaire
if (isset($_POST['modifier'])) {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];

    $conn->query("UPDATE fournisseurs SET 
        nom = '$nom', 
        email = '$email', 
        telephone = '$telephone', 
        adresse = '$adresse' 
        WHERE id = $id");

    header("Location: fournisseurs.php?modification=success");
exit;

}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un fournisseur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>✏️ Modifier les informations du fournisseur</h2>

    <form method="post" class="bg-light p-4 rounded shadow-sm mt-4">
        <div class="mb-3">
            <label>Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($fournisseur['nom']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($fournisseur['email']) ?>">
        </div>
        <div class="mb-3">
            <label>Téléphone</label>
            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($fournisseur['telephone']) ?>">
        </div>
        <div class="mb-3">
            <label>Adresse</label>
            <textarea name="adresse" class="form-control"><?= htmlspecialchars($fournisseur['adresse']) ?></textarea>
        </div>
        <button type="submit" name="modifier" class="btn btn-success" >💾 Enregistrer les modifications</button>
        <a href="fournisseurs.php" class="btn btn-outline-secondary ms-2">Annuler</a>
    </form>
</div>
</body>
</html>
