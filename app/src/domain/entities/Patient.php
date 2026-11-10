<?php
declare(strict_types=1);

namespace toubilib\domain\entities;

use DateTimeImmutable;

final class Patient
{
    public function __construct(
        public readonly string $id,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $telephone,
        public readonly ?DateTimeImmutable $dateNaissance = null,
        public readonly ?string $adresse = null,
        public readonly ?string $codePostal = null,
        public readonly ?string $ville = null,
        public readonly ?string $email = null,
    ) {
    }
}
