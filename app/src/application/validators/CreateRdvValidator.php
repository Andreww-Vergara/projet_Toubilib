<?php
declare(strict_types=1);

namespace toubilib\application\validators;

use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\ValidationException;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\domain\entities\RendezVous;

/**
 * Validateur MÉTIER d'une demande de RDV.
 * Le DTO garantit déjà que les données sont complètes et bien formées ; ici on vérifie
 * ce qui nécessite la base de données et les règles métier :
 *   1. le praticien et le patient existent        → ValidationException (422)
 *   2. motif, horaire et disponibilité (domaine)   → RdvInvalideException (422) / PraticienIndisponibleException (409)
 * Si tout est correct, il retourne l'entité RendezVous prête à être enregistrée.
 */
final class CreateRdvValidator
{
    public function __construct(
        private readonly PraticienRepositoryInterface $praticiens,
        private readonly PatientRepositoryInterface $patients,
    ) {
    }

    public function valider(CreateRdvDTO $dto): RendezVous
    {
        // Le praticien est chargé AVEC son agenda du jour demandé (nécessaire pour la disponibilité)
        $praticien = $this->praticiens->findByIdAvecAgenda($dto->praticienId, $dto->dateHeureDebut);
        $patient   = $this->patients->findById($dto->patientId);

        $errors = [];
        if ($praticien === null) {
            $errors['praticien_id'] = 'praticien inconnu';
        }
        if ($patient === null) {
            $errors['patient_id'] = 'patient inconnu';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // Les règles métier sont dans le DOMAINE : le validateur se contente de les déclencher
        return RendezVous::creer($praticien, $patient, $dto->dateHeureDebut, $dto->motifVisite);
    }
}
