<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\ports\api\ServiceRdvInterface;

/**
 * POST /rdvs   (corps JSON : praticien_id, patient_id, date_heure_debut, motif_visite)
 *
 * 201 Created + en-tête Location + RDV créé
 * 400 corps absent ou JSON mal formé
 * 422 données invalides (champ manquant/mal formé, praticien/patient inconnu, motif ou horaire refusé)
 * 409 créneau déjà occupé
 */
final class CreerRdvAction extends AbstractRdvAction
{
    public function __construct(private readonly ServiceRdvInterface $service)
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        // Le BodyParsingMiddleware a déjà décodé le JSON ; null si corps vide ou JSON invalide
        $donnees = $request->getParsedBody();
        if (!is_array($donnees)) {
            throw new InvalidArgumentException('Corps de requête JSON attendu (Content-Type: application/json)');
        }

        // HTTP → DTO (présence, type, format), puis appel du cas d'utilisation
        $rdv = $this->service->creerRdv(CreateRdvDTO::fromArray($donnees));

        return $this->json($response, $this->ressourceRdv($rdv), 201)
            ->withHeader('Location', '/rdvs/' . $rdv->id);
    }
}
