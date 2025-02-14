<?php
// Database konfigurasjon
$host = 'localhost';
$dbname = 'butikk';
$username = 'root';
$password = 'root';  // Standard MAMP passord, endre om ditt er annleis

try {
    // Opprettar ein ny PDO-instans for å koble til databasen
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Håndterer feil ved tilkobling
    echo "Connection failed: " . $e->getMessage();
}
?>
