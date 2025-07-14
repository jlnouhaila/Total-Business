<!DOCTYPE html>
<html lang="fr">
<head><?php
// Rediriger automatiquement vers la page de login
header("Location: login.php");
exit();
?>

    <meta charset="UTF-8">
    <title>Accueil - Gestion Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5 text-center">
    <h1 class="display-4 mb-3">📦 Gestion de Stock Informatique</h1>
    <p class="lead">Suivi, réapprovisionnement et gestion des fournisseurs en un seul endroit.</p>
    <a href="login.php" class="btn btn-primary btn-lg mt-4">Se connecter</a>
</div>
</body>
</html>
