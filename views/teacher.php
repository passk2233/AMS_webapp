<?php
/** @var bool $error @var array $bySubject @var array $semesters
 *  @var int $selectedSemId @var string $title */
require __DIR__ . '/layout/header.php';
?>
<style>
  .score-pill.b9 { background: rgba(26,160,83,.12); color: #0c7a3c; }
  .score-pill.b7 { background: rgba(58,87,232,.12); color: var(--primary-fill); }
  .score-pill.b5 { background: rgba(241,106,27,.12); color: #b4540e; }
  .score-pill.b0 { background: rgba(192,50,33,.12); color: var(--danger); }
  .sem-filter { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
  .sem-filter span { font-size: 14px; font-weight: 600; color: var(--muted); }
  .sem-filter select { width: auto; min-width: 180px; }
</style>

<?php if (!empty($semesters)): ?>
  <form class="sem-filter" method="get">
    <span>ພາກຮຽນ</span>
    <select class="input" name="semester" onchange="this.form.submit()" aria-label="ເລືອກພາກຮຽນ">
      <option value="0" <?= $selectedSemId === 0 ? 'selected' : '' ?>>ທັງໝົດ</option>
      <?php foreach ($semesters as $s): $sid = (int) ($s['id'] ?? 0); ?>
        <option value="<?= $sid ?>" <?= $sid === $selectedSemId ? 'selected' : '' ?>><?= esc(semester_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn" type="submit">ສະແດງ</button></noscript>
  </form>
<?php endif; ?>

<?php if ($error): ?>
  <div class="state">ໂຫຼດຜົນການປະເມີນບໍ່ໄດ້ ກະລຸນາລອງໃໝ່</div>
<?php elseif (count($bySubject) === 0): ?>
  <div class="state">ຍັງບໍ່ມີວິຊາ ຫຼື ຜົນການປະເມີນ</div>
<?php else: ?>
  <p class="muted small">ວິຊາ → ຫ້ອງ → ຈຳນວນຄົນທີ່ປະເມີນ</p>
  <div class="admin-groups">
  <?php foreach ($bySubject as $subject => $subjectReports): ?>
    <details class="subject-group" open>
      <summary class="subject-heading">
        <span class="subject-title"><?= esc($subject) ?></span>
        <span class="subject-meta"><?= count($subjectReports) ?> ຫ້ອງ</span>
      </summary>
      <div class="class-list">
      <?php foreach ($subjectReports as $r): ?>
        <a class="admin-class-row" href="<?= url('report') ?>?plan=<?= $r['plan_id'] ?>">
          <div class="class-copy">
            <div class="class-name"><?= esc($r['class'] !== '' ? $r['class'] : '-') ?></div>
            <div class="completion-meter" aria-hidden="true">
              <span style="width: <?= completion_percent($r) ?>%;"></span>
            </div>
          </div>
          <div class="completion-copy">
            <span class="completion-count"><?= esc(completion_label($r)) ?></span>
            <span class="score-pill <?= badge_class((float) $r['average']) ?>">
              <?= ((float) $r['average']) > 0 ? number_format((float) $r['average'], 2) : '-' ?>
            </span>
          </div>
          <span class="muted">›</span>
        </a>
      <?php endforeach; ?>
      </div>
    </details>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
