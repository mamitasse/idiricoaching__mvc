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
    /** Adhérent : calendrier + créneaux du jour (coach choisi) */
    public function adherent(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'adherent')) {
            flash('error','Connecte-toi en tant qu’adhérent.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $user = $_SESSION['user'];
        $coachId = (int)($user['coach_id'] ?? 0);
        if ($coachId <= 0) {
            flash('error','Aucun coach associé à ton compte.');
            $this->redirect(BASE_URL.'?action=home');
        }

        // Lecture des paramètres de navigation calendrier
        $ym = $_GET['ym'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) {
            $ym = date('Y-m');
        }
        [$year, $month] = array_map('intval', explode('-', $ym));

        // Date sélectionnée (par défaut aujourd’hui si même mois, sinon 1er du mois)
        $selectedDate = $_GET['date'] ?? null;
        $today = (new DateTime('today'))->format('Y-m-d');
        if (!$selectedDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || substr($selectedDate, 0, 7) !== $ym) {
            $selectedDate = (substr($today, 0, 7) === $ym) ? $today : sprintf('%s-01', $ym);
        }

        $slotM = new Slot();
        // Auto-génère toute la grille du mois 08:00→20:00
        $slotM->ensureMonthGrid($coachId, $year, $month, 8, 20);

        // Carte des jours avec nb de créneaux disponibles
        $availMap = $slotM->monthAvailabilityMap($coachId, $ym);

        // Créneaux disponibles du jour sélectionné
        $dayAvail = $slotM->availableByDayForCoach($coachId, $selectedDate);

        // Mes réservations (historique)
        $resM = new Reservation();
        $myRes = $resM->forAdherent((int)$user['id']);

        $u = new User();
        $coach = $u->getById($coachId);

        // Navigation mois précédent / suivant
        $cur = new DateTime($ym . '-01');
        $prevYm = $cur->modify('-1 month')->format('Y-m');
        $nextYm = $cur->modify('+2 month')->format('Y-m'); // (on a fait -1 puis +2 => +1 net)

        $this->render('dashboard/adherent', [
            'title'         => 'Mon tableau de bord — Adhérent',
            'ym'            => $ym,
            'prevYm'        => $prevYm,
            'nextYm'        => $nextYm,
            'selectedDate'  => $selectedDate,
            'availability'  => $availMap,
            'availableSlots'=> $dayAvail,
            'reservations'  => $myRes,
            'coach'         => $coach,
        ]);
    }

    /** Coach : calendrier + créneaux du jour (gérer dispo/suppression) */
    public function coach(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Connecte-toi en tant que coach.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $coach = $_SESSION['user'];

        // Lecture des paramètres calendrier
        $ym = $_GET['ym'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) {
            $ym = date('Y-m');
        }
        [$year, $month] = array_map('intval', explode('-', $ym));

        $selectedDate = $_GET['date'] ?? null;
        $today = (new DateTime('today'))->format('Y-m-d');
        if (!$selectedDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || substr($selectedDate, 0, 7) !== $ym) {
            $selectedDate = (substr($today, 0, 7) === $ym) ? $today : sprintf('%s-01', $ym);
        }

        $slotM = new Slot();
        // Auto-génère la grille du mois
        $slotM->ensureMonthGrid((int)$coach['id'], $year, $month, 8, 20);

        // Résumé des slots par jour pour le calendrier (total & réservés)
        $summaryMap = $slotM->monthSlotsSummaryMap((int)$coach['id'], $ym);

        // Créneaux du jour
        $daySlots = $slotM->daySlotsForCoach((int)$coach['id'], $selectedDate);

        // Réservations reçues (liste globale — tu peux filtrer par jour plus tard si tu veux)
        $resM = new Reservation();
        $reservations = $resM->forCoach((int)$coach['id']);

        // Nav mois
        $cur = new DateTime($ym . '-01');
        $prevYm = $cur->modify('-1 month')->format('Y-m');
        $nextYm = $cur->modify('+2 month')->format('Y-m');

        $this->render('dashboard/coach', [
            'title'        => 'Mon tableau de bord — Coach',
            'ym'           => $ym,
            'prevYm'       => $prevYm,
            'nextYm'       => $nextYm,
            'selectedDate' => $selectedDate,
            'summary'      => $summaryMap,
            'slots'        => $daySlots,
            'reservations' => $reservations,
        ]);
    }
}
