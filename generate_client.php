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

// Vérifier et assainir le nom du client
$raw_name = trim($_POST['client_name'] ?? '');

// Validation stricte : caractères alphanumériques, tirets et underscores uniquement (max 32 car.)
if (!preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $raw_name)) {
    header("Location: index.php?error=" . urlencode("Le nom du client est invalide. Utilisez uniquement des lettres, chiffres, tirets et underscores (max 32 caractères)."));
    exit;
}
$client_name = $raw_name;

// Vérifier si le nom existe déjà
$stmt = $pdo->prepare("SELECT id FROM clients WHERE name = ?");
$stmt->execute([$client_name]);
if ($stmt->fetch()) {
    header("Location: index.php?error=" . urlencode("Un client nommé '{$client_name}' existe déjà."));
    exit;
}

// Générer une paire de clés WireGuard
$private_key = trim(shell_exec("wg genkey 2>/dev/null") ?? "");
$public_key = "";
if (!empty($private_key)) {
    $cmd = "echo " . escapeshellarg($private_key) . " | wg pubkey 2>/dev/null";
    $public_key = trim(shell_exec($cmd) ?? "");
}

// En environnement de test ou si wg n'est pas dans le PATH
if (empty($private_key) || empty($public_key)) {
    if (PHP_OS_FAMILY === 'Windows') {
        // Fallback simulation pour environnement de développement local Windows
        $private_key = base64_encode(random_bytes(32));
        $public_key = base64_encode(random_bytes(32));
    } else {
        header("Location: index.php?error=" . urlencode("Échec de la génération des clés : l'outil 'wg' est introuvable ou inaccessible sur le serveur."));
        exit;
    }
}

// Attribution intelligente et automatique d'une adresse IP disponible
$prefix = $wg_network_prefix ?? '10.0.0.';
$stmt = $pdo->query("SELECT ip_address FROM clients");
$used_ips = $stmt->fetchAll(PDO::FETCH_COLUMN);

$assigned_ip = null;
for ($i = 2; $i <= 254; $i++) {
    $candidate_ip = $prefix . $i;
    if (!in_array($candidate_ip, $used_ips, true)) {
        $assigned_ip = $candidate_ip;
        break;
    }
}

if (!$assigned_ip) {
    header("Location: index.php?error=" . urlencode("Sous-réseau saturé : aucune adresse IP disponible (limite de 253 clients atteinte)."));
    exit;
}

// Insérer le client dans la base de données
$stmt = $pdo->prepare("INSERT INTO clients (name, ip_address, private_key, public_key) VALUES (?, ?, ?, ?)");
$stmt->execute([$client_name, $assigned_ip, $private_key, $public_key]);

// Mettre à jour wg0.conf si présent
if (file_exists($wg_config_path)) {
    if (!is_writable($wg_config_path)) {
        header("Location: index.php?error=" . urlencode("Client enregistré en BDD, mais {$wg_config_path} n'est pas accessible en écriture pour le serveur web."));
        exit;
    }

    $wg_block = "\n# Client: {$client_name}\n"
              . "[Peer]\n"
              . "PublicKey = {$public_key}\n"
              . "AllowedIPs = {$assigned_ip}/32\n"
              . "PersistentKeepalive = 25\n";

    file_put_contents($wg_config_path, $wg_block, FILE_APPEND | LOCK_EX);

    // Appliquer dynamiquement à chaud sans redémarrer le service (évite de couper les autres utilisateurs)
    $iface = escapeshellarg($wg_interface);
    $pub = escapeshellarg($public_key);
    $ip = escapeshellarg($assigned_ip . "/32");
    @shell_exec("sudo wg set {$iface} peer {$pub} allowed-ips {$ip} 2>/dev/null");
}

header("Location: index.php?success=1&name=" . urlencode($client_name));
exit;
