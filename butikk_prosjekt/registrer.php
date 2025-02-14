<?php
session_start();
require_once 'db_config.php';

if (isset($_POST['register'])) {
    try {
        // Sjekk om e-posten allerede er registrert
        $stmt = $db->prepare("SELECT * FROM bruker WHERE `e-post` = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->rowCount() > 0) {
            $error = "Denne e-postadressen er allerede registrert";
        } else {
            // Hash passordet
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Sett inn ny bruker
            $stmt = $db->prepare("INSERT INTO bruker (fornavn, etternavn, `e-post`, telefon, adresse, postnummer, poststad, passord) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['fornavn'],
                $_POST['etternavn'],
                $_POST['email'],
                $_POST['telefon'],
                $_POST['adresse'],
                $_POST['postnummer'],
                $_POST['poststed'],
                $hashed_password
            ]);
            
            $success = "Konto opprettet! Du kan nå logge inn.";
        }
    } catch(PDOException $e) {
        $error = "Feil ved registrering: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrer - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Registrer ny konto</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <a href="login.php">Gå til innlogging</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fornavn" class="form-label">Fornavn</label>
                                    <input type="text" class="form-control" id="fornavn" name="fornavn" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="etternavn" class="form-label">Etternavn</label>
                                    <input type="text" class="form-control" id="etternavn" name="etternavn" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-post</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="telefon" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="telefon" name="telefon" required>
                            </div>

                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" required>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="postnummer" class="form-label">Postnummer</label>
                                    <input type="text" class="form-control" id="postnummer" name="postnummer" required>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="poststed" class="form-label">Poststed</label>
                                    <input type="text" class="form-control" id="poststed" name="poststed" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Passord</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="register" class="btn btn-primary">Registrer</button>
                                <a href="login.php" class="btn btn-link">Har du allerede en konto? Logg inn</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
