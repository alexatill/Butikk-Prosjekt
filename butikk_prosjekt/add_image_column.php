<?php
require_once 'db_config.php';

try {
    // Legg til bilde-kolonne i produkt-tabellen
    $sql = "ALTER TABLE produkt ADD COLUMN bilde VARCHAR(255)";
    $db->exec($sql);
    
    echo "Bilde-kolonne ble lagt til i produkt-tabellen.";
} catch(PDOException $e) {
    echo "Feil ved oppdatering av database: " . $e->getMessage();
}
?>
