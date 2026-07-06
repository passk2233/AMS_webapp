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

/** "HH:MM[:SS]" → minutes since midnight; -1 when unparseable. */
function mins(?string $s): int
{
    $p = explode(':', trim((string) $s));
    return count($p) >= 2 ? (int) $p[0] * 60 + (int) $p[1] : -1;
}

// A booking is "usage" when approved or pending (reject/cancel freed the slot).
$USING   = ['approved' => 'ອະນຸມັດແລ້ວ', 'pending' => 'ລໍຖ້າ'];
$LAO_DAY = [1 => 'ຈັນ', 2 => 'ອັງຄານ', 3 => 'ພຸດ', 4 => 'ພະຫັດ', 5 => 'ສຸກ', 6 => 'ເສົາ', 7 => 'ອາທິດ'];

$events   = [];   // [day 1..7 => [ ['start','end','title','sub','kind','status'], ... ]]
$weekDays = [];   // day 1..7 => DateTime (the shown week's Mon..Sun)

if ($room) {
    // Shown week: Monday..Sunday, shiftable with ?week=±n.
    $weekOffset = (int) ($_GET['week'] ?? 0);
    $monday     = new DateTime('monday this week');
    if ($weekOffset !== 0) {
        $monday->modify(($weekOffset * 7) . ' day');
    }
    for ($d = 1; $d <= 7; $d++) {
        $weekDays[$d] = (clone $monday)->modify(($d - 1) . ' day');
    }
    $weekStart = $weekDays[1]->format('Y-m-d');
    $weekEnd   = $weekDays[7]->format('Y-m-d');

    // ── Fixed class schedule (แผนการเรียน) — recurring every week by weekday ──
    // Pull the active semester's plans and keep this room's. day_of_week is a
    // string "1".."7" (Mon..Sun), matching PHP's date('N').
    $groups = group_index();
    $sem    = active_semester();
    $plans  = $sem ? api_get_all('/study-plans?semaster_id=' . (int) ($sem['id'] ?? 0)) : [];
    foreach ($plans as $p) {
        $pRoom = (int) ($p['room_id'] ?? ($p['room']['id'] ?? 0));
        if ($pRoom !== $roomId) {
            continue;
        }
        $dow = (int) ($p['day_of_week'] ?? 0);
        $s   = mins($p['start_time'] ?? '');
        $e   = mins($p['end_time'] ?? '');
        if ($dow < 1 || $dow > 7 || $s < 0 || $e <= $s) {
            continue;
        }
        $names          = plan_names($p, $groups);
        $events[$dow][] = [
            'start'  => $s,
            'end'    => $e,
            'title'  => $names['subject'] !== '-' ? $names['subject'] : 'ຮຽນ',
            'sub'    => trim($names['teacher'] . ' · ' . $names['class'], ' ·'),
            'kind'   => 'class',
            'status' => '',
        ];
    }

    // ── Room bookings — dated; only those falling inside the shown week ──
    $rows = api_get_all('/room-bookings?room_id=' . $roomId);
    foreach ($rows as $b) {
        $st = strtolower((string) ($b['status'] ?? ''));
        if (!isset($USING[$st])) {
            continue;
        }
        $date = substr((string) ($b['booking_date'] ?? ''), 0, 10);
        if ($date < $weekStart || $date > $weekEnd) {
            continue;
        }
        $s = mins($b['start_time'] ?? '');
        $e = mins($b['end_time'] ?? '');
        if ($s < 0 || $e <= $s) {
            continue;
        }
        $dow            = (int) (new DateTime($date))->format('N');
        $events[$dow][] = [
            'start'  => $s,
            'end'    => $e,
            'title'  => trim((string) ($b['purpose'] ?? '')) ?: 'ຈອງຫ້ອງ',
            'sub'    => $USING[$st],
            'kind'   => 'booking',
            'status' => $st,
        ];
    }
}

// Grid bounds: 08:00–17:00 baseline, widened to fit any earlier/later event.
$dayStart = 8 * 60;
$dayEnd   = 17 * 60;
foreach ($events as $dayEvents) {
    foreach ($dayEvents as $ev) {
        $dayStart = min($dayStart, $ev['start']);
        $dayEnd   = max($dayEnd, $ev['end']);
    }
}
$dayStart = intdiv($dayStart, 60) * 60;          // floor to the hour
$dayEnd   = (int) (ceil($dayEnd / 60) * 60);     // ceil to the hour
$rowH     = 52;                                   // px per hour
$gridH    = ($dayEnd - $dayStart) / 60 * $rowH;

$title = 'ການໃຊ້ຫ້ອງ' . ($room ? ' — ' . room_label($room) : '');
require __DIR__ . '/views/layout/header.php';
?>
<style>
  .rooms { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px; }
  .room-btn { display:block; padding:20px 14px; background:#fff; border:1px solid var(--line,#e5e7eb);
    border-radius:12px; text-align:center; font-weight:700; font-size:16px; color:inherit; transition:.12s; }
  .room-btn:hover { border-color:var(--primary-fill,#3a57e8); box-shadow:0 4px 14px rgba(58,87,232,.15); transform:translateY(-2px); }
  .room-btn small { display:block; font-weight:400; color:var(--muted,#6b7280); margin-top:4px; }

  .week-nav { display:flex; align-items:center; gap:12px; margin:6px 0 14px; }
  .week-nav .label { font-weight:700; }
  .legend { display:flex; gap:16px; margin:0 0 12px; font-size:12px; color:var(--muted,#6b7280); flex-wrap:wrap; }
  .legend i { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:5px; vertical-align:-1px; }

  .tt { border:1px solid var(--line,#e5e7eb); border-radius:12px; overflow:hidden; background:#fff; }
  .tt-head, .tt-body { display:grid; grid-template-columns:56px repeat(7,1fr); }
  .tt-head .cell { padding:8px 4px; text-align:center; font-size:13px; font-weight:700; color:var(--primary-fill,#3a57e8);
    border-left:1px solid var(--line,#e5e7eb); }
  .tt-head .cell.today { background:#eef1fe; }
  .tt-head .spacer { border-left:0; }
  .tt-gutter { position:relative; }
  .tt-gutter .hr { position:absolute; right:6px; font-size:11px; color:var(--muted,#6b7280); transform:translateY(-7px); }
  .tt-col { position:relative; border-left:1px solid var(--line,#e5e7eb); }
  .ev { position:absolute; left:3px; right:3px; border-radius:8px; padding:4px 6px; font-size:12px;
    color:#fff; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.18); }
  .ev.class { background:#3f7ee8; }
  .ev.booking.approved { background:#1aa053; }
  .ev.booking.pending  { background:#e0a500; }
  .ev .t { font-weight:700; line-height:1.15; }
  .ev .m { opacity:.92; font-size:11px; line-height:1.2; margin-top:2px; }
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

  <div class="week-nav">
    <a class="btn ghost" href="<?= url('rooms') ?>?room_id=<?= $roomId ?>&week=<?= $weekOffset - 1 ?>">‹ ອາທິດກ່ອນ</a>
    <span class="label"><?= esc($weekDays[1]->format('d/m')) ?> – <?= esc($weekDays[7]->format('d/m/Y')) ?></span>
    <a class="btn ghost" href="<?= url('rooms') ?>?room_id=<?= $roomId ?>&week=<?= $weekOffset + 1 ?>">ອາທິດຕໍ່ໄປ ›</a>
    <?php if ($weekOffset !== 0): ?>
      <a class="btn ghost" href="<?= url('rooms') ?>?room_id=<?= $roomId ?>">ອາທິດນີ້</a>
    <?php endif; ?>
  </div>
  <div class="legend">
    <span><i style="background:#3f7ee8"></i>ຕາມແຜນການຮຽນ</span>
    <span><i style="background:#1aa053"></i>ຈອງ (ອະນຸມັດ)</span>
    <span><i style="background:#e0a500"></i>ຈອງ (ລໍຖ້າ)</span>
  </div>

  <div class="tt">
    <div class="tt-head">
      <div class="cell spacer"></div>
      <?php $todayStr = date('Y-m-d'); foreach ($weekDays as $d => $dt): ?>
        <div class="cell <?= $dt->format('Y-m-d') === $todayStr ? 'today' : '' ?>">
          <?= esc($LAO_DAY[$d]) ?> <?= esc($dt->format('d/m')) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="tt-body">
      <div class="tt-gutter" style="height:<?= $gridH ?>px">
        <?php for ($m = $dayStart; $m <= $dayEnd; $m += 60): ?>
          <div class="hr" style="top:<?= ($m - $dayStart) / 60 * $rowH ?>px">
            <?= sprintf('%02d:00', intdiv($m, 60)) ?>
          </div>
        <?php endfor; ?>
      </div>
      <?php for ($d = 1; $d <= 7; $d++):
          $bg = 'repeating-linear-gradient(#fff,#fff ' . ($rowH - 1) . 'px,'
              . 'var(--line,#e5e7eb) ' . ($rowH - 1) . 'px,var(--line,#e5e7eb) ' . $rowH . 'px)';
      ?>
        <div class="tt-col" style="height:<?= $gridH ?>px;background-image:<?= $bg ?>">
          <?php foreach ($events[$d] ?? [] as $ev):
              $top = ($ev['start'] - $dayStart) / 60 * $rowH;
              $h   = max(($ev['end'] - $ev['start']) / 60 * $rowH, 22);
          ?>
            <div class="ev <?= esc($ev['kind']) ?> <?= esc($ev['status']) ?>"
                 style="top:<?= $top ?>px;height:<?= $h ?>px">
              <div class="t"><?= esc($ev['title']) ?></div>
              <div class="m"><?= esc(sprintf('%02d:%02d–%02d:%02d', intdiv($ev['start'], 60), $ev['start'] % 60, intdiv($ev['end'], 60), $ev['end'] % 60)) ?></div>
              <?php if ($ev['sub'] !== ''): ?><div class="m"><?= esc($ev['sub']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/views/layout/footer.php'; ?>
