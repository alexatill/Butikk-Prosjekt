<?php
// Starter sesjonen for å få tilgang til innloggingsinformasjon
session_start();
// Øydelegg sesjonen for å logge ut brukeren
session_destroy();
// Omleier brukeren tilbake til hovedsiden
header("Location: index.php");
exit();
?>
