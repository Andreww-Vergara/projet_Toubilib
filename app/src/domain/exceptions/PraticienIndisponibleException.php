<?php
declare(strict_types=1);

namespace toubilib\domain\exceptions;

use RuntimeException;

/** Le créneau demandé chevauche un RDV existant du praticien. → HTTP 409 Conflict. */
class PraticienIndisponibleException extends RuntimeException
{
}
