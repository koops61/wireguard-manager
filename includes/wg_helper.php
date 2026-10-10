<?php
/**
 * Fonctions utilitaires pour WireGuard Manager
 */

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.3');
}

/**
 * Génère le contenu d'un fichier .conf pour un client WireGuard
 */
function generate_client_conf(array $client, string $server_pubkey, string $endpoint, string $dns): string {
    $name = $client['name'];
    $privkey = $client['private_key'];
    $ip = $client['ip_address'];

    $conf = "# WireGuard Client: {$name}\n";
    $conf .= "[Interface]\n";
    $conf .= "PrivateKey = {$privkey}\n";
    $conf .= "Address = {$ip}/24\n";
    if (!empty($dns)) {
        $conf .= "DNS = {$dns}\n";
    }
    $conf .= "\n";
    $conf .= "[Peer]\n";
    $conf .= "PublicKey = {$server_pubkey}\n";
    $conf .= "Endpoint = {$endpoint}\n";
    $conf .= "AllowedIPs = 0.0.0.0/0, ::/0\n";
    $conf .= "PersistentKeepalive = 25\n";

    return $conf;
}

/**
 * Récupère l'état et les statistiques en direct des clients WireGuard (Handshake, RX/TX, IP)
 */
function get_wg_peer_stats(string $interface = 'wg0'): array {
    $stats = [];
    $output = @shell_exec("sudo wg show " . escapeshellarg($interface) . " dump 2>/dev/null");
    if (empty($output)) {
        return $stats;
    }

    $lines = explode("\n", trim($output));
    if (count($lines) < 2) {
        return $stats;
    }

    // Retirer la première ligne (correspond à l'interface serveur elle-même)
    array_shift($lines);

    foreach ($lines as $line) {
        $parts = explode("\t", trim($line));
        // Format attendu par wg dump :
        // 0: public_key, 1: preshared_key, 2: endpoint, 3: allowed_ips, 4: latest_handshake, 5: rx_bytes, 6: tx_bytes, 7: persistent_keepalive
        if (count($parts) >= 7) {
            $pubkey = trim($parts[0]);
            $endpoint = $parts[2] !== '(none)' ? trim($parts[2]) : null;
            $handshake = (int)$parts[4];
            $rx = (int)$parts[5];
            $tx = (int)$parts[6];

            // Un client est considéré en ligne si son dernier handshake date de moins de 180 secondes (3 minutes)
            $is_online = ($handshake > 0 && (time() - $handshake) < 180);

            $stats[$pubkey] = [
                'endpoint'  => $endpoint,
                'handshake' => $handshake,
                'rx'        => $rx,
                'tx'        => $tx,
                'is_online' => $is_online
            ];
        }
    }

    return $stats;
}

/**
 * Formate un nombre d'octets en unité lisible (Ko, Mo, Go)
 */
function format_bytes(int $bytes, int $precision = 1): string {
    if ($bytes <= 0) return '0 o';
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    $pow = floor(log($bytes, 1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

/**
 * Formate un horodatage de dernier handshake en temps écoulé
 */
function format_last_seen(int $timestamp): string {
    if ($timestamp <= 0) return 'Jamais';
    $diff = time() - $timestamp;
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    return floor($diff / 86400) . ' j';
}
