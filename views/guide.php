<?php
/** @var string $title */
require __DIR__ . '/layout/header.php';

// Colour-coded score bands (matches the admin badge colours b9/b7/b5/b0).
$bands = [
    ['9 – 10', 'ການສອນມີຄຸນນະພາບດີຫຼາຍ',                          'var(--success)'],
    ['7 – 8',  'ການສອນມີຄຸນນະພາບດີ',                              '#2f9e44'],
    ['5 – 6',  'ການສອນມີຄຸນນະພາບພໍໃຊ້',                           'var(--warning)'],
    ['1 – 4',  'ການສອນຍັງບໍ່ມີຄຸນນະພາບພຽງພໍ ຕ້ອງໄດ້ປັບປຸງເພີ່ມ', 'var(--danger)'],
];

// Drop a screen-recording in at assets/guide.mp4 / .webm (played as a video) or
// assets/guide.gif (shown as a looping image) to enable the walkthrough.
$video = null;   // [path, mime] for <video>
$gif   = null;   // path for <img>
foreach (['assets/guide.mp4' => 'video/mp4', 'assets/guide.webm' => 'video/webm'] as $path => $mime) {
    if (is_file(__DIR__ . '/../' . $path)) { $video = [$path, $mime]; break; }
}
if (!$video && is_file(__DIR__ . '/../assets/guide.mp4')) {
    $gif = 'assets/guide.gif';
}
?>
<div class="card guide-hero">
  <h1>ຍິນດີຕ້ອນຮັບສູ່ການປະເມີນອາຈານ</h1>
  <p class="muted">ກະລຸນາອ່ານຄວາມໝາຍຂອງຄະແນນ ແລະ ເບິ່ງວິດີໂອແນະນຳ ກ່ອນເລີ່ມການປະເມີນ</p>
</div>

<h2 class="cat-head">ຄວາມໝາຍຂອງແຕ່ລະຊ່ວງຄະແນນ (1–10)</h2>
<div class="card">
  <ul class="score-bands">
    <?php foreach ($bands as [$range, $meaning, $color]): ?>
      <li>
        <span class="band-badge" style="background: <?= $color ?>"><?= esc($range) ?></span>
        <span class="band-text"><?= esc($meaning) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<h2 class="cat-head">ວິດີໂອແນະນຳການນຳໃຊ້</h2>
<div class="card">
  <?php if ($video): ?>
    <video class="guide-video" controls preload="metadata" playsinline>
      <source src="<?= esc($video[0]) ?>" type="<?= esc($video[1]) ?>">
      ໂປຣແກຣມຂອງທ່ານບໍ່ຮອງຮັບການຫຼິ້ນວິດີໂອ
    </video>
  <?php elseif ($gif): ?>
    <img class="guide-video" src="<?= esc($gif) ?>" alt="ວິດີໂອແນະນຳການນຳໃຊ້ການປະເມີນ" loading="lazy">
  <?php else: ?>
    <div class="guide-video placeholder" role="note">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
      </svg>
      <p>ຍັງບໍ່ມີວິດີໂອແນະນຳ</p>
      <p class="small muted">ເພີ່ມໄຟລ໌ <code>assets/guide.mp4</code> ຫຼື <code>assets/guide.gif</code> ເພື່ອສະແດງບ່ອນນີ້</p>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($planId)): ?>
  <a class="btn block" href="evaluate.php?plan=<?= $planId ?>">ເລີ່ມການປະເມີນ ›</a>
<?php else: ?>
  <a class="btn block" href="student.php">ເລີ່ມການປະເມີນ ›</a>
<?php endif; ?>

<?php require __DIR__ . '/layout/footer.php'; ?>
