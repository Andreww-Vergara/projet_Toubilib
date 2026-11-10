<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\ports\api\ServicePraticienInterface;

/** GET /praticiens/{id}   200 | 404 praticien inexistant   (fonctionnalité 2) */
final class GetPraticienAction extends AbstractAction
{
    public function __construct(private readonly ServicePraticienInterface $service)
    {
    }

    // $id est injecté par PHP-DI Bridge à partir du placeholder {id} de la route.
    public function __invoke(Request $request, Response $response, string $id): Response
    {
        $praticien = $this->service->getPraticien($id);
        $url = '/praticiens/' . $praticien->id;

        return $this->json($response, [
            'type'      => 'ressource',
            'praticien' => get_object_vars($praticien),
            'links'     => [
                'self'       => ['href' => $url],
                'praticiens' => ['href' => '/praticiens'],
                // Ces deux routes exigent ?debut=...&fin=... (période à choisir par le client)
                'agenda'     => ['href' => $url . '/rdvs'],
                'creneaux'   => ['href' => $url . '/creneaux'],
                'creer_rdv'  => ['href' => '/rdvs', 'method' => 'POST'],
            ],
        ]);
    }
}
