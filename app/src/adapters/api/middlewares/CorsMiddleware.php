<?php
declare(strict_types=1);

namespace toubilib\adapters\api\middlewares;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Ajoute les headers CORS à TOUTES les réponses (y compris les erreurs)
 * et répond directement aux requêtes de pré-vérification OPTIONS.
 * Doit être le middleware le plus externe (ajouté en dernier).
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string $allowedOrigin = '*',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $request->getMethod() === 'OPTIONS'
            ? $this->responseFactory->createResponse(204)
            : $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowedOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, Origin, X-Requested-With')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}
