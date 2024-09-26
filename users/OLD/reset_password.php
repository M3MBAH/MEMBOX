<?php
session_start();
require '../includes/config.php';
require '../includes/functions.php';

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hashed_password, 'id' => $_SESSION['reset_user_id']]);

        unset($_SESSION['reset_user_id']);
        $success = 'Changement de mot de passe réussi ! Vous pouvez maintenant vous <a href="login.php">connecter</a>.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="<?php echo $css_url; ?>/reset_password.css">
</head>
<body>
    <div class="reset-password-container">
        <h2>Réinitialiser le mot de passe</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form action="reset_password.php" method="post">
            <div class="input-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Réinitialiser</button>
        </form>
    </div>
</body>
</html>
