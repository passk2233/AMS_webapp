<?php
// Front controller: clean role-prefixed URLs (student/eval, admin/report)
// mapped onto the page-per-route controllers. Apache rewrites every
// non-file request here (.htaccess); php -S uses this file as its router.

// php -S dev server: let real files (assets, old *.php URLs) be served as-is.
if (PHP_SAPI === 'cli-server') {
    $f = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($f)) {
        return false;
    }
}

$routes = [
    'login'             => 'login.php',
    'logout'            => 'logout.php',
    'student'           => 'student.php',
    'student/eval'      => 'evaluate.php',
    'student/guide'     => 'guide.php',
    'admin'             => 'admin.php',
    'admin/report'      => 'report.php',
    'admin/report-bulk' => 'report_bulk.php',
    'admin/questions'   => 'questions.php',
    'admin/window'      => 'window.php',
    'teacher'           => 'teacher.php',
    'report'            => 'report.php',   // shared: admin + owning teacher (report.php gates)
    'rooms'             => 'rooms.php',     // room-usage viewer, any logged-in user
];

// Strip the mount folder (e.g. /webapp) so routes match at any depth.
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$path = trim(substr((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen($base)), '/');

if (isset($routes[$path])) {
    require __DIR__ . '/' . $routes[$path];
    exit;
}

require __DIR__ . '/config.php';

if ($path !== '') {
    http_response_code(404);
    $title = '404';
    require __DIR__ . '/views/layout/header.php';
    echo '<div class="state"><div class="state-title">404 — ບໍ່ພົບໜ້ານີ້</div>'
       . '<a class="btn ghost" href="' . url() . '">ກັບໜ້າຫຼັກ</a></div>';
    require __DIR__ . '/views/layout/footer.php';
    exit;
}

// Home: send to the role's landing page.
if (empty($_SESSION['token'])) {
    header('Location: ' . url('login'));
} else {
    header('Location: ' . url(home_path()));
}
exit;
