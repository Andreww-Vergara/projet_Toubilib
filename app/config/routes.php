<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use toubilib\adapters\api\actions\CreerRdvAction;
use toubilib\adapters\api\actions\GetAgendaPraticienAction;
use toubilib\adapters\api\actions\GetCreneauxPraticienAction;
use toubilib\adapters\api\actions\GetPraticienAction;
use toubilib\adapters\api\actions\GetRdvAction;
use toubilib\adapters\api\actions\ListerPraticiensAction;

return function (App $app): void {

    // Route de contrôle temporaire : vérifie l'API + la connexion à la BD
    $app->get('/health', function (Request $request, Response $response, PDO $pdo) {
        $counts = [];
        foreach (['specialite', 'praticien', 'patient', 'rdv'] as $table) {
            $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        }

        $response->getBody()->write(json_encode(['status' => 'ok', 'tables' => $counts]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ---------- Praticiens ----------
    // #1 Lister les praticiens
    $app->get('/praticiens', ListerPraticiensAction::class);
    // #2 Détail d'un praticien
    $app->get('/praticiens/{id}', GetPraticienAction::class);
    // #4 Agenda d'un praticien sur une période  (?debut=...&fin=...)
    $app->get('/praticiens/{id}/rdvs', GetAgendaPraticienAction::class);
    // #7 Créneaux occupés d'un praticien        (?debut=...&fin=...)
    $app->get('/praticiens/{id}/creneaux', GetCreneauxPraticienAction::class);

    // ---------- Rendez-vous ----------
    // #3 Détail d'un RDV
    $app->get('/rdvs/{id}', GetRdvAction::class);
    // #5 Créer un RDV
    $app->post('/rdvs', CreerRdvAction::class);
    // #6 Annuler, honorer, ignorer un RDV : même classe d'action, 3 entrées du conteneur (voir api.php)
    $app->patch('/rdvs/{id}/annuler', 'rdv.annuler');
    $app->patch('/rdvs/{id}/honorer', 'rdv.honorer');
    $app->patch('/rdvs/{id}/ignorer', 'rdv.ignorer');
};
