<?php
/** @var string $ym */
/** @var string $prevYm */
/** @var string $nextYm */
/** @var string $selectedDate */
/** @var array  $availability (['YYYY-MM-DD' => int]) */
/** @var array  $availableSlots */
/** @var array  $reservations */
/** @var array|null $coach */

function fdt(string $sqlDT): string { return (new DateTime($sqlDT))->format('d/m/Y H:i'); }
function dmy(string $ymd): string { return (new DateTime($ymd))->format('d/m/Y'); }

$months = [1=>'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
[$y,$m] = array_map('intval', explode('-', $ym));
$monthTitle = $months[$m] . ' ' . $y;

$first = new DateTime($ym . '-01');
$daysInMonth = (int)$first->format('t');
$startDow = (int)$first->format('N'); // 1=Mon..7=Sun
$today = (new DateTime('today'))->format('Y-m-d');
?>
<section class="card">
  <div class="cal-header">
    <a class="btn" href="<?= BASE_URL ?>?action=adherentDashboard&ym=<?= e($prevYm) ?>">←</a>
    <h2><?= e($monthTitle) ?></h2>
    <a class="btn" href="<?= BASE_URL ?>?action=adherentDashboard&ym=<?= e($nextYm) ?>">→</a>
  </div>

  <div class="cal-grid cal-head">
    <div>Lu</div><div>Ma</div><div>Me</div><div>Je</div><div>Ve</div><div>Sa</div><div>Di</div>
  </div>

  <div class="cal-grid">
    <?php for ($i=1; $i<$startDow; $i++): ?>
      <div class="cal-day empty"></div>
    <?php endfor; ?>

    <?php for ($d=1; $d<=$daysInMonth; $d++): 
      $day = sprintf('%s-%02d', $ym, $d);
      $cnt = $availability[$day] ?? 0;
      $classes = ['cal-day'];
      if ($day === $today) $classes[] = 'today';
      if ($day === $selectedDate) $classes[] = 'selected';
      if ($cnt > 0) $classes[] = 'has-avail';
      ?>
      <a class="<?= e(implode(' ', $classes)) ?>"
         href="<?= BASE_URL ?>?action=adherentDashboard&ym=<?= e($ym) ?>&date=<?= e($day) ?>">
        <div class="cal-num"><?= $d ?></div>
        <?php if ($cnt > 0): ?><div class="cal-dot" title="<?= $cnt ?> créneau(x) dispo"></div><?php endif; ?>
      </a>
    <?php endfor; ?>
  </div>
</section>

<section class="card">
  <h2>Créneaux disponibles — <?= e(dmy($selectedDate)) ?><?= $coach ? ' (Coach '.e($coach['first_name'].' '.$coach['last_name']).')' : '' ?></h2>
  <?php if (empty($availableSlots)): ?>
    <p>Aucun créneau disponible ce jour.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Début</th><th>Fin</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($availableSlots as $s): ?>
        <tr>
          <td><?= e(fdt($s['start_time'])) ?></td>
          <td><?= e(fdt($s['end_time'])) ?></td>
          <td>
            <form method="post" action="<?= BASE_URL ?>?action=creneauReserve" class="inline">
              <?= csrf_input() ?>
              <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-primary" type="submit">Réserver</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Mes réservations</h2>
  <?php if (empty($reservations)): ?>
    <p>Aucune réservation.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Début</th><th>Fin</th><th>Coach</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($reservations as $r): ?>
        <tr>
          <td><?= e(fdt($r['start_time'])) ?></td>
          <td><?= e(fdt($r['end_time'])) ?></td>
          <td><?= e($r['coach_first'].' '.$r['coach_last']) ?></td>
          <td><?= e($r['status']) ?><?= ((int)$r['paid']===1?' (payé)':'') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
