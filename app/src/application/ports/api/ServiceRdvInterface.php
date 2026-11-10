<?php
declare(strict_types=1);

namespace toubilib\application\ports\api;

use DateTimeImmutable;
use toubilib\application\dto\AgendaRdvDTO;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\CreneauDTO;
use toubilib\application\dto\RdvDTO;
use toubilib\application\exceptions\ValidationException;
use toubilib\domain\exceptions\EntityNotFoundException;
use toubilib\domain\exceptions\PraticienIndisponibleException;
use toubilib\domain\exceptions\RdvInvalideException;
use toubilib\domain\exceptions\TransitionStatutInvalideException;

interface ServiceRdvInterface
{
    /** @throws EntityNotFoundException si le rendez-vous n'existe pas */
    public function getRdv(string $id): RdvDTO;

    /**
     * Agenda complet (avec patients) d'un praticien sur une période. (fonctionnalité 4)
     * @return AgendaRdvDTO[]
     * @throws EntityNotFoundException si le praticien n'existe pas
     */
    public function getAgenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    /**
     * Créneaux occupés d'un praticien sur une période, RDV annulés exclus. (fonctionnalité 7)
     * @return CreneauDTO[]
     * @throws EntityNotFoundException si le praticien n'existe pas
     */
    public function getCreneauxOccupes(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    /**
     * Crée un RDV (fonctionnalité 5) et retourne le RDV créé.
     * @throws ValidationException            praticien ou patient inconnu (422)
     * @throws RdvInvalideException           date passée, motif refusé, hors horaires (422)
     * @throws PraticienIndisponibleException créneau déjà occupé (409)
     */
    public function creerRdv(CreateRdvDTO $dto): RdvDTO;

    /**
     * Changements d'état d'un RDV (annuler = fonctionnalité 6). Retournent le RDV modifié.
     * @throws EntityNotFoundException           si le rendez-vous n'existe pas (404)
     * @throws TransitionStatutInvalideException si la transition est interdite (409)
     */
    public function annuler(string $id): RdvDTO;

    /** @see annuler() */
    public function honorer(string $id): RdvDTO;

    /** @see annuler() */
    public function ignorer(string $id): RdvDTO;
}
