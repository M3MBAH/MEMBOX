<?php
session_start();

// Charger la configuration et les fonctions globales de manière dynamique
require dirname(__DIR__) . '/includes/config.php';   // Détecter dynamiquement le chemin vers config.php
require dirname(__DIR__) . '/includes/functions.php'; // Détecter dynamiquement le chemin vers functions.php

// Vérification de l'inactivité pour déconnexion automatique après 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: {$config['base_url']}users/login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Si l'utilisateur est déjà connecté, redirection vers la shoutbox
if (isset($_SESSION['user_id'])) {
    header("Location: {$config['base_url']}shoutbox.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Vérification du nom d'utilisateur dans la base de données
        $stmt = $pdo->prepare('SELECT id, username, password, role, active FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['active'] == 1) {
                // Si le compte est actif, démarrer la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['LAST_ACTIVITY'] = time();
                header("Location: {$config['base_url']}shoutbox.php");
                exit();
            } else {
                $error = 'Votre compte est désactivé, veuillez contacter l\'administrateur.';
            }
        } else {
            $error = 'Nom d’utilisateur ou mot de passe incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <!-- Chemins d'accès pour les fichiers CSS communs et spécifiques -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <h2 class="glowing-btn"><span class="glowing-txt">Connexion</span></h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="input-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Se connecter</button>
        </form>

        <p class="forgot-link"><a href="<?php echo $config['base_url']; ?>users/forgot_password.php">Mot de passe oublié</a></p>
        <p class="register-link">Pas encore inscrit ? <a href="<?php echo $config['base_url']; ?>users/register.php">Créer un compte</a></p>
    </div>
</body>
</html>
