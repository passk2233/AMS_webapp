<?php
require __DIR__ . '/config.php';
require_admin();

$planId = (int) ($_GET['plan'] ?? 0);
if ($planId <= 0) {
    header('Location: admin.php');
    exit;
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
