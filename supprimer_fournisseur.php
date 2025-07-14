<?php
ob_start(); // Pour éviter l'erreur "headers already sent"
include("connexion.php");

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = intval($_GET['id']);

    // Vérifier si le fournisseur est utilisé dans des commandes
    $stmt_verif = $conn->prepare("SELECT COUNT(*) AS total FROM commandes WHERE fournisseur_id = ?");
    $stmt_verif->bind_param("i", $id);
    $stmt_verif->execute();
    $result = $stmt_verif->get_result()->fetch_assoc();

    if ($result['total'] > 0) {
        // Redirection avec message d'erreur
        header("Location: fournisseurs.php?erreur=utilise");
        exit();
    }

    // Supprimer le fournisseur
    $stmt = $conn->prepare("DELETE FROM fournisseurs WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: fournisseurs.php?supprime=1");
        exit();
    } else {
        header("Location: fournisseurs.php?erreur=suppression");
        exit();
    }

} else {
    header("Location: fournisseurs.php?erreur=invalide");
    exit();
}

ob_end_flush();
