<?php
declare(strict_types=1);

// Session + API base. The browser talks only to this PHP app (same origin),
// and PHP calls the Go API server-side via cURL — so there is no CORS at all.
// Override the API base with an API_URL env var if needed.
// ── Production hardening ────────────────────────────────────────────────────
// Detect real HTTPS (direct, or behind a TLS-terminating proxy). We mark the
// session cookie Secure only when actually on HTTPS, so local http:// dev keeps
// its session. On HTTPS we also stop printing errors and log them instead, so a
// stack trace never reaches a user.
// ponytail: HTTPS == "prod" here. Serving prod over plain http without a proxy?
// front it with one that sends X-Forwarded-Proto, or hardcode $https = true.
$https = ($_SERVER['HTTPS'] ?? '') !== ''
      || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
      || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
if ($https) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
ini_set('session.use_strict_mode', '1');        // reject attacker-supplied session ids
session_set_cookie_params([
    'secure'   => $https,
    'httponly' => true,                         // JS can't read it → XSS can't steal the session
    'samesite' => 'Lax',
]);
session_start();
define('API_BASE', getenv('API_URL') ?: 'https://api.phetsamone.xyz/api/v1');

// Wire the model + helper layer so a controller only needs require 'config.php'.
require __DIR__ . '/helpers.php';
require __DIR__ . '/models/api.php';
require __DIR__ . '/models/auth.php';
require __DIR__ . '/models/evaluation.php';
