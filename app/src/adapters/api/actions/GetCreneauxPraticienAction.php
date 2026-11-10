<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\ports\api\ServiceRdvInterface;

/**
 * GET /praticiens/{id}/creneaux?debut=...&fin=...   (fonctionnalité 7)
 * 200 | 404 praticien inexistant | 422 période absente ou invalide
 */
final class GetCreneauxPraticienAction extends AbstractAction
{
    public function __construct(private readonly ServiceRdvInterface $service)
    {
    }

    public function __invoke(Request $request, Response $response, string $id): Response
    {
        [$debut, $fin] = $this->periode($request);
        $creneaux = $this->service->getCreneauxOccupes($id, $debut, $fin);

        return $this->json($response, [
            'type'         => 'collection',
            'praticien_id' => $id,
            'debut'        => $debut->format('Y-m-d\TH:i:s'),
            'fin'          => $fin->format('Y-m-d\TH:i:s'),
            'count'        => count($creneaux),
            'creneaux'     => array_map(get_object_vars(...), $creneaux),
            'links'        => [
                'self'      => ['href' => $this->urlRequete($request)],
                'praticien' => ['href' => '/praticiens/' . $id],
            ],
        ]);
    }
}
