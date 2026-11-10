<?php
declare(strict_types=1);

namespace tests\pest\fakes;

use DateTimeImmutable;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\domain\entities\Praticien;

final class InMemoryPraticienRepository implements PraticienRepositoryInterface
{
    /** @var array<string, Praticien> */
    private array $praticiens = [];

    /**
     * @param Praticien[] $praticiens
     * @param InMemoryRdvRepository|null $rdvs source de l'agenda (comme le vrai repository)
     */
    public function __construct(array $praticiens = [], private readonly ?InMemoryRdvRepository $rdvs = null)
    {
        foreach ($praticiens as $p) {
            $this->praticiens[$p->id] = $p;
        }
    }

    public function findAll(): array
    {
        return array_values($this->praticiens);
    }

    public function findById(string $id): ?Praticien
    {
        return $this->praticiens[$id] ?? null;
    }

    public function findByIdAvecAgenda(string $id, DateTimeImmutable $jour): ?Praticien
    {
        $praticien = $this->findById($id);
        if ($praticien !== null && $this->rdvs !== null) {
            $debut = $jour->setTime(0, 0);
            $praticien->chargerAgenda($this->rdvs->findByPraticienAndPeriode($id, $debut, $debut->modify('+1 day')));
        }

        return $praticien;
    }
}
