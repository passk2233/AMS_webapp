<?php
/** @var int $planId @var ?array $report @var string $title */
require __DIR__ . '/layout/header.php';
?>
<div class="actions">
  <a class="btn ghost" href="<?= url('admin') ?>">‹ ກັບຄືນ</a>
  <?php if ($report): ?><button class="btn" id="dl-pdf" type="button" data-busy="ກຳລັງສ້າງ PDF...">ດາວໂຫຼດ PDF</button><?php endif; ?>
</div>

<?php if (!$report): ?>
  <div class="state">ບໍ່ມີຂໍ້ມູນການປະເມີນສຳລັບແຜນການສອນນີ້</div>
<?php else: ?>
  <?php require __DIR__ . '/_report.php'; ?>

  <?php
    // Filename: teacher + subject + class + date + semester.
    $sem = $sem ?? active_semester();
    $semLabel = semester_label($sem);
    $pdfParts = array_filter([
        $report['teacher'] ?? '',
        $report['subject'] ?? '',
        $report['class'] ?? '',
        date('d-m-Y'),
        $semLabel,
    ], fn ($p) => trim((string) $p) !== '');
    // Collapse any stray/double spaces (some source fields have trailing spaces).
    $pdfName = trim((string) preg_replace('/\s+/u', ' ', implode(' ', $pdfParts)));
  ?>
  <script src="<?= url('assets/html2pdf.bundle.min.js') ?>"></script>
  <script>
  (function () {
    var btn  = document.getElementById('dl-pdf');
    var node = document.querySelector('.report');
    if (!btn || !node) { return; }
    var name = <?= json_encode(($pdfName !== '' ? $pdfName : 'report') . '.pdf', JSON_UNESCAPED_UNICODE) ?>;

    btn.addEventListener('click', function () {
      // No html2pdf (offline/load fail) → fall back to the browser's print-to-PDF.
      if (typeof window.html2pdf !== 'function') { window.print(); return; }
      var label = btn.textContent;
      btn.disabled = true;
      btn.textContent = btn.dataset.busy || '...';
      // Default html2canvas mode — it captures the live DOM exactly as shown,
      // so Lao shapes correctly. (foreignObjectRendering renders blank; don't add it.)
      window.html2pdf().set({
        filename:     name,
        margin:       [10, 2, 10, 2],                // mm — tight to the page edge
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'avoid-all'], avoid: ['tr', '.legend', '.comments'] }
      }).from(node).save().catch(function () {
        window.print();                            // last-resort fallback
      }).finally(function () {
        btn.disabled = false;
        btn.textContent = label;
      });
    });
  })();
  </script>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
