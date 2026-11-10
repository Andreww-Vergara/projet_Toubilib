<?php
declare(strict_types=1);

namespace toubilib\adapters\pdo;

use DateTimeImmutable;
use PDO;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\RdvRepositoryInterface;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\Specialite;

final class PdoPraticienRepository implements PraticienRepositoryInterface
{
    // bit(1) est casté en int : PDO le renverrait sinon sous forme de chaîne '0'/'1'.
    private const SELECT = <<<'SQL'
        SELECT p.id, p.nom, p.prenom, p.titre, p.ville, p.email, p.telephone,
               p.rpps_id, p.structure_id,
               p.nouveau_patient::int AS nouveau_patient,
               s.id AS specialite_id, s.libelle AS specialite_libelle,
               s.description AS specialite_description
        FROM praticien p
        JOIN specialite s ON s.id = p.specialite_id
        SQL;

    public function __construct(
        private readonly PDO $pdo,
        // Pour charger l'agenda on réutilise le repository des RDV (pas de SQL rdv dupliqué ici)
        private readonly RdvRepositoryInterface $rdvs,
    ) {
    }

    public function findByIdAvecAgenda(string $id, DateTimeImmutable $jour): ?Praticien
    {
        $praticien = $this->findById($id);
        if ($praticien === null) {
            return null;
        }

        $debutJour = $jour->setTime(0, 0);
        $praticien->chargerAgenda(
            $this->rdvs->findByPraticienAndPeriode($id, $debutJour, $debutJour->modify('+1 day'))
        );

        return $praticien;
    }

    public function findAll(): array
    {
        $rows = $this->pdo->query(self::SELECT . ' ORDER BY p.nom, p.prenom')->fetchAll();

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(string $id): ?Praticien
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->hydrate($row);
    }

    /** Traduit une ligne SQL en entité du domaine (mapping BD → domaine). */
    private function hydrate(array $row): Praticien
    {
        return new Praticien(
            id: $row['id'],
            nom: $row['nom'],
            prenom: $row['prenom'],
            titre: $row['titre'],
            ville: $row['ville'],
            email: $row['email'],
            telephone: $row['telephone'],
            specialite: new Specialite(
                (int) $row['specialite_id'],
                $row['specialite_libelle'],
                $row['specialite_description'],
            ),
            accepteNouveauPatient: (int) $row['nouveau_patient'] === 1,
            rppsId: $row['rpps_id'],
            structureId: $row['structure_id'],
        );
    }
}
