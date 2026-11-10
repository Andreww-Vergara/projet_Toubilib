<?php
declare(strict_types=1);

namespace toubilib\application\ports\api;

use toubilib\application\dto\PraticienDetailDTO;
use toubilib\application\dto\PraticienDTO;
use toubilib\domain\exceptions\EntityNotFoundException;

interface ServicePraticienInterface
{
    /** @return PraticienDTO[] */
    public function listerPraticiens(): array;

    /** @throws EntityNotFoundException si le praticien n'existe pas */
    public function getPraticien(string $id): PraticienDetailDTO;
}
