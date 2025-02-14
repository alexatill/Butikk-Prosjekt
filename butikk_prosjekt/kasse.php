<?php
// Starter sesjonen for å få tilgang til innloggingsinformasjon
session_start();
require_once 'db_config.php';

// Sjekker om brukeren er logget inn
if (!isset($_SESSION['bruker_id'])) {
    // Om brukeren ikke er logget inn, redirect til login-siden
    header("Location: login.php");
    exit();
}

// Sjekker om handlekurven er tom
if (empty($_SESSION['handlekurv'])) {
    // Om handlekurven er tom, redirect til handlekurv-siden
    header("Location: handlekurv.php");
    exit();
}

// Hent brukerinformasjon
$stmt = $db->prepare("SELECT * FROM bruker WHERE brukerid = ?");
$stmt->execute([$_SESSION['bruker_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Håndter bestilling
if (isset($_POST['place_order'])) {
    try {
        // Starter en transaksjon for å sikre at alle operasjoner blir utført eller ingen
        $db->beginTransaction();

        // Sjekk lagerstatus og oppdater handlekurv
        $product_ids = array_keys($_SESSION['handlekurv']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        $stmt_products = $db->prepare("SELECT produktid, navn, antall FROM produkt WHERE produktid IN ($placeholders)");
        $stmt_products->execute($product_ids);
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
        
        // Lager en liste for å samle feilmeldinger
        $error_messages = [];
        foreach ($products as $product) {
            $requested_quantity = $_SESSION['handlekurv'][$product['produktid']];
            if ($requested_quantity > $product['antall']) {
                // Om det ikke er nok på lager, legg til en feilmelding
                $error_messages[] = "Ikke nok på lager av {$product['navn']}. Tilgjengelig: {$product['antall']}, Forespurt: {$requested_quantity}";
            }
        }
        
        if (!empty($error_messages)) {
            // Om det er feilmeldinger, kast en unntakelse
            throw new Exception(implode("<br>", $error_messages));
        }

        // Opprett bestilling
        $stmt = $db->prepare("INSERT INTO bestilling (brukerid, dato, pris) VALUES (?, NOW(), ?)");
        
        // Beregn total pris
        $total = 0;
        $stmt_products = $db->prepare("SELECT * FROM produkt WHERE produktid IN ($placeholders)");
        $stmt_products->execute($product_ids);
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $_SESSION['handlekurv'][$product['produktid']];
            $total += $product['pris'] * $quantity;
        }

        $stmt->execute([$_SESSION['bruker_id'], $total]);
        $bestilling_id = $db->lastInsertId();

        // Legg til bestillingsdetaljer og oppdater lagerstatus
        $stmt_details = $db->prepare("INSERT INTO bestillingsdetaljer (bestillingid, produktid, antall) VALUES (?, ?, ?)");
        $stmt_update_stock = $db->prepare("UPDATE produkt SET antall = antall - ? WHERE produktid = ?");
        
        foreach ($_SESSION['handlekurv'] as $produktid => $quantity) {
            // Legg til i bestillingsdetaljer
            $stmt_details->execute([$bestilling_id, $produktid, $quantity]);
            
            // Oppdater lagerstatus
            $stmt_update_stock->execute([$quantity, $produktid]);
        }

        // Fullfør transaksjonen
        $db->commit();
        
        // Tøm handlekurven og redirect til bekreftelsessiden
        $_SESSION['handlekurv'] = [];
        $_SESSION['last_order_id'] = $bestilling_id;
        header("Location: bekreftelse.php");
        exit();
        
    } catch (Exception $e) {
        // Om det oppstår en feil, avbryt transaksjonen
        $db->rollBack();
        $error = "Feil ved behandling av bestilling: " . $e->getMessage();
    }
}

// Hent produkter i handlekurven
$cart_items = [];
$total = 0;
if (!empty($_SESSION['handlekurv'])) {
    $product_ids = array_keys($_SESSION['handlekurv']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $db->prepare("SELECT * FROM produkt WHERE produktid IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['handlekurv'][$product['produktid']];
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $product['pris'] * $quantity,
            'in_stock' => $product['antall']
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
    <title>Kasse - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Kasse</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Leveringsadresse</h4>
                    </div>
                    <div class="card-body">
                        <p><strong><?php echo htmlspecialchars($user['fornavn'] . ' ' . $user['etternavn']); ?></strong></p>
                        <p><?php echo htmlspecialchars($user['adresse']); ?></p>
                        <p><?php echo htmlspecialchars($user['postnummer'] . ' ' . $user['poststad']); ?></p>
                        <p>Telefon: <?php echo htmlspecialchars($user['telefon']); ?></p>
                        <p>E-post: <?php echo htmlspecialchars($user['e-post']); ?></p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>Ordresammendrag</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produkt</th>
                                        <th>Antall</th>
                                        <th>Pris</th>
                                        <th>Sum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product']['navn']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($item['product']['pris'], 2, ',', ' '); ?> kr</td>
                                        <td><?php echo number_format($item['subtotal'], 2, ',', ' '); ?> kr</td>
                                    </tr>
                                    <?php if ($item['quantity'] > $item['in_stock']): ?>
                                    <tr>
                                        <td colspan="4" class="text-danger">
                                            Advarsel: Bare <?php echo $item['in_stock']; ?> stk på lager av dette produktet
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong><?php echo number_format($total, 2, ',', ' '); ?> kr</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Betal</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="place_order" class="btn btn-success btn-lg">Bekreft og betal</button>
                                <a href="handlekurv.php" class="btn btn-outline-secondary">Tilbake til handlekurv</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
