<?php
declare(strict_types=1);

/*
 * TD1.4 : détail d'un RDV au format de l'annexe, honorer / ignorer, créneaux occupés.
 */

use tests\pest\fakes\InMemoryPatientRepository;
use tests\pest\fakes\InMemoryPraticienRepository;
use tests\pest\fakes\InMemoryRdvRepository;
use toubilib\application\dto\CreneauDTO;
use toubilib\application\usecases\ServiceRdv;
use toubilib\application\validators\CreateRdvValidator;
use toubilib\domain\entities\StatutRdv;
use toubilib\domain\exceptions\EntityNotFoundException;
use toubilib\domain\exceptions\TransitionStatutInvalideException;

beforeEach(function () {
    $this->rdvs = new InMemoryRdvRepository(
        unRdv('2025-12-08 10:00', id: 'passe'),                       // a eu lieu
        unRdv('2025-12-08 11:00', StatutRdv::ANNULE, 'annule'),       // annulé
        unRdv('2025-12-08 14:00', id: 'apres-midi', duree: 30),
    );
    $praticiens = new InMemoryPraticienRepository([unPraticien()], $this->rdvs);
    $patients   = new InMemoryPatientRepository(unPatient());
    $this->service = new ServiceRdv($this->rdvs, $praticiens, $patients, new CreateRdvValidator($praticiens, $patients));
});

it('retourne le détail au format de l\'annexe (noms, dates jj/mm/aaaa, libellé, état)', function () {
    $rdv = $this->service->getRdv('passe');

    expect($rdv->praticien_nom)->toBe('Marie Dupont')
        ->and($rdv->patient_nom)->toBe('Paul Martin')
        ->and($rdv->date_debut)->toBe('08/12/2025 10:00')
        ->and($rdv->motif_visite)->toBe('Consultation')
        ->and($rdv->etat)->toBe('PREVU');
});

it('honore et ignore un RDV passé, et enregistre le changement', function () {
    expect($this->service->honorer('passe')->etat)->toBe('HONORE')
        ->and($this->rdvs->findById('passe')->statut)->toBe(StatutRdv::HONORE)
        ->and($this->rdvs->nbUpdates)->toBe(1);
});

it('refuse d\'ignorer un RDV déjà honoré', function () {
    $this->service->honorer('passe');
    $this->service->ignorer('passe');
})->throws(TransitionStatutInvalideException::class);

it('lève 404 pour honorer un RDV inexistant', function () {
    $this->service->honorer('inconnu');
})->throws(EntityNotFoundException::class);

describe('getCreneauxOccupes()', function () {

    it('retourne seulement les heures, sans les RDV annulés', function () {
        $creneaux = $this->service->getCreneauxOccupes(
            'praticien-1', new DateTimeImmutable('2025-12-08 00:00'), new DateTimeImmutable('2025-12-08 23:59')
        );

        expect($creneaux)->toHaveCount(2)
            ->and($creneaux[0])->toBeInstanceOf(CreneauDTO::class)
            ->and(get_object_vars($creneaux[1]))->toBe([
                'date_debut' => '2025-12-08T14:00:00',
                'date_fin'   => '2025-12-08T14:30:00',
            ]);
    });

    it('lève 404 si le praticien n\'existe pas', function () {
        $this->service->getCreneauxOccupes('inconnu', new DateTimeImmutable(), new DateTimeImmutable('+1 day'));
    })->throws(EntityNotFoundException::class);
});
