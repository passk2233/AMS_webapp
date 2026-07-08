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
    // The two live calls this page needs — the open-window check and this
    // student's own answers — go out in one parallel batch (the answers stay
    // live so a just-submitted evaluation flips to "done" on the next load).
    $minePath = '/evaluation-results?student_id=' . (int) $studentId . '&limit=200';
    $batch    = api_multi_get([
        'win'  => '/open-evalu?limit=1',
        'mine' => $minePath . '&page=1',
    ], $st);
    // Open only if the newest window is active AND now is within its time
    // bounds — the same $isOpen the admin screen uses. Checking the clock (not
    // just inactive=0) is what closes evaluation once close_time passes.
    $win = api_list($batch['win'])[0] ?? null;
    $now = time();
    $winOpen = $win
        && (int) ($win['inactive'] ?? 0) === 0
        && (empty($win['open_time'])  || strtotime((string) $win['open_time'])  <= $now)
        && (empty($win['close_time']) || strtotime((string) $win['close_time']) >= $now);
    if (($st['win'] ?? 0) < 200 || ($st['win'] ?? 0) >= 300) {
        $status = 'error';
    } elseif (!$winOpen) {
        $status = 'closed';
    } else {
        $questions = api_list(cached_get('/evaluation-questions?is_active=1', 86400));
        $qCount    = count($questions);
        $sem       = active_semester();
        $plans     = study_plans($sem['id'] ?? null, (int) $groupId);
        $groups    = group_index();

        // A student's answers fit one 200-row page unless they evaluate 20+
        // plans; pull the remaining pages (still in parallel) when they don't.
        $mine  = api_list($batch['mine']);
        $pages = min((int) ($batch['mine']['meta']['total_pages'] ?? 1), 100);
        $rest  = [];
        for ($p = 2; $p <= $pages; $p++) {
            $rest[$p] = $minePath . '&page=' . $p;
        }
        foreach ($rest ? api_multi_get($rest) : [] as $data) {
            $mine = array_merge($mine, api_list($data));
        }

        $mineByPlan = [];
        foreach ($mine as $r) {
            $mineByPlan[(int) ($r['study_plan_id'] ?? 0)][] = $r;
        }

        foreach ($plans as $p) {
            $planId = (int) ($p['id'] ?? 0);
            $names  = plan_names($p, $groups);
            $targets[] = [
                'id'      => $planId,
                'teacher' => $names['teacher'],
                'subject' => $names['subject'],
                'class'   => $names['class'],
                'done'    => $qCount > 0 && count($mineByPlan[$planId] ?? []) >= $qCount,
            ];
        }
    }
}

$title = 'ປະເມີນອາຈານ';
require __DIR__ . '/views/student.php';
