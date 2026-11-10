<?php
declare(strict_types=1);

namespace toubilib\adapters\api\middlewares;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Throwable;
use toubilib\application\exceptions\ValidationException;
use toubilib\domain\exceptions\EntityNotFoundException;
use toubilib\domain\exceptions\PraticienIndisponibleException;
use toubilib\domain\exceptions\RdvInvalideException;
use toubilib\domain\exceptions\TransitionStatutInvalideException;

/**
 * Gestionnaire d'erreurs branché sur l'ErrorMiddleware de Slim :
 * convertit toute exception en réponse JSON avec le bon code HTTP.
 */
final class JsonErrorHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly bool $debug = false,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $e,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        $extra = [];

        // L'ordre compte : HttpMethodNotAllowedException hérite de HttpException.
        [$status, $message] = match (true) {
            $e instanceof HttpMethodNotAllowedException => [405, 'Méthode HTTP non autorisée sur cette ressource'],
            $e instanceof HttpException                 => [$e->getCode() ?: 500, $e->getMessage()],
            $e instanceof EntityNotFoundException       => [404, $e->getMessage()],
            $e instanceof TransitionStatutInvalideException => [409, $e->getMessage()],
            $e instanceof PraticienIndisponibleException    => [409, $e->getMessage()],
            $e instanceof ValidationException           => [422, $e->getMessage()],
            $e instanceof RdvInvalideException          => [422, $e->getMessage()],
            $e instanceof InvalidArgumentException      => [400, $e->getMessage()],
            default                                     => [500, 'Erreur interne du serveur'],
        };

        if ($e instanceof ValidationException) {
            $extra['errors'] = $e->getErrors();
        }

        if ($status >= 500) {
            error_log(sprintf('[toubilib] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($this->debug) {
                $extra['debug'] = ['exception' => $e::class, 'message' => $e->getMessage()];
            }
        }

        $response = $this->responseFactory->createResponse($status);
        if ($e instanceof HttpMethodNotAllowedException) {
            $response = $response->withHeader('Allow', implode(', ', $e->getAllowedMethods()));
        }

        $response->getBody()->write(json_encode(
            ['error' => ['status' => $status, 'message' => $message, ...$extra]],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
