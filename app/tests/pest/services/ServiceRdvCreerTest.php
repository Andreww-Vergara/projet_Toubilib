<?php
declare(strict_types=1);

/*
 * Cas d'utilisation complet "créer un RDV" : DTO → validateur → domaine → repository → DTO.
 * Le service utilise l'heure réelle : on prend donc lundi prochain à 10:00.
 */

use tests\pest\fakes\InMemoryPatientRepository;
use tests\pest\fakes\InMemoryPraticienRepository;
use tests\pest\fakes\InMemoryRdvRepository;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\ValidationException;
use toubilib\application\usecases\ServiceRdv;
use toubilib\application\validators\CreateRdvValidator;
use toubilib\domain\exceptions\PraticienIndisponibleException;

beforeEach(function () {
    $this->lundi = (new DateTimeImmutable('next monday'))->setTime(10, 0);
    $this->rdvs  = new InMemoryRdvRepository(unRdv($this->lundi->format('Y-m-d 14:00'), id: 'existant'));

    $praticiens = new InMemoryPraticienRepository([unPraticien()], $this->rdvs);
    $patients   = new InMemoryPatientRepository(unPatient());
    $this->service = new ServiceRdv($this->rdvs, $praticiens, $patients, new CreateRdvValidator($praticiens, $patients));

    $this->demande = fn (array $modifs = []) => CreateRdvDTO::fromArray([
        'praticien_id'     => 'praticien-1',
        'patient_id'       => 'patient-1',
        'date_heure_debut' => $this->lundi->format('Y-m-d H:i'),
        'motif_visite'     => 'CO',
        ...$modifs,
    ]);
});

it('crée le RDV, l\'enregistre et retourne son DTO', function () {
    $dto = $this->service->creerRdv(($this->demande)());

    expect($dto->etat)->toBe('PREVU')
        ->and($dto->duree)->toBe(20)
        ->and($this->rdvs->nbSaves)->toBe(1)
        ->and($this->rdvs->findById($dto->id))->not->toBeNull();
});

it('refuse un praticien et un patient inconnus (422, les deux signalés)', function () {
    try {
        $this->service->creerRdv(($this->demande)(['praticien_id' => 'inconnu', 'patient_id' => 'inconnu']));
        $this->fail('ValidationException attendue');
    } catch (ValidationException $e) {
        expect($e->getErrors())->toHaveKeys(['praticien_id', 'patient_id']);
    }
});

it('refuse un créneau occupé et n\'enregistre rien', function () {
    $occupe = ($this->demande)(['date_heure_debut' => $this->lundi->format('Y-m-d 14:10')]);

    expect(fn () => $this->service->creerRdv($occupe))->toThrow(PraticienIndisponibleException::class)
        ->and($this->rdvs->nbSaves)->toBe(0);
});
