<?php
declare(strict_types=1);

/** HTML-escape for views. */
function esc(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Read a one-shot flash message (set after a PRG redirect) and clear it. */
function flash_take(): ?string
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return ($f !== null && $f !== '') ? (string) $f : null;
}
