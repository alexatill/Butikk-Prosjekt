<?php
// Slå på feilmeldingar for å lettare finne feil
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_config.php';

// Handterer sletting av produkt
if (isset($_POST['slett_produkt']) && isset($_POST['product_id'])) {
    try {
        $product_id = $_POST['product_id'];
        
        // Sjekk om produktet er brukt i bestillingar
        $stmt = $db->prepare("SELECT COUNT(*) FROM bestillingsdetaljer WHERE produktid = ?");
        $stmt->execute([$product_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = "Kan ikkje slette produktet fordi det er brukt i bestillingar.";
        } else {
            // Viss produktet ikkje er brukt i bestillingar, slett det
            $stmt = $db->prepare("DELETE FROM produkt WHERE produktid = ?");
            if ($stmt->execute([$product_id])) {
                $success = "Produkt vart sletta.";
                // Redirect for å unngå at same produkt vert sletta ved refresh
                header("Location: index.php?deleted=true");
                exit();
            } else {
                $error = "Kunne ikkje slette produktet.";
            }
        }
    } catch(PDOException $e) {
        $error = "Feil ved sletting: " . $e->getMessage();
    }
}

// Hent alle produkt frå databasen
try {
    $stmt = $db->query("SELECT * FROM produkt");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Feil ved henting av produkt: " . $e->getMessage();
    $products = [];
}

// Tel antal produkt i handlekorga
$cart_count = 0;
if (isset($_SESSION['handlekurv'])) {
    foreach ($_SESSION['handlekurv'] as $quantity) {
        $cart_count += $quantity;
    }
}

// Vis slettebekreftelse
if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
    $success = "Produkt vart sletta.";
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHub - Datakomponent Butikk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            height: 100%;
        }
        .product-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .delete-form {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
    </style>
</head>
<body>
    <!-- Navigasjon -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">TechHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Hjem</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="alle_bestillinger.php">Se Alle Bestillinger</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="legg_til_produkt.php">Legg til Produkt</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Kategorier
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Prosessorer</a></li>
                            <li><a class="dropdown-item" href="#">Grafikkort</a></li>
                            <li><a class="dropdown-item" href="#">Hovedkort</a></li>
                            <li><a class="dropdown-item" href="#">Minne</a></li>
                            <li><a class="dropdown-item" href="#">Lagring</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="handlekurv.php">
                            Handlekurv <?php if ($cart_count > 0): ?><span class="badge bg-primary"><?php echo $cart_count; ?></span><?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['bruker_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['fornavn']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="mine_bestillinger.php">Mine bestillinger</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logg ut</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Logg inn</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registrer.php">Registrer</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <div class="row">
            <!-- Sidebar Filtre -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <h4>Filtre</h4>
                    <hr>
                    <h5>Prisklasse</h5>
                    <div class="mb-3">
                        <input type="range" class="form-range" min="0" max="20000" id="priceRange">
                        <div class="d-flex justify-content-between">
                            <span>0 kr</span>
                            <span>20000 kr</span>
                        </div>
                    </div>
                    
                    <h5>Merke</h5>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="brand1">
                        <label class="form-check-label" for="brand1">Intel</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="brand2">
                        <label class="form-check-label" for="brand2">AMD</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="brand3">
                        <label class="form-check-label" for="brand3">NVIDIA</label>
                    </div>
                    
                    <h5 class="mt-3">Sorter etter</h5>
                    <select class="form-select">
                        <option>Pris: Lav til Høy</option>
                        <option>Pris: Høy til Lav</option>
                        <option>Mest Populære</option>
                        <option>Nyeste Først</option>
                    </select>
                </div>
            </div>

            <!-- Produktvisning -->
            <div class="col-lg-9">
                <div class="row">
                    <?php
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            // Sjekk om bilde eksisterer
                            $bilde_sti = 'bilde/' . $product['produktid'] . '.';
                            $bilde_funnet = false;
                            foreach(['jpg', 'jpeg', 'png', 'gif'] as $ext) {
                                if (file_exists($bilde_sti . $ext)) {
                                    $bilde_sti .= $ext;
                                    $bilde_funnet = true;
                                    break;
                                }
                            }
                            ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php if ($bilde_funnet): ?>
                                        <img src="<?php echo htmlspecialchars($bilde_sti); ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($product['navn']); ?>"
                                             style="height: 200px; object-fit: contain; padding: 10px;">
                                    <?php else: ?>
                                        <div class="text-center p-4 bg-light">
                                            <i class="fas fa-image fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['navn']); ?></h5>
                                        <p class="card-text">
                                            <strong>Type:</strong> <?php echo htmlspecialchars($product['type']); ?><br>
                                            <strong>Modell:</strong> <?php echo htmlspecialchars($product['modell']); ?><br>
                                            <strong>Farge:</strong> <?php echo htmlspecialchars($product['farge']); ?><br>
                                            <strong>Pris:</strong> <?php echo htmlspecialchars($product['pris']); ?> kr<br>
                                            <strong>På lager:</strong> <?php echo htmlspecialchars($product['antall']); ?> stk
                                        </p>
                                        
                                        <form method="POST" action="handlekurv.php" class="d-inline">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo $product['produktid']; ?>">
                                            <button type="submit" class="btn btn-primary">Legg i handlekurv</button>
                                        </form>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Er du sikker på at du vil slette dette produktet?');">
                                            <input type="hidden" name="slett_produkt" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $product['produktid']; ?>">
                                            <button type="submit" class="btn btn-danger">Slett</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12"><p class="text-center">Ingen produkt funne.</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>