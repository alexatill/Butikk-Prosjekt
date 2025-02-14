<?php
// Starter sesjonen for å lagre informasjon
session_start();
require_once 'db_config.php';

// Sjekk om brukeren er logget inn
if (!isset($_SESSION['bruker_id'])) {
    header('Location: login.php');
    exit();
}

// Handterer sletting av bestilling
if (isset($_POST['slett_bestilling'])) {
    try {
        $bestilling_id = $_POST['bestilling_id'];
        
        // Start en transaksjon
        $db->beginTransaction();
        
        // Slett først alle bestillingslinjer
        $stmt = $db->prepare("DELETE FROM bestillingsdetaljer WHERE bestillingid = ?");
        $stmt->execute([$bestilling_id]);
        
        // Deretter slett selve bestillingen
        $stmt = $db->prepare("DELETE FROM bestilling WHERE bestillingid = ?");
        $stmt->execute([$bestilling_id]);
        
        // Fullfør transaksjonen
        $db->commit();
        
        $success_message = "Bestilling ble slettet";
    } catch (Exception $e) {
        // Hvis noe går galt, rull tilbake endringene
        $db->rollBack();
        $error_message = "Kunne ikke slette bestilling: " . $e->getMessage();
    }
}

try {
    // Hent alle bestillinger med brukerinformasjon og bestillingsdetaljer
    $sql = "SELECT b.bestillingid, b.dato, b.pris as totalpris, 
            br.fornavn, br.etternavn, br.`e-post`, br.telefon,
            GROUP_CONCAT(
                CONCAT(p.navn, ' (', bd.antall, ' stk)') 
                SEPARATOR ', '
            ) as produkter
            FROM bestilling b
            JOIN bruker br ON b.brukerid = br.brukerid
            JOIN bestillingsdetaljer bd ON b.bestillingid = bd.bestillingid
            JOIN produkt p ON bd.produktid = p.produktid
            GROUP BY b.bestillingid
            ORDER BY b.dato DESC";
            
    // Utfør spørringen for å hente bestillingene
    $stmt = $db->query($sql);
    $bestillinger = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Håndter feil ved henting av bestillinger
    $error_message = "Feil ved henting av bestillinger: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alle Bestillinger - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigasjon -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">TechHub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Hjem</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4">Alle Bestillinger</h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="mb-3">
            <a href="index.php" class="btn btn-primary">Tilbake til butikken</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Bestillings-ID</th>
                        <th>Dato</th>
                        <th>Kunde</th>
                        <th>E-post</th>
                        <th>Telefon</th>
                        <th>Produkter</th>
                        <th>Total Pris</th>
                        <th>Handling</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bestillinger as $bestilling): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bestilling['bestillingid']); ?></td>
                            <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($bestilling['dato']))); ?></td>
                            <td><?php echo htmlspecialchars($bestilling['fornavn'] . ' ' . $bestilling['etternavn']); ?></td>
                            <td><?php echo htmlspecialchars($bestilling['e-post']); ?></td>
                            <td><?php echo htmlspecialchars($bestilling['telefon']); ?></td>
                            <td><?php echo htmlspecialchars($bestilling['produkter']); ?></td>
                            <td><?php echo htmlspecialchars($bestilling['totalpris']); ?> kr</td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Er du sikker på at du vil slette denne bestillingen?');" class="d-inline">
                                    <input type="hidden" name="bestilling_id" value="<?php echo $bestilling['bestillingid']; ?>">
                                    <button type="submit" name="slett_bestilling" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Slett
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
