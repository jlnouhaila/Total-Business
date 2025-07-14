function demanderMotDePasse() {
    let mdp = prompt("Entrez le mot de passe pour accéder au site :");

    if (mdp === "admin123") {
        window.location.href = "produits.php";
    } else if (mdp !== null) {
        alert("Mot de passe incorrect !");
    }
}
