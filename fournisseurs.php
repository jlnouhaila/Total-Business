<?php
session_start();
ob_start(); // Pour éviter les erreurs de header

include("connexion.php");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    try {
        $nom = htmlspecialchars(trim($_POST['nom']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $tel = preg_replace('/[^0-9]/', '', trim($_POST['telephone']));
        $adresse = htmlspecialchars(trim($_POST['adresse']));

        if (empty($nom)) {
            throw new Exception("Le nom du fournisseur est obligatoire.");
        }

        $stmt = $conn->prepare("INSERT INTO fournisseurs (nom, email, telephone, adresse) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nom, $email, $tel, $adresse);
        $stmt->execute();

        $_SESSION['success_message'] = "Fournisseur ajouté avec succès.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fournisseurs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container-lg mt-5">
    <h2 class="mb-4 text-primary">📋 Gestion des Fournisseurs</h2>

    <!-- Message d'alerte -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5><i class="bi bi-plus-circle me-2"></i>Ajouter un Fournisseur</h5>
        </div>
        <div class="card-body">
            <form method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="nom" class="form-control" required autocomplete="name" placeholder="Ex: Techno SARL">
                        <div class="invalid-feedback">Nom requis.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" autocomplete="email" placeholder="exemple@domaine.com">
                        <div class="invalid-feedback">Email invalide.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" id="telephone" class="form-control" pattern="[0-9]{10}" maxlength="10" placeholder="0612345678">
                        <div class="invalid-feedback">Numéro à 10 chiffres requis.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea name="adresse" id="adresse" rows="2" class="form-control" placeholder="Adresse complète..."></textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" name="ajouter" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des fournisseurs -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des Fournisseurs</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php
                $result = $conn->query("SELECT * FROM fournisseurs ORDER BY nom ASC");
                ?>
                <p class="text-muted">Nombre total : <strong><?= $result->num_rows ?></strong></p>

                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Adresse</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($f = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['nom']) ?></td>
                                    <td><?= $f['email'] ? '<a href="mailto:' . htmlspecialchars($f['email']) . '">' . htmlspecialchars($f['email']) . '</a>' : '' ?></td>
                                    <td><?= $f['telephone'] ? '<a href="tel:' . htmlspecialchars($f['telephone']) . '">' . htmlspecialchars($f['telephone']) . '</a>' : '' ?></td>
                                    <td><?= nl2br(htmlspecialchars($f['adresse'])) ?></td>
                                    <td class="text-end">
                                        <a href="modifier_fournisseur.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $f['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="deleteModal<?= $f['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $f['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmation</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Supprimer le fournisseur <strong><?= htmlspecialchars($f['nom']) ?></strong> ?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <a href="supprimer_fournisseur.php?id=<?= $f['id'] ?>" class="btn btn-danger">Confirmer</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Aucun fournisseur trouvé.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Validation JS -->
<script>
(() => {
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

<?php ob_end_flush(); ?>
