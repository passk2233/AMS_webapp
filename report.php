<?php
require __DIR__ . '/config.php';
require_login();

$planId = (int) ($_GET['plan'] ?? 0);
if ($planId <= 0) {
    header('Location: ' . url(home_path()));
    exit;
}

// Admins see any plan; a teacher may only open a plan they own. Anyone else is
// bounced. The teacher's plan set is the same list their dashboard is built from.
if (($_SESSION['role'] ?? '') !== 'admin') {
    $ownIds = array_map(fn ($p) => (int) ($p['id'] ?? 0), teacher_plans((int) ($_SESSION['teacher_id'] ?? 0)));
    if (($_SESSION['role'] ?? '') !== 'teacher' || !in_array($planId, $ownIds, true)) {
        header('Location: ' . url('login'));
        exit;
    }
    // Same admin gate as the teacher dashboard: no direct-link bypass to a
    // plan's detail while results are unreleased.
    if (!teacher_results_visible()) {
        header('Location: ' . url('teacher'));
        exit;
    }
}

// Every page: a large class can exceed the API's 200-row page cap, which would
// otherwise drop answers and skew the averages.
$rows = api_get_all('/evaluation-results?study_plan_id=' . $planId);

// Real names from the plan (best-effort). /study-plans/{id} is empty in gateway
// mode, so the plan is found in the translated list — same source as admin.php.
$groups = group_index();
$sem    = active_semester();
$planMap = [];
foreach (study_plans($sem['id'] ?? null, null) as $p) {
    if ((int) ($p['id'] ?? 0) === $planId) {
        $planMap[$planId] = plan_names($p, $groups);
        break;
    }
}

// Real question text + category (and canonical order). Result rows don't embed
// the eva_question object, so without this the report shows "ຄຳຖາມ #id"/"ອື່ນໆ".
$questionMap = [];
foreach (api_list(cached_get('/evaluation-questions?limit=500', 86400)) as $q) {
    $qid = (int) ($q['eva_question_id'] ?? 0);
    if ($qid > 0) {
        $questionMap[$qid] = [
            'question' => (string) ($q['question'] ?? ''),
            'category' => (string) ($q['category'] ?? ''),
        ];
    }
}

$reports = reports_from_rows($rows, $planMap, $questionMap);
$report  = $reports[0] ?? null;

$title = 'ບົດລາຍງານການປະເມີນ';
require __DIR__ . '/views/report.php';
