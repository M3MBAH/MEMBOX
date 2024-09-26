<?php
// Charger la configuration et la base de données
$config = include(__DIR__ . '/config.php'); // Utilisation de __DIR__ pour un chemin relatif sûr

// Connexion à la base de données
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour récupérer les messages
function fetch_messages() {
    global $pdo;
    $stmt = $pdo->query("SELECT messages.message, users.username, messages.timestamp, users.role 
                         FROM messages 
                         JOIN users ON messages.user_id = users.id 
                         ORDER BY messages.timestamp DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour poster un message
function post_message($user_id, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}

// Fonction pour vérifier si les inscriptions sont ouvertes
function are_registrations_open() {
    global $pdo;
    $stmt = $pdo->query("SELECT registration_open FROM settings LIMIT 1");
    return (bool) $stmt->fetchColumn();
}

// Fonction pour obtenir le rôle de l'utilisateur
function get_user_role($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($role && is_array($role)) {
        return $role['role'];
    }
    return null;
}

// Fonction pour obtenir le rôle d'un utilisateur par son nom d'utilisateur
function get_user_role_by_username($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($role && is_array($role)) {
        return $role['role'];
    }
    return null;
}

// Fonction pour vérifier si un utilisateur est administrateur
function is_admin($user_id) {
    return get_user_role($user_id) === 'admin';
}

// Fonction pour vérifier si un utilisateur est modérateur
function is_moderator($user_id) {
    return get_user_role($user_id) === 'moderator';
}
