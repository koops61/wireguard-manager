<?php
require '/config/secure.php';
require '/config/config.php';
?>
<?php
require '/config/config.php';

// Vérifier si un nom a été fourni
if (!isset($_POST['client_name']) || empty($_POST['client_name'])) {
    die("❌ Erreur : Aucun nom de client fourni !");
}

$client_name = htmlspecialchars($_POST['client_name']); // Sécurisation contre XSS

// Générer une paire de clés WireGuard
$private_key = trim(shell_exec("wg genkey"));
$public_key = trim(shell_exec("echo $private_key | wg pubkey"));

// Vérifier si les clés sont bien générées
if (empty($private_key) || empty($public_key)) {
    die("❌ Erreur : Échec de la génération des clés WireGuard !");
}

// Vérifier si wg0.conf est accessible en écriture
$wg_config_path = "/etc/wireguard/wg0.conf";
if (!is_writable($wg_config_path)) {
    die("❌ Erreur : Impossible d'écrire dans wg0.conf, vérifiez les permissions !");
}

// Générer une adresse IP unique pour le client
$stmt = $pdo->query("SELECT MAX(id) AS max_id FROM clients");
$row = $stmt->fetch();
$new_id = ($row['max_id'] ?? 1) + 1;  // Assurer un ID unique
$ip_address = "10.0.0." . (2 + $new_id);

// Insérer le client dans la base de données via PDO
$stmt = $pdo->prepare("INSERT INTO clients (name, ip_address, private_key, public_key) VALUES (?, ?, ?, ?)");
$stmt->execute([$client_name, $ip_address, $private_key, $public_key]);

// Ajouter le client dans wg0.conf avec file_put_contents()
$wg_config = "\n# Client: $client_name\n";
$wg_config .= "[Peer]\n";
$wg_config .= "PublicKey = $public_key\n";
$wg_config .= "AllowedIPs = $ip_address/32\n";
$wg_config .= "PersistentKeepalive = 25\n";

// Écrire directement dans wg0.conf
if (!file_put_contents($wg_config_path, $wg_config, FILE_APPEND)) {
    die("❌ Erreur : Impossible d'écrire dans wg0.conf !");
}

// Appliquer la configuration WireGuard après mise à jour
shell_exec("sudo systemctl restart wg-quick@wg0");

// Vérifier que le client a bien été ajouté dans wg0.conf
$wg_status = shell_exec("sudo wg show");
if (strpos($wg_status, $public_key) === false) {
    die("⚠️ Avertissement : Le client a été ajouté dans wg0.conf mais WireGuard ne l'a pas appliqué !");
}

// Rediriger vers l'accueil avec un message de succès
header("Location: index.php?success=1&name=" . urlencode($client_name));
exit;
?>
