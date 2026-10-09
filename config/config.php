<?php
// Configuration de la connexion à la base de données
$db_host = 'localhost';
$db_name = 'wireguard_manager';
$db_user = 'root';
$db_pass = 'password';

// Paramètres WireGuard du serveur
$wg_config_path = '/etc/wireguard/wg0.conf';
$wg_interface   = 'wg0';

// Clé publique du serveur WireGuard (nécessaire pour la configuration des clients)
// Si le fichier /etc/wireguard/publickey existe et est lisible, on le lit automatiquement.
$wg_server_public_key = '';
if (file_exists('/etc/wireguard/publickey') && is_readable('/etc/wireguard/publickey')) {
    $wg_server_public_key = trim(file_get_contents('/etc/wireguard/publickey'));
}
if (empty($wg_server_public_key)) {
    // Clé publique de secours si le fichier n'est pas accessible
    $wg_server_public_key = 'METTRE_ICI_LA_CLE_PUBLIQUE_DU_SERVEUR';
}

// Endpoint externe du serveur WireGuard (IP publique ou nom de domaine + port)
$wg_server_endpoint = 'ton-domaine-ou-ip.com:51820';

// Serveurs DNS attribués aux clients VPN
$wg_client_dns = '1.1.1.1, 8.8.8.8';

// Sous-réseau des clients
$wg_network_prefix = '10.0.0.'; // IPs attribuées: 10.0.0.2 à 10.0.0.254

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Erreur de connexion BDD : " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez vérifier la configuration dans config/config.php.");
}
