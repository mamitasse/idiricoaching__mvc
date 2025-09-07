<?php
/** @var string $ym */
/** @var string $prevYm */
/** @var string $nextYm */
/** @var string $selectedDate */
/** @var array  $summary (['YYYY-MM-DD' => ['total'=>X,'reserved'=>Y]]) */
/** @var array  $slots    (créneaux du jour) */
/** @var array  $reservations */

function fdt(string $sqlDT): string { return (new DateTime($sqlDT))->format('d/m/Y H:i'); }
function dmy(string $ymd): string { return (new DateTime($ymd))->format('d/m/Y'); }

$months = [1=>'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
[$y,$m] = array_map('intval', explode('-', $ym));
$monthTitle = $months[$m] . ' ' . $y;

$first = new DateTime($ym . '-01');
$daysInMonth = (int)$first->format('t');
$startDow = (int)$first->format('N');
$today = (new DateTime('today'))->format('Y-m-d');
?>
<section class="card">
  <div class="cal-header">
    <a class="btn" href="<?= BASE_URL ?>?action=coachDashboard&ym=<?= e($prevYm) ?>">←</a>
    <h2><?= e($monthTitle) ?></h2>
    <a class="btn" href="<?= BASE_URL ?>?action=coachDashboard&ym=<?= e($nextYm) ?>">→</a>
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
      $sum = $summary[$day] ?? ['total'=>0,'reserved'=>0];
      $classes = ['cal-day'];
      if ($day === $today) $classes[] = 'today';
      if ($day === $selectedDate) $classes[] = 'selected';
      if ($sum['total'] > 0) $classes[] = 'has-avail';
      ?>
      <a class="<?= e(implode(' ', $classes)) ?>"
         href="<?= BASE_URL ?>?action=coachDashboard&ym=<?= e($ym) ?>&date=<?= e($day) ?>">
        <div class="cal-num"><?= $d ?></div>
        <?php if ($sum['total'] > 0): ?>
          <div class="cal-badges">
            <span class="badge"><?= (int)$sum['total'] ?></span>
            <?php if ($sum['reserved'] > 0): ?><span class="badge warn"><?= (int)$sum['reserved'] ?></span><?php endif; ?>
          </div>
        <?php endif; ?>
      </a>
    <?php endfor; ?>
  </div>
</section>

<section class="card">
  <h2>Créneaux du <?= e(dmy($selectedDate)) ?></h2>
  <?php if (empty($slots)): ?>
    <p>Aucun créneau pour ce jour (la grille 08:00→20:00 est générée automatiquement ; actualise si besoin).</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Début</th><th>Fin</th><th>Statut</th><th>Réservé ?</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($slots as $s): ?>
      <tr>
        <td><?= e(fdt($s['start_time'])) ?></td>
        <td><?= e(fdt($s['end_time'])) ?></td>
        <td>
          <?php if ($s['status']==='available'): ?>
            <span class="badge ok">Disponible</span>
          <?php elseif ($s['status']==='blocked'): ?>
            <span class="badge warn">Indisponible</span>
          <?php else: ?>
            <span class="badge">Supprimé</span>
          <?php endif; ?>
        </td>
        <td><?= ((int)$s['reserved_count'] > 0) ? 'Oui' : 'Non' ?></td>
        <td>
          <?php if ((int)$s['reserved_count'] === 0): ?>
            <?php if ($s['status']==='available'): ?>
              <form method="post" action="<?= BASE_URL ?>?action=creneauBlock" class="inline">
                <?= csrf_input() ?>
                <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                <button class="btn" type="submit">Indispo</button>
              </form>
            <?php elseif ($s['status']==='blocked'): ?>
              <form method="post" action="<?= BASE_URL ?>?action=creneauUnblock" class="inline">
                <?= csrf_input() ?>
                <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                <button class="btn" type="submit">Rendre dispo</button>
              </form>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>?action=creneauDelete" class="inline" onsubmit="return confirm('Supprimer ce créneau ?');">
              <?= csrf_input() ?>
              <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
              <button class="btn" type="submit">Supprimer</button>
            </form>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Réservations reçues (toutes)</h2>
  <?php if (empty($reservations)): ?>
    <p>Aucune réservation.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Début</th><th>Fin</th><th>Adhérent</th><th>Email</th><th>Statut</th></tr></thead>
    <tbody>
    <?php foreach ($reservations as $r): ?>
      <tr>
        <td><?= e(fdt($r['start_time'])) ?></td>
        <td><?= e(fdt($r['end_time'])) ?></td>
        <td><?= e($r['adh_first'].' '.$r['adh_last']) ?></td>
        <td><?= e($r['email']) ?></td>
        <td><?= e($r['status']) ?><?= ((int)$r['paid']===1?' (payé)':'') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
