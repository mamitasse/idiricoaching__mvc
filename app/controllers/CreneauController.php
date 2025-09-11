<?php
declare(strict_types=1);

namespace App\controllers;

use App\core\Controller;
use App\models\Slot;
use App\models\Reservation;
use App\models\User;
use DateTime;

final class CreneauController extends Controller
{
    /** Coach : ajouter un créneau (accepte datetime-local OU date+time) */
    public function add(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Accès coach requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $coachId = (int)$_SESSION['user']['id'];

        // Deux formats possibles :
        $start = trim($_POST['start'] ?? ''); // datetime-local "Y-m-d\TH:i"
        $end   = trim($_POST['end'] ?? '');
        $date       = trim($_POST['date'] ?? '');        // "Y-m-d"
        $start_time = trim($_POST['start_time'] ?? '');  // "HH:ii"
        $end_time   = trim($_POST['end_time'] ?? '');    // "HH:ii"

        if ($date && $start_time && $end_time) {
            $startSql = $date . ' ' . $start_time . ':00';
            $endSql   = $date . ' ' . $end_time . ':00';
        } else {
            $startDT = DateTime::createFromFormat('Y-m-d\TH:i', $start) ?: DateTime::createFromFormat('Y-m-d H:i', $start);
            $endDT   = DateTime::createFromFormat('Y-m-d\TH:i', $end)   ?: DateTime::createFromFormat('Y-m-d H:i', $end);
            if (!$startDT || !$endDT) {
                flash('error','Format de date invalide.');
                $this->redirect(BASE_URL.'?action=coachDashboard');
            }
            $startSql = $startDT->format('Y-m-d H:i:s');
            $endSql   = $endDT->format('Y-m-d H:i:s');
            $date     = substr($startSql, 0, 10);
        }

        if (strtotime($endSql) <= strtotime($startSql)) {
            flash('error','La fin doit être après le début.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
        }

        $slot = new Slot();
        if ($slot->overlaps($coachId, $startSql, $endSql)) {
            flash('error','Chevauchement avec un autre créneau.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
        }

        $slot->add($coachId, $startSql, $endSql);
        flash('success','Créneau ajouté.');
        $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
    }

    /** Coach : supprimer un créneau libre (non réservé) */
    public function delete(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Accès coach requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slotId = (int)($_POST['slot_id'] ?? 0);
        $date   = $_POST['date'] ?? (new DateTime('today'))->format('Y-m-d');
        if ($slotId <= 0) {
            flash('error','Créneau invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
        }

        $slot = new Slot();
        $ok = $slot->deleteIfFree((int)$_SESSION['user']['id'], $slotId);

        flash($ok ? 'success' : 'error', $ok ? 'Créneau supprimé.' : 'Impossible de supprimer : réservé ou inexistant.');
        $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
    }

    /** Coach : bloquer (indispo) un créneau libre */
    public function block(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Accès coach requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slotId = (int)($_POST['slot_id'] ?? 0);
        $date   = $_POST['date'] ?? (new DateTime('today'))->format('Y-m-d');

        $slot = new Slot();
        $ok = $slot->setStatusIfFree((int)$_SESSION['user']['id'], $slotId, 'blocked');

        flash($ok ? 'success' : 'error', $ok ? 'Créneau rendu indisponible.' : 'Impossible de bloquer (réservé ou inexistant).');
        $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
    }

    /** Coach : débloquer (rendre dispo) un créneau libre */
    public function unblock(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Accès coach requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slotId = (int)($_POST['slot_id'] ?? 0);
        $date   = $_POST['date'] ?? (new DateTime('today'))->format('Y-m-d');

        $slot = new Slot();
        $ok = $slot->setStatusIfFree((int)$_SESSION['user']['id'], $slotId, 'available');

        flash($ok ? 'success' : 'error', $ok ? 'Créneau rendu disponible.' : 'Action impossible (réservé ou inexistant).');
        $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
    }

    /** Coach : réserver pour un adhérent */
    public function reserveForAdherent(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Accès coach requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slotId     = (int)($_POST['slot_id'] ?? 0);
        $adherentId = (int)($_POST['adherent_id'] ?? 0);
        $date       = $_POST['date'] ?? (new DateTime('today'))->format('Y-m-d');

        if ($slotId <= 0 || $adherentId <= 0) {
            flash('error','Paramètres invalides.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date).'&adherent='.$adherentId);
        }

        $coachId = (int)$_SESSION['user']['id'];
        $slotM = new Slot();
        $resM  = new Reservation();
        $userM = new User();

        $slot = $slotM->getById($slotId);
        if (!$slot || (int)$slot['coach_id'] !== $coachId) {
            flash('error','Créneau introuvable pour ce coach.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date).'&adherent='.$adherentId);
        }
        if ($resM->existsForSlot($slotId) || ($slot['status'] ?? '') !== 'available') {
            flash('error','Créneau indisponible ou déjà réservé.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date).'&adherent='.$adherentId);
        }

        $adh = $userM->getById($adherentId);
        if (!$adh || ($adh['role'] ?? '') !== 'adherent' || (int)($adh['coach_id'] ?? 0) !== $coachId) {
            flash('error','Cet adhérent n’est pas rattaché à vous.');
            $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode($date));
        }

        $resM->create($slotId, $adherentId);
        flash('success','Créneau réservé pour l’adhérent.');
        $this->redirect(BASE_URL.'?action=coachDashboard&date='.urlencode(substr($slot['start_time'],0,10)).'&adherent='.$adherentId);
    }

    /** Adhérent : réserver pour soi (→ reste sur le même jour) */
    public function reserve(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'adherent')) {
            flash('error','Accès adhérent requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        $slotId = (int)($_POST['slot_id'] ?? 0);
        if ($slotId <= 0) {
            flash('error','Créneau invalide.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        $slotM = new Slot();
        $resM  = new Reservation();
        $slot  = $slotM->getById($slotId);
        if (!$slot) {
            flash('error','Créneau introuvable.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        $myCoachId = (int)($_SESSION['user']['coach_id'] ?? 0);
        if ((int)$slot['coach_id'] !== $myCoachId) {
            flash('error','Ce créneau n’appartient pas à ton coach.');
            $this->redirect(BASE_URL.'?action=adherentDashboard&date='.urlencode(substr($slot['start_time'],0,10)));
        }

        if (($slot['status'] ?? '') !== 'available' || $resM->existsForSlot($slotId)) {
            flash('error','Ce créneau n’est pas disponible.');
            $this->redirect(BASE_URL.'?action=adherentDashboard&date='.urlencode(substr($slot['start_time'],0,10)));
        }

        $resM->create($slotId, (int)$_SESSION['user']['id']);
        flash('success','Réservation confirmée !');
        // 🔁 On retourne sur la même date pour voir la mise à jour
        $this->redirect(BASE_URL.'?action=adherentDashboard&date='.urlencode(substr($slot['start_time'],0,10)));
    }

    /** Adhérent : annuler (36 h avant le début) et rester sur la même date */
    public function cancelReservation(): void
    {
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'adherent')) {
            flash('error','Accès adhérent requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        $resId = (int)($_POST['reservation_id'] ?? 0);
        $resM = new Reservation();
        $res  = $resM->getWithSlot($resId);
        if (!$res || (int)$res['adherent_id'] !== (int)$_SESSION['user']['id']) {
            flash('error','Réservation introuvable.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        $start = new DateTime($res['start_time']);
        $limit = (new DateTime())->modify('+36 hours');
        if ($start <= $limit) {
            flash('error','Annulation impossible à moins de 36 h du créneau.');
            $this->redirect(BASE_URL.'?action=adherentDashboard&date='.urlencode(substr($res['start_time'],0,10)));
        }

        if ($resM->cancel($resId, (int)$_SESSION['user']['id'])) {
            flash('success','Réservation annulée.');
        } else {
            flash('error','Impossible d’annuler (déjà annulée ?).');
        }
        $this->redirect(BASE_URL.'?action=adherentDashboard&date='.urlencode(substr($res['start_time'],0,10)));
    }
}
