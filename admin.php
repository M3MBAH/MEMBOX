<?php
session_start();
$timeout_duration = 1800; // 30 minutes en secondes

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: ../users/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // Mise à jour de l'activité

require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/functions.php';

// Vérifier si l'utilisateur est admin ou modérateur
$user_role = get_user_role($_SESSION['user_id']);
if (!$user_role || ($user_role != 'admin' && $user_role != 'moderator')) {
    header("Location: ../users/login.php");
    exit();
}

$error = '';
$success = '';

// Gestion de la déconnexion
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../users/login.php");
    exit();
}

// Récupérer l'état des inscriptions
$stmt = $pdo->query("SELECT registration_open FROM settings LIMIT 1");
$registration_open = $stmt->fetchColumn();

// Gestion des actions : activation, désactivation, changement de mot de passe, gestion des inscriptions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_ids = $_POST['user_ids'] ?? [];

    // Gérer l'ouverture/fermeture des inscriptions (uniquement pour les admins)
    if ($action === 'toggle_registration' && $user_role == 'admin') {
        $new_state = $registration_open ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE settings SET registration_open = ?");
        $stmt->execute([$new_state]);
        $registration_open = $new_state;
        $success = $registration_open ? "Les inscriptions sont maintenant ouvertes." : "Les inscriptions sont maintenant fermées.";
    } elseif (!empty($user_ids)) {
        if ($action === 'change_password') {
            $new_password = $_POST['new_password'] ?? '';
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                foreach ($user_ids as $user_id) {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                }
                $success = "Mot de passe modifié avec succès.";
            } else {
                $error = "Le mot de passe ne peut pas être vide.";
            }
        } elseif ($action === 'change_role' && $user_role == 'admin') {
            $new_role = $_POST['new_role'];
            foreach ($user_ids as $user_id) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
            }
            $success = "Le rôle a été modifié avec succès.";
        } elseif ($action == 'activate' || $action == 'deactivate') {
            foreach ($user_ids as $user_id) {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_role_to_modify = $stmt->fetchColumn();

                if ($user_role_to_modify !== 'admin' || $user_role === 'admin') {
                    $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id = ?");
                    $stmt->execute([$action == 'activate' ? 1 : 0, $user_id]);
                } else {
                    $error = "Les modérateurs ne peuvent pas modifier les comptes admin.";
                }
            }
            if (!$error) {
                $success = "Action effectuée avec succès.";
            }
        }
    } else {
        $error = "Veuillez sélectionner au moins un utilisateur.";
    }
}

// Récupérer la liste des utilisateurs
$stmt = $pdo->query("SELECT id, username, email, active, role, secret_answer FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau Admin - Gestion des utilisateurs</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h2>Liste des utilisateurs</h2>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <form action="admin.php" method="post">
            <div class="actions">
                <?php if ($user_role == 'admin'): ?>
                    <button type="submit" name="action" value="toggle_registration"
                        class="toggle-registration <?php echo $registration_open ? 'active' : 'inactive'; ?>">
                        <?php echo $registration_open ? 'Fermer les inscriptions' : 'Ouvrir les inscriptions'; ?>
                    </button>
                    <button type="submit" name="action" value="change_role">Changer de rôle</button>
                    <select name="new_role">
                        <option value="user">Utilisateur</option>
                        <option value="moderator">Modérateur</option>
                        <option value="admin">Admin</option>
                    </select>
                <?php endif; ?>
                <?php if ($user_role == 'admin' || $user_role == 'moderator'): ?>
                    <button type="submit" name="action" value="activate">Activer</button>
                    <button type="submit" name="action" value="deactivate">Désactiver</button>
                    <input type="password" name="new_password" placeholder="Nouveau mot de passe">
                    <button type="submit" name="action" value="change_password">Changer le mot de passe</button>
                <?php endif; ?>
            </div>

            <table class="user-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Rôle</th>
                        <th>Réponse secrète</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr class="<?php echo $index % 2 === 0 ? 'light-row' : 'dark-row'; ?>">
                            <td><input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" <?php echo ($user['role'] === 'admin' && $user_role !== 'admin') ? 'disabled' : ''; ?>></td>
                            <td class="<?php echo $user['active'] ? '' : 'inactive-user'; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['active'] ? 'Actif' : 'Inactif'; ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars($user['secret_answer']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <form action="admin.php" method="post" style="margin-top: 20px;">
            <button type="submit" name="logout" class="logout-button">Déconnexion</button>
            <a href="../shoutbox.php" class="shoutbox-button">Aller à la Shoutbox</a>
        </form>
    </div>
</body>
</html>
