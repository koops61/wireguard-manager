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

$public_key = trim($client['public_key']);
$client_name = $client['name'];

// 1. Vérifier l'accès en écriture à wg0.conf avant toute suppression
if (file_exists($wg_config_path) && !is_writable($wg_config_path)) {
    header("Location: index.php?error=" . urlencode("Erreur : Le fichier {$wg_config_path} n'est pas modifiable par le serveur web (permissions insuffisantes pour www-data)."));
    exit;
}

// 2. Retirer le pair en direct de WireGuard à chaud (sans coupure pour les autres)
if (!empty($public_key)) {
    $iface = escapeshellarg($wg_interface);
    $pub = escapeshellarg($public_key);
    @shell_exec("sudo wg set {$iface} peer {$pub} remove 2>/dev/null");
}

// 3. Fonction robuste de suppression d'un bloc [Peer] dans wg0.conf
function remove_peer_block(string $content, string $target_pubkey): string {
    $target_pubkey = trim($target_pubkey);
    if (empty($target_pubkey)) {
        return $content;
    }

    $lines = preg_split('/\r\n|\r|\n/', $content);
    $output = [];
    $current_section = null;
    $current_section_lines = [];
    $pending_comments = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (preg_match('/^\[(Interface|Peer)\]/i', $trimmed, $m)) {
            if ($current_section !== null) {
                $sec_text = implode("\n", $current_section_lines);
                // Si ce n'est pas la section du peer cible, on la conserve
                if ($current_section !== 'peer' || strpos($sec_text, $target_pubkey) === false) {
                    $output = array_merge($output, $current_section_lines);
                }
            }
            $current_section = strtolower($m[1]);
            $current_section_lines = array_merge($pending_comments, [$line]);
            $pending_comments = [];
            continue;
        }

        if (strpos($trimmed, '#') === 0) {
            $pending_comments[] = $line;
        } elseif ($trimmed === '') {
            if ($current_section !== null) {
                $current_section_lines[] = $line;
            } else {
                $output[] = $line;
            }
        } else {
            if (!empty($pending_comments)) {
                $current_section_lines = array_merge($current_section_lines, $pending_comments);
                $pending_comments = [];
            }
            $current_section_lines[] = $line;
        }
    }

    // Traiter la dernière section
    if ($current_section !== null) {
        $sec_text = implode("\n", $current_section_lines);
        if ($current_section !== 'peer' || strpos($sec_text, $target_pubkey) === false) {
            $output = array_merge($output, $current_section_lines);
        }
    }

    if (!empty($pending_comments)) {
        $output = array_merge($output, $pending_comments);
    }

    $result = implode("\n", $output);
    return preg_replace("/\n{3,}/", "\n\n", trim($result)) . "\n";
}

// 4. Mettre à jour le fichier wg0.conf sur le disque
if (file_exists($wg_config_path) && is_readable($wg_config_path) && is_writable($wg_config_path)) {
    $wg_config = file_get_contents($wg_config_path);
    if ($wg_config !== false) {
        $cleaned_config = remove_peer_block($wg_config, $public_key);
        file_put_contents($wg_config_path, $cleaned_config, LOCK_EX);
    }
}

// 5. Supprimer le client de la base de données
$stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
$stmt->execute([$client_id]);

header("Location: index.php?deleted=1&name=" . urlencode($client_name));
exit;
