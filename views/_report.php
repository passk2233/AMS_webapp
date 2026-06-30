<?php /** Printable report block. Expects $report. Shared by the on-screen view and the PDF document. */ ?>
  <div class="report">
    <div class="head">
      <div class="f"><span class="k">ອາຈານ:</span> <span class="v"><?= esc($report['teacher']) ?></span></div>
      <div class="f"><span class="k">ວັນທີ:</span> <span class="v"><?= date('d/m/Y') ?></span></div>
      <div class="f"><span class="k">ຫ້ອງ:</span> <span class="v"><?= esc($report['class']) ?></span></div>
      <div class="f"><span class="k">ວິຊາ:</span> <span class="v"><?= esc($report['subject']) ?></span></div>
    </div>

    <table>
      <thead>
        <tr><th>ລາຍການມາດຕະຖານຕ່າງໆ</th><th>ຄະແນນແຕ່ລະດ້ານ ຄະແນນເຕັມ (10)</th></tr>
      </thead>
      <tbody>
        <?php foreach ($report['categories'] as $c): ?>
          <tr class="band"><td colspan="2"><?= esc($c['title']) ?></td></tr>
          <?php foreach ($c['lines'] as $l): ?>
            <tr>
              <td><?= esc($l['text']) ?></td>
              <td class="score <?= score_class((float) $l['score']) ?>"><?= number_format($l['score'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <tr class="total"><td>ຄະແນນລວມ</td><td class="score"><?= number_format($report['total'], 2) ?></td></tr>
        <tr class="average"><td>ຄະແນນສະເລ່ຍ</td><td class="score"><?= number_format($report['average'], 2) ?></td></tr>
        <tr class="verdict"><td colspan="2"><?= esc(score_verdict((float) $report['average'])) ?></td></tr>
      </tbody>
    </table>

    <div class="legend">
      <h4>ໝາຍເຫດ:</h4>
      <ul>
        <?php foreach (score_legend() as $line): ?><li><?= esc($line) ?></li><?php endforeach; ?>
      </ul>
    </div>

    <div class="comments">
      <div class="ctitle">ຄຳຄິດເຫັນຂອງນັກສຶກສາ <?= esc($report['class']) ?></div>
      <div class="cbody">
        <?php if (count($report['comments']) === 0): ?>
          <span class="muted">ບໍ່ມີຄຳຄິດເຫັນ</span>
        <?php else: ?>
          <?php foreach ($report['comments'] as $c): ?><p>• <?= esc($c) ?></p><?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
