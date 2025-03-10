<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php"); // Rediriger vers la page de connexion
    exit;
}
$log_file = "/var/log/web_access.log";
$ip = $_SERVER['REMOTE_ADDR'];
$date = date("Y-m-d H:i:s");
$page = $_SERVER['REQUEST_URI'];

$log_entry = "[$date] IP: $ip - Page: $page\n";

file_put_contents($log_file, $log_entry, FILE_APPEND);
?>

