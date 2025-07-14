<?php 
// Configuration et sécurité
require_once("connexion.php");

// Vérification de la connexion à la base de données
if (!$conn) {
    die("Erreur de connexion à la base de données");
}

// Définition des constantes
define('DATE_FORMAT', 'd/m/Y');
define('CURRENCY_SUFFIX', ' DH');
define('ITEMS_PER_PAGE', 10);

// Paramètres de pagination
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * ITEMS_PER_PAGE;

// Initialisation des variables de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Factures | Historique</title>
    
    <!-- Intégration Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-container {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .table-container {
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
        }
        
        .product-list {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        
        .product-list li {
            padding: 2px 0;
            font-size: 0.9rem;
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .search-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .filter-badge {
            background-color: var(--secondary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-container">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-0"><i class="bi bi-receipt"></i> Historique des Factures</h1>
                    <p class="mb-0 opacity-75">Gestion complète de votre facturation</p>
                </div>
                <a href="ajouter_facture.php" class="btn btn-light btn-lg">
                    <i class="bi bi-plus-circle"></i> Nouvelle facture
                </a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Messages de notification -->
        <?php if (isset($_GET['ajoute'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                    <div>
                        <strong>Succès !</strong> La facture a été ajoutée avec succès.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php elseif (isset($_GET['modifie'])): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                    <div>
                        <strong>Modification réussie !</strong> La facture a été mise à jour.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>

        <!-- Barre de recherche et filtres -->
        <div class="card search-card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Rechercher par numéro, client ou fournisseur..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="statut">
                            <option value="">Tous les statuts</option>
                            <option value="payée" <?= ($statut === 'payée') ? 'selected' : '' ?>>Payée</option>
                            <option value="en attente" <?= ($statut === 'en attente') ? 'selected' : '' ?>>En attente</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="date" class="form-control" name="date" 
                                   value="<?= htmlspecialchars($date) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.querySelector('input[name=date]').value=''">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($search) || !empty($statut) || !empty($date)): ?>
                    <div class="mt-3">
                        <a href="historique.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                        </a>
                        
                        <span class="small text-muted">Filtres actifs :</span>
                        
                        <?php if (!empty($search)): ?>
                            <span class="badge filter-badge ms-2">
                                <i class="bi bi-search me-1"></i> "<?= htmlspecialchars($search) ?>"
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($statut)): ?>
                            <span class="badge filter-badge ms-2">
                                <i class="bi bi-funnel me-1"></i> Statut: <?= htmlspecialchars($statut) ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($date)): ?>
                            <span class="badge filter-badge ms-2">
                                <i class="bi bi-calendar me-1"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($date))) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tableau des factures -->
        <div class="table-container">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Date</th>
                        <th>Fournisseur</th>
                        <th>Client</th>
                        <th>Produits</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Construction de la requête avec filtres
                        $sql = "SELECT c.*, f.nom AS fournisseur
                                FROM commandes c
                                JOIN fournisseurs f ON c.fournisseur_id = f.id
                                WHERE 1=1";
                        
                        $params = [];
                        $types = "";
                        
                        // Filtre par recherche texte
                        if (!empty($search)) {
                            $search_param = "%" . $search . "%";
                            $sql .= " AND (c.numero_commande LIKE ? OR c.client_nom LIKE ? OR f.nom LIKE ?)";
                            $params = array_merge($params, [$search_param, $search_param, $search_param]);
                            $types .= "sss";
                        }
                        
                        // Filtre par statut
                        if (!empty($statut)) {
                            $sql .= " AND c.statut = ?";
                            $params[] = $statut;
                            $types .= "s";
                        }
                        
                        // Filtre par date
                        if (!empty($date)) {
                            $sql .= " AND DATE(c.date_commande) = ?";
                            $params[] = $date;
                            $types .= "s";
                        }
                        
                        // Requête pour le nombre total d'éléments
                        $count_sql = "SELECT COUNT(*) as total FROM ($sql) as total_query";
                        $count_stmt = $conn->prepare($count_sql);
                        
                        if (!empty($params)) {
                            $count_stmt->bind_param($types, ...$params);
                        }
                        
                        $count_stmt->execute();
                        $total_items = $count_stmt->get_result()->fetch_assoc()['total'];
                        $total_pages = ceil($total_items / ITEMS_PER_PAGE);
                        
                        // Ajout de la pagination à la requête principale
                        $sql .= " ORDER BY c.date_commande DESC LIMIT ? OFFSET ?";
                        $params = array_merge($params, [ITEMS_PER_PAGE, $offset]);
                        $types .= "ii";
                        
                        // Préparation et exécution de la requête principale
                        $stmt = $conn->prepare($sql);
                        
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        
                        $stmt->execute();
                        $res = $stmt->get_result();
                        
                        if (!$res) {
                            throw new Exception("Erreur lors de la récupération des factures");
                        }
                        
                        if ($res->num_rows === 0): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-file-earmark-excel"></i>
                                        <h4>Aucune facture trouvée</h4>
                                        <?php if (!empty($search) || !empty($statut) || !empty($date)): ?>
                                            <p>Aucun résultat ne correspond à vos critères de recherche</p>
                                            <a href="historique.php" class="btn btn-primary">
                                                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser la recherche
                                            </a>
                                        <?php else: ?>
                                            <p>Commencez par ajouter votre première facture</p>
                                            <a href="ajouter_facture.php" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Créer une facture
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else:
                            while ($cmd = $res->fetch_assoc()):
                                // Formatage des données
                                $id = (int)$cmd['id'];
                                $numero = htmlspecialchars($cmd['numero_commande']);
                                $date = date(DATE_FORMAT, strtotime($cmd['date_commande']));
                                $fournisseur = htmlspecialchars(ucwords(strtolower($cmd['fournisseur'])));
                                $client_nom = htmlspecialchars($cmd['client_nom']);
                                $total = number_format($cmd['total'], 2, ',', ' ') . CURRENCY_SUFFIX;
                                
                                // Badge de statut
                                $statut_class = strtolower($cmd['statut']) === 'payée' ? 'bg-success' : 'bg-warning text-dark';
                                $statut_icon = strtolower($cmd['statut']) === 'payée' ? 'bi-check-circle' : 'bi-hourglass';
                                $statut_text = strtolower($cmd['statut']) === 'payée' ? 'Payée' : 'En attente';
                                
                                // Récupération des produits
                                $produits_cmd = [];
                                $sqlProduits = "SELECT p.nom, cp.quantite 
                                               FROM commande_produits cp
                                               JOIN produits p ON cp.produit_id = p.id
                                               WHERE cp.commande_id = ?";
                                
                                $stmtProduits = $conn->prepare($sqlProduits);
                                $stmtProduits->bind_param("i", $id);
                                $stmtProduits->execute();
                                $resProduits = $stmtProduits->get_result();
                                
                                while ($prod = $resProduits->fetch_assoc()) {
                                    $produits_cmd[] = sprintf(
                                        '<li>%s <span class="text-muted">(x%d)</span></li>',
                                        htmlspecialchars($prod['nom']),
                                        (int)$prod['quantite']
                                    );
                                }
                                
                                $liste_produits = !empty($produits_cmd) 
                                    ? sprintf('<ul class="product-list">%s</ul>', implode('', $produits_cmd))
                                    : '<span class="text-muted fst-italic">Aucun produit</span>';
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= $numero ?></td>
                                    <td><?= $date ?></td>
                                    <td><?= $fournisseur ?></td>
                                    <td><?= $client_nom ?></td>
                                    <td><?= $liste_produits ?></td>
                                    <td class="fw-bold text-primary"><?= $total ?></td>
                                    <td>
                                        <span class="status-badge <?= $statut_class ?>">
                                            <i class="bi <?= $statut_icon ?> me-1"></i> <?= $statut_text ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            <a href="voir_facture.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary action-btn" title="Détails" data-bs-toggle="tooltip">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="bon_commande.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-secondary action-btn" title="Exporter en PDF" data-bs-toggle="tooltip">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                            <a href="modifier_facture.php?id=<?= $id ?>" class="btn btn-sm btn-outline-warning action-btn" title="Modifier" data-bs-toggle="tooltip">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger action-btn" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $id ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Modal de confirmation de suppression -->
                                        <div class="modal fade" id="deleteModal<?= $id ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmer la suppression</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Êtes-vous sûr de vouloir supprimer la facture <strong><?= $numero ?></strong> ? Cette action est irréversible.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                        <a href="supprimer_facture.php?id=<?= $id ?>" class="btn btn-danger">Supprimer</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                        endif;
                    } catch (Exception $e) {
                        echo '<tr><td colspan="8" class="text-center text-danger">'.$e->getMessage().'</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" tabindex="-1" aria-disabled="true">
                            <i class="bi bi-chevron-left"></i> Précédent
                        </a>
                    </li>
                    
                    <?php 
                    // Affichage des numéros de page
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">
                            Suivant <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center text-muted">
            <p class="mb-0">Système de gestion de factures &copy; <?= date('Y') ?></p>
        </div>
    </footer>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        // Activation des tooltips Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: 'hover'
                });
            });
        });
    </script>
</body>
</html>