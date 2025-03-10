<?php
session_start();

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    die("🚨 Trop de tentatives ! Réessaye plus tard.");
}

// Charger les identifiants depuis le fichier sécurisé
$config = require '/config/config_login.php';
$valid_username = $config['username'];
$valid_password_hash = $config['password_hash'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if ($username === $valid_username && password_verify($password, $valid_password_hash)) {
        $_SESSION["loggedin"] = true;
        $_SESSION['login_attempts'] = 0; // Réinitialiser les tentatives
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['login_attempts']++;
        $error = "❌ Identifiants incorrects !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f4f4f4; padding: 20px; }
        .login-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px 0px #0000001a; display: inline-block; }
        input { padding: 10px; margin: 10px 0; width: 80%; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>🔒 Connexion</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">Se connecter</button>
    </form>
</div>

</body>
</html>
