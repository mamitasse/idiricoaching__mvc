<?php
declare(strict_types=1);

namespace App\controllers;

use App\core\Controller;
use App\models\Slot;
use App\models\Reservation;
use App\models\User;
use DateTime;

final class DashboardController extends Controller
{
    /** Tableau de bord ADHÉRENT — style “React-like” (date picker + cartes de créneaux) */
    public function adherent(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'adherent')) {
            flash('error','Connecte-toi en tant qu’adhérent.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $adh = $_SESSION['user'];
        $coachId = (int)($adh['coach_id'] ?? 0);
        if ($coachId <= 0) {
            flash('error','Aucun coach associé à ton compte.');
            $this->redirect(BASE_URL.'?action=home');
        }

        // Date sélectionnée (YYYY-MM-DD)
        $selectedDate = $_GET['date'] ?? (new DateTime('today'))->format('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = (new DateTime('today'))->format('Y-m-d');
        }

        // Auto-génère la grille du MOIS de la date sélectionnée (08h→20h, pas 1h)
        [$y,$m] = array_map('intval', explode('-', substr($selectedDate,0,7)));
        $slotM = new Slot();
        $slotM->ensureMonthGrid($coachId, $y, $m, 8, 20);

        // Créneaux disponibles pour ce jour chez SON coach
        $available = $slotM->availableByDayForCoach($coachId, $selectedDate);

        // Optionnel : si on est le jour même, on peut filtrer les créneaux déjà passés
        $today = (new DateTime('today'))->format('Y-m-d');
        if ($selectedDate === $today) {
            $now = new DateTime();
            $available = array_values(array_filter($available, function(array $s) use ($now) {
                return (new DateTime($s['start_time'])) >= $now;
            }));
        }

        // Mes réservations (historique + futur)
        $resM = new Reservation();
        $myReservations = $resM->forAdherent((int)$adh['id']);

        // Infos coach + header
        $u = new User();
        $coach = $u->getById($coachId);

        $this->render('dashboard/adherent', [
            'title'         => 'Mon tableau de bord — Adhérent',
            'userName'      => $adh['first_name'].' '.$adh['last_name'],
            'coachName'     => $coach ? ($coach['first_name'].' '.$coach['last_name']) : '—',
            'todayDate'     => (new DateTime('today'))->format('d/m/Y'),
            'selectedDate'  => $selectedDate,
            'availableSlots'=> $available,
            'reservations'  => $myReservations,
        ]);
    }

    /** Tableau de bord COACH — style “React-like” (déjà fourni précédemment) */
    public function coach(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Connecte-toi en tant que coach.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $coach = $_SESSION['user'];
        $coachId = (int)$coach['id'];

        // Paramètres UI
        $selectedDate = $_GET['date'] ?? (new DateTime('today'))->format('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = (new DateTime('today'))->format('Y-m-d');
        }
        $selectedAdherent = isset($_GET['adherent']) && ctype_digit($_GET['adherent']) ? (int)$_GET['adherent'] : 0;

        // Auto-génère la grille du mois de la date sélectionnée
        [$y,$m] = array_map('intval', explode('-', substr($selectedDate,0,7)));
        $slotM = new Slot();
        $slotM->ensureMonthGrid($coachId, $y, $m, 8, 20);

        // Créneaux du jour
        $daySlots = $slotM->daySlotsForCoach($coachId, $selectedDate);

        // Adhérents rattachés
        $u = new User();
        $adherents = $u->adherentsOfCoach($coachId);

        // Réservations
        $resM = new Reservation();
        $reservations = $resM->forCoach($coachId);
        $reservedSlotsForAdh = [];
        if ($selectedAdherent > 0) {
            $reservedSlotsForAdh = $resM->reservedSlotsForAdherent($coachId, $selectedAdherent);
        }

        $coachName = $coach['first_name'].' '.$coach['last_name'];
        $todayDate = (new DateTime('today'))->format('d/m/Y');

        $this->render('dashboard/coach', [
            'title'          => 'Mon tableau de bord — Coach',
            'coachName'      => $coachName,
            'todayDate'      => $todayDate,
            'selectedDate'   => $selectedDate,
            'adherents'      => $adherents,
            'selectedAdh'    => $selectedAdherent,
            'slots'          => $daySlots,
            'reservedForAdh' => $reservedSlotsForAdh,
            'reservations'   => $reservations,
        ]);
    }
}
