<?php
declare(strict_types=1);

namespace toubilib\application\dto;

use toubilib\domain\entities\Praticien;

final class PraticienDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $titre,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $ville,
        public readonly string $email,
        public readonly string $telephone,
        public readonly string $specialite,
        public readonly bool $accepte_nouveau_patient,
    ) {
    }

    public static function fromEntity(Praticien $p): self
    {
        return new self(
            $p->id,
            $p->titre,
            $p->nom,
            $p->prenom,
            $p->ville,
            $p->email,
            $p->telephone,
            $p->specialite->libelle,
            $p->accepteNouveauPatient,
        );
    }
}
