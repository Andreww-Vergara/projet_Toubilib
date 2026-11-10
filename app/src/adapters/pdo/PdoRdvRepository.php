<?php
declare(strict_types=1);

namespace toubilib\adapters\pdo;

use DateInterval;
use DateTimeImmutable;
use PDO;
use toubilib\application\ports\spi\RdvRepositoryInterface;
use toubilib\domain\entities\MotifVisite;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\StatutRdv;

final class PdoRdvRepository implements RdvRepositoryInterface
{
    private const SELECT = <<<'SQL'
        SELECT id, praticien_id, patient_id, date_heure_debut, date_heure_fin,
               date_creation, status, duree, motif_visite
        FROM rdv
        SQL;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(string $id): ?RendezVous
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByPraticienAndPeriode(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $sql = self::SELECT . "
            WHERE praticien_id = :pid
              AND date_heure_debut < :fin
              AND COALESCE(date_heure_fin, date_heure_debut + duree * interval '1 minute') > :debut
            ORDER BY date_heure_debut";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'pid'   => $praticienId,
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin'   => $fin->format('Y-m-d H:i:s'),
        ]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(RendezVous $rdv): void
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO rdv (id, praticien_id, patient_id, date_heure_debut, date_heure_fin,
                             date_creation, status, duree, motif_visite)
            VALUES (:id, :praticien_id, :patient_id, :debut, :fin, :creation, :status, :duree, :motif)
            SQL);

        $stmt->execute([
            'id'           => $rdv->id,
            'praticien_id' => $rdv->praticienId,
            'patient_id'   => $rdv->patientId,
            'debut'        => $rdv->dateHeureDebut->format('Y-m-d H:i:s'),
            'fin'          => $rdv->dateHeureFin->format('Y-m-d H:i:s'),
            'creation'     => $rdv->dateCreation?->format('Y-m-d H:i:s'),
            'status'       => $rdv->statut->value,
            'duree'        => $rdv->duree,
            'motif'        => $rdv->motifVisite?->value,
        ]);
    }

    public function update(RendezVous $rdv): void
    {
        // Seul le statut peut changer après la création (entité → BD : statut → status)
        $stmt = $this->pdo->prepare('UPDATE rdv SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $rdv->statut->value,
            'id'     => $rdv->id,
        ]);
    }

    /** Traduit une ligne SQL en entité du domaine (status → statut, strings → dates/enums). */
    private function hydrate(array $row): RendezVous
    {
        $debut = new DateTimeImmutable($row['date_heure_debut']);
        $duree = (int) $row['duree'];

        return new RendezVous(
            id: $row['id'],
            praticienId: $row['praticien_id'],
            patientId: $row['patient_id'],
            dateHeureDebut: $debut,
            // date_heure_fin peut être NULL en base : on la recalcule depuis la durée
            dateHeureFin: $row['date_heure_fin'] !== null
                ? new DateTimeImmutable($row['date_heure_fin'])
                : $debut->add(new DateInterval('PT' . $duree . 'M')),
            dateCreation: $row['date_creation'] !== null ? new DateTimeImmutable($row['date_creation']) : null,
            statut: StatutRdv::from((int) $row['status']),
            duree: $duree,
            motifVisite: $row['motif_visite'] !== null ? MotifVisite::tryFrom($row['motif_visite']) : null,
        );
    }
}
