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
function require_teacher(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'teacher') {
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
    $student = $me['student'] ?? null;

    // roles arrive as strings or {name:…} objects; flatten to lowercase names,
    // then resolve one app role. teacher_id present == teacher even if the roles
    // array is terse.
    $roleNames = [];
    foreach ((array) ($me['roles'] ?? []) as $r) {
        $name        = is_array($r) ? ($r['name'] ?? $r['role'] ?? '') : $r;
        $roleNames[] = strtolower(trim((string) $name));
    }
    $explicit = strtolower((string) ($me['role'] ?? $me['primary_role'] ?? ''));
    if (in_array('admin', $roleNames, true) || $explicit === 'admin') {
        $role = 'admin';
    } elseif (in_array('teacher', $roleNames, true) || $explicit === 'teacher' || !empty($me['teacher_id'])) {
        $role = 'teacher';
    } else {
        $role = $explicit ?: ($roleNames[0] ?? '');
    }

    $_SESSION['role']         = $role;
    $_SESSION['user_id']      = $me['id'] ?? null;
    $_SESSION['teacher_id']   = $me['teacher_id'] ?? null;
    $_SESSION['student_id']   = $me['std_id'] ?? ($student['id'] ?? null);
    $_SESSION['std_group_id'] = $student['std_group_id'] ?? ($me['std_group_id'] ?? null);
    $_SESSION['name']         = $me['name'] ?? '';

    return in_array($role, ['admin', 'student', 'teacher'], true);
}
