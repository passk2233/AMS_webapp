</main>

<div class="nav-progress" role="status" aria-live="polite" aria-label="ກຳລັງໂຫຼດ"></div>

<dialog class="modal" id="app-confirm" aria-labelledby="confirm-title" aria-describedby="confirm-body">
  <form method="dialog" class="modal-card">
    <div class="modal-icon primary" aria-hidden="true">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>
    <h2 class="modal-title" id="confirm-title"></h2>
    <p class="modal-body" id="confirm-body"></p>
    <div class="modal-actions">
      <button class="btn ghost" value="cancel" autofocus></button>
      <button class="btn" value="confirm"></button>
    </div>
  </form>
</dialog>

<script>
(function () {
  // ── Navigation loading bar ────────────────────────────────────────────────
  var bar = document.querySelector('.nav-progress');
  function startProgress() { if (bar) bar.classList.add('is-active'); }
  function stopProgress()  { if (bar) bar.classList.remove('is-active'); }
  // Clear on bfcache restore and when a cancelled unload returns focus to us.
  window.addEventListener('pageshow', stopProgress);
  window.addEventListener('focus', stopProgress);

  // ── Confirm dialog ────────────────────────────────────────────────────────
  var dlg = document.getElementById('app-confirm');
  var icon = dlg.querySelector('.modal-icon');
  var ok   = dlg.querySelector('[value=confirm]');
  var no   = dlg.querySelector('[value=cancel]');
  var pending = null;

  // Populate + open the shared dialog. opt: {title, body, confirmLabel,
  // cancelLabel, variant: 'primary'|'danger', onConfirm}.
  window.confirmAction = function (opt) {
    dlg.querySelector('.modal-title').textContent = opt.title || '';
    dlg.querySelector('.modal-body').textContent  = opt.body || '';
    ok.textContent = opt.confirmLabel || 'ຕົກລົງ';
    no.textContent = opt.cancelLabel || 'ຍົກເລີກ';
    icon.className = 'modal-icon ' + (opt.variant === 'danger' ? 'danger' : 'primary');
    ok.className   = 'btn' + (opt.variant === 'danger' ? ' danger' : '');
    pending = opt.onConfirm || null;
    if (typeof dlg.showModal === 'function') {
      dlg.showModal();
    } else if (window.confirm((opt.title ? opt.title + '\n\n' : '') + (opt.body || '')) && pending) {
      pending();   // fallback for browsers without <dialog>
    }
  };
  dlg.addEventListener('close', function () {
    var run = dlg.returnValue === 'confirm' ? pending : null;
    pending = null;
    if (run) run();
  });

  // ── Delegated link handling: logout warning + loading bar ─────────────────
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented) return;
    if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target === '_blank' || a.hasAttribute('download')) return;

    if (a.classList.contains('logout')) {            // warn before signing out
      e.preventDefault();
      window.confirmAction({
        title: 'ອອກຈາກລະບົບ?',
        body: 'ທ່ານຕ້ອງການອອກຈາກລະບົບແທ້ບໍ?',
        confirmLabel: 'ອອກຈາກລະບົບ',
        cancelLabel: 'ຍົກເລີກ',
        variant: 'danger',
        onConfirm: function () { startProgress(); location.href = a.href; }
      });
      return;
    }
    var href = a.getAttribute('href') || '';
    if (href.charAt(0) === '#') return;
    if (a.origin !== location.origin) return;        // external / mailto / tel
    startProgress();                                  // internal navigation
  }, false);

  // Forms that reach the server (and weren't cancelled by a page handler).
  document.addEventListener('submit', function (e) {
    if (!e.defaultPrevented) startProgress();
  }, false);
})();
</script>
</body>
</html>
