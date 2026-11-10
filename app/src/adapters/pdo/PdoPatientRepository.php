<?php
declare(strict_types=1);

namespace toubilib\adapters\pdo;

use DateTimeImmutable;
use PDO;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\domain\entities\Patient;

final class PdoPatientRepository implements PatientRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(string $id): ?Patient
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone 
            FROM patient WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Patient(
            id: $row['id'],
            nom: $row['nom'],
            prenom: $row['prenom'],
            telephone: $row['telephone'],
            dateNaissance: $row['date_naissance'] !== null ? new DateTimeImmutable($row['date_naissance']) : null,
            adresse: $row['adresse'],
            codePostal: $row['code_postal'],
            ville: $row['ville'],
            email: $row['email'],
        );
    }
}