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
require 'includes/config.php';
require 'includes/functions.php';

// Gérer la déconnexion
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: $base_url/users/login.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: $base_url/users/login.php");
    exit();
}

// Récupérer le rôle de l'utilisateur connecté
$user_role = get_user_role($_SESSION['user_id']);
$is_admin = $user_role === 'admin';
$is_moderator = $user_role === 'moderator';

$current_user = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['message'])) {
    $user_id = $_SESSION['user_id'];
    $message = $_POST['message'];
    post_message($user_id, $message);
}

$messages = fetch_messages();

// Définir la liste des emojis disponibles
$emoji_list = ['grinning', 'joy', 'heart_eyes', 'sunglasses', 'thumbsup'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3MBA'S SHOUTBOX</title>
    <link rel="stylesheet" href="<?php echo $css_url; ?>/shoutbox.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <form method="POST">
        <button type="submit" name="logout" class="logout-button">Déconnexion</button>
    </form>

    <?php if ($is_admin): ?>
        <a href="<?php echo $base_url; ?>/admin.php" class="admin-button">Administration</a>
    <?php elseif ($is_moderator): ?>
        <a href="<?php echo $base_url; ?>/admin.php" class="moderation-button">Modération</a>
    <?php endif; ?>

    <div class="shoutbox-container">
        <h2 class="glowing-btn"><span class="glowing-txt">M3MBA'S SHOUTBOX</span></h2>

        <div class="messages" id="messages">
            <?php
            $i = 0;
            if (is_array($messages) && !empty($messages)) {
                foreach ($messages as $message):
                    $user = htmlspecialchars($message['username']);
                    $role = get_user_role_by_username($user);
                    $role_display = '';

                    // Définir la classe de style en fonction du rôle
                    if ($role === 'admin') {
                        $user_class = 'admin';
                    } elseif ($role === 'moderator') {
                        $user_class = 'moderator';
                    } else {
                        $user_class = 'user';
                    }

                    $text = $message['message'];
                    $timestamp = $message['timestamp'];
                    $message_id = $message['id'];
                    $bgColor = $i % 2 == 0 ? '#333' : '#444';
            ?>
                    <div class="message" id="message-<?php echo $message_id; ?>" style="background-color: <?php echo $bgColor; ?>">
                        <strong class="<?php echo $user_class; ?>"><?php echo $user; ?>:</strong>
                        <p><?php echo $text; ?></p>
                        <span><?php echo $timestamp; ?></span>
                    </div>
            <?php
                    $i++;
                endforeach;
            } else {
                echo "<p>Aucun message à afficher</p>";
            }
            ?>
        </div>

        <!-- Bouton pour basculer l'affichage de la zone des emojis -->
        <button id="toggle-emoji">Afficher les emojis</button>

        <!-- Section Emoji (initialement masquée) -->
        <div class="emoji-container" id="emoji-container" style="display: none;">
            <?php
            if (is_array($emoji_list) && !empty($emoji_list)) {
                foreach ($emoji_list as $emoji): ?>
                    <img src="<?php echo $assets_url; ?>/emojis/<?php echo $emoji; ?>.svg" class="emoji" alt="<?php echo $emoji; ?>" />
                <?php endforeach;
            } else {
                echo "<p>Aucun emoji disponible</p>";
            }
            ?>
        </div>

        <form id="messageForm">
            <div class="input-group">
                <textarea name="message" id="messageInput" required></textarea>
                <button type="submit">Envoyer</button>
            </div>
        </form>
    </div>

    <!-- Ajouter les éléments audio -->
    <audio id="newMessageSound" src="<?php echo $assets_url; ?>/sounds/notification.mp3"></audio>
    <audio id="myMessageSound" src="<?php echo $assets_url; ?>/sounds/my_message.mp3"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            const emojiContainer = document.getElementById('emoji-container');
            const toggleEmojiButton = document.getElementById('toggle-emoji');
            const newMessageSound = document.getElementById('newMessageSound');
            const myMessageSound = document.getElementById('myMessageSound');
            let lastMessageCount = <?php echo count($messages); ?>;

            document.querySelectorAll('.emoji').forEach(function(emoji) {
                emoji.addEventListener('click', function() {
                    const imgTag = `<img src="<?php echo $assets_url; ?>/emojis/${emoji.alt}.svg" alt="${emoji.alt}" class="emoji-inline">`;
                    messageInput.value += ` ${imgTag} `;
                });
            });

            toggleEmojiButton.addEventListener('click', function() {
                if (emojiContainer.style.display === 'none') {
                    emojiContainer.style.display = 'flex';
                    toggleEmojiButton.textContent = 'Masquer les emojis';
                } else {
                    emojiContainer.style.display = 'none';
                    toggleEmojiButton.textContent = 'Afficher les emojis';
                }
            });

            function loadMessages() {
                $.ajax({
                    url: "shoutbox.php",
                    method: "GET",
                    success: function(data) {
                        const newMessages = $(data).find('#messages').html();
                        const currentMessageCount = $(data).find('.message').length;

                        const lastMessageUser = $(data).find('.message strong').last().text().split(':')[0].trim();
                        if (currentMessageCount > lastMessageCount && lastMessageUser !== '<?php echo $current_user; ?>') {
                            newMessageSound.play(); // Jouer le son pour les nouveaux messages d'autres utilisateurs
                        }

                        $('#messages').html(newMessages);
                        lastMessageCount = currentMessageCount;
                    }
                });
            }

            function submitMessage() {
                const message = $('#messageInput').val();
                if (message.trim() === "") return;

                $.ajax({
                    url: "shoutbox.php",
                    method: "POST",
                    data: { message: message },
                    success: function() {
                        $('#messageInput').val('');
                        loadMessages();  // Recharge les messages après envoi
                        myMessageSound.play(); // Jouer le son pour mon propre message
                        lastMessageCount++;  // Augmente le compteur pour éviter le son des nouveaux messages
                    }
                });
            }

            $('#messageForm').submit(function(e) {
                e.preventDefault();
                submitMessage();
            });

            $('#messageInput').keypress(function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    submitMessage();
                }
            });

            setInterval(loadMessages, 5000);  // Recharge les messages toutes les 5 secondes
        });
    </script>
</body>
</html>
