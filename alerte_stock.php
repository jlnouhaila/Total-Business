<?php include("connexion.php"); ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Ton fichier CSS personnalisé -->
<link href="style.css" rel="stylesheet">

    <meta charset="UTF-8">
    <title>Produits en stock faible</title>
</head>
<body>
<h2>Produits à commander (stock bas)</h2>
<table border="1">
   
    <?php
    $res = $conn->query("SELECT * FROM produits WHERE quantite <= seuil_min");
    while ($row = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$row['nom']}</td>
            <td>{$row['quantite']}</td>
        </tr>";
    }
    ?>
</table>
</body>
</html>
