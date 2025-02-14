<?php
// Starter sesjonen for å lagre informasjon
session_start();
require_once 'db_config.php';

// Sjekker om skjemaet for å legge til produkt er sendt inn
if (isset($_POST['submit'])) {
    try {
        // Forbereder SQL-setning for å sette inn nytt produkt i databasen
        $stmt = $db->prepare("INSERT INTO produkt (navn, antall, type, modell, farge, pris) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['navn'],
            $_POST['antall'],
            $_POST['type'],
            $_POST['modell'],
            $_POST['farge'],
            $_POST['pris']
        ]);
        
        // Hent produkt ID som nettopp ble lagt til
        $produktId = $db->lastInsertId();
        
        // Handter bildeopplasting om det er lastet opp
        if (isset($_FILES['bilde']) && $_FILES['bilde']['error'] === UPLOAD_ERR_OK) {
            $tillatte_typer = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!in_array($_FILES['bilde']['type'], $tillatte_typer)) {
                throw new Exception('Ugyldig filtype. Kun JPG, PNG og GIF er tillatt.');
            }
            
            // Lag bilde-mappe om den ikke finnes
            if (!file_exists('bilde')) {
                mkdir('bilde');
            }
            
            // Bruk produkt ID som filnavn
            $filtype = pathinfo($_FILES['bilde']['name'], PATHINFO_EXTENSION);
            $bilde_navn = $produktId . '.' . $filtype;
            
            // Flytt filen til bilde-mappen
            if (!move_uploaded_file($_FILES['bilde']['tmp_name'], 'bilde/' . $bilde_navn)) {
                throw new Exception('Kunne ikkje laste opp bildet.');
            }
        }
        
        $success = "Produkt lagt til!";
    } catch(Exception $e) {
        // Håndter feil ved lagring av produkt
        $error = "Feil ved lagring: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nb">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legg til produkt - TechHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Legg til nytt produkt</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="navn" class="form-label">Produktnavn</label>
                        <input type="text" class="form-control" id="navn" name="navn" required>
                    </div>

                    <div class="mb-3">
                        <label for="antall" class="form-label">Antall på lager</label>
                        <input type="number" class="form-control" id="antall" name="antall" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Velg type</option>
                            <option value="Prosessor">Prosessor</option>
                            <option value="Grafikkort">Grafikkort</option>
                            <option value="Hovedkort">Hovedkort</option>
                            <option value="Minne">Minne</option>
                            <option value="Lagring">Lagring</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modell" class="form-label">Modell</label>
                        <input type="text" class="form-control" id="modell" name="modell" required>
                    </div>
                    <div class="mb-3">
                        <label for="farge" class="form-label">Farge</label>
                        <input type="text" class="form-control" id="farge" name="farge" required>
                    </div>
                    <div class="mb-3">
                        <label for="pris" class="form-label">Pris</label>
                        <input type="text" class="form-control" id="pris" name="pris" required>
                    </div>
                    <div class="mb-3">
                        <label for="bilde" class="form-label">Produktbilde</label>
                        <input type="file" class="form-control" id="bilde" name="bilde" accept="image/*">
                        <small class="text-muted">Tillatte filtyper: JPG, PNG, GIF</small>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Legg til produkt</button>
                    <a href="index.php" class="btn btn-secondary">Tilbake</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
