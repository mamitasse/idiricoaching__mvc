<?php
/** Variables :
 * @var string $userName
 * @var string $coachName
 * @var string $todayDate
 * @var string $selectedDate
 * @var array  $availableSlots
 * @var array  $reservations
 */
function timeHM(string $sqlDT): string { return (new DateTime($sqlDT))->format('H:i'); }
function dmy(string $ymd): string { return (new DateTime($ymd))->format('d/m/Y'); }
?>
<section class="card">
  <h1>Bonjour <?= e($userName) ?></h1>
  <p>Nous sommes le <?= e($todayDate) ?> — Ton coach : <strong><?= e($coachName) ?></strong></p>
</section>

<section class="card">
  <form method="get" action="" class="form" style="display:grid; grid-template-columns:1fr; gap:12px; max-width:360px;">
    <input type="hidden" name="action" value="adherentDashboard">
    <label>
      Sélectionner une date
      <input type="date" name="date" value="<?= e($selectedDate) ?>" onchange="this.form.submit()">
    </label>
  </form>
</section>

<section class="card">
  <h2>Créneaux disponibles — <?= e(dmy($selectedDate)) ?> (coach <?= e($coachName) ?>)</h2>

  <div class="slots-container">
    <?php if (empty($availableSlots)): ?>
      <p>Aucun créneau disponible pour cette journée.</p>
    <?php else: ?>
      <?php foreach ($availableSlots as $s): ?>
        <div class="slot available-slot">
          <div class="slot-time"><?= e(timeHM($s['start_time'])) ?> - <?= e(timeHM($s['end_time'])) ?></div>
          <div class="slot-actions">
            <form class="inline" method="post" action="<?= BASE_URL ?>?action=creneauReserve">
              <?= csrf_input() ?>
              <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-primary" type="submit">Réserver</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="card">
  <h2>Mes réservations</h2>
  <?php if (empty($reservations)): ?>
    <p>Aucune réservation.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Heure</th><th>Coach</th><th>Statut</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($reservations as $r): ?>
        <?php
          $start = new DateTime($r['start_time']);
          $date  = $start->format('d/m/Y');
          $heure = $start->format('H:i').' - '.(new DateTime($r['end_time']))->format('H:i');
          $canCancel = ($start > (new DateTime())->modify('+36 hours')) && ($r['status'] !== 'cancelled');
        ?>
        <tr>
          <td><?= e($date) ?></td>
          <td><?= e($heure) ?></td>
          <td><?= e($r['coach_first'].' '.$r['coach_last']) ?></td>
          <td><?= e($r['status']) ?><?= ((int)$r['paid']===1?' (payé)':'') ?></td>
          <td>
            <?php if ($canCancel): ?>
              <form method="post" action="<?= BASE_URL ?>?action=reservationCancel" class="inline"
                    onsubmit="return confirm('Confirmer l’annulation ?');">
                <?= csrf_input() ?>
                <input type="hidden" name="reservation_id" value="<?= (int)$r['id'] ?>">
                <button class="btn" type="submit">Annuler</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
