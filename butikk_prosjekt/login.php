<?php
// Starter sesjonen for å lagre innloggingsinformasjon
session_start();
require_once 'db_config.php';

// Sjekker om innloggingsskjemaet er sendt inn
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Hent brukeren basert på e-post
        $stmt = $db->prepare("SELECT * FROM bruker WHERE `e-post` = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifiserer passordet
        if ($user && password_verify($password, $user['passord'])) {
            // Lagre bruker-ID og fornavn i sesjonen
            $_SESSION['bruker_id'] = $user['brukerid'];
            $_SESSION['fornavn'] = $user['fornavn'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Feil e-post eller passord";
        }
    } catch(PDOException $e) {
        $error = "Feil ved innlogging: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Logg inn</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-post</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Passord</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="login" class="btn btn-primary">Logg inn</button>
                                <a href="registrer.php" class="btn btn-link">Ikkje registrert? Opprett konto</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
