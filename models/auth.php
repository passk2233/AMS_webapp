<?php
declare(strict_types=1);

// ── Auth guards ──────────────────────────────────────────────────────────────
function require_login(): void
{
    if (empty($_SESSION['token'])) {
        header('Location: ' . url('login'));
        exit;
    }
}
function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . url('login'));
        exit;
    }
}
function require_student(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'student') {
        header('Location: ' . url('login'));
        exit;
    }
}

/** Populate role + ids in the session from /auth/me. Returns true if usable. */
function load_me(): bool
{
    $me = api('GET', '/auth/me')['data'] ?? null;
    if (!is_array($me)) {
        return false;
    }
    $roles   = $me['roles'] ?? [];
    $role    = strtolower((string) ($me['role'] ?? ($me['primary_role'] ?? ($roles[0] ?? ''))));
    $student = $me['student'] ?? null;

    $_SESSION['role']         = $role;
    $_SESSION['user_id']      = $me['id'] ?? null;
    $_SESSION['student_id']   = $me['std_id'] ?? ($student['id'] ?? null);
    $_SESSION['std_group_id'] = $student['std_group_id'] ?? ($me['std_group_id'] ?? null);
    $_SESSION['name']         = $me['name'] ?? '';

    return $role === 'admin' || $role === 'student';
}
