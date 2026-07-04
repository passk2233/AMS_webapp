<?php
/** @var ?string $flash @var bool $error @var array $windows @var ?array $current @var bool $isOpen @var bool $teacherVisible @var string $title */
require __DIR__ . '/layout/header.php';

$fmt = fn (?string $t): string => $t ? date('d/m/Y H:i', strtotime($t)) : '-';
?>
<?php if (!empty($flash)): ?>
  <div class="banner ok flash" role="status">✓ <?= esc($flash) ?></div>
<?php endif; ?>

<h1>ໄລຍະການປະເມີນ</h1>

<?php if ($error): ?>
  <div class="state">ໂຫຼດຂໍ້ມູນບໍ່ໄດ້ ກະລຸນາລອງໃໝ່</div>
<?php else: ?>

  <div class="card">
    <?php if ($isOpen): ?>
      <p><strong style="color:#0c7a3c;">● ເປີດຢູ່</strong> — ນັກສຶກສາສາມາດເຂົ້າປະເມີນໄດ້</p>
      <p class="muted small">
        ເປີດ: <?= esc($fmt($current['open_time'] ?? null)) ?> ·
        ປິດ: <?= esc($fmt($current['close_time'] ?? null)) ?>
      </p>
      <form method="post" action="window.php" onsubmit="return confirm('ປິດໄລຍະການປະເມີນປະຈຸບັນແທ້ບໍ?');">
        <input type="hidden" name="action" value="close">
        <input type="hidden" name="id" value="<?= (int) ($current['id'] ?? 0) ?>">
        <button class="btn danger" type="submit">ປິດການປະເມີນ</button>
      </form>
    <?php else: ?>
      <p><strong style="color:var(--danger);">● ປິດຢູ່</strong> — ນັກສຶກສາຍັງເຂົ້າປະເມີນບໍ່ໄດ້</p>
    <?php endif; ?>
  </div>

  <h2>ການເຜີຍຜົນໃຫ້ອາຈານ</h2>
  <div class="card">
    <?php if ($teacherVisible): ?>
      <p><strong style="color:#0c7a3c;">● ເປີດເຜີຍຢູ່</strong> — ອາຈານເຫັນຜົນການປະເມີນຂອງຕົນເອງໄດ້</p>
      <form method="post" action="window.php" onsubmit="return confirm('ປິດການເຜີຍຜົນຈາກອາຈານແທ້ບໍ?');">
        <input type="hidden" name="action" value="visibility">
        <input type="hidden" name="visible" value="0">
        <button class="btn danger" type="submit">ປິດການເຜີຍຜົນ</button>
      </form>
    <?php else: ?>
      <p><strong style="color:var(--danger);">● ປິດຢູ່</strong> — ອາຈານຍັງເບິ່ງຜົນການປະເມີນບໍ່ໄດ້</p>
      <form method="post" action="window.php">
        <input type="hidden" name="action" value="visibility">
        <input type="hidden" name="visible" value="1">
        <button class="btn" type="submit">ເປີດເຜີຍຜົນໃຫ້ອາຈານ</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$isOpen): ?>
    <h2>ເປີດການປະເມີນ</h2>
    <form class="card" method="post" action="window.php">
      <input type="hidden" name="action" value="open">
      <label class="field">
        <span>ເວລາເປີດ</span>
        <input class="input" type="datetime-local" name="open_time" value="<?= date('Y-m-d\TH:i') ?>" required>
      </label>
      <label class="field">
        <span>ເວລາປິດ</span>
        <input class="input" type="datetime-local" name="close_time" value="<?= date('Y-m-d\TH:i', strtotime('+14 days')) ?>" required>
      </label>
      <p class="muted small">ເມື່ອເປີດແລ້ວ ລະບົບຈະສົ່ງແຈ້ງເຕືອນຫານັກສຶກສາທຸກຄົນອັດຕະໂນມັດ</p>
      <div class="actions" style="margin:0;">
        <button class="btn" type="submit">ເປີດການປະເມີນ</button>
      </div>
    </form>
  <?php endif; ?>

  <?php if ($windows): ?>
    <h2>ປະຫວັດ</h2>
    <?php foreach ($windows as $w): ?>
      <div class="card q-row">
        <div class="grow"><?= esc($fmt($w['open_time'] ?? null)) ?> → <?= esc($fmt($w['close_time'] ?? null)) ?></div>
        <span class="muted small"><?= ((int) ($w['inactive'] ?? 0) === 1) ? 'ປິດແລ້ວ' : 'ເປີດໃຊ້' ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

<?php endif; ?>
<?php require __DIR__ . '/layout/footer.php'; ?>
