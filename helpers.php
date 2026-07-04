<?php
declare(strict_types=1);

/** HTML-escape for views. */
function esc(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Absolute app URL for a clean route path, e.g. url('student/eval').
 * Prefixes the folder the app is mounted at ('' at site root, '/webapp' under
 * XAMPP htdocs) so links survive both deployments and nested paths.
 */
function url(string $path = ''): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Read a one-shot flash message (set after a PRG redirect) and clear it. */
function flash_take(): ?string
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return ($f !== null && $f !== '') ? (string) $f : null;
}
