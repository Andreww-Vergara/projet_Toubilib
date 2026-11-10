<?php
declare(strict_types=1);

namespace toubilib\application\ports\spi;

use DateTimeImmutable;
use toubilib\domain\entities\Praticien;

interface PraticienRepositoryInterface
{
    /** @return Praticien[] */
    public function findAll(): array;

    public function findById(string $id): ?Praticien;

    /**
     * Praticien avec son agenda du JOUR de $jour (ses RDV de 00:00 à 24:00).
     * Un RDV ne pouvant pas déborder sur un autre jour, cela suffit pour vérifier la disponibilité.
     */
    public function findByIdAvecAgenda(string $id, DateTimeImmutable $jour): ?Praticien;
}
