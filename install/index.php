<?php
$installation_successful = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $admin_username = $_POST['admin_username'];
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];

    try {
        // Activer les rapports d'erreurs pour PDO
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        // Connexion à la base de données
        $dsn = "mysql:host=$db_host;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Création de la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        // Création de la table des utilisateurs et des rôles
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
            active TINYINT(1) DEFAULT 1,
            secret_question VARCHAR(255),
            secret_answer VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Création de la table des messages
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Création de la table des emojis
        $pdo->exec("CREATE TABLE IF NOT EXISTS emojis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emoji_name VARCHAR(100) NOT NULL,
            emoji_url VARCHAR(255) NOT NULL
        )");

        // Création de la table des activités des utilisateurs
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type VARCHAR(50),
            activity_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $admin_username, ':email' => $admin_email]);
        $user_exists = $stmt->fetchColumn();

        if ($user_exists) {
            throw new Exception("L'utilisateur ou l'email existe déjà. Veuillez choisir un autre nom d'utilisateur ou email.");
        }

        // Insertion du compte administrateur
        $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, active) VALUES (:username, :email, :password, 'admin', 1)");
        $stmt->execute([
            ':username' => $admin_username,
            ':email' => $admin_email,
            ':password' => $hashed_password
        ]);

        // Détection automatique de la base URL en remontant d'un niveau pour sortir du répertoire 'install'
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $script_path = dirname(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME'])); // remonter au parent
        $base_url = $protocol . $host . $script_path . '/';

        // Création du fichier config.php dans le dossier includes
        $config_content = "<?php
        // Charger les informations de connexion à la base de données depuis db.php
        \$config = include(dirname(__DIR__) . '/includes/db.php');
        
        // Définir l'URL de base de l'application
        \$base_url = '$base_url';

        return [
            'db_host' => \$config['db_host'],
            'db_name' => \$config['db_name'],
            'db_user' => \$config['db_user'],
            'db_pass' => \$config['db_pass'],
            'base_url' => \$base_url,
        ];
        ";
        file_put_contents('../includes/config.php', $config_content);

        // Création du fichier db.php dans le dossier includes
        $db_content = "<?php
        return [
            'db_host' => '$db_host',
            'db_name' => '$db_name',
            'db_user' => '$db_user',
            'db_pass' => '$db_pass',
        ];
        ";
        file_put_contents('../includes/db.php', $db_content);

        $installation_successful = true;

    } catch (PDOException $e) {
        $error_message = "Erreur lors de l'installation (PDO) : " . $e->getMessage();
    } catch (Exception $e) {
        $error_message = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEMBOX | Installation de la Shoutbox</title> <!-- Modification du nom de la page -->
    <!-- Charger correctement les fichiers CSS -->
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/install.css">
</head>
<body>
    <div class="install-container">
        <h2>Installation de la Shoutbox</h2>

        <!-- Affichage du message d'erreur en rouge -->
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <?php if ($installation_successful): ?>
            <p class="success">
                Installation réussie ! Vous pouvez maintenant 
                <a href="<?php echo $base_url; ?>users/login.php" class="connect-link">vous connecter</a>.
            </p>
        <?php endif; ?>

        <form action="index.php" method="post">
            <!-- Introduction sur l'interface d'installation -->
            <p class="install-intro">Vous êtes sur l'interface d'installation de la MEMBOX, veuillez renseigner correctement les informations ci-dessous afin que l'installation se fasse sans encombre.</p>
            <p class="install-note">* Pensez à adapter le chemin (Adresse) de la BDD suivant si elle est locale ou en ligne</p>

            <!-- Champs du formulaire pour la base de données -->
            <div class="input-group">
                <label for="db_host">* Chemin (Adresse) de la base de données</label>
                <input type="text" id="db_host" name="db_host" required>
            </div>
            <div class="input-group">
                <label for="db_name">Indiquer le nom de la BDD pour sa création</label>
                <input type="text" id="db_name" name="db_name" required>
            </div>
            <div class="input-group">
                <label for="db_user">Identifiant de la BDD pour installation</label>
                <input type="text" id="db_user" name="db_user" required>
            </div>
            <div class="input-group">
                <label for="db_pass">Mot de passe de la BDD pour installation</label>
                <input type="password" id="db_pass" name="db_pass" required>
            </div>

            <!-- Section de création du compte administrateur -->
            <h3 class="admin-section-title">Création du compte Administrateur pour gérer la Shoutbox</h3>
            <p class="admin-warning">** Attention ! Les minuscules et majuscules sont différenciées lors de la connexion avec votre pseudo, si vous avez un doute, mettez le tout en minuscule, cela évitera les problèmes pour se connecter.</p>

            <!-- Champs du formulaire pour le compte administrateur -->
            <div class="input-group">
                <label for="admin_username">** Pseudo du compte administrateur</label>
                <input type="text" id="admin_username" name="admin_username" required>
            </div>
            <div class="input-group">
                <label for="admin_email">Email du compte administrateur</label>
                <input type="email" id="admin_email" name="admin_email" required>
            </div>
            <div class="input-group">
                <label for="admin_password">Mot de passe du compte Administrateur</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>

            <!-- Centrage du bouton Installer -->
            <div class="form-footer">
                <button type="submit">Installer</button>
            </div>
        </form>
    </div>
</body>
</html>
