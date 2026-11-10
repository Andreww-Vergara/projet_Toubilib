<?php
declare(strict_types=1);

namespace toubilib\application\dto;

use toubilib\domain\entities\Patient;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;

/**
 * Représentation d'un rendez-vous retournée par l'API (format de l'annexe du TD1.4).
 * Utilisée par toutes les opérations sur UN rdv : détail, création, annuler/honorer/ignorer.
 */
final class RdvDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $praticien_id,
        public readonly ?string $praticien_nom,
        public readonly string $patient_id,
        public readonly ?string $patient_nom,
        public readonly string $date_debut,
        public readonly string $date_fin,
        public readonly ?string $date_creation,
        public readonly int $duree,
        public readonly ?string $motif_visite,
        public readonly string $etat,
    ) {
    }

    /** Praticien et patient servent seulement à afficher leurs noms (null s'ils sont introuvables). */
    public static function fromEntity(RendezVous $r, ?Praticien $praticien = null, ?Patient $patient = null): self
    {
        return new self(
            id: $r->id,
            praticien_id: $r->praticienId,
            praticien_nom: $praticien === null ? null : "$praticien->prenom $praticien->nom",
            patient_id: $r->patientId,
            patient_nom: $patient === null ? null : "$patient->prenom $patient->nom",
            date_debut: $r->dateHeureDebut->format('d/m/Y H:i'),
            date_fin: $r->dateHeureFin->format('d/m/Y H:i'),
            date_creation: $r->dateCreation?->format('d/m/Y'),
            duree: $r->duree,
            motif_visite: $r->motifVisite?->libelle(),
            etat: $r->statut->name,   // PREVU, HONORE, IGNORE, ANNULE
        );
    }
}
