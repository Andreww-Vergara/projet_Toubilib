<?php
declare(strict_types=1);

/*
 * Contrôles "HTTP" du DTO : présence, type, format, protection.
 * Aucune règle métier ici (le DTO ne sait pas si le praticien existe).
 */

use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\ValidationException;
use toubilib\domain\entities\MotifVisite;

$valide = [
    'praticien_id'     => '4305f5e9-be5a-4ccf-8792-7e07d7017363',
    'patient_id'       => 'da362a38-c1bf-39ed-8b2e-3bc99a86cfde',
    'date_heure_debut' => '2026-12-07 10:30',
    'motif_visite'     => 'CO',
];

/** Retourne les erreurs levées par fromArray($data), ou [] si aucune. */
function erreursDe(array $data): array
{
    try {
        CreateRdvDTO::fromArray($data);
        return [];
    } catch (ValidationException $e) {
        return $e->getErrors();
    }
}

it('construit un DTO typé à partir de données correctes', function () use ($valide) {
    $dto = CreateRdvDTO::fromArray($valide);

    expect($dto->praticienId)->toBe($valide['praticien_id'])
        ->and($dto->dateHeureDebut->format('Y-m-d H:i:s'))->toBe('2026-12-07 10:30:00')
        ->and($dto->motifVisite)->toBe(MotifVisite::CONSULTATION);
});

it('accepte le format ISO avec T et un motif en minuscules entouré d\'espaces', function () use ($valide) {
    $dto = CreateRdvDTO::fromArray([...$valide, 'date_heure_debut' => '2026-12-07T10:30:00', 'motif_visite' => ' ci ']);
    expect($dto->motifVisite)->toBe(MotifVisite::CONSULTATION_INITIALE);
});

it('signale TOUS les champs manquants en une seule fois', function () {
    expect(array_keys(erreursDe([])))
        ->toEqual(['praticien_id', 'patient_id', 'date_heure_debut', 'motif_visite']);
});

it('rejette les mauvais types', function () use ($valide) {
    expect(erreursDe([...$valide, 'praticien_id' => 42, 'patient_id' => ['x']]))
        ->toHaveKeys(['praticien_id', 'patient_id']);
});

it('rejette un identifiant contenant des caractères dangereux', function () use ($valide) {
    expect(erreursDe([...$valide, 'patient_id' => "x' OR '1'='1"]))->toHaveKey('patient_id')
        ->and(erreursDe([...$valide, 'patient_id' => '<script>']))->toHaveKey('patient_id');
});

it('rejette une date mal formée ou inexistante', function (string $date) use ($valide) {
    expect(erreursDe([...$valide, 'date_heure_debut' => $date]))->toHaveKey('date_heure_debut');
})->with(['07/12/2026 10:30', '2026-12-07', 'demain', '2026-02-30 10:00', '2026-12-07 25:00']);

it('rejette un motif inconnu en indiquant les valeurs possibles', function () use ($valide) {
    expect(erreursDe([...$valide, 'motif_visite' => 'C0'])['motif_visite'])->toContain('CI, CO, CS');
});
