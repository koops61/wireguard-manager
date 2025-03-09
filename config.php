<?php
$host = "localhost";
$dbname = "wireguard_manager";
$username = "your_mysql_user";
$password = "your_mysql_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
