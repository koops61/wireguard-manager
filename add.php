<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $ip_address = "10.0.0." . rand(3, 250);

    $stmt = $pdo->prepare("INSERT INTO clients (name, ip_address) VALUES (?, ?)");
    $stmt->execute([$name, $ip_address]);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un Client</title>
</head>
<body>
    <h1>Ajouter un Nouveau Client WireGuard</h1>
    <form method="POST">
        <label>Nom du Client :</label>
        <input type="text" name="name" required>
        <button type="submit">Ajouter</button>
    </form>
</body>
</html>
