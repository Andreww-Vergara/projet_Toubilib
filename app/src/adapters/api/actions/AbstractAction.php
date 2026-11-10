<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use DateTimeImmutable;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use toubilib\application\exceptions\ValidationException;

/**
 * Outils communs à toutes les actions.
 * Convention des réponses (annexe TD1.4) :
 *   { "type": "ressource" | "collection", "<nom>": données, "links": { "self": {"href": ...}, ... } }
 */
abstract class AbstractAction
{
    private const FORMAT_PERIODE = 'Obligatoire. Format : 2025-12-05 ou 2025-12-05T08:00:00';

    /** Écrit $data en JSON dans la réponse avec le bon Content-Type et le bon status. */
    protected function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    /** URL relative de la requête, paramètres compris (pour le lien "self" des collections). */
    protected function urlRequete(Request $request): string
    {
        $uri = $request->getUri();

        return $uri->getPath() . ($uri->getQuery() !== '' ? '?' . $uri->getQuery() : '');
    }

    /**
     * Lit et valide les paramètres ?debut=...&fin=... (agenda, créneaux).
     * @return array{DateTimeImmutable, DateTimeImmutable}
     * @throws ValidationException (422) si absents, mal formés ou fin < debut
     */
    protected function periode(Request $request): array
    {
        $params = $request->getQueryParams();
        $debut = $this->parseDate($params['debut'] ?? null, false);
        $fin   = $this->parseDate($params['fin'] ?? null, true);

        $errors = [];
        if ($debut === null) {
            $errors['debut'] = self::FORMAT_PERIODE;
        }
        if ($fin === null) {
            $errors['fin'] = self::FORMAT_PERIODE;
        }
        if ($debut !== null && $fin !== null && $fin < $debut) {
            $errors['fin'] = '"fin" doit être postérieure à "debut"';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [$debut, $fin];
    }

    /** Convertit le texte reçu en date ; null si absent ou invalide. */
    private function parseDate(mixed $value, bool $finDeJournee): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }

        // Si on reçoit seulement une date (2025-12-05), "fin" signifie la fin de cette journée.
        if ($finDeJournee && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = $date->setTime(23, 59, 59);
        }

        return $date;
    }
}
