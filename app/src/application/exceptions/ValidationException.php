<?php
declare(strict_types=1);

namespace toubilib\application\exceptions;

use RuntimeException;

/** Données invalides (→ HTTP 422). Utilisée à partir de la Phase 2 (création de RDV). */
class ValidationException extends RuntimeException
{
    /** @param array<string,string> $errors champ => message */
    public function __construct(private readonly array $errors, string $message = 'Données invalides')
    {
        parent::__construct($message);
    }

    /** @return array<string,string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
