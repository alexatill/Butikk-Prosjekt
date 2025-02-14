<?php
// Starter sesjonen for å få tilgang til innloggingsinformasjon
session_start();
require_once 'db_config.php';

// Sjekker om brukeren er logget inn og har en bestillings-ID
if (!isset($_SESSION['bruker_id']) || !isset($_SESSION['last_order_id'])) {
    header("Location: index.php");
    exit();
}

// Hent bestillingsinformasjon
try {
    $stmt = $db->prepare("SELECT b.*, bd.*, p.navn, p.pris as enhetspris
        FROM bestilling b
        JOIN bestillingsdetaljer bd ON b.bestillingid = bd.bestillingid
        JOIN produkt p ON bd.produktid = p.produktid
        WHERE b.bestillingid = ? AND b.brukerid = ?");
    $stmt->execute([$_SESSION['last_order_id'], $_SESSION['bruker_id']]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sjekker om bestillingen finnes
    if (empty($order_items)) {
        header("Location: index.php");
        exit();
    }

    // Hent brukerinformasjon
    $stmt = $db->prepare("SELECT * FROM bruker WHERE brukerid = ?");
    $stmt->execute([$_SESSION['bruker_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Håndter feil ved henting av bestilling
    $error = "Feil ved henting av bestilling: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordrebekreftelse - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Takk for din bestilling!</h3>
                </div>
                <div class="card-body">
                    <h4>Ordrebekreftelse #<?php echo $_SESSION['last_order_id']; ?></h4>
                    <p>Din bestilling er mottatt og vil bli behandlet snarest.</p>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Leveringsadresse:</h5>
                            <p>
                                <?php echo htmlspecialchars($user['fornavn'] . ' ' . $user['etternavn']); ?><br>
                                <?php echo htmlspecialchars($user['adresse']); ?><br>
                                <?php echo htmlspecialchars($user['postnummer'] . ' ' . $user['poststad']); ?><br>
                                Telefon: <?php echo htmlspecialchars($user['telefon']); ?><br>
                                E-post: <?php echo htmlspecialchars($user['e-post']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5>Ordredetaljer:</h5>
                            <p>
                                Ordrenummer: #<?php echo $_SESSION['last_order_id']; ?><br>
                                Dato: <?php echo date('d.m.Y', strtotime($order_items[0]['dato'])); ?><br>
                                Status: Mottatt
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produkt</th>
                                    <th>Antall</th>
                                    <th>Pris per stk</th>
                                    <th>Sum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Beregner totalpris for bestillingen
                                $total = 0;
                                foreach ($order_items as $item): 
                                    // Beregner subtotal for hver produktlinje
                                    $subtotal = $item['enhetspris'] * $item['antall'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['navn']); ?></td>
                                    <td><?php echo $item['antall']; ?></td>
                                    <td><?php echo number_format($item['enhetspris'], 2, ',', ' '); ?> kr</td>
                                    <td><?php echo number_format($subtotal, 2, ',', ' '); ?> kr</td>
                                </tr>
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

                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">Fortsett å handle</a>
                        <a href="mine_bestillinger.php" class="btn btn-outline-secondary">Se mine bestillinger</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
