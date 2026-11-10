<?php
declare(strict_types=1);

namespace toubilib\application\dto;

use DateTimeImmutable;
use toubilib\application\exceptions\ValidationException;
use toubilib\domain\entities\MotifVisite;

/**
 * Données de création d'un RDV (fonctionnalité 5), transportées de l'action vers le service.
 *
 * Le constructeur est privé : la SEULE façon d'obtenir un CreateRdvDTO est fromArray(),
 * qui contrôle présence, type et format. Un DTO incomplet ou mal formé ne peut donc pas
 * exister. Ce DTO ne vérifie PAS que le praticien/patient existent : c'est le rôle du
 * validateur métier (il faut la base de données pour ça).
 */
final class CreateRdvDTO
{
    /** Identifiants : lettres, chiffres et tirets uniquement (UUID), 64 caractères max (taille de la colonne). */
    private const ID_REGEX = '/^[A-Za-z0-9-]{1,64}$/';

    /** Formats de date acceptés : '2026-12-01 10:30', '2026-12-01T10:30', avec ou sans secondes. */
    private const FORMATS_DATE = ['Y-m-d H:i', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'];

    private function __construct(
        public readonly string $praticienId,
        public readonly string $patientId,
        public readonly DateTimeImmutable $dateHeureDebut,
        public readonly MotifVisite $motifVisite,
    ) {
    }

    /**
     * @param array<string,mixed> $data corps JSON de la requête
     * @throws ValidationException avec la liste des erreurs, champ par champ
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        $praticienId = self::identifiant($data, 'praticien_id', $errors);
        $patientId   = self::identifiant($data, 'patient_id', $errors);
        $date        = self::date($data, 'date_heure_debut', $errors);
        $motif       = self::motif($data, 'motif_visite', $errors);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self($praticienId, $patientId, $date, $motif);
    }

    private static function identifiant(array $data, string $champ, array &$errors): ?string
    {
        $valeur = $data[$champ] ?? null;

        if (!is_string($valeur) || trim($valeur) === '') {
            $errors[$champ] = 'obligatoire (chaîne de caractères)';
            return null;
        }
        // Protection : on refuse tout caractère qui n'a rien à faire dans un identifiant
        if (!preg_match(self::ID_REGEX, trim($valeur))) {
            $errors[$champ] = 'format invalide (identifiant attendu)';
            return null;
        }

        return trim($valeur);
    }

    private static function date(array $data, string $champ, array &$errors): ?DateTimeImmutable
    {
        $valeur = $data[$champ] ?? null;

        if (!is_string($valeur) || trim($valeur) === '') {
            $errors[$champ] = 'obligatoire (format AAAA-MM-JJ HH:MM)';
            return null;
        }

        foreach (self::FORMATS_DATE as $format) {
            // '!' remet à zéro ce qui n'est pas dans le format (les secondes deviennent 00)
            $date = DateTimeImmutable::createFromFormat('!' . $format, trim($valeur));
            // Le "aller-retour" rejette les dates qui n'existent pas (2026-02-30 deviendrait 03-02)
            if ($date !== false && $date->format($format) === trim($valeur)) {
                return $date;
            }
        }

        $errors[$champ] = 'date invalide (format AAAA-MM-JJ HH:MM)';
        return null;
    }

    private static function motif(array $data, string $champ, array &$errors): ?MotifVisite
    {
        $valeur = $data[$champ] ?? null;
        $motif  = is_string($valeur) ? MotifVisite::tryFrom(strtoupper(trim($valeur))) : null;

        if ($motif === null) {
            $valeurs = implode(', ', array_map(fn (MotifVisite $m) => $m->value, MotifVisite::cases()));
            $errors[$champ] = "obligatoire, valeurs possibles : $valeurs";
        }

        return $motif;
    }
}
