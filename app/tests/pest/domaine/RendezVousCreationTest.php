<?php
declare(strict_types=1);

/*
 * RendezVous::chevauche() et la validation-création RendezVous::creer() (TD1.3).
 * "Maintenant" est fixé au lundi 1er décembre 2025 ; le RDV est demandé le lundi 8.
 */

use toubilib\domain\entities\MotifVisite;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\StatutRdv;
use toubilib\domain\exceptions\PraticienIndisponibleException;
use toubilib\domain\exceptions\RdvInvalideException;

$maintenant = new DateTimeImmutable('2025-12-01 08:00');

describe('chevauche()', function () {

    it('détecte le chevauchement dans les deux sens', function () {
        $a = unRdv('2025-12-08 10:00', id: 'a', duree: 30);   // 10:00-10:30
        $b = unRdv('2025-12-08 10:20', id: 'b', duree: 20);   // 10:20-10:40
        expect($a->chevauche($b))->toBeTrue()
            ->and($b->chevauche($a))->toBeTrue();
    });

    it('considère que deux RDV qui se suivent ne se chevauchent pas', function () {
        $a = unRdv('2025-12-08 10:00', id: 'a', duree: 20);   // 10:00-10:20
        $b = unRdv('2025-12-08 10:20', id: 'b', duree: 20);   // 10:20-10:40
        expect($a->chevauche($b))->toBeFalse()
            ->and($b->chevauche($a))->toBeFalse();
    });
});

describe('creer()', function () use ($maintenant) {

    it('crée un RDV prévu avec la durée et la fin calculées', function () use ($maintenant) {
        $rdv = RendezVous::creer(
            unPraticien(), unPatient(),
            new DateTimeImmutable('2025-12-08 10:00'), MotifVisite::CONSULTATION_INITIALE, $maintenant,
        );

        expect($rdv->statut)->toBe(StatutRdv::PREVU)
            ->and($rdv->duree)->toBe(30)
            ->and($rdv->dateHeureFin->format('H:i'))->toBe('10:30')
            ->and($rdv->praticienId)->toBe('praticien-1')
            ->and($rdv->patientId)->toBe('patient-1')
            ->and($rdv->dateCreation)->toEqual($maintenant)
            ->and($rdv->id)->toMatch('/^[0-9a-f-]{36}$/');   // UUID généré
    });

    it('refuse une date passée', function () use ($maintenant) {
        RendezVous::creer(unPraticien(), unPatient(),
            new DateTimeImmutable('2025-11-24 10:00'), MotifVisite::CONSULTATION, $maintenant);
    })->throws(RdvInvalideException::class, 'passé');

    it('refuse un motif non accepté par le praticien', function () use ($maintenant) {
        RendezVous::creer(unPraticien(accepteNouveauPatient: false), unPatient(),
            new DateTimeImmutable('2025-12-08 10:00'), MotifVisite::CONSULTATION_INITIALE, $maintenant);
    })->throws(RdvInvalideException::class, 'nouveaux patients');

    it('refuse un horaire hors des plages', function () use ($maintenant) {
        RendezVous::creer(unPraticien(), unPatient(),
            new DateTimeImmutable('2025-12-08 12:30'), MotifVisite::CONSULTATION, $maintenant);
    })->throws(RdvInvalideException::class, 'Horaire');

    it('refuse un créneau déjà occupé', function () use ($maintenant) {
        $praticien = unPraticien();
        $praticien->chargerAgenda([unRdv('2025-12-08 10:00', id: 'existant', duree: 20)]);

        RendezVous::creer($praticien, unPatient(),
            new DateTimeImmutable('2025-12-08 10:10'), MotifVisite::CONSULTATION_SUIVI, $maintenant);
    })->throws(PraticienIndisponibleException::class);
});
