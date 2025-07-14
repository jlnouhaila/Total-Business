<?php
include("connexion.php");
include("auth.php");

$id = $_GET['id'];
$conn->query("DELETE FROM produits WHERE id = $id");

header("Location: produits.php");
?>