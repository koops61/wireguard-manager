<?php
session_start();
session_destroy(); // Supprimer toutes les sessions
header("Location: login.php");
exit;
?>
