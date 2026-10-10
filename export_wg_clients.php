<?php
require_once __DIR__ . '/secure.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/wg_helper.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Vérification CSRF
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Jeton CSRF invalide ou expiré.");
}

// Support export unitaire ou multiple
$selected_ids = [];
if (!empty($_POST['single_client_id'])) {
    $selected_ids = [intval($_POST['single_client_id'])];
} elseif (!empty($_POST['selected_clients']) && is_array($_POST['selected_clients'])) {
    $selected_ids = array_filter(array_map('intval', $_POST['selected_clients']));
}

if (empty($selected_ids)) {
    header("Location: index.php?error=" . urlencode("Aucun client sélectionné pour l'export."));
    exit;
}

$placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id IN ($placeholders)");
$stmt->execute($selected_ids);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($clients)) {
    header("Location: index.php?error=" . urlencode("Aucun client trouvé pour l'export."));
    exit;
}



// Cas 1 : Téléchargement d'un seul client (fichier .conf direct)
if (count($clients) === 1) {
    $client = $clients[0];
    $conf_content = generate_client_conf($client, $wg_server_public_key, $wg_server_endpoint, $wg_client_dns);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $client['name']) . ".conf";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($conf_content));
    echo $conf_content;
    exit;
}

// Cas 2 : Téléchargement multiple (archive ZIP)
if (!class_exists('ZipArchive')) {
    die("L'extension PHP ZipArchive n'est pas installée sur le serveur. Impossible de générer l'archive ZIP.");
}

$zip = new ZipArchive();
$temp_zip = tempnam(sys_get_temp_dir(), 'wg_zip_');

if ($zip->open($temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Impossible de créer l'archive temporaire ZIP.");
}

foreach ($clients as $client) {
    $conf_content = generate_client_conf($client, $wg_server_public_key, $wg_server_endpoint, $wg_client_dns);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $client['name']) . ".conf";
    $zip->addFromString($filename, $conf_content);
}

$zip->close();

$zip_download_name = "wireguard-clients-" . date("Ymd-His") . ".zip";
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_download_name . '"');
header('Content-Length: ' . filesize($temp_zip));
header('Pragma: no-cache');
header('Expires: 0');

readfile($temp_zip);
@unlink($temp_zip);
exit;
