<?php
declare(strict_types=1);

/*
 * Tests du SERVICE : on vérifie l'enchaînement findById → annuler → update → DTO.
 * Les repositories sont des faux en mémoire (tests/pest/fakes) : aucune base de données.
 */

use tests\pest\fakes\InMemoryPatientRepository;
use tests\pest\fakes\InMemoryPraticienRepository;
use tests\pest\fakes\InMemoryRdvRepository;
use toubilib\application\dto\RdvDTO;
use toubilib\application\usecases\ServiceRdv;
use toubilib\application\validators\CreateRdvValidator;
use toubilib\domain\entities\StatutRdv;
use toubilib\domain\exceptions\EntityNotFoundException;
use toubilib\domain\exceptions\TransitionStatutInvalideException;

function serviceAvec(InMemoryRdvRepository $rdvs): ServiceRdv
{
    $praticiens = new InMemoryPraticienRepository();
    $patients   = new InMemoryPatientRepository();

    return new ServiceRdv($rdvs, $praticiens, $patients, new CreateRdvValidator($praticiens, $patients));
}

it('annule le RDV, le sauvegarde et retourne un DTO', function () {
    $repo = new InMemoryRdvRepository(unRdv('+2 days', id: 'abc'));

    $dto = serviceAvec($repo)->annuler('abc');

    expect($dto)->toBeInstanceOf(RdvDTO::class)
        ->and($dto->etat)->toBe('ANNULE')
        ->and($repo->nbUpdates)->toBe(1)
        ->and($repo->findById('abc')->statut)->toBe(StatutRdv::ANNULE);
});

it('lève EntityNotFoundException si le RDV n\'existe pas', function () {
    serviceAvec(new InMemoryRdvRepository())->annuler('inconnu');
})->throws(EntityNotFoundException::class);

it('ne sauvegarde rien si l\'annulation est refusée', function () {
    $repo = new InMemoryRdvRepository(unRdv('-2 days', id: 'abc'));

    expect(fn () => serviceAvec($repo)->annuler('abc'))
        ->toThrow(TransitionStatutInvalideException::class)
        ->and($repo->nbUpdates)->toBe(0);
});
