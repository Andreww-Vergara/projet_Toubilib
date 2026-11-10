<?php
declare(strict_types=1);

namespace toubilib\domain\entities;

final class Specialite
{
    public function __construct(
        public readonly int $id,
        public readonly string $libelle,
        public readonly ?string $description = null,
    ) {
    }
}
