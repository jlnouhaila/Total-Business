<?php
session_start();

// Supprimer toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion ou d'accueil
header("Location: login.php"); // ou index.php si tu veux revenir à l'accueil
exit();
?>
