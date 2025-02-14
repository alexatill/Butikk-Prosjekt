<?php
// Starter sesjonen for å få tilgang til innloggingsinformasjon
session_start();
require_once 'db_config.php';

// Sjekker om brukeren er logget inn
if (!isset($_SESSION['bruker_id'])) {
    header("Location: login.php");
    exit();
}

// Hent alle bestillinger for brukeren
try {
    $stmt = $db->prepare("SELECT 
        b.bestillingid,
        b.dato,
        b.pris as total_pris,
        GROUP_CONCAT(CONCAT(p.navn, ' (', bd.antall, ' stk)') SEPARATOR ', ') as produkter
        FROM bestilling b
        JOIN bestillingsdetaljer bd ON b.bestillingid = bd.bestillingid
        JOIN produkt p ON bd.produktid = p.produktid
        WHERE b.brukerid = ?
        GROUP BY b.bestillingid
        ORDER BY b.dato DESC");
    // Utfør spørringen for å hente bestillingene
    $stmt->execute([$_SESSION['bruker_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Håndter feil ved henting av bestillinger
    $error = "Feil ved henting av bestillinger: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mine bestillinger - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Mine bestillinger</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                Du har ingen tidligere bestillinger. <a href="index.php">Start å handle</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ordrenr.</th>
                            <th>Dato</th>
                            <th>Produkter</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['bestillingid']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($order['dato'])); ?></td>
                            <td><?php echo htmlspecialchars($order['produkter']); ?></td>
                            <td><?php echo number_format($order['total_pris'], 2, ',', ' '); ?> kr</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Tilbake til butikken</a>
        </div>
    </div>
</body>
</html>
