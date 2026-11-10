<?php
declare(strict_types=1);

namespace toubilib\application\dto;

use toubilib\domain\entities\Patient;
use toubilib\domain\entities\RendezVous;

final class AgendaRdvDTO
{
    private const FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(
        public readonly string $id,
        public readonly string $date_heure_debut,
        public readonly string $date_heure_fin,
        public readonly int $duree,
        public readonly ?string $motif_visite,
        public readonly string $etat,
        public readonly ?array $patient,
    ) {
    }

    public static function fromEntity(RendezVous $r, ?Patient $p): self
    {
        return new self(
            $r->id,
            $r->dateHeureDebut->format(self::FORMAT),
            $r->dateHeureFin->format(self::FORMAT),
            $r->duree,
            $r->motifVisite?->value,
            $r->statut->name,   // même valeur que "etat" dans RdvDTO
            $p === null ? null : ['id' => $p->id, 'nom' => $p->nom, 'prenom' => $p->prenom],
        );
    }
}