<?php
declare(strict_types=1);

require __DIR__ . '/../config/autoload.php'; // <-- autoload plan B
require __DIR__ . '/../config/config.php';   // <-- ta config/boot app

use App\controllers\HomeController;
use App\controllers\AuthController;
use App\controllers\DashboardController;
use App\controllers\CreneauController;

$action = $_GET['action'] ?? 'home';

$mustBePost = [
    'signupPost','loginPost',
    'creneauAdd','creneauDelete','creneauReserve',
    'creneauBlock','creneauUnblock','creneauReserveForAdherent',
    'reservationCancel',       // adhérent
    'reservationCancelByCoach' // coach
];

if (in_array($action, $mustBePost, true) && ($_SERVER['REQUEST_METHOD'] !== 'POST')) {
    http_response_code(405);
    header('Allow: POST');
    echo '405 Method Not Allowed';
    exit;
}

try {
    switch ($action) {
        case 'home':           (new HomeController())->index(); break;

        // Auth
        case 'inscription':    (new AuthController())->signupForm(); break;
        case 'signupPost':     (new AuthController())->signupPost(); break;
        case 'connexion':      (new AuthController())->loginForm(); break;
        case 'loginPost':      (new AuthController())->loginPost(); break;
        case 'logout':         (new AuthController())->logout(); break;

        // Dashboards
        case 'adherentDashboard': (new DashboardController())->adherent(); break;
        case 'coachDashboard':    (new DashboardController())->coach(); break;

        // Slots & Reservations
        case 'creneauAdd':                (new CreneauController())->add(); break;
        case 'creneauDelete':             (new CreneauController())->delete(); break;
        case 'creneauReserve':            (new CreneauController())->reserve(); break;
        case 'creneauBlock':              (new CreneauController())->block(); break;
        case 'creneauUnblock':            (new CreneauController())->unblock(); break;
        case 'creneauReserveForAdherent': (new CreneauController())->reserveForAdherent(); break;
        case 'reservationCancel':         (new CreneauController())->cancelReservation(); break;     // adhérent
        case 'reservationCancelByCoach':  (new CreneauController())->cancelByCoach(); break;        // coach

        default:
            http_response_code(404);
            (new HomeController())->notFound();
    }
} catch (Throwable $e) {
    if (defined('IN_DEV') && IN_DEV) throw $e;
    http_response_code(500);
    (new HomeController())->error($e);
}
