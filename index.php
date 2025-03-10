<?php
require '/config/secure.php';
require '/config/config.php';
?>
<?php
require '/config/config.php';

// Récupérer la liste des clients
$stmt = $pdo->query("SELECT * FROM clients ORDER BY id ASC");
$clients = $stmt->fetchAll();
?>

<?php if (isset($_GET['success']) && isset($_GET['name'])): ?>
    <p style="color: green; font-weight: bold;">✅ Client <?= htmlspecialchars($_GET['name']) ?> ajouté avec succès !</p>
<?php endif; ?>

<?php if (isset($_GET['deleted']) && isset($_GET['name'])): ?>
    <p style="color: red; font-weight: bold;">❌ Client <?= htmlspecialchars($_GET['name']) ?> supprimé de WireGuard.</p>
<?php endif; ?>


<div class="actions">
    <form action="generate_client.php" method="post">
        <label for="client_name">Nom du Client :</label>
        <input type="text" id="client_name" name="client_name" required>
        <button type="submit" class="add-btn">➕ Générer un Client</button>
    </form>
</div>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WireGuard Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
	<a href="logout.php">🔓 Se déconnecter</a>
</head>
<body>
    <h1>Liste des Clients WireGuard</h1>

    <form id="downloadForm" action="export_wg_clients.php" method="post">
        <table>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>ID</th>
                <th>Nom</th>
                <th>Adresse IP</th>
                <th>Clé publique</th>
                <th>Date d'ajout</th>
                <th>Action</th>
            </tr>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><input type="checkbox" name="selected_clients[]" value="<?= $client['id'] ?>"></td>
                <td><?= $client['id'] ?></td>
                <td><?= htmlspecialchars($client['name']) ?></td>
                <td><?= $client['ip_address'] ?></td>
                <td class="public-key"><span class="key-icon">🔑</span> <?= $client['public_key'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($client['created_at'])) ?></td>
                <td><a href="delete.php?id=<?= $client['id'] ?>" class="delete-btn" onclick="return confirm('Voulez-vous vraiment supprimer ce client ?')">❌ Supprimer</a></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="actions">
            <button type="submit" class="export-btn">📥 Télécharger les fichiers WireGuard</button>
        </div>
    </form>

    <script>
    document.getElementById("selectAll").addEventListener("click", function() {
        var checkboxes = document.querySelectorAll("input[name='selected_clients[]']");
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });
    </script>
</body>
</html>
