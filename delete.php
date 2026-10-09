<?php
require_once __DIR__ . '/secure.php';
require_once __DIR__ . '/config/config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Vérification CSRF
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: index.php?error=" . urlencode("Jeton CSRF invalide ou session expirée."));
    exit;
}

$client_id = intval($_POST['id'] ?? 0);
if ($client_id <= 0) {
    header("Location: index.php?error=" . urlencode("Identifiant de client invalide."));
    exit;
}

// Récupérer les informations du client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    header("Location: index.php?error=" . urlencode("Client introuvable dans la base de données."));
    exit;
}

$public_key = $client['public_key'];
$client_name = $client['name'];

// 1. Retirer le pair en direct de WireGuard à chaud (sans coupure de service pour les autres)
if (!empty($public_key)) {
    $iface = escapeshellarg($wg_interface);
    $pub = escapeshellarg($public_key);
    @shell_exec("sudo wg set {$iface} peer {$pub} remove 2>/dev/null");
}

// 2. Mettre à jour le fichier wg0.conf
if (file_exists($wg_config_path) && is_readable($wg_config_path) && is_writable($wg_config_path)) {
    $wg_config = file_get_contents($wg_config_path);
    if ($wg_config !== false) {
        // Ciblage robuste du bloc [Peer] par sa clé publique
        $escaped_key = preg_quote($public_key, '/');
        $pattern = '/(?:#\s*Client:[^\n]*\r?\n)?\[Peer\]\r?\nPublicKey\s*=\s*' . $escaped_key . '\b[^\n]*\r?\nAllowedIPs\s*=[^\n]*\r?\n(?:PersistentKeepalive\s*=[^\n]*\r?\n)?/i';
        $cleaned_config = preg_replace($pattern, '', $wg_config);
        $cleaned_config = preg_replace("/(\r?\n){3,}/", "\n\n", trim($cleaned_config)) . "\n";

        file_put_contents($wg_config_path, $cleaned_config, LOCK_EX);
    }
}

// 3. Supprimer le client de la base de données
$stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
$stmt->execute([$client_id]);

header("Location: index.php?deleted=1&name=" . urlencode($client_name));
exit;
