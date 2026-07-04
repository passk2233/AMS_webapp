<?php
/** @var int $planId @var array $questions @var array $names @var string $error @var string $title */
require __DIR__ . '/layout/header.php';
$missing = $missing ?? [];
?>
<div class="actions">
  <a class="btn ghost" href="<?= url('student') ?>">‹ ກັບຄືນ</a>
</div>

<form method="post" action="<?= url('student/eval') ?>?plan=<?= $planId ?>" data-plan="<?= $planId ?>">
  <input type="hidden" name="plan" value="<?= $planId ?>">

  <div class="card">
    <div class="eval-title"><?= esc($names['subject']) ?></div>
    <div class="sub muted"><?= esc($names['teacher']) ?></div>
    <?php if ($names['class'] !== ''): ?><div class="sub muted small"><?= esc($names['class']) ?></div><?php endif; ?>
  </div>

  <div class="scale-help">
    <div class="scale-help-title">ⓘ ຄວາມໝາຍຂອງຄະແນນ (1–10)</div>
    <ul>
      <?php foreach (score_legend() as $line): ?><li><?= esc($line) ?></li><?php endforeach; ?>
    </ul>
  </div>

  <?php if ($error !== ''): ?><div class="error" role="alert"><?= esc($error) ?></div><?php endif; ?>

  <?php
    // Group questions by category, preserving first-seen order (matches the
    // admin questions screen). One card per category instead of one per question.
    $grouped = [];
    $catOrder = [];
    foreach ($questions as $q) {
        $c = trim((string) ($q['category'] ?? ''));
        if (!isset($grouped[$c])) {
            $catOrder[] = $c;
            $grouped[$c] = [];
        }
        $grouped[$c][] = $q;
    }
    foreach ($catOrder as $c): ?>
        <?php if ($c !== ''): ?><h2 class="cat-head"><?= esc($c) ?></h2><?php endif; ?>
        <div class="card qgroup">
        <?php foreach ($grouped[$c] as $q):
            $qid   = (int) $q['eva_question_id'];
            $isErr = in_array($qid, $missing, true); ?>
          <div class="qrow">
            <div class="qtext" id="q-<?= $qid ?>"><?= esc($q['question']) ?></div>
            <div class="score-stepper<?= $isErr ? ' is-error' : '' ?>" data-qid="<?= $qid ?>">
              <input
                class="score-input"
                type="number"
                name="score[<?= $qid ?>]"
                value="<?= esc((string) ($_POST['score'][$qid] ?? '')) ?>"
                min="1"
                max="10"
                step="1"
                inputmode="numeric"
                onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                required
                aria-labelledby="q-<?= $qid ?>"<?= $isErr ? ' aria-invalid="true"' : '' ?>
              >
              <div class="score-actions">
                <button class="score-btn" type="button" data-step="-1" aria-label="ຫຼຸດຄະແນນ">−</button>
                <button class="score-btn" type="button" data-step="1" aria-label="ເພີ່ມຄະແນນ">+</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
  <?php endforeach; ?>

  <div class="card">
    <label class="field field--flush">
      <span>ຄຳຄິດເຫັນເພີ່ມເຕີມ (ບໍ່ບັງຄັບ)</span>
      <textarea class="input" name="comment" rows="4" placeholder="ຂຽນຄຳຄິດເຫັນ..."><?= esc($_POST['comment'] ?? '') ?></textarea>
    </label>
  </div>

  <p class="eval-progress" aria-live="polite">ໃຫ້ຄະແນນແລ້ວ <span data-count>0</span> / <?= count($questions) ?> ຂໍ້</p>
  <button class="btn block" type="submit" data-busy="ກຳລັງສົ່ງ...">ສົ່ງການປະເມີນ</button>
</form>

<script>
(function () {
  var form     = document.querySelector('form');
  var draftKey = 'eval-draft-' + (form.getAttribute('data-plan') || '0');
  var inputs   = Array.prototype.slice.call(form.querySelectorAll('.score-input'));
  var comment  = form.querySelector('textarea[name=comment]');
  var counter  = form.querySelector('[data-count]');
  var progress = form.querySelector('.eval-progress');
  var submit   = form.querySelector('button[type=submit]');

  function clamp(n) { return Math.max(1, Math.min(10, n)); }
  function scored() {
    return inputs.filter(function (i) { var n = parseInt(i.value, 10); return n >= 1 && n <= 10; }).length;
  }
  function updateProgress() {
    var n = scored();
    if (counter) { counter.textContent = n; }
    if (progress) { progress.classList.toggle('is-complete', inputs.length > 0 && n === inputs.length); }
  }
  function saveDraft() {
    try {
      var s = {};
      inputs.forEach(function (i) { if (i.value !== '') { s[i.name] = i.value; } });
      localStorage.setItem(draftKey, JSON.stringify({ scores: s, comment: comment ? comment.value : '' }));
    } catch (e) {}
  }
  function onChange() {
    updateProgress();
    saveDraft();
  }

  // ── Per-stepper behaviour ─────────────────────────────────────────────────
  document.querySelectorAll('.score-stepper').forEach(function (group) {
    var input = group.querySelector('.score-input');

    // null = unset (empty). A value only ever exists in the valid 1–10 range.
    function current() { var n = parseInt(input.value, 10); return Number.isNaN(n) ? null : n; }
    function show(value) {
      input.value = value === null ? '' : value;
      if (value !== null) { group.classList.remove('is-error'); input.removeAttribute('aria-invalid'); }
    }

    group.querySelectorAll('.score-btn').forEach(function (button) {
      button.addEventListener('click', function () {
        var step = parseInt(button.dataset.step, 10);
        var c = current();
        show(c === null ? (step > 0 ? 1 : null) : clamp(c + step));  // first "+" picks 1; "−" stays unset
        input.focus();
        onChange();
      });
    });

    input.addEventListener('input', function () {
      if (input.value !== '') { var c = current(); show(c === null ? null : clamp(c)); }
      input.setCustomValidity('');             // clear once they engage the field
      onChange();
    });
    // Lao message instead of the browser's English "Please fill out this field".
    input.addEventListener('invalid', function () {
      input.setCustomValidity('ກະລຸນາໃຫ້ຄະແນນ 1 ຫາ 10');
    });
  });

  if (comment) { comment.addEventListener('input', onChange); }

  // ── Restore an in-progress draft (survives a back-tap or interruption) ─────
  // Only fill fields the server left empty, so a failed-submit re-render wins.
  try {
    var d = JSON.parse(localStorage.getItem(draftKey) || 'null');
    if (d) {
      inputs.forEach(function (i) {
        if (i.value === '' && d.scores && d.scores[i.name] != null) { i.value = d.scores[i.name]; }
      });
      if (comment && comment.value === '' && d.comment) { comment.value = d.comment; }
    }
  } catch (e) {}
  updateProgress();

  // ── Server-side validation error: jump to the first un-scored question ─────
  var firstErr = document.querySelector('.score-stepper.is-error .score-input');
  if (firstErr) {
    var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    firstErr.focus({ preventScroll: true });
    firstErr.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
  }

  // ── Submit: confirm (irreversible) → spinner → send ────────────────────────
  var confirmed = false;   // set once the student confirms the warning dialog
  var submitting = false;  // suppresses the unsaved-progress guard on real sends

  form.addEventListener('submit', function (e) {
    if (submit.disabled) { e.preventDefault(); return; }   // fires only after native validation passes

    // First pass: native validation passed but not yet confirmed. Warn that the
    // submission is final, then re-submit programmatically on confirm.
    if (!confirmed) {
      e.preventDefault();
      var send = function () {
        confirmed = true; submitting = true;
        if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
      };
      if (window.confirmAction) {
        window.confirmAction({
          title: 'ຢືນຢັນການສົ່ງ',
          body: 'ເມື່ອສົ່ງແລ້ວ ຈະບໍ່ສາມາດແກ້ໄຂການປະເມີນວິຊານີ້ໄດ້. ຢືນຢັນການສົ່ງບໍ?',
          confirmLabel: 'ສົ່ງການປະເມີນ',
          cancelLabel: 'ກັບໄປກວດ',
          variant: 'primary',
          onConfirm: send
        });
      } else { send(); }
      return;
    }

    // Confirmed pass: lock the button and let the POST go through.
    submit.disabled = true;
    submit.innerHTML = '<span class="spinner" aria-hidden="true"></span>' + submit.dataset.busy;
    // ponytail: server preserves input on failure, so dropping the draft here is safe.
    try { localStorage.removeItem(draftKey); } catch (e2) {}
  });

  // ── Unsaved-progress guard: warn before leaving with un-submitted scores ────
  window.addEventListener('beforeunload', function (e) {
    if (!submitting && scored() > 0) { e.preventDefault(); e.returnValue = ''; }
  });
})();
</script>
<?php require __DIR__ . '/layout/footer.php'; ?>
