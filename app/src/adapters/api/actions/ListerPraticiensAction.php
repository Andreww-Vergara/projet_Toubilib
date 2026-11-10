<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\dto\PraticienDTO;
use toubilib\application\ports\api\ServicePraticienInterface;

/** GET /praticiens   200   (fonctionnalité 1) */
final class ListerPraticiensAction extends AbstractAction
{
    public function __construct(private readonly ServicePraticienInterface $service)
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $praticiens = array_map(
            fn (PraticienDTO $p) => [
                ...get_object_vars($p),
                'links' => ['self' => ['href' => '/praticiens/' . $p->id]],
            ],
            $this->service->listerPraticiens()
        );

        return $this->json($response, [
            'type'       => 'collection',
            'count'      => count($praticiens),
            'praticiens' => $praticiens,
            'links'      => ['self' => ['href' => '/praticiens']],
        ]);
    }
}
