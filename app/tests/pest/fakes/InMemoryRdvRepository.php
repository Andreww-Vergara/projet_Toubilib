<?php
declare(strict_types=1);

namespace tests\pest\fakes;

use DateTimeImmutable;
use toubilib\application\ports\spi\RdvRepositoryInterface;
use toubilib\domain\entities\RendezVous;

/**
 * Faux repository en mémoire : implante le MÊME port de sortie que PdoRdvRepository,
 * ce qui permet de tester le service sans base de données (inversion de dépendances).
 */
final class InMemoryRdvRepository implements RdvRepositoryInterface
{
    /** @var array<string, RendezVous> */
    private array $rdvs = [];

    /** Nombre d'appels à update(), pour vérifier que le service a bien sauvegardé. */
    public int $nbUpdates = 0;

    public function __construct(RendezVous ...$rdvs)
    {
        foreach ($rdvs as $rdv) {
            $this->rdvs[$rdv->id] = $rdv;
        }
    }

    public function findById(string $id): ?RendezVous
    {
        return $this->rdvs[$id] ?? null;
    }

    public function findByPraticienAndPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        return array_values(array_filter(
            $this->rdvs,
            fn (RendezVous $r) => $r->praticienId === $praticienId
                && $r->dateHeureDebut < $fin
                && $r->dateHeureFin > $debut
        ));
    }

    /** Nombre d'appels à save(), pour vérifier qu'un RDV refusé n'est jamais enregistré. */
    public int $nbSaves = 0;

    public function save(RendezVous $rdv): void
    {
        $this->rdvs[$rdv->id] = $rdv;
        $this->nbSaves++;
    }

    public function update(RendezVous $rdv): void
    {
        $this->rdvs[$rdv->id] = $rdv;
        $this->nbUpdates++;
    }
}
