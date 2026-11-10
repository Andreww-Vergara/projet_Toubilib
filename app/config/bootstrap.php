<?php
declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use toubilib\adapters\api\middlewares\CorsMiddleware;
use toubilib\adapters\api\middlewares\JsonErrorHandler;

// 1. Variables d'environnement (app/config/.env) → lues dans settings.php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 2. Conteneur d'injection de dépendances, SANS autowire :
//    tout ce que le conteneur fournit doit être déclaré dans l'un des 3 fichiers.
$builder = new ContainerBuilder();
$builder->useAutowiring(false);
$builder->addDefinitions(
    __DIR__ . '/settings.php',   // valeurs de configuration
    __DIR__ . '/services.php',   // PDO, repositories, services métier
    __DIR__ . '/api.php',        // actions et middlewares Slim
);
$container = $builder->build();

// 3. Application Slim (via le bridge PHP-DI) : les actions des routes sont prises dans le conteneur
$app = Bridge::create($container);

// 4. Middlewares. Slim les exécute en ordre INVERSE d'ajout : le dernier ajouté est le plus externe.
//    Exécution : Cors → Erreurs → Routing → Body parsing → action
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$debug = $container->get('app.debug');
$errorMiddleware = $app->addErrorMiddleware($debug, true, true);
$errorMiddleware->setDefaultErrorHandler($container->get(JsonErrorHandler::class));

$app->add($container->get(CorsMiddleware::class));

// 5. Routes
(require __DIR__ . '/routes.php')($app);

return $app;
