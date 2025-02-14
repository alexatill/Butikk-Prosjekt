<?php
require_once 'db_config.php';

try {
    $sql = "ALTER TABLE bruker ADD COLUMN passord VARCHAR(255) NOT NULL AFTER poststad";
    $db->exec($sql);
    echo "Successfully added passord column";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
