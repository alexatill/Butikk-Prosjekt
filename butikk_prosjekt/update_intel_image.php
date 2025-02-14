<?php
require_once 'db_config.php';

try {
    $sql = "UPDATE produkt SET bilde_url = 'bilde/intel.jpg' WHERE navn LIKE '%Intel%' AND type = 'Prosessor'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    echo "Successfully updated Intel processor image";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
