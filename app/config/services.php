<?php
declare(strict_types=1);

/*
 * SERVICES : la connexion PDO, les repositories (ports SPI) et les services métier (ports API).
 * Sans autowire, chaque objet est construit À LA MAIN par une fonction (factory) qui
 * demande ses dépendances au conteneur avec $c->get(...).
 * Les clés sont les INTERFACES : on choisit ici quelle implantation se cache derrière.
 */

use Psr\Container\ContainerInterface;
use toubilib\adapters\pdo\PdoPatientRepository;
use toubilib\adapters\pdo\PdoPraticienRepository;
use toubilib\adapters\pdo\PdoRdvRepository;
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\application\ports\api\ServiceRdvInterface;
use toubilib\application\ports\spi\PatientRepositoryInterface;
use toubilib\application\ports\spi\PraticienRepositoryInterface;
use toubilib\application\ports\spi\RdvRepositoryInterface;
use toubilib\application\usecases\ServicePraticien;
use toubilib\application\usecases\ServiceRdv;
use toubilib\application\validators\CreateRdvValidator;

return [
    // Connexion à la base, construite à partir du setting 'db'
    PDO::class => function (ContainerInterface $c): PDO {
        $db = $c->get('db');
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $db['driver'], $db['host'], $db['port'], $db['database']);

        return new PDO($dsn, $db['username'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    },

    // Ports SPI (sortie) → adaptateurs PDO
    PraticienRepositoryInterface::class => fn (ContainerInterface $c) => new PdoPraticienRepository(
        $c->get(PDO::class),
        $c->get(RdvRepositoryInterface::class),
    ),
    PatientRepositoryInterface::class   => fn (ContainerInterface $c) => new PdoPatientRepository($c->get(PDO::class)),
    RdvRepositoryInterface::class       => fn (ContainerInterface $c) => new PdoRdvRepository($c->get(PDO::class)),

    // Validateurs métier
    CreateRdvValidator::class => fn (ContainerInterface $c) => new CreateRdvValidator(
        $c->get(PraticienRepositoryInterface::class),
        $c->get(PatientRepositoryInterface::class),
    ),

    // Ports API (entrée) → cas d'utilisation
    ServicePraticienInterface::class => fn (ContainerInterface $c) => new ServicePraticien(
        $c->get(PraticienRepositoryInterface::class),
    ),
    ServiceRdvInterface::class => fn (ContainerInterface $c) => new ServiceRdv(
        $c->get(RdvRepositoryInterface::class),
        $c->get(PraticienRepositoryInterface::class),
        $c->get(PatientRepositoryInterface::class),
        $c->get(CreateRdvValidator::class),
    ),
];
