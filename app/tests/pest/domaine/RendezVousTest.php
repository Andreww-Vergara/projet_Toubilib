<?php
declare(strict_types=1);

/*
 * Tests du DOMAINE : on teste l'entité RendezVous seule, sans service ni base de données.
 * On fixe "maintenant" pour que les tests ne dépendent pas de la date du jour.
 */

use toubilib\domain\entities\StatutRdv;
use toubilib\domain\exceptions\TransitionStatutInvalideException;

$maintenant = new DateTimeImmutable('2025-12-05 10:00');

describe('annuler()', function () use ($maintenant) {

    it('annule un RDV prévu et futur', function () use ($maintenant) {
        $rdv = unRdv('2025-12-06 09:00');
        $rdv->annuler($maintenant);
        expect($rdv->statut)->toBe(StatutRdv::ANNULE);
    });

    it('refuse d\'annuler un RDV passé', function () use ($maintenant) {
        unRdv('2025-12-04 09:00')->annuler($maintenant);
    })->throws(TransitionStatutInvalideException::class, 'passé');

    it('refuse d\'annuler un RDV en cours (commencé à l\'instant)', function () use ($maintenant) {
        unRdv('2025-12-05 10:00')->annuler($maintenant);
    })->throws(TransitionStatutInvalideException::class);

    it('refuse d\'annuler un RDV déjà annulé, honoré ou ignoré', function (StatutRdv $statut) use ($maintenant) {
        unRdv('2025-12-06 09:00', $statut)->annuler($maintenant);
    })->throws(TransitionStatutInvalideException::class)
      ->with([StatutRdv::ANNULE, StatutRdv::HONORE, StatutRdv::IGNORE]);

    it('ne modifie pas le statut quand l\'annulation est refusée', function () use ($maintenant) {
        $rdv = unRdv('2025-12-04 09:00');
        try {
            $rdv->annuler($maintenant);
        } catch (TransitionStatutInvalideException) {
        }
        expect($rdv->statut)->toBe(StatutRdv::PREVU);
    });
});

describe('honorer() et ignorer()', function () use ($maintenant) {

    it('honore un RDV prévu qui a eu lieu', function () use ($maintenant) {
        $rdv = unRdv('2025-12-05 09:00');
        $rdv->honorer($maintenant);
        expect($rdv->statut)->toBe(StatutRdv::HONORE);
    });

    it('ignore un RDV prévu qui a eu lieu', function () use ($maintenant) {
        $rdv = unRdv('2025-12-05 09:00');
        $rdv->ignorer($maintenant);
        expect($rdv->statut)->toBe(StatutRdv::IGNORE);
    });

    it('refuse d\'honorer un RDV futur', function () use ($maintenant) {
        unRdv('2025-12-06 09:00')->honorer($maintenant);
    })->throws(TransitionStatutInvalideException::class);

    it('refuse d\'ignorer un RDV futur', function () use ($maintenant) {
        unRdv('2025-12-06 09:00')->ignorer($maintenant);
    })->throws(TransitionStatutInvalideException::class);

    it('refuse d\'honorer un RDV annulé', function () use ($maintenant) {
        unRdv('2025-12-05 09:00', StatutRdv::ANNULE)->honorer($maintenant);
    })->throws(TransitionStatutInvalideException::class);
});
