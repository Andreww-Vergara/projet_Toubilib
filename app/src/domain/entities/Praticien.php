<?php
declare(strict_types=1);

namespace toubilib\domain\entities;

use DateTimeImmutable;
use toubilib\domain\exceptions\PraticienIndisponibleException;
use toubilib\domain\exceptions\RdvInvalideException;

final class Praticien
{
    /** Jours de consultation (format 'N' : 1 = lundi … 7 = dimanche). */
    public const JOURS_OUVRABLES = [1, 2, 3, 4, 5];

    /** Plages de consultation : un RDV doit tenir ENTIÈREMENT dans l'une d'elles. */
    public const PLAGES_HORAIRES = [
        ['09:00', '12:00'],
        ['14:00', '19:00'],
    ];

    /**
     * Agenda du praticien : ses RDV connus. Rempli par le repository (findByIdAvecAgenda)
     * uniquement quand on en a besoin, et seulement pour le jour concerné.
     * @var RendezVous[]
     */
    private array $agenda = [];

    public function __construct(
        public readonly string $id,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $titre,
        public readonly string $ville,
        public readonly string $email,
        public readonly string $telephone,
        public readonly Specialite $specialite,
        public readonly bool $accepteNouveauPatient,
        public readonly ?string $rppsId = null,
        public readonly ?string $structureId = null,
    ) {
    }

    // ---------- Agenda ----------

    /** @param RendezVous[] $rdvs */
    public function chargerAgenda(array $rdvs): void
    {
        $this->agenda = $rdvs;
    }

    /** @return RendezVous[] */
    public function agenda(): array
    {
        return $this->agenda;
    }

    // ---------- Étape 1 : motif et date ----------

    /**
     * Le motif est-il accepté par ce praticien ?
     * La base ne stocke pas de motifs par praticien : la règle retenue est qu'une
     * consultation INITIALE (premier RDV d'un patient) n'est possible que si le praticien
     * accepte de nouveaux patients. Les autres motifs sont acceptés par tous.
     */
    public function verifierMotif(MotifVisite $motif): void
    {
        if ($motif === MotifVisite::CONSULTATION_INITIALE && !$this->accepteNouveauPatient) {
            throw new RdvInvalideException(
                "Ce praticien n'accepte pas de nouveaux patients : consultation initiale impossible"
            );
        }
    }

    /** Durée du RDV (en minutes) selon le motif. */
    public function dureeConsultation(MotifVisite $motif): int
    {
        return $motif->duree();
    }

    /** Jour ouvrable + RDV entièrement compris dans une plage horaire. */
    public function verifierHoraire(DateTimeImmutable $debut, int $duree): void
    {
        if (!in_array((int) $debut->format('N'), self::JOURS_OUVRABLES, true)) {
            throw new RdvInvalideException('Le praticien ne consulte pas ce jour-là (lundi à vendredi uniquement)');
        }

        // Comparaison de chaînes 'HH:MM' : fonctionne car le format est fixe (05:00 < 14:00)
        $heureDebut = $debut->format('H:i');
        $heureFin   = $debut->modify("+$duree minutes")->format('H:i');

        foreach (self::PLAGES_HORAIRES as [$ouverture, $fermeture]) {
            if ($heureDebut >= $ouverture && $heureFin <= $fermeture && $heureFin > $heureDebut) {
                return;
            }
        }

        throw new RdvInvalideException(sprintf(
            'Horaire impossible (%s-%s) : le RDV doit tenir dans les plages %s',
            $heureDebut,
            $heureFin,
            implode(' ou ', array_map(fn ($p) => "$p[0]-$p[1]", self::PLAGES_HORAIRES)),
        ));
    }

    // ---------- Étape 2 : disponibilité ----------

    /** Lève une exception si la demande chevauche un RDV de l'agenda (les RDV annulés ne comptent pas). */
    public function verifierDisponibilite(RendezVous $demande): void
    {
        foreach ($this->agenda as $rdv) {
            if ($rdv->statut !== StatutRdv::ANNULE && $demande->chevauche($rdv)) {
                throw new PraticienIndisponibleException(sprintf(
                    'Le praticien a déjà un rendez-vous de %s à %s',
                    $rdv->dateHeureDebut->format('H:i'),
                    $rdv->dateHeureFin->format('H:i'),
                ));
            }
        }
    }
}
