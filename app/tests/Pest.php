<?php
declare(strict_types=1);

/*
 * Fichier chargé automatiquement par Pest avant les tests.
 * On y met les fonctions utilitaires partagées par tous les tests.
 */

use toubilib\domain\entities\MotifVisite;
use toubilib\domain\entities\Patient;
use toubilib\domain\entities\Praticien;
use toubilib\domain\entities\RendezVous;
use toubilib\domain\entities\Specialite;
use toubilib\domain\entities\StatutRdv;

function unPraticien(bool $accepteNouveauPatient = true, string $id = 'praticien-1'): Praticien
{
    return new Praticien(
        id: $id,
        nom: 'Dupont',
        prenom: 'Marie',
        titre: 'Dr.',
        ville: 'Nancy',
        email: 'marie.dupont@sante.fr',
        telephone: '03 83 00 00 00',
        specialite: new Specialite(1, 'médecine générale'),
        accepteNouveauPatient: $accepteNouveauPatient,
    );
}

function unPatient(string $id = 'patient-1'): Patient
{
    return new Patient(id: $id, nom: 'Martin', prenom: 'Paul', telephone: '06 00 00 00 00');
}

/** Fabrique un RDV de test qui commence à $debut (ex. '+2 days', '-1 hour', '2025-12-05 10:00'). */
function unRdv(
    string $debut = '+2 days',
    StatutRdv $statut = StatutRdv::PREVU,
    string $id = 'rdv-1',
    int $duree = 20,
): RendezVous {
    $dateDebut = new DateTimeImmutable($debut);

    return new RendezVous(
        id: $id,
        praticienId: 'praticien-1',
        patientId: 'patient-1',
        dateHeureDebut: $dateDebut,
        dateHeureFin: $dateDebut->modify("+$duree minutes"),
        dateCreation: new DateTimeImmutable('-10 days'),
        statut: $statut,
        duree: $duree,
        motifVisite: MotifVisite::CONSULTATION,
    );
}
