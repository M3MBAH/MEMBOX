<?php
session_start();
require '../includes/config.php';
require '../includes/functions.php';

$error = '';
$success = '';

// Vérifier si les inscriptions sont ouvertes
if (!are_registrations_open()) {
    // Si les inscriptions sont fermées, afficher le message d'avertissement
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inscriptions fermées</title>
        <link rel="stylesheet" href="<?php echo $css_url; ?>/register.css">
    </head>
    <body>
        <div class="register-container">
            <h2 class="closed-message">Inscriptions fermées jusqu'à nouvel ordre</h2>
            <a href="login.php" class="login-button">Retour à la connexion</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $secret_question = trim($_POST['secret_question']);
    $secret_answer = strtolower(trim($_POST['secret_answer']));

    if (empty($username) || empty($email) || empty($password) || empty($secret_question) || empty($secret_answer)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Vérifiez si l'email est déjà utilisé
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insérer l'utilisateur dans la base de données
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, secret_question, secret_answer) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$username, $email, $hashed_password, $secret_question, $secret_answer]);

            $success = 'Inscription réussie ! Vous pouvez maintenant vous <a href="login.php">connecter</a>.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="<?php echo $css_url; ?>/register.css">
</head>
<body>
    <div class="register-container">
        <h2>Inscription</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form action="register.php" method="post">
            <div class="input-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="secret_question">Choisissez une question secrète</label>
                <select id="secret_question" name="secret_question" required>
                    <option value="">-- Sélectionnez une question --</option>
                    <!-- Liste des questions secrètes -->
                    <option value="ami">Comment s'appelait votre meilleur ami lorsque vous étiez adolescent ?</option>
                    <option value="animal">Comment s'appelait votre premier animal de compagnie ?</option>
                    <option value="plat">Quel est le premier plat que vous avez appris à cuisiner ?</option>
                    <option value="film">Quel est le premier film que vous avez vu au cinéma ?</option>
                    <option value="avion">Où êtes-vous allé la première fois que vous avez pris l'avion ?</option>
                    <option value="instituteur">Comment s'appelait votre instituteur préféré à l'école primaire ?</option>
                    <option value="metier">Quel serait selon vous le métier idéal ?</option>
                    <option value="livre">Quel est le livre pour enfants que vous préférez ?</option>
                    <option value="vehicule">Quel était le modèle de votre premier véhicule ?</option>
                    <option value="surnom">Quel était votre surnom lorsque vous étiez enfant ?</option>
                    <option value="acteur">Quel était votre personnage ou acteur de cinéma préféré lorsque vous étiez étudiant ?</option>
                    <option value="chanteur">Quel était votre chanteur ou groupe préféré lorsque vous étiez étudiant ?</option>
                    <option value="ville">Dans quelle ville vos parents se sont-ils rencontrés ?</option>
                    <option value="patron">Comment s'appelait votre premier patron ?</option>
                    <option value="rue">Quel est le nom de la rue où vous avez grandi ?</option>
                    <option value="plage">Quel est le nom de la première plage où vous vous êtes baigné ?</option>
                    <option value="album">Quel est le premier album que vous avez acheté ?</option>
                    <option value="equipe">Quel est le nom de votre équipe de sport préférée ?</option>
                    <option value="grand-pere">Quel était le métier de votre grand-père ?</option>
                </select>
            </div>
            <p class="note">Veuillez saisir toute la réponse en minuscule pour y répondre plus tard sans erreur...</p>
            <div class="input-group">
                <label for="secret_answer">Réponse à la question secrète</label>
                <input type="text" id="secret_answer" name="secret_answer" required>
            </div>
            <button type="submit">S'inscrire</button>
        </form>
        <p>Déjà inscrit ? <a href="login.php">Connectez-vous</a></p>
    </div>
</body>
</html>
