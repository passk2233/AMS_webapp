<?php
require __DIR__ . '/config.php';
require_student();

$studentId = $_SESSION['student_id'] ?? null;
$groupId   = $_SESSION['std_group_id'] ?? null;

// Self-heal: the session ids are filled once at login from /auth/me, which
// resolves the student from a roster index. If that index was cold at login,
// the ids land empty and the page would show "not linked" for the whole
// session. Re-pull /auth/me once before giving up so a now-warm index links it.
if (!$studentId || !$groupId) {
    load_me();
    $studentId = $_SESSION['student_id'] ?? null;
    $groupId   = $_SESSION['std_group_id'] ?? null;
}

$flash   = flash_take();
$status  = 'ready';     // ready | notlinked | closed | error
$targets = [];

if (!$studentId || !$groupId) {
    $status = 'notlinked';
} else {
    $win = api('GET', '/open-evalu?inactive=0&limit=1');
    if (!$win['ok']) {
        $status = 'error';
    } elseif (count(api_list($win['data'])) === 0) {
        $status = 'closed';
    } else {
        $questions = api_list(api('GET', '/evaluation-questions?is_active=1')['data']);
        $qCount    = count($questions);
        $sem       = active_semester();
        $plans     = study_plans($sem['id'] ?? null, (int) $groupId);
        $groups    = group_index();

        foreach ($plans as $p) {
            $planId = (int) ($p['id'] ?? 0);
            $mine   = api_list(api('GET', '/evaluation-results?study_plan_id=' . $planId
                . '&student_id=' . (int) $studentId . '&limit=200')['data']);
            $names  = plan_names($p, $groups);
            $targets[] = [
                'id'      => $planId,
                'teacher' => $names['teacher'],
                'subject' => $names['subject'],
                'class'   => $names['class'],
                'done'    => $qCount > 0 && count($mine) >= $qCount,
            ];
        }
    }
}

$title = 'ປະເມີນອາຈານ';
require __DIR__ . '/views/student.php';
