<?php
session_start();
$timeout_duration = 1800; // 30 minutes en secondes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: $base_url/users/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // Mise à jour de l'heure de la dernière activité
require '../includes/config.php';
require '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $secret_answer = strtolower(trim($_POST['secret_answer']));

    if (empty($username) || empty($secret_answer)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare('SELECT id, secret_question, secret_answer FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && $user['secret_answer'] === $secret_answer) {
            $_SESSION['reset_user_id'] = $user['id'];
            header("Location: reset_password.php");
            exit();
        } else {
            $error = 'Nom d’utilisateur ou réponse à la question secrète incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="<?php echo $css_url; ?>/forgot_password.css">
</head>
<body>
    <div class="forgot-password-container">
        <h2>Mot de passe oublié</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="forgot_password.php" method="post">
            <div class="input-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="secret_answer">Réponse à la question secrète</label>
                <input type="text" id="secret_answer" name="secret_answer" required>
            </div>
            <button type="submit">Vérifier</button>
        </form>

        <form action="<?php echo $base_url; ?>/users/login.php" method="get">
            <button type="submit">Accueil</button> <!-- Bouton pour retourner à la page de connexion -->
        </form>
    </div>
</body>
</html>
