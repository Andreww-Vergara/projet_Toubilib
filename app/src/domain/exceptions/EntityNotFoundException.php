<?php
declare(strict_types=1);

namespace toubilib\domain\exceptions;

use RuntimeException;

/** Levée quand une entité demandée n'existe pas (→ HTTP 404). */
class EntityNotFoundException extends RuntimeException
{
    public static function of(string $entite, string $id): self
    {
        return new self(sprintf('%s "%s" introuvable', $entite, $id));
    }
}
