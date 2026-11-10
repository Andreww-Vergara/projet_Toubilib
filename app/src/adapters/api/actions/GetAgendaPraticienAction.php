<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\dto\AgendaRdvDTO;
use toubilib\application\ports\api\ServiceRdvInterface;

/**
 * GET /praticiens/{id}/rdvs?debut=...&fin=...   (fonctionnalité 4)
 * 200 | 404 praticien inexistant | 422 période absente ou invalide
 */
final class GetAgendaPraticienAction extends AbstractAction
{
    public function __construct(private readonly ServiceRdvInterface $service)
    {
    }

    public function __invoke(Request $request, Response $response, string $id): Response
    {
        [$debut, $fin] = $this->periode($request);

        $rdvs = array_map(
            fn (AgendaRdvDTO $r) => [
                ...get_object_vars($r),
                'links' => ['self' => ['href' => '/rdvs/' . $r->id]],
            ],
            $this->service->getAgenda($id, $debut, $fin)
        );

        return $this->json($response, [
            'type'         => 'collection',
            'praticien_id' => $id,
            'debut'        => $debut->format('Y-m-d\TH:i:s'),
            'fin'          => $fin->format('Y-m-d\TH:i:s'),
            'count'        => count($rdvs),
            'rdvs'         => $rdvs,
            'links'        => [
                'self'      => ['href' => $this->urlRequete($request)],
                'praticien' => ['href' => '/praticiens/' . $id],
            ],
        ]);
    }
}
