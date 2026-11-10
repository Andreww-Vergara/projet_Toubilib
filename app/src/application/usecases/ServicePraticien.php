<?php
declare(strict_types=1);

namespace toubilib\application\usecases;

use toubilib\application\dto\PraticienDetailDTO;
use toubilib\application\dto\PraticienDTO;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\domain\exceptions\EntityNotFoundException;

final class ServicePraticien implements ServicePraticienInterface
{
    // On dépend de l'INTERFACE du repository, jamais de PDO : c'est l'inversion de dépendances.
    public function __construct(private readonly PraticienRepositoryInterface $praticiens)
    {
    }

    public function listerPraticiens(): array
    {
        return array_map(PraticienDTO::fromEntity(...), $this->praticiens->findAll());
    }

    public function getPraticien(string $id): PraticienDetailDTO
    {
        $praticien = $this->praticiens->findById($id)
            ?? throw EntityNotFoundException::of('Praticien', $id);

        return PraticienDetailDTO::fromEntity($praticien);
    }
}
