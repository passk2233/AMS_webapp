<?php
require __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string) ($_POST['username'] ?? ''));
    $p = (string) ($_POST['password'] ?? '');
    if ($u === '' || $p === '') {
        $error = 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ';
    } else {
        $r = api('POST', '/auth/login', [
            'username'     => $u,
            'password'     => $p,
            'platform'     => 'web',
            'device_token' => null,
        ], false);

        if ($r['status'] === 200 && !empty($r['data']['token'])) {
            session_regenerate_id(true); // fresh id on login → defeats session fixation
            $_SESSION['token'] = $r['data']['token'];
            if (load_me()) {
                header('Location: ' . url(home_path()));
                exit;
            }
            // Authenticated but not a usable role for this app.
            session_unset();
            $error = 'ບັນຊີນີ້ບໍ່ສາມາດໃຊ້ໃນລະບົບປະເມີນ';
        } elseif ($r['status'] === 401) {
            $error = 'ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ';
        } else {
            $error = 'ເຊື່ອມຕໍ່ເຊີບເວີບໍ່ໄດ້ ກະລຸນາລອງໃໝ່';
        }
    }
}

require __DIR__ . '/views/login.php';
