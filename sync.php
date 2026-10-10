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

if (!file_exists($wg_config_path)) {
    header("Location: index.php?error=" . urlencode("Fichier de configuration {$wg_config_path} introuvable."));
    exit;
}

if (!is_readable($wg_config_path)) {
    header("Location: index.php?error=" . urlencode("Impossible de lire {$wg_config_path} (droits insuffisants pour www-data)."));
    exit;
}

$wg_content = file_get_contents($wg_config_path);
if ($wg_content === false) {
    header("Location: index.php?error=" . urlencode("Erreur lors de la lecture de {$wg_config_path}."));
    exit;
}

// Récupérer les clés publiques déjà présentes en base de données
$stmt = $pdo->query("SELECT public_key FROM clients");
$existing_keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Parser les blocs [Peer] de wg0.conf
$lines = preg_split('/\r\n|\r|\n/', $wg_content);
$peers = [];
$current_peer = null;
$pending_name = null;

foreach ($lines as $line) {
    $trimmed = trim($line);

    // Détection d'un commentaire avec nom de client (# Client: nom ou # nom)
    if (preg_match('/^#\s*(?:Client:\s*)?([a-zA-Z0-9_-]+)/i', $trimmed, $matches)) {
        $pending_name = $matches[1];
        continue;
    }

    if (preg_match('/^\[Peer\]/i', $trimmed)) {
        if ($current_peer !== null && !empty($current_peer['public_key'])) {
            $peers[] = $current_peer;
        }
        $current_peer = [
            'name'       => $pending_name ?? '',
            'public_key' => '',
            'ip_address' => ''
        ];
        $pending_name = null;
        continue;
    }

    if ($current_peer !== null) {
        if (preg_match('/^PublicKey\s*=\s*(.+)$/i', $trimmed, $matches)) {
            $current_peer['public_key'] = trim($matches[1]);
        } elseif (preg_match('/^AllowedIPs\s*=\s*([0-9.]+)(?:\/[0-9]+)?/i', $trimmed, $matches)) {
            $current_peer['ip_address'] = trim($matches[1]);
        }
    }
}

if ($current_peer !== null && !empty($current_peer['public_key'])) {
    $peers[] = $current_peer;
}

$imported_count = 0;
$stmt_insert = $pdo->prepare("INSERT INTO clients (name, ip_address, private_key, public_key) VALUES (?, ?, ?, ?)");

foreach ($peers as $peer) {
    $pubkey = $peer['public_key'];
    $ip = $peer['ip_address'];

    // Si le client n'est pas déjà dans la base
    if (!in_array($pubkey, $existing_keys, true)) {
        $name = !empty($peer['name']) ? $peer['name'] : 'client-' . str_replace('.', '-', $ip);
        
        // Assurer l'unicité du nom en BDD
        $check_name = $pdo->prepare("SELECT id FROM clients WHERE name = ?");
        $check_name->execute([$name]);
        if ($check_name->fetch()) {
            $name .= '-' . substr(md5($pubkey), 0, 4);
        }

        // Remarque : Le fichier serveur wg0.conf ne contient jamais la clé privée du client.
        $private_key = 'INCONNUE_IMPORTEE';

        $stmt_insert->execute([$name, $ip, $private_key, $pubkey]);
        $existing_keys[] = $pubkey;
        $imported_count++;
    }
}

if ($imported_count > 0) {
    $msg = "✅ Synchronisation terminée : {$imported_count} client(s) importé(s) depuis wg0.conf !";
} else {
    $msg = "ℹ️ Tous les clients de wg0.conf sont déjà présents dans la base de données.";
}

header("Location: index.php?sync_info=" . urlencode($msg));
exit;
