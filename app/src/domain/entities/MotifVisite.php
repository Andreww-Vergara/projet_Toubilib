<?php
declare(strict_types=1);

namespace toubilib\domain\entities;

/**
 * Motifs de visite communs à tous les praticiens.
 * ATTENTION : dans les données le code de la consultation est 'CO' (lettre O), pas 'C0' (zéro).
 */
enum MotifVisite: string
{
    case CONSULTATION_INITIALE = 'CI'; // 30 mn
    case CONSULTATION          = 'CO'; // 20 mn
    case CONSULTATION_SUIVI    = 'CS'; // 15 mn

    public function libelle(): string
    {
        return match ($this) {
            self::CONSULTATION_INITIALE => 'Consultation initiale',
            self::CONSULTATION          => 'Consultation',
            self::CONSULTATION_SUIVI    => 'Consultation de suivi',
        };
    }

    public function duree(): int
    {
        return match ($this) {
            self::CONSULTATION_INITIALE => 30,
            self::CONSULTATION          => 20,
            self::CONSULTATION_SUIVI    => 15,
        };
    }
}
