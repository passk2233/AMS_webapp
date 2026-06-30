<?php /** @var string $error */ ?>
<!doctype html>
<html lang="lo">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#3a57e8">
<title>AMS_UP — ເຂົ້າສູ່ລະບົບ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-screen">
  <form class="card login-card" method="post" action="login.php">
    <div class="login-logo" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20h9"/>
        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
      </svg>
    </div>
    <h1>ການປະເມີນອາຈານ</h1>
    <p class="muted">AMS_UP — ເຂົ້າສູ່ລະບົບ</p>

    <label class="field">
      <span>ອີເມວ</span>
      <input class="input" type="email" name="email" autofocus autocomplete="email"
             inputmode="email" value="<?= esc($_POST['email'] ?? '') ?>">
    </label>
    <label class="field">
      <span>ລະຫັດຜ່ານ</span>
      <input class="input" type="password" name="password">
    </label>

    <?php if ($error !== ''): ?>
      <div class="error"><?= esc($error) ?></div>
    <?php endif; ?>

    <button class="btn block" type="submit" data-busy="ກຳລັງເຂົ້າ...">ເຂົ້າສູ່ລະບົບ</button>
    <p class="muted small" style="text-align:center;margin-top:14px;">ນັກສຶກສາ ແລະ ອາຈານ: ໃຊ້ອີເມວເຂົ້າສູ່ລະບົບ</p>
  </form>
</div>

<div class="nav-progress" role="status" aria-live="polite" aria-label="ກຳລັງໂຫຼດ"></div>
<script>
// Login round-trips through the auth API + roster lookup — show the wait.
document.querySelector('form').addEventListener('submit', function () {
  var b = this.querySelector('button[type=submit]');
  if (b.disabled) return;
  b.disabled = true;
  b.innerHTML = '<span class="spinner" aria-hidden="true"></span>' + b.dataset.busy;
  document.querySelector('.nav-progress').classList.add('is-active');
});
</script>
</body>
</html>
