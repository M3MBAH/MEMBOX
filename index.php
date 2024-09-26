<?php
require 'includes/config.php';
require 'includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Shoutbox</title>
    <link rel="stylesheet" href="<?php echo $css_url; ?>/style.css">
</head>
<body>
    <h1>Bienvenue dans la Shoutbox</h1>
    <a href="<?php echo $base_url; ?>/shoutbox.php">Accéder à la Shoutbox</a>
</body>
</html>
