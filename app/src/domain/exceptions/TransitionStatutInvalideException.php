<?php
declare(strict_types=1);

namespace toubilib\domain\exceptions;

use RuntimeException;

/**
 * Levée quand on demande à un rendez-vous un changement d'état interdit
 * (annuler un RDV passé, honorer un RDV déjà annulé...). → HTTP 409 Conflict.
 */
class TransitionStatutInvalideException extends RuntimeException
{
}
