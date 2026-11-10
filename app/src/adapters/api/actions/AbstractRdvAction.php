<?php
declare(strict_types=1);

namespace toubilib\adapters\api\actions;

use toubilib\application\dto\RdvDTO;

/**
 * Base des actions qui retournent UN rendez-vous (détail, création, changements d'état) :
 * elles partagent exactement la même représentation JSON, définie une seule fois ici.
 */
abstract class AbstractRdvAction extends AbstractAction
{
    /** Ressource rdv au format de l'annexe du TD1.4. */
    protected function ressourceRdv(RdvDTO $rdv): array
    {
        $url = '/rdvs/' . $rdv->id;

        $links = [
            'self'      => ['href' => $url],
            'praticien' => ['href' => '/praticiens/' . $rdv->praticien_id],
            'patient'   => ['href' => '/patients/' . $rdv->patient_id],
        ];

        // HATEOAS : on n'annonce que les actions encore possibles. Un RDV annulé, honoré
        // ou ignoré ne peut plus changer d'état, donc ces liens disparaissent.
        if ($rdv->etat === 'PREVU') {
            $links['annuler'] = ['href' => $url . '/annuler'];
            $links['honorer'] = ['href' => $url . '/honorer'];
            $links['ignorer'] = ['href' => $url . '/ignorer'];
        }

        return [
            'type'        => 'ressource',
            'rendez_vous' => get_object_vars($rdv),
            'links'       => $links,
        ];
    }
}
