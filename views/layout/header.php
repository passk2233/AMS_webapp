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
<link rel="stylesheet" href="<?= url('assets/style.css') ?>?v=<?= @filemtime(__DIR__ . '/../../assets/style.css') ?: '1' ?>">
</head>
<body>
<?php $role = $_SESSION['role'] ?? ''; $home = url(home_path()); ?>
<header class="topbar">
  <div class="topbar-in">
    <a class="brand" href="<?= $home ?>">ການປະເມີນອາຈານ</a>
    <nav>
      <?php if ($role === 'admin'): ?>
        <a href="<?= url('admin') ?>">ຜົນການປະເມີນ</a>
        <a href="<?= url('admin/questions') ?>">ຄຳຖາມ</a>
        <a href="<?= url('admin/window') ?>">ໄລຍະປະເມີນ</a>
      <?php elseif ($role === 'teacher'): ?>
        <a href="<?= url('teacher') ?>">ຜົນການປະເມີນຂອງຂ້ອຍ</a>
      <?php else: ?>
        <a href="<?= url('student') ?>">ວິຊາ</a>
        <a href="<?= url('student/guide') ?>">ຄູ່ມືການໃຊ້</a>
      <?php endif; ?>
      <a href="<?= url('rooms') ?>">ການໃຊ້ຫ້ອງ</a>
      <a href="<?= url('logout') ?>" class="logout">ອອກຈາກລະບົບ</a>
    </nav>
  </div>
</header>
<main class="wrap">
