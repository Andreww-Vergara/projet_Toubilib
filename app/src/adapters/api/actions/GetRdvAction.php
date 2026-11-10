<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\ports\api\ServiceRdvInterface;

/** GET /rdvs/{id}   200 | 404 rdv inexistant */
final class GetRdvAction extends AbstractRdvAction
{
    public function __construct(private readonly ServiceRdvInterface $service)
    {
    }

    public function __invoke(Request $request, Response $response, string $id): Response
    {
        return $this->json($response, $this->ressourceRdv($this->service->getRdv($id)));
    }
}
