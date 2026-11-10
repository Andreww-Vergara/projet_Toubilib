<?php
declare(strict_types=1);

namespace toubilib\application\dto;

use toubilib\domain\entities\RendezVous;

/**
 * Créneau OCCUPÉ d'un praticien (fonctionnalité 7) : seulement les heures, aucune
 * information sur le patient ni sur le motif. C'est la différence avec l'agenda (n°4).
 */
final class CreneauDTO
{
    public function __construct(
        public readonly string $date_debut,
        public readonly string $date_fin,
    ) {
    }

    public static function fromEntity(RendezVous $r): self
    {
        return new self(
            $r->dateHeureDebut->format('Y-m-d\TH:i:s'),
            $r->dateHeureFin->format('Y-m-d\TH:i:s'),
        );
    }
}
