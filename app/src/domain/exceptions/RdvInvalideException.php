<?php
declare(strict_types=1);

namespace toubilib\domain\exceptions;

use RuntimeException;

/**
 * Règle métier violée par une demande de RDV : motif refusé par le praticien,
 * date passée, jour non ouvrable, heure hors des plages de consultation. → HTTP 422.
 */
class RdvInvalideException extends RuntimeException
{
}
