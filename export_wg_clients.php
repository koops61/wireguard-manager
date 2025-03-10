<?php
require '/config/secure.php';
require '/config/config.php';
?>
<?php
require '/config/config.php';

if (!isset($_POST['selected_clients'])) {
    die("Aucun client sélectionné !");
}

// Paramètres généraux
$endpoint = "domain.com:port-wireguard";
$allowed_ips = "0.0.0.0/0, ::/0";

// Récupérer les IDs sélectionnés
$selected_ids = implode(",", array_map('intval', $_POST['selected_clients']));

// Récupérer les informations des clients sélectionnés
$stmt = $pdo->query("SELECT * FROM clients WHERE id IN ($selected_ids)");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($clients)) {
    die("Aucune donnée à exporter.");
}

// Génération des fichiers ZIP
$zip = new ZipArchive();
$zip_filename = "wg-clients.zip";

if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
    die("Impossible de créer l'archive ZIP.");
}

foreach ($clients as $client) {
    $wg_config = "# Client: " . htmlspecialchars($client['name']) . "\n";
    $wg_config .= "[Interface]\n";
    $wg_config .= "PrivateKey = PLACEHOLDER_PRIVATE_KEY\n"; // Remplacer manuellement
    $wg_config .= "Address = " . $client['ip_address'] . "/24\n\n";
    $wg_config .= "# Clé publique de " . htmlspecialchars($client['name']) . "\n";
    $wg_config .= "[Peer]\n";
    $wg_config .= "PublicKey = " . $client['public_key'] . "\n";
    $wg_config .= "AllowedIPs = $allowed_ips\n";
    $wg_config .= "Endpoint = $endpoint\n";
    $wg_config .= "PersistentKeepalive = 25\n";

    // Créer un fichier temporaire
    $filename = "wg-client-" . $client['id'] . ".conf";
    $zip->addFromString($filename, $wg_config);
}

// Fermer l'archive ZIP
$zip->close();

// Télécharger l'archive ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
header('Content-Length: ' . filesize($zip_filename));
readfile($zip_filename);

// Supprimer le fichier ZIP temporaire après téléchargement
unlink($zip_filename);
exit;
?>
