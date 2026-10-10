<?php
if (session_status() === PHP_SESSION_NONE) {
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

// Rediriger si déjà connecté
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: index.php");
    exit;
}

// Initialisation des tentatives
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lockout_time'])) {
    $_SESSION['lockout_time'] = 0;
}

// Vérifier si le compte est temporairement bloqué
$remaining_lockout = $_SESSION['lockout_time'] - time();
if ($remaining_lockout > 0) {
    $lockout_msg = "🚨 Trop de tentatives. Veuillez patienter " . ceil($remaining_lockout / 60) . " minute(s).";
} else {
    $lockout_msg = null;
}

// Charger les identifiants
$config_file = __DIR__ . '/config/config_login.php';
if (!file_exists($config_file)) {
    die("Fichier de configuration de connexion manquant (config/config_login.php).");
}
$config = require $config_file;
$valid_username = $config['username'] ?? '';
$valid_password_hash = $config['password_hash'] ?? '';

// Initialiser le token CSRF pour le formulaire de connexion
if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted_token = $_POST["csrf_token"] ?? '';
    if (!hash_equals($_SESSION['login_csrf_token'], $submitted_token)) {
        $error = "❌ Session invalide ou expirée, veuillez réactualiser.";
    } elseif ($lockout_msg) {
        $error = $lockout_msg;
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($username === $valid_username && password_verify($password, $valid_password_hash)) {
            // Regénération de l'ID de session pour prévenir la fixation de session
            session_regenerate_id(true);
            $_SESSION["loggedin"] = true;
            $_SESSION['login_attempts'] = 0;
            $_SESSION['lockout_time'] = 0;
            unset($_SESSION['login_csrf_token']);
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + 300; // Blocage de 5 minutes
                $error = "🚨 Trop de tentatives échouées. Compte bloqué pendant 5 minutes.";
            } else {
                $remaining = 5 - $_SESSION['login_attempts'];
                $error = "❌ Identifiants incorrects ! ($remaining tentative(s) restante(s))";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - WireGuard Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-header">
        <div class="logo-badge">🛡️</div>
        <h2>WireGuard Manager</h2>
        <p class="subtitle">Espace d'administration sécurisé · v1.3</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif ($lockout_msg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($lockout_msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        
        <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" placeholder="admin" required autofocus autocomplete="username" <?= $lockout_msg ? 'disabled' : '' ?>>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" placeholder="••••••••••••" required autocomplete="current-password" <?= $lockout_msg ? 'disabled' : '' ?>>
        </div>

        <button type="submit" class="btn btn-primary btn-block" <?= $lockout_msg ? 'disabled' : '' ?>>
            🔐 Se connecter
        </button>
    </form>
</div>

</body>
</html>
