<?php
declare(strict_types=1);

/*
 * Règles métier portées par l'entité Praticien (TD1.3, exercice 2).
 * 2025-12-08 est un LUNDI, 2025-12-06 un SAMEDI.
 */

use toubilib\domain\entities\MotifVisite;
use toubilib\domain\entities\StatutRdv;
use toubilib\domain\exceptions\PraticienIndisponibleException;
use toubilib\domain\exceptions\RdvInvalideException;

describe('verifierMotif()', function () {

    it('accepte une consultation initiale si le praticien prend de nouveaux patients', function () {
        unPraticien(accepteNouveauPatient: true)->verifierMotif(MotifVisite::CONSULTATION_INITIALE);
    })->throwsNoExceptions();

    it('refuse une consultation initiale si le praticien ne prend plus de nouveaux patients', function () {
        unPraticien(accepteNouveauPatient: false)->verifierMotif(MotifVisite::CONSULTATION_INITIALE);
    })->throws(RdvInvalideException::class, 'nouveaux patients');

    it('accepte toujours consultation et suivi', function (MotifVisite $motif) {
        unPraticien(accepteNouveauPatient: false)->verifierMotif($motif);
    })->throwsNoExceptions()->with([MotifVisite::CONSULTATION, MotifVisite::CONSULTATION_SUIVI]);
});

it('calcule la durée selon le motif', function (MotifVisite $motif, int $attendu) {
    expect(unPraticien()->dureeConsultation($motif))->toBe($attendu);
})->with([
    [MotifVisite::CONSULTATION_INITIALE, 30],
    [MotifVisite::CONSULTATION, 20],
    [MotifVisite::CONSULTATION_SUIVI, 15],
]);

describe('verifierHoraire()', function () {

    it('accepte un RDV entièrement dans une plage', function (string $debut, int $duree) {
        unPraticien()->verifierHoraire(new DateTimeImmutable($debut), $duree);
    })->throwsNoExceptions()->with([
        'ouverture du matin'        => ['2025-12-08 09:00', 30],
        'finit pile à 12:00'        => ['2025-12-08 11:30', 30],
        'reprise de l\'après-midi'  => ['2025-12-08 14:00', 15],
        'finit pile à 19:00'        => ['2025-12-08 18:40', 20],
    ]);

    it('refuse un RDV hors plage ou qui déborde', function (string $debut, int $duree) {
        unPraticien()->verifierHoraire(new DateTimeImmutable($debut), $duree);
    })->throws(RdvInvalideException::class, 'Horaire impossible')->with([
        'trop tôt'                  => ['2025-12-08 08:30', 20],
        'déborde sur midi'          => ['2025-12-08 11:40', 30],
        'pause de midi'             => ['2025-12-08 12:30', 15],
        'déborde après 19:00'       => ['2025-12-08 18:45', 20],
        'le soir'                   => ['2025-12-08 20:00', 15],
    ]);

    it('refuse le week-end', function () {
        unPraticien()->verifierHoraire(new DateTimeImmutable('2025-12-06 10:00'), 20);
    })->throws(RdvInvalideException::class, 'lundi à vendredi');
});

describe('verifierDisponibilite()', function () {

    // Agenda : un RDV le lundi de 10:00 à 10:30
    beforeEach(function () {
        $this->praticien = unPraticien();
        $this->praticien->chargerAgenda([unRdv('2025-12-08 10:00', id: 'existant', duree: 30)]);
    });

    it('refuse une demande qui chevauche un RDV existant', function (string $debut, int $duree) {
        $this->praticien->verifierDisponibilite(unRdv($debut, id: 'demande', duree: $duree));
    })->throws(PraticienIndisponibleException::class, '10:00 à 10:30')->with([
        'commence pendant'   => ['2025-12-08 10:15', 20],
        'finit pendant'      => ['2025-12-08 09:45', 20],
        'englobe'            => ['2025-12-08 09:50', 60],
        'même créneau'       => ['2025-12-08 10:00', 30],
    ]);

    it('accepte une demande juste avant ou juste après', function (string $debut) {
        $this->praticien->verifierDisponibilite(unRdv($debut, id: 'demande', duree: 20));
    })->throwsNoExceptions()->with([
        'finit à 10:00'      => ['2025-12-08 09:40'],
        'commence à 10:30'   => ['2025-12-08 10:30'],
    ]);

    it('ignore les RDV annulés de l\'agenda', function () {
        $praticien = unPraticien();
        $praticien->chargerAgenda([unRdv('2025-12-08 10:00', StatutRdv::ANNULE, 'annule', 30)]);
        $praticien->verifierDisponibilite(unRdv('2025-12-08 10:00', id: 'demande', duree: 30));
    })->throwsNoExceptions();
});
