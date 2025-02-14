<?php
// Starter sesjonen for å få tilgang til handlekurvinformasjon
session_start();
require_once 'db_config.php';

// Sjekker om handlekurven eksisterer i session
if (!isset($_SESSION['handlekurv'])) {
    $_SESSION['handlekurv'] = [];
}

// Håndter legg til / fjern fra handlekurv
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add' && isset($_POST['product_id'])) {
        $produktid = $_POST['product_id'];
        // Legg til produkt i handlekurven eller oppdater antallet
        if (isset($_SESSION['handlekurv'][$produktid])) {
            $_SESSION['handlekurv'][$produktid]++;
        } else {
            $_SESSION['handlekurv'][$produktid] = 1;
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    } elseif ($_POST['action'] === 'remove' && isset($_POST['product_id'])) {
        $produktid = $_POST['product_id'];
        // Fjern produkt fra handlekurven
        if (isset($_SESSION['handlekurv'][$produktid])) {
            unset($_SESSION['handlekurv'][$produktid]);
        }
        header('Location: handlekurv.php');
        exit();
    } elseif ($_POST['action'] === 'update' && isset($_POST['quantities'])) {
        // Oppdater antall for produktene i handlekurven
        foreach ($_POST['quantities'] as $produktid => $quantity) {
            if ($quantity > 0) {
                $_SESSION['handlekurv'][$produktid] = $quantity;
            } else {
                unset($_SESSION['handlekurv'][$produktid]);
            }
        }
        header('Location: handlekurv.php');
        exit();
    }
}

// Hent produktinformasjon for varene i handlekurven
$cart_items = [];
$total = 0;
if (!empty($_SESSION['handlekurv'])) {
    $product_ids = array_keys($_SESSION['handlekurv']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    // Hent produktene fra databasen
    $stmt = $db->prepare("SELECT * FROM produkt WHERE produktid IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lager en liste over produktene i handlekurven med antall og sum
    foreach ($products as $product) {
        $quantity = $_SESSION['handlekurv'][$product['produktid']];
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $product['pris'] * $quantity
        ];
        $total += $product['pris'] * $quantity;
    }
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handlekurv - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Din handlekurv</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Handlekurven din er tom. <a href="index.php">Fortsett å handle</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produkt</th>
                                <th>Pris</th>
                                <th>Antall</th>
                                <th>Sum</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product']['navn']); ?></td>
                                <td><?php echo number_format($item['product']['pris'], 2, ',', ' '); ?> kr</td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $item['product']['produktid']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" min="0" max="99" class="form-control" style="width: 80px;">
                                </td>
                                <td><?php echo number_format($item['subtotal'], 2, ',', ' '); ?> kr</td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['produktid']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Fjern</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong><?php echo number_format($total, 2, ',', ' '); ?> kr</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Fortsett å handle</a>
                    <div>
                        <button type="submit" name="action" value="update" class="btn btn-primary">Oppdater handlekurv</button>
                        <?php if (isset($_SESSION['bruker_id'])): ?>
                            <a href="kasse.php" class="btn btn-success">Gå til kassen</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-success">Logg inn for å betale</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
