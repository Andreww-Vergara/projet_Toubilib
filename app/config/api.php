<?php
declare(strict_types=1);

/*
 * API : tout ce qui concerne la couche HTTP (Slim) — les actions et les middlewares.
 * Chaque action reçoit le service (port API) dont elle a besoin.
 * ⚠ Sans autowire, une action utilisée dans routes.php mais absente d'ici provoque une erreur
 *   "No entry or class found" : à chaque nouvelle action, ajouter sa définition ici.
 */

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use toubilib\adapters\api\actions\ChangerEtatRdvAction;
use toubilib\adapters\api\actions\CreerRdvAction;
use toubilib\adapters\api\actions\GetAgendaPraticienAction;
use toubilib\adapters\api\actions\GetCreneauxPraticienAction;
use toubilib\adapters\api\actions\GetPraticienAction;
use toubilib\adapters\api\actions\GetRdvAction;
use toubilib\adapters\api\actions\ListerPraticiensAction;
use toubilib\adapters\api\middlewares\CorsMiddleware;
use toubilib\adapters\api\middlewares\JsonErrorHandler;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\api\ServiceRdvInterface;

return [
    // Fabrique de réponses PSR-7 (utilisée par les middlewares)
    ResponseFactoryInterface::class => fn () => new ResponseFactory(),

    // Middlewares
    CorsMiddleware::class => fn (ContainerInterface $c) => new CorsMiddleware(
        $c->get(ResponseFactoryInterface::class),
        $c->get('cors.origin'),
    ),
    JsonErrorHandler::class => fn (ContainerInterface $c) => new JsonErrorHandler(
        $c->get(ResponseFactoryInterface::class),
        $c->get('app.debug'),
    ),

    // Actions — praticiens
    ListerPraticiensAction::class     => fn (ContainerInterface $c) => new ListerPraticiensAction($c->get(ServicePraticienInterface::class)),
    GetPraticienAction::class         => fn (ContainerInterface $c) => new GetPraticienAction($c->get(ServicePraticienInterface::class)),
    GetAgendaPraticienAction::class   => fn (ContainerInterface $c) => new GetAgendaPraticienAction($c->get(ServiceRdvInterface::class)),
    GetCreneauxPraticienAction::class => fn (ContainerInterface $c) => new GetCreneauxPraticienAction($c->get(ServiceRdvInterface::class)),

    // Actions — rendez-vous
    GetRdvAction::class   => fn (ContainerInterface $c) => new GetRdvAction($c->get(ServiceRdvInterface::class)),
    CreerRdvAction::class => fn (ContainerInterface $c) => new CreerRdvAction($c->get(ServiceRdvInterface::class)),

    // Même classe, trois configurations : la clé est un simple nom, utilisé tel quel dans routes.php
    'rdv.annuler' => fn (ContainerInterface $c) => new ChangerEtatRdvAction($c->get(ServiceRdvInterface::class), 'annuler'),
    'rdv.honorer' => fn (ContainerInterface $c) => new ChangerEtatRdvAction($c->get(ServiceRdvInterface::class), 'honorer'),
    'rdv.ignorer' => fn (ContainerInterface $c) => new ChangerEtatRdvAction($c->get(ServiceRdvInterface::class), 'ignorer'),
];
