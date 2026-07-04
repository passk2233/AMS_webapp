<?php
require __DIR__ . '/config.php';

// Best-effort server-side revoke, then clear the local session.
if (!empty($_SESSION['token'])) {
    api('POST', '/auth/logout', null);
}
session_unset();
session_destroy();
header('Location: ' . url('login'));
exit;
