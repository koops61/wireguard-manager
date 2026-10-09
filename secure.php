<?php
if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée des cookies de session
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path'     => $cookieParams['path'],
        'domain'   => $cookieParams['domain'],
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Fonctions de protection CSRF
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Journalisation sécurisée des accès
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0750, true);
}

$log_file = $log_dir . '/web_access.log';
$ip = preg_replace('/[^0-9a-fA-F:.]/', '', $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');
$date = date("Y-m-d H:i:s");
$page = str_replace(["\r", "\n"], '', $_SERVER['REQUEST_URI'] ?? '/');

$log_entry = "[$date] IP: $ip - Page: $page\n";

// Écriture du log sans bloquer l'application en cas d'erreur de permissions
if (is_writable($log_dir) || (file_exists($log_file) && is_writable($log_file))) {
    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
