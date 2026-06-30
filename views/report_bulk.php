<?php
/** @var array $allReports @var string $pdfName @var string $title */
require __DIR__ . '/layout/header.php';
?>
<div class="actions">
  <a class="btn ghost" href="admin.php">‹ ກັບຄືນ</a>
  <?php if (count($allReports) > 0): ?><button class="btn" id="dl-pdf-bulk" type="button" data-busy="ກຳລັງສ້າງ PDF...">ດາວໂຫຼດ PDF (<?= count($allReports) ?> ລາຍງານ)</button><?php endif; ?>
</div>

<?php if (count($allReports) === 0): ?>
  <div class="state">ບໍ່ມີຂໍ້ມູນການປະເມີນສຳລັບແຜນການສອນທີ່ເລືອກ</div>
<?php else: ?>
  <div id="bulk-reports">
    <?php foreach ($allReports as $i => $report): ?>
      <?php if ($i > 0): ?><div class="page-break"></div><?php endif; ?>
      <?php require __DIR__ . '/_report.php'; ?>
    <?php endforeach; ?>
  </div>

  <script src="assets/html2pdf.bundle.min.js"></script>
  <script>
  (function () {
    var btn  = document.getElementById('dl-pdf-bulk');
    var node = document.getElementById('bulk-reports');
    if (!btn || !node) { return; }
    var name = <?= json_encode(($pdfName !== '' ? $pdfName : 'bulk-report') . '.pdf', JSON_UNESCAPED_UNICODE) ?>;

    btn.addEventListener('click', function () {
      if (typeof window.html2pdf !== 'function') { window.print(); return; }
      var label = btn.textContent;
      btn.disabled = true;
      btn.textContent = btn.dataset.busy || '...';

      window.html2pdf().set({
        filename:     name,
        margin:       [10, 2, 10, 2],
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'avoid-all'], before: ['.page-break'], avoid: ['tr', '.legend', '.comments'] }
      }).from(node).save().catch(function () {
        window.print();
      }).finally(function () {
        btn.disabled = false;
        btn.textContent = label;
      });
    });
  })();
  </script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
