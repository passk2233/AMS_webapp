<?php
declare(strict_types=1);

// Session + API base. The browser talks only to this PHP app (same origin),
// and PHP calls the Go API server-side via cURL — so there is no CORS at all.
// Override the API base with an API_URL env var if needed.
session_start();
define('API_BASE', getenv('API_URL') ?: 'https://api.phetsamone.xyz/api/v1');

// Wire the model + helper layer so a controller only needs require 'config.php'.
require __DIR__ . '/helpers.php';
require __DIR__ . '/models/api.php';
require __DIR__ . '/models/auth.php';
require __DIR__ . '/models/evaluation.php';
