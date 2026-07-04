<?php
/** @var ?array $edit @var array $questions @var array $cats @var array $order @var array $byCat @var string $title */
require __DIR__ . '/layout/header.php';
?>
<?php if (!empty($flash)): ?>
  <div class="banner ok flash" role="status">✓ <?= esc($flash) ?></div>
<?php endif; ?>
<h1><?= $edit ? 'ແກ້ໄຂຄຳຖາມ' : 'ເພີ່ມຄຳຖາມ' ?></h1>
<form class="card" method="post" action="<?= url('admin/questions') ?>">
  <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['eva_question_id'] ?>"><?php endif; ?>
  <label class="field">
    <span>ຄຳຖາມ</span>
    <textarea class="input" name="question" rows="2" required><?= esc($edit['question'] ?? '') ?></textarea>
  </label>
  <label class="field">
    <span>ໝວດ (ບໍ່ບັງຄັບ)<?= $cats ? ' — ມີຢູ່: ' . esc(implode(', ', array_keys($cats))) : '' ?></span>
    <input class="input" type="text" name="category" value="<?= esc($edit['category'] ?? '') ?>">
  </label>
  <label class="field" style="display:flex;align-items:center;gap:8px;">
    <input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>>
    <span style="margin:0;">ເປີດໃຊ້ງານ</span>
  </label>
  <div class="actions" style="margin:0;">
    <button class="btn" type="submit"><?= $edit ? 'ບັນທຶກ' : 'ເພີ່ມ' ?></button>
    <?php if ($edit): ?><a class="btn ghost" href="<?= url('admin/questions') ?>">ຍົກເລີກ</a><?php endif; ?>
  </div>
</form>

<?php foreach ($order as $c): ?>
  <h2 class="cat-head"><?= esc($c) ?></h2>
  <?php foreach ($byCat[$c] as $q):
      $qid = (int) $q['eva_question_id'];
      $active = (int) ($q['is_active'] ?? 1) === 1; ?>
    <div class="card q-row">
      <div class="grow"<?= $active ? '' : ' style="opacity:.5;"' ?>><?= esc($q['question']) ?></div>
      <div class="q-actions">
        <form method="post" action="<?= url('admin/questions') ?>">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= $qid ?>">
          <input type="hidden" name="question" value="<?= esc($q['question']) ?>">
          <input type="hidden" name="category" value="<?= esc($q['category'] ?? '') ?>">
          <input type="hidden" name="is_active" value="<?= $active ? 0 : 1 ?>">
          <button class="btn ghost" type="submit" title="ເປີດ/ປິດໃຊ້ງານ"><?= $active ? 'ປິດໃຊ້ງານ' : 'ເປີດໃຊ້ງານ' ?></button>
        </form>
        <a class="btn ghost" href="<?= url('admin/questions') ?>?edit=<?= $qid ?>">ແກ້</a>
        <form method="post" action="<?= url('admin/questions') ?>" onsubmit="return confirm('ລຶບຄຳຖາມນີ້?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $qid ?>">
          <button class="btn danger" type="submit">ລຶບ</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if (count($questions) === 0): ?>
  <div class="state">ຍັງບໍ່ມີຄຳຖາມ</div>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
