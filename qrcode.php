<?php
require_once __DIR__ . '/secure.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/wg_helper.php';

$client_id = intval($_GET['id'] ?? 0);
$token = $_GET['csrf_token'] ?? '';

if (!verify_csrf_token($token)) {
    http_response_code(403);
    die("Jeton CSRF invalide ou session expirée.");
}

if ($client_id <= 0) {
    http_response_code(400);
    die("Identifiant client invalide.");
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    http_response_code(404);
    die("Client introuvable.");
}

// Générer le contenu de la configuration WireGuard
$conf_content = generate_client_conf($client, $wg_server_public_key, $wg_server_endpoint, $wg_client_dns);

// Si le format demandé est le texte brut
if (isset($_GET['format']) && $_GET['format'] === 'raw') {
    header('Content-Type: text/plain; charset=utf-8');
    echo $conf_content;
    exit;
}

// Génération du QR Code au format SVG via qrencode
$cmd = "echo " . escapeshellarg($conf_content) . " | qrencode -t svg -s 6 -m 2 2>/dev/null";
$svg = @shell_exec($cmd);

if (!empty($svg) && strpos($svg, '<svg') !== false) {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: private, max-age=3600');
    echo $svg;
    exit;
}

// Fallback visuel si qrencode n'est pas installé sur le système
header('Content-Type: image/svg+xml');
echo '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200">
    <rect width="100%" height="100%" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" rx="12"/>
    <text x="50%" y="35%" text-anchor="middle" fill="#0f172a" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="bold">📱 qrencode requis</text>
    <text x="50%" y="55%" text-anchor="middle" fill="#64748b" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="12">Pour afficher le QR code directement,</text>
    <text x="50%" y="70%" text-anchor="middle" fill="#64748b" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="12">installez le paquet sur le Raspberry Pi :</text>
    <rect x="25" y="145" width="250" height="32" fill="#e2e8f0" rx="6"/>
    <text x="50%" y="166" text-anchor="middle" fill="#1e293b" font-family="monospace" font-size="11" font-weight="bold">sudo apt install qrencode -y</text>
</svg>';
exit;
