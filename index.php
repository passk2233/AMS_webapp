<?php
require __DIR__ . '/config.php';

if (empty($_SESSION['token'])) {
    header('Location: login.php');
} elseif (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: student.php');
}
exit;
