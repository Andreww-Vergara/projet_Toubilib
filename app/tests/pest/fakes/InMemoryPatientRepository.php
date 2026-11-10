<?php
declare(strict_types=1);

namespace tests\pest\fakes;

use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\domain\entities\Patient;

final class InMemoryPatientRepository implements PatientRepositoryInterface
{
    /** @var array<string, Patient> */
    private array $patients = [];

    public function __construct(Patient ...$patients)
    {
        foreach ($patients as $p) {
            $this->patients[$p->id] = $p;
        }
    }

    public function findById(string $id): ?Patient
    {
        return $this->patients[$id] ?? null;
    }
}
