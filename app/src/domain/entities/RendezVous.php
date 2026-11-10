<?php
declare(strict_types=1);

namespace toubilib\domain\entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use toubilib\domain\exceptions\PraticienIndisponibleException;
use toubilib\domain\exceptions\RdvInvalideException;
use toubilib\domain\exceptions\TransitionStatutInvalideException;

final class RendezVous
{
    public function __construct(
        public readonly string $id,
        public readonly string $praticienId,
        public readonly string $patientId,
        public readonly DateTimeImmutable $dateHeureDebut,
        public readonly DateTimeImmutable $dateHeureFin,
        public readonly ?DateTimeImmutable $dateCreation,
        // Lisible de partout, mais modifiable UNIQUEMENT par les méthodes de l'entité (PHP 8.4+).
        public private(set) StatutRdv $statut,
        public readonly int $duree,
        public readonly ?MotifVisite $motifVisite,
    ) {
    }

    /**
     * Validation-création d'un nouveau RDV (fonctionnalité 5).
     * Reçoit des entités qui EXISTENT déjà (chargées par l'application) et applique
     * toutes les règles métier ; si l'une échoue, aucune entité n'est créée.
     *
     * @throws RdvInvalideException           date passée, motif refusé, horaire impossible
     * @throws PraticienIndisponibleException le créneau chevauche un RDV du praticien
     */
    public static function creer(
        Praticien $praticien,
        Patient $patient,
        DateTimeImmutable $debut,
        MotifVisite $motif,
        DateTimeImmutable $maintenant = new DateTimeImmutable(),
    ): self {
        if ($debut <= $maintenant) {
            throw new RdvInvalideException('Impossible de prendre un rendez-vous dans le passé');
        }

        $praticien->verifierMotif($motif);
        $duree = $praticien->dureeConsultation($motif);
        $praticien->verifierHoraire($debut, $duree);

        $rdv = new self(
            id: Uuid::uuid4()->toString(),
            praticienId: $praticien->id,
            patientId: $patient->id,
            dateHeureDebut: $debut,
            dateHeureFin: $debut->modify("+$duree minutes"),
            dateCreation: $maintenant,
            statut: StatutRdv::PREVU,
            duree: $duree,
            motifVisite: $motif,
        );

        // En dernier : on a besoin de la date de FIN, donc du RDV complet
        $praticien->verifierDisponibilite($rdv);

        return $rdv;
    }

    /**
     * Deux RDV [A.debut, A.fin[ et [B.debut, B.fin[ se chevauchent si et seulement si
     *     A.debut < B.fin  ET  B.debut < A.fin
     * (l'un commence avant que l'autre finisse, dans les deux sens).
     * Deux RDV qui se suivent (10:00-10:20 puis 10:20-10:40) ne se chevauchent PAS.
     */
    public function chevauche(RendezVous $autre): bool
    {
        return $this->dateHeureDebut < $autre->dateHeureFin
            && $autre->dateHeureDebut < $this->dateHeureFin;
    }

    /*
     * Méthodes de gestion de l'état. Transitions autorisées :
     *   PREVU → ANNULE   seulement si le RDV n'a pas encore commencé
     *   PREVU → HONORE   seulement si le RDV a commencé (le patient est venu)
     *   PREVU → IGNORE   seulement si le RDV a commencé (le patient n'est pas venu)
     * Tout autre changement lève TransitionStatutInvalideException.
     * $maintenant est paramétrable pour pouvoir tester avec une date fixe.
     */

    public function annuler(DateTimeImmutable $maintenant = new DateTimeImmutable()): void
    {
        $this->exigerStatutPrevu(StatutRdv::ANNULE);

        if ($this->dateHeureDebut <= $maintenant) {
            throw new TransitionStatutInvalideException(
                'Un rendez-vous passé ou en cours ne peut pas être annulé'
            );
        }

        $this->statut = StatutRdv::ANNULE;
    }

    public function honorer(DateTimeImmutable $maintenant = new DateTimeImmutable()): void
    {
        $this->exigerStatutPrevu(StatutRdv::HONORE);
        $this->exigerDejaCommence('honoré', $maintenant);
        $this->statut = StatutRdv::HONORE;
    }

    public function ignorer(DateTimeImmutable $maintenant = new DateTimeImmutable()): void
    {
        $this->exigerStatutPrevu(StatutRdv::IGNORE);
        $this->exigerDejaCommence('ignoré', $maintenant);
        $this->statut = StatutRdv::IGNORE;
    }

    private function exigerStatutPrevu(StatutRdv $cible): void
    {
        if ($this->statut !== StatutRdv::PREVU) {
            throw new TransitionStatutInvalideException(sprintf(
                'Transition impossible : %s → %s (seul un rendez-vous prévu peut changer d\'état)',
                strtolower($this->statut->name),
                strtolower($cible->name),
            ));
        }
    }

    private function exigerDejaCommence(string $action, DateTimeImmutable $maintenant): void
    {
        if ($this->dateHeureDebut > $maintenant) {
            throw new TransitionStatutInvalideException(
                "Un rendez-vous qui n'a pas encore eu lieu ne peut pas être $action"
            );
        }
    }
}
