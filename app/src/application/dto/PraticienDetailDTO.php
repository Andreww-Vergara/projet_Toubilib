<?php
declare(strict_types=1);

namespace toubilib\application\dto;

use toubilib\domain\entities\Praticien;

final class PraticienDetailDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $titre,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly array $specialite,
        public readonly string $ville,
        public readonly string $email,
        public readonly string $telephone,
        public readonly ?string $rpps_id,
        public readonly ?string $structure_id,
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
            [
                'id'          => $p->specialite->id,
                'libelle'     => $p->specialite->libelle,
                'description' => $p->specialite->description,
            ],
            $p->ville,
            $p->email,
            $p->telephone,
            $p->rppsId,
            $p->structureId,
            $p->accepteNouveauPatient,
        );
    }
}
