<?php
declare(strict_types=1);

namespace toubilib\domain\entities;

/** Statut d'un rendez-vous (colonne rdv.status). 0 = prévu : c'est la valeur des données existantes. */
enum StatutRdv: int
{
    case PREVU   = 0;
    case HONORE  = 1;
    case IGNORE  = 2;
    case ANNULE  = -1;
}
