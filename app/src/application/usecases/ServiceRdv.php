<?php
declare(strict_types=1);

namespace toubilib\application\usecases;

use DateTimeImmutable;
use toubilib\application\dto\AgendaRdvDTO;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\CreneauDTO;
use toubilib\application\dto\RdvDTO;
use toubilib\application\ports\api\ServiceRdvInterface;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\RdvRepositoryInterface;
use toubilib\application\validators\CreateRdvValidator;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\StatutRdv;
use toubilib\domain\exceptions\EntityNotFoundException;

final class ServiceRdv implements ServiceRdvInterface
{
    public function __construct(
        private readonly RdvRepositoryInterface $rdvs,
        private readonly PraticienRepositoryInterface $praticiens,
        private readonly PatientRepositoryInterface $patients,
        private readonly CreateRdvValidator $validator,
    ) {
    }

    public function getRdv(string $id): RdvDTO
    {
        return $this->versDTO($this->trouverRdv($id));
    }

    public function getAgenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        return array_map(
            fn (RendezVous $r) => AgendaRdvDTO::fromEntity($r, $this->patients->findById($r->patientId)),
            $this->rdvsDuPraticien($praticienId, $debut, $fin)
        );
    }

    public function getCreneauxOccupes(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        // Un RDV annulé libère son créneau : on ne le renvoie pas
        $occupes = array_filter(
            $this->rdvsDuPraticien($praticienId, $debut, $fin),
            fn (RendezVous $r) => $r->statut !== StatutRdv::ANNULE
        );

        return array_values(array_map(CreneauDTO::fromEntity(...), $occupes));
    }

    public function creerRdv(CreateRdvDTO $dto): RdvDTO
    {
        // 1. Le validateur charge praticien (+ agenda) et patient, et le domaine crée l'entité
        //    → s'il y a un problème, une exception part d'ici et rien n'est enregistré
        $rdv = $this->validator->valider($dto);

        // 2. Persistance   3. retour d'un DTO
        $this->rdvs->save($rdv);

        return $this->versDTO($rdv);
    }

    // Les trois changements d'état suivent le même schéma :
    // retrouver l'entité → le DOMAINE applique la règle → sauvegarder → retourner un DTO

    public function annuler(string $id): RdvDTO
    {
        $rdv = $this->trouverRdv($id);
        $rdv->annuler();
        $this->rdvs->update($rdv);

        return $this->versDTO($rdv);
    }

    public function honorer(string $id): RdvDTO
    {
        $rdv = $this->trouverRdv($id);
        $rdv->honorer();
        $this->rdvs->update($rdv);

        return $this->versDTO($rdv);
    }

    public function ignorer(string $id): RdvDTO
    {
        $rdv = $this->trouverRdv($id);
        $rdv->ignorer();
        $this->rdvs->update($rdv);

        return $this->versDTO($rdv);
    }

    // ---------- outils internes ----------

    private function trouverRdv(string $id): RendezVous
    {
        return $this->rdvs->findById($id)
            ?? throw EntityNotFoundException::of('Rendez-vous', $id);
    }

    /** @return RendezVous[] */
    private function rdvsDuPraticien(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        // Praticien inexistant → 404 (et non une liste vide trompeuse)
        if ($this->praticiens->findById($praticienId) === null) {
            throw EntityNotFoundException::of('Praticien', $praticienId);
        }

        return $this->rdvs->findByPraticienAndPeriode($praticienId, $debut, $fin);
    }

    /** Ajoute les noms du praticien et du patient (format de l'annexe). */
    private function versDTO(RendezVous $rdv): RdvDTO
    {
        return RdvDTO::fromEntity(
            $rdv,
            $this->praticiens->findById($rdv->praticienId),
            $this->patients->findById($rdv->patientId),
        );
    }
}
