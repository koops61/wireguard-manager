<?php
require '/config/secure.php';
require '/config/config.php';
?>
<?php
require '/config/config.php';

if (!isset($_GET['id'])) {
    die("❌ Erreur : Aucun client sélectionné !");
}

$client_id = intval($_GET['id']); // Sécurisation de l'entrée

// Récupérer les informations du client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    die("❌ Erreur : Client introuvable !");
}

$public_key = $client['public_key'];
$client_name = $client['name'];

// Supprimer le client de la base de données
$stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
$stmt->execute([$client_id]);

// Charger le fichier wg0.conf
$wg_config_path = "/etc/wireguard/wg0.conf";
$wg_config = file_get_contents($wg_config_path);

if ($wg_config === false) {
    die("❌ Erreur : Impossible de lire wg0.conf !");
}

// Construire le modèle à supprimer avec `PersistentKeepalive = 25`
$pattern = "/\n# Client: " . preg_quote($client_name, '/') . "\n\[Peer\]\nPublicKey = " . preg_quote($public_key, '/') . "[^\n]*\nAllowedIPs = [^\n]*\n(?:PersistentKeepalive = 25\n)?/m";

// Appliquer la suppression du bloc du client
$wg_config = preg_replace($pattern, "", $wg_config);

// Supprimer les lignes vides en trop
$wg_config = preg_replace("/\n{2,}/", "\n\n", trim($wg_config)) . "\n";

// Réécrire le fichier wg0.conf sans le client supprimé
if (!file_put_contents($wg_config_path, $wg_config)) {
    die("❌ Erreur : Impossible de mettre à jour wg0.conf !");
}

// Appliquer la nouvelle configuration WireGuard
shell_exec("sudo systemctl restart wg-quick@wg0");

// Rediriger vers l'accueil avec un message de confirmation
header("Location: index.php?deleted=1&name=" . urlencode($client_name));
exit;
?>
