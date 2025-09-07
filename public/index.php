<?php
declare(strict_types=1);

/**
 * Front Controller — point d’entrée unique
 * - Charge la config (session, autoload, helpers, DB consts, BASE_URL)
 * - Lit ?action=...
 * - Protège les actions qui doivent être en POST
 * - Route vers le bon contrôleur
 */

require __DIR__ . '/../config/config.php';

use App\controllers\HomeController;
use App\controllers\AuthController;
use App\controllers\DashboardController;
use App\controllers\CreneauController;

/* Action demandée (défaut = home) */
$action = $_GET['action'] ?? 'home';

/* Actions qui DOIVENT être appelées en POST */
$mustBePost = [
    'signupPost',
    'loginPost',
    'creneauAdd',
    'creneauDelete',
    'creneauReserve',
];

/* Méthode incorrecte → 405 */
if (in_array($action, $mustBePost, true) && ($_SERVER['REQUEST_METHOD'] !== 'POST')) {
    http_response_code(405);
    header('Allow: POST');
    echo '405 Method Not Allowed';
    exit;
}

try {
    switch ($action) {
        /* --------- Pages publiques --------- */
        case 'home':
            (new HomeController())->index();
            break;

        /* --------- Authentification --------- */
        case 'inscription':
            (new AuthController())->signupForm();
            break;

        case 'signupPost': // POST
            (new AuthController())->signupPost();
            break;

        case 'connexion':
            (new AuthController())->loginForm();
            break;

        case 'loginPost':  // POST
            (new AuthController())->loginPost();
            break;

        case 'logout':
            (new AuthController())->logout();
            break;

        /* --------- Dashboards --------- */
        case 'adherentDashboard':
            (new DashboardController())->adherent();
            break;

        case 'coachDashboard':
            (new DashboardController())->coach();
            break;

        /* --------- Créneaux --------- */
        case 'creneauAdd':     // POST (coach)
            (new CreneauController())->add();
            break;

        case 'creneauDelete':  // POST (coach)
            (new CreneauController())->delete();
            break;

        case 'creneauReserve': // POST (adhérent)
            (new CreneauController())->reserve();
            break;

        /* --------- 404 --------- */
        default:
            http_response_code(404);
            (new HomeController())->notFound();
    }

} catch (Throwable $e) {
    // En dev on laisse remonter l’exception pour debug
    if (defined('IN_DEV') && IN_DEV) {
        throw $e;
    }
    // En prod : page 500 propre
    http_response_code(500);
    (new HomeController())->error($e);
}
