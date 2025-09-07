<?php
declare(strict_types=1);

namespace App\controllers;

use App\core\Controller;
use App\models\Slot;
use App\models\Reservation;
use DateTime;

final class CreneauController extends Controller
{
    /** Coach : ajouter manuellement un créneau (optionnel, pour exceptions) */
    public function add(): void
    {
        // Accès coach uniquement
        if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'coach')) {
            flash('error','Accès coach requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }
        if (!csrf_verify($_POST['_token'] ?? null)) {
            flash('error','CSRF invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $start = trim($_POST['start'] ?? '');
        $end   = trim($_POST['end'] ?? '');

        // Parse <input type="datetime-local">  (Y-m-d\TH:i) ou fallback "Y-m-d H:i"
        $startDT = DateTime::createFromFormat('Y-m-d\TH:i', $start) ?: DateTime::createFromFormat('Y-m-d H:i', $start);
        $endDT   = DateTime::createFromFormat('Y-m-d\TH:i', $end)   ?: DateTime::createFromFormat('Y-m-d H:i', $end);

        if (!$startDT || !$endDT) {
            flash('error','Format de date invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }
        if ($endDT <= $startDT) {
            flash('error','La fin doit être après le début.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $startSql = $startDT->format('Y-m-d H:i:s');
        $endSql   = $endDT->format('Y-m-d H:i:s');

        $slot = new Slot();
        $coachId = (int)$_SESSION['user']['id'];

        if ($slot->overlaps($coachId, $startSql, $endSql)) {
            flash('error','Chevauchement avec un autre créneau.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slot->add($coachId, $startSql, $endSql);
        flash('success','Créneau ajouté.');
        $this->redirect(BASE_URL.'?action=coachDashboard');
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
        if ($slotId <= 0) {
            flash('error','Créneau invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slot = new Slot();
        $ok = $slot->deleteIfFree((int)$_SESSION['user']['id'], $slotId);

        if ($ok) {
            flash('success','Créneau supprimé.');
        } else {
            flash('error','Impossible de supprimer : créneau réservé ou inexistant.');
        }
        $this->redirect(BASE_URL.'?action=coachDashboard');
    }

    /** Adhérent : réserver un créneau disponible chez SON coach */
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

        // Sécurité : l’adhérent ne réserve que chez SON coach
        $myCoachId = (int)($_SESSION['user']['coach_id'] ?? 0);
        if ((int)$slot['coach_id'] !== $myCoachId) {
            flash('error','Ce créneau n’appartient pas à ton coach.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        // Doit être disponible et non réservé
        if (($slot['status'] ?? '') !== 'available') {
            flash('error','Ce créneau n’est pas disponible.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }
        if ($resM->existsForSlot($slotId)) {
            flash('error','Créneau déjà réservé.');
            $this->redirect(BASE_URL.'?action=adherentDashboard');
        }

        $resM->create($slotId, (int)$_SESSION['user']['id']);
        flash('success','Réservation confirmée !');
        $this->redirect(BASE_URL.'?action=adherentDashboard');
    }

    /** Coach : rendre un créneau indisponible (bloquer) s’il n’est pas réservé */
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
        if ($slotId <= 0) {
            flash('error','Créneau invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slot = new Slot();
        $ok = $slot->setStatusIfFree((int)$_SESSION['user']['id'], $slotId, 'blocked');

        if ($ok) {
            flash('success','Créneau rendu indisponible.');
        } else {
            flash('error','Impossible de bloquer (créneau réservé ou inexistant).');
        }
        $this->redirect(BASE_URL.'?action=coachDashboard');
    }

    /** Coach : rendre un créneau disponible (débloquer) s’il n’est pas réservé */
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
        if ($slotId <= 0) {
            flash('error','Créneau invalide.');
            $this->redirect(BASE_URL.'?action=coachDashboard');
        }

        $slot = new Slot();
        $ok = $slot->setStatusIfFree((int)$_SESSION['user']['id'], $slotId, 'available');

        if ($ok) {
            flash('success','Créneau rendu disponible.');
        } else {
            flash('error','Action impossible (créneau réservé ou inexistant).');
        }
        $this->redirect(BASE_URL.'?action=coachDashboard');
    }
}
