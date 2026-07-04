<?php
/** @var bool $error @var array $reports @var array $groups @var string $title */
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
      <?php foreach ($semesters as $s): $sid = (int) ($s['id'] ?? 0); ?>
        <option value="<?= $sid ?>" <?= $sid === $selectedSemId ? 'selected' : '' ?>><?= esc(semester_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn" type="submit">ສະແດງ</button></noscript>
  </form>
<?php endif; ?>

<?php if ($error): ?>
  <div class="state">ໂຫຼດຜົນການປະເມີນບໍ່ໄດ້ ກະລຸນາລອງໃໝ່</div>
<?php elseif (count($reports) === 0): ?>
  <div class="state">ຍັງບໍ່ມີຜົນການປະເມີນ</div>
<?php else: ?>
  <p class="muted small">ອາຈານ → ວິຊາ → ຫ້ອງ → ຈຳນວນຄົນທີ່ປະເມີນ (<?= count($reports) ?>)</p>
  <div class="admin-groups">
  <?php foreach ($groups as $teacher => $teacherGroup): ?>
    <?php
      // Collect all plan IDs for this teacher.
      $teacherPlanIds = [];
      foreach ($teacherGroup['subjects'] as $subjectReports) {
        foreach ($subjectReports as $r) {
          $teacherPlanIds[] = (int) $r['plan_id'];
        }
      }
    ?>
    <details class="teacher-group">
      <summary class="teacher-heading">
        <span class="teacher-title"><?= esc($teacher) ?></span>
        <span class="teacher-actions">
          <button class="btn-dl btn-dl-teacher" type="button"
                  data-plan-ids="<?= esc(implode(',', $teacherPlanIds)) ?>"
                  data-label="<?= esc($teacher) ?>"
                  title="ດາວໂຫຼດ PDF ທຸກວິຊາຂອງອາຈານ">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span class="btn-dl-text">PDF ທັງໝົດ</span>
          </button>
          <span class="teacher-meta"><?= count($teacherGroup['subjects']) ?> ວິຊາ</span>
        </span>
      </summary>
      <div class="teacher-body">
      <?php foreach ($teacherGroup['subjects'] as $subject => $subjectReports): ?>
        <?php
          $subjectPlanIds = array_map(fn ($r) => (int) $r['plan_id'], $subjectReports);
        ?>
        <details class="subject-group">
          <summary class="subject-heading">
            <span class="subject-title"><?= esc($subject) ?></span>
            <span class="subject-actions">
              <button class="btn-dl btn-dl-subject" type="button"
                      data-plan-ids="<?= esc(implode(',', $subjectPlanIds)) ?>"
                      data-label="<?= esc($teacher . ' ' . $subject) ?>"
                      title="ດາວໂຫຼດ PDF ທຸກຫ້ອງຂອງວິຊານີ້">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span class="btn-dl-text">PDF</span>
              </button>
              <span class="subject-meta"><?= count($subjectReports) ?> ຫ້ອງ</span>
            </span>
          </summary>
          <div class="class-list">
          <?php foreach ($subjectReports as $r): ?>
            <a class="admin-class-row" href="<?= url('admin/report') ?>?plan=<?= $r['plan_id'] ?>">
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
    </details>
  <?php endforeach; ?>
  </div>

  <script>
  (function () {
    // Download buttons: navigate to bulk report page.
    document.querySelectorAll('.btn-dl').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        var ids   = btn.dataset.planIds || '';
        var label = btn.dataset.label || '';
        if (!ids) return;
        window.location.href = '<?= url('admin/report-bulk') ?>?plans=' + encodeURIComponent(ids)
                             + '&label=' + encodeURIComponent(label);
      });
    });
  })();
  </script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
