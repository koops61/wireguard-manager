<?php
require 'config.php';

// Récupérer la liste des clients
$stmt = $pdo->query("SELECT * FROM clients");
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>WireGuard Manager</title>
</head>
<body>
    <h1>Liste des Clients WireGuard</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Adresse IP</th>
            <th>Action</th>
        </tr>
        <?php foreach ($clients as $client): ?>
        <tr>
            <td><?= $client['id'] ?></td>
            <td><?= htmlspecialchars($client['name']) ?></td>
            <td><?= $client['ip_address'] ?></td>
            <td><a href="delete.php?id=<?= $client['id'] ?>">Supprimer</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="add.php">Ajouter un client</a>
</body>
</html>
