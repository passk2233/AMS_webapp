<?php /** @var string $title */ ?>
<!doctype html>
<html lang="lo">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#3a57e8">
<title><?= esc($title ?? 'AMS_UP — ການປະເມີນ') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../../assets/style.css') ?: '1' ?>">
</head>
<body>
<?php $home = (($_SESSION['role'] ?? '') === 'admin') ? 'admin.php' : 'student.php'; ?>
<header class="topbar">
  <div class="topbar-in">
    <a class="brand" href="<?= $home ?>">ການປະເມີນອາຈານ</a>
    <nav>
      <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <a href="admin.php">ຜົນການປະເມີນ</a>
        <a href="questions.php">ຄຳຖາມ</a>
        <a href="window.php">ໄລຍະປະເມີນ</a>
      <?php else: ?>
        <a href="student.php">ວິຊາ</a>
        <a href="guide.php">ຄູ່ມືການໃຊ້</a>
      <?php endif; ?>
      <a href="logout.php" class="logout">ອອກຈາກລະບົບ</a>
    </nav>
  </div>
</header>
<main class="wrap">
