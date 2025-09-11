<?php
/** Variables passées :
 * @var string $coachName
 * @var string $todayDate
 * @var string $selectedDate       YYYY-MM-DD
 * @var array  $adherents          [{id,first_name,last_name,email}]
 * @var int    $selectedAdh
 * @var array  $slots              (créneaux du jour avec reserved_count, status)
 * @var array  $reservedForAdh     (liste réservée pour l’adhérent sélectionné)
 * @var array  $reservations       (toutes réservations — optionnel)
 */

function timeHM(string $sqlDT): string { return (new DateTime($sqlDT))->format('H:i'); }
?>
<section class="card">
  <h1>Bonjour <?= e($coachName) ?></h1>
  <p>Nous sommes le <?= e($todayDate) ?></p>
</section>

<section class="card">
  <form method="get" action="" class="form" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <input type="hidden" name="action" value="coachDashboard">
    <label>
      Sélectionner une date
      <input type="date" name="date" value="<?= e($selectedDate) ?>" onchange="this.form.submit()">
    </label>

    <label>
      Adhérent
      <select name="adherent" onchange="this.form.submit()">
        <option value="">— Tous —</option>
        <?php foreach ($adherents as $a): ?>
          <option value="<?= (int)$a['id'] ?>" <?= $selectedAdh===(int)$a['id']?'selected':'' ?>>
            <?= e($a['first_name'].' '.$a['last_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </form>

  <?php if ($selectedAdh): ?>
    <div style="margin-top:10px;">
      <h3>Créneaux réservés par <?= e($adherents[array_search($selectedAdh, array_column($adherents,'id'))]['first_name'] ?? 'Adh') ?>
          <?= e($adherents[array_search($selectedAdh, array_column($adherents,'id'))]['last_name']  ?? '') ?></h3>
      <?php if (empty($reservedForAdh)): ?>
        <p>Aucun créneau réservé.</p>
      <?php else: ?>
        <ul>
        <?php foreach ($reservedForAdh as $rs): ?>
          <li><?= e($rs['date']) ?> de <?= e($rs['start_time']) ?> à <?= e($rs['end_time']) ?></li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Créneaux du <?= e($selectedDate) ?></h2>

  <div class="slots-container">
    <?php if (empty($slots)): ?>
      <p>Aucun créneau pour cette journée.</p>
    <?php else: ?>
      <?php foreach ($slots as $s):
        $reserved = ((int)$s['reserved_count'] > 0);
        $status = $s['status'];
        $cls = $reserved ? 'reserved-slot' : ($status === 'blocked' ? 'unavailable-slot' : 'available-slot');
      ?>
        <div class="slot <?= e($cls) ?>">
          <div class="slot-time"><?= e(timeHM($s['start_time'])) ?> - <?= e(timeHM($s['end_time'])) ?></div>
          <div class="slot-actions">
            <?php if (!$reserved): ?>
              <?php if ($status === 'available'): ?>
                <form class="inline" method="post" action="<?= BASE_URL ?>?action=creneauBlock">
                  <?= csrf_input() ?>
                  <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                  <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                  <button class="btn" type="submit">Indisponible</button>
                </form>
              <?php elseif ($status === 'blocked'): ?>
                <form class="inline" method="post" action="<?= BASE_URL ?>?action=creneauUnblock">
                  <?= csrf_input() ?>
                  <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                  <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                  <button class="btn" type="submit">Disponible</button>
                </form>
              <?php endif; ?>

              <form class="inline" method="post" action="<?= BASE_URL ?>?action=creneauDelete" onsubmit="return confirm('Supprimer ce créneau ?');">
                <?= csrf_input() ?>
                <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                <button class="btn" type="submit">Supprimer</button>
              </form>

              <form class="inline" method="post" action="<?= BASE_URL ?>?action=creneauReserveForAdherent">
                <?= csrf_input() ?>
                <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
                <input type="hidden" name="adherent_id" value="<?= (int)$selectedAdh ?>">
                <button class="btn btn-primary" type="submit" <?= $selectedAdh ? '' : 'disabled title="Sélectionne un adhérent"' ?>>
                  Réserver pour l’adhérent
                </button>
              </form>
            <?php else: ?>
              <span class="badge warn">Réservé</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="card">
  <h2>Ajouter un créneau</h2>
  <form method="post" action="<?= BASE_URL ?>?action=creneauAdd" class="form" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
    <?= csrf_input() ?>
    <input type="hidden" name="date" value="<?= e($selectedDate) ?>">
    <label>Début
      <input type="time" name="start_time" required>
    </label>
    <label>Fin
      <input type="time" name="end_time" required>
    </label>
    <div style="align-self:end">
      <button class="btn btn-primary" type="submit">Ajouter</button>
    </div>
  </form>
</section>
