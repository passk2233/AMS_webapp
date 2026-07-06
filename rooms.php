<?php
require __DIR__ . '/config.php';
require_login();

// Rooms come from the legacy catalogue via /rooms; cache an hour (stable data).
$rooms = api_list(cached_get('/rooms', 3600));

$roomId = (int) ($_GET['room_id'] ?? 0);
$room   = null;
foreach ($rooms as $r) {
    if ((int) ($r['id'] ?? 0) === $roomId) {
        $room = $r;
        break;
    }
}

/** room_code is the human label; fall back to the id so a button always reads. */
function room_label(array $r): string
{
    return (string) ($r['room_code'] ?? $r['name'] ?? ('#' . ($r['id'] ?? '?')));
}

// A room is "in use" when it has an approved or pending booking. Reject/cancel
// rows freed the slot, so they are not usage.
$USING = ['approved' => 'ອະນຸມັດແລ້ວ', 'pending' => 'ລໍຖ້າ'];

$byDate = [];
if ($room) {
    $rows = api_get_all('/room-bookings?room_id=' . $roomId);
    $rows = array_filter($rows, fn ($b) => isset($USING[strtolower((string) ($b['status'] ?? ''))]));
    usort($rows, fn ($a, $b) =>
        [$a['booking_date'] ?? '', $a['start_time'] ?? ''] <=> [$b['booking_date'] ?? '', $b['start_time'] ?? '']);
    foreach ($rows as $b) {
        $date = substr((string) ($b['booking_date'] ?? ''), 0, 10);
        $byDate[$date][] = $b;
    }
}

/** "HH:MM:SS"/ISO → "HH:MM". */
function hhmm(?string $s): string
{
    return substr((string) $s, 0, 5);
}
$LAO_DAY = ['ອາທິດ', 'ຈັນ', 'ອັງຄານ', 'ພຸດ', 'ພະຫັດ', 'ສຸກ', 'ເສົາ'];

$title = 'ການໃຊ້ຫ້ອງ' . ($room ? ' — ' . room_label($room) : '');
require __DIR__ . '/views/layout/header.php';
?>
<style>
  .rooms { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px; }
  .room-btn { display:block; padding:20px 14px; background:#fff; border:1px solid var(--line,#e5e7eb);
    border-radius:12px; text-align:center; font-weight:700; font-size:16px; color:inherit; transition:.12s; }
  .room-btn:hover { border-color:var(--primary-fill,#3a57e8); box-shadow:0 4px 14px rgba(58,87,232,.15); transform:translateY(-2px); }
  .room-btn small { display:block; font-weight:400; color:var(--muted,#6b7280); margin-top:4px; }
  .day { background:#fff; border:1px solid var(--line,#e5e7eb); border-radius:12px; margin-bottom:16px; overflow:hidden; }
  .day-head { padding:10px 16px; background:#eef1fe; font-weight:700; }
  .rooms-table { width:100%; border-collapse:collapse; }
  .rooms-table th, .rooms-table td { padding:10px 16px; text-align:left; border-top:1px solid var(--line,#e5e7eb); font-size:15px; }
  .rooms-table th { color:var(--muted,#6b7280); font-weight:600; font-size:13px; }
  .rooms-table .time { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
  .room-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:12px; font-weight:600; }
  .room-badge.approved { background:#dcfce7; color:#166534; }
  .room-badge.pending  { background:#fef9c3; color:#854d0e; }
</style>

<?php if (!$room): ?>
  <p class="muted small">ເລືອກຫ້ອງເພື່ອເບິ່ງຕາຕະລາງ ແລະ ຊ່ວງເວລາທີ່ຫ້ອງຖືກໃຊ້</p>
  <?php if (!$rooms): ?>
    <div class="state">ບໍ່ພົບຫ້ອງ</div>
  <?php else: ?>
    <div class="rooms">
      <?php foreach ($rooms as $r): ?>
        <a class="room-btn" href="<?= url('rooms') ?>?room_id=<?= (int) ($r['id'] ?? 0) ?>">
          <?= esc(room_label($r)) ?>
          <?php if (!empty($r['building'])): ?><small><?= esc((string) $r['building']) ?></small><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php else: ?>
  <div class="actions">
    <a class="btn ghost" href="<?= url('rooms') ?>">‹ ກັບໄປເລືອກຫ້ອງ</a>
  </div>
  <h2>ຫ້ອງ <?= esc(room_label($room)) ?></h2>
  <p class="muted small">ຊ່ວງເວລາທີ່ຫ້ອງຖືກໃຊ້ (ການຈອງທີ່ອະນຸມັດ ຫຼື ລໍຖ້າ)</p>

  <?php if (!$byDate): ?>
    <div class="state">ຍັງບໍ່ມີການໃຊ້ຫ້ອງນີ້</div>
  <?php else: foreach ($byDate as $date => $items):
      $ts  = strtotime($date) ?: time();
      $dow = $LAO_DAY[(int) date('w', $ts)];
  ?>
    <div class="day">
      <div class="day-head"><?= esc($date) ?> · ວັນ<?= esc($dow) ?></div>
      <table class="rooms-table">
        <thead><tr><th>ເວລາ</th><th>ຈຸດປະສົງ</th><th>ສະຖານະ</th></tr></thead>
        <tbody>
        <?php foreach ($items as $b): $st = strtolower((string) ($b['status'] ?? '')); ?>
          <tr>
            <td class="time"><?= esc(hhmm($b['start_time'] ?? '')) ?>–<?= esc(hhmm($b['end_time'] ?? '')) ?></td>
            <td><?= esc((string) ($b['purpose'] ?? '—')) ?></td>
            <td><span class="room-badge <?= esc($st) ?>"><?= esc($USING[$st] ?? $st) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/views/layout/footer.php'; ?>
