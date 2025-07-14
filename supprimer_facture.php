<?php
ob_start(); // 🔄 Active le buffering de sortie
include("connexion.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // sécurité
    $delete_query = "DELETE FROM commandes WHERE id = $id";

    if ($conn->query($delete_query)) {
        header("Location: liste_commandes.php?supprime=1");
        exit(); // 🔚 toujours ajouter exit après header
    } else {
        echo "Erreur lors de la suppression : " . $conn->error;
    }
} else {
    echo "ID de la facture non spécifié.";
}

ob_end_flush(); // 🔁 Vide le buffer
?>
