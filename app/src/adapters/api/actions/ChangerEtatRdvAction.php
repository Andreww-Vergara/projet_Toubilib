<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\ports\api\ServiceRdvInterface;

/**
 * PATCH /rdvs/{id}/annuler | /rdvs/{id}/honorer | /rdvs/{id}/ignorer
 * 200 + RDV modifié | 404 RDV inexistant | 409 transition interdite
 *
 * Une seule classe pour les trois routes : seule la méthode du service appelée change.
 * Le conteneur en crée trois instances ('rdv.annuler', 'rdv.honorer', 'rdv.ignorer'), voir api.php.
 */
final class ChangerEtatRdvAction extends AbstractRdvAction
{
    private const TRANSITIONS = ['annuler', 'honorer', 'ignorer'];

    public function __construct(
        private readonly ServiceRdvInterface $service,
        private readonly string $transition,
    ) {
        if (!in_array($transition, self::TRANSITIONS, true)) {
            throw new InvalidArgumentException("Transition inconnue : $transition");
        }
    }

    public function __invoke(Request $request, Response $response, string $id): Response
    {
        $rdv = match ($this->transition) {
            'annuler' => $this->service->annuler($id),
            'honorer' => $this->service->honorer($id),
            'ignorer' => $this->service->ignorer($id),
        };

        return $this->json($response, $this->ressourceRdv($rdv));
    }
}
