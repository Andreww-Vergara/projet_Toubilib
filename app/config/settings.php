<?php
declare(strict_types=1);

/*
 * SETTINGS : uniquement des valeurs de configuration (aucun objet).
 * Elles viennent du fichier .env (chargé dans bootstrap.php) et sont récupérées
 * ailleurs avec $c->get('db'), $c->get('app.debug'), etc.
 */

return [
    'app.debug' => filter_var($_ENV['app.debug'] ?? 'false', FILTER_VALIDATE_BOOLEAN),

    'cors.origin' => $_ENV['cors.origin'] ?? '*',

    'db' => [
        'driver'   => $_ENV['lib.driver']   ?? 'pgsql',
        'host'     => $_ENV['lib.host']     ?? 'toubilib.db',
        'port'     => $_ENV['lib.port']     ?? '5432',
        'database' => $_ENV['lib.database'] ?? 'toubilib',
        'username' => $_ENV['lib.username'] ?? null,
        'password' => $_ENV['lib.password'] ?? null,
    ],
];
