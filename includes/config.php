<?php
        // Charger les informations de connexion à la base de données depuis db.php
        $config = include(dirname(__DIR__) . '/includes/db.php');
        
        // Définir l'URL de base de l'application
        $base_url = 'http://localhost/shoutbox/';

        return [
            'db_host' => $config['db_host'],
            'db_name' => $config['db_name'],
            'db_user' => $config['db_user'],
            'db_pass' => $config['db_pass'],
            'base_url' => $base_url,
        ];
        