<?php
// Utilitaire pour générer un hash de mot de passe sécurisé pour config/config_login.php
// Recommandé en ligne de commande : php hash_password.php "mon_mot_de_passe"

if (php_sapi_name() === 'cli') {
    $password = $argv[1] ?? '';
    if (empty($password)) {
        fwrite(STDERR, "Usage : php hash_password.php \"VotreMotDePasse\"\n");
        exit(1);
    }
    echo "Mot de passe hashé :\n" . password_hash($password, PASSWORD_BCRYPT) . "\n";
    exit(0);
}

// Interface Web simple pour génération à la volée
$generated_hash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $generated_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur de Hash - WireGuard Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-header">
        <div class="logo-badge">🔑</div>
        <h2>Générateur de Hash</h2>
        <p class="subtitle">Créez votre hash pour config/config_login.php</p>
    </div>

    <?php if ($generated_hash): ?>
        <div class="alert alert-success" style="word-break: break-all; flex-direction: column; align-items: flex-start;">
            <strong>Hash généré :</strong>
            <code style="background: #fff; padding: 6px; border-radius: 4px; display: block; margin-top: 6px; font-size: 12px;"><?= htmlspecialchars($generated_hash, ENT_QUOTES, 'UTF-8') ?></code>
        </div>
        <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Copiez ce hash dans le fichier <code>config/config_login.php</code> puis supprimez ce fichier <code>hash_password.php</code>.</p>
    <?php endif; ?>

    <form method="post" class="login-form">
        <div class="form-group">
            <label for="password">Mot de passe souhaité</label>
            <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Générer le Hash</button>
    </form>
    <div style="margin-top: 15px; text-align: center;">
        <a href="login.php" style="font-size: 13px; color: #2563eb; text-decoration: none;">← Retour à la connexion</a>
    </div>
</div>
</body>
</html>
