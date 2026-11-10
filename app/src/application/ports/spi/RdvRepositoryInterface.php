<?php
declare(strict_types=1);

namespace toubilib\application\ports\spi;

use DateTimeImmutable;
use toubilib\domain\entities\RendezVous;

interface RdvRepositoryInterface
{
    public function findById(string $id): ?RendezVous;

    /**
     * RDV d'un praticien qui chevauchent la période [$debut, $fin], triés par heure de début.
     * @return RendezVous[]
     */
    public function findByPraticienAndPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    /** Insère un NOUVEAU rendez-vous en base. */
    public function save(RendezVous $rdv): void;

    /** Enregistre en base les modifications d'un RDV existant (aujourd'hui : son statut). */
    public function update(RendezVous $rdv): void;
}
