<?php
/** @var string $status @var array $targets @var string $title */
require __DIR__ . '/layout/header.php';
?>

<?php if (!empty($flash)): ?>
  <div class="banner ok flash" role="status">✓ <?= esc($flash) ?></div>
<?php endif; ?>

<div class="actions" style="margin-bottom:8px;">
  <a class="btn ghost" href="guide.php">📖 ເບິ່ງຄູ່ມືການໃຊ້</a>
</div>

<?php
  // Reusable feather-style icons for the empty/error states below.
  $icon_paths = [
    'user'  => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'alert' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    'check' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
  ];
  $icon = fn (string $path) => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"'
      . ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
      . $icon_paths[$path] . '</svg>';
?>
<?php if ($status === 'notlinked'): ?>
  <div class="state">
    <div class="state-icon warn" aria-hidden="true"><?= $icon('user') ?></div>
    <div class="state-title">ບັນຊີນັກສຶກສາຍັງບໍ່ໄດ້ເຊື່ອມຕໍ່ຂໍ້ມູນ</div>
    <p class="state-sub">ກະລຸນາລອງໃໝ່ ຫຼື ຕິດຕໍ່ຫ້ອງທະບຽນ ຖ້າຍັງເຫັນຂໍ້ຄວາມນີ້</p>
    <a class="btn ghost" href="student.php">ລອງໃໝ່</a>
  </div>
<?php elseif ($status === 'closed'): ?>
  <div class="state">
    <div class="state-icon" aria-hidden="true"><?= $icon('clock') ?></div>
    <div class="state-title">ໄລຍະການປະເມີນຍັງບໍ່ໄດ້ເປີດ</div>
    <p class="state-sub">ເມື່ອເປີດໄລຍະປະເມີນແລ້ວ ວິຊາຕ່າງໆ ຈະສະແດງຢູ່ບ່ອນນີ້</p>
  </div>
<?php elseif ($status === 'error'): ?>
  <div class="state">
    <div class="state-icon danger" aria-hidden="true"><?= $icon('alert') ?></div>
    <div class="state-title">ໂຫຼດຂໍ້ມູນບໍ່ໄດ້</div>
    <p class="state-sub">ການເຊື່ອມຕໍ່ມີບັນຫາ ກະລຸນາລອງໃໝ່ອີກຄັ້ງ</p>
    <a class="btn" href="student.php">ລອງໃໝ່</a>
  </div>
<?php elseif (count($targets) === 0): ?>
  <div class="state">
    <div class="state-icon" aria-hidden="true"><?= $icon('check') ?></div>
    <div class="state-title">ບໍ່ມີວິຊາໃຫ້ປະເມີນ</div>
    <p class="state-sub">ຍັງບໍ່ມີວິຊາໃນກຸ່ມຮຽນຂອງທ່ານທີ່ຕ້ອງປະເມີນໃນຕອນນີ້</p>
  </div>
<?php else:
    $pending = count(array_filter($targets, fn ($t) => !$t['done'])); ?>
  <div class="banner <?= $pending === 0 ? 'ok' : '' ?>">
    <?= $pending === 0
        ? 'ທ່ານໄດ້ປະເມີນຄົບທຸກວິຊາແລ້ວ ຂອບໃຈ!'
        : 'ຍັງເຫຼືອ ' . $pending . ' ວິຊາທີ່ຕ້ອງປະເມີນ' ?>
  </div>
  <?php foreach ($targets as $t): ?>
    <?php if ($t['done']): ?>
      <div class="row done">
        <div class="avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $t['subject'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
        <div class="grow">
          <div class="title"><?= esc($t['subject']) ?></div>
          <div class="sub"><?= esc($t['teacher']) ?></div>
          <?php if ($t['class'] !== ''): ?><div class="sub small"><?= esc($t['class']) ?></div><?php endif; ?>
        </div>
        <span class="chip">✓ ປະເມີນແລ້ວ</span>
      </div>
    <?php else: ?>
      <a class="row" href="guide.php?plan=<?= $t['id'] ?>">
        <div class="avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr((string) $t['subject'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
        <div class="grow">
          <div class="title"><?= esc($t['subject']) ?></div>
          <div class="sub"><?= esc($t['teacher']) ?></div>
          <?php if ($t['class'] !== ''): ?><div class="sub small"><?= esc($t['class']) ?></div><?php endif; ?>
        </div>
        <span class="muted">›</span>
      </a>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
