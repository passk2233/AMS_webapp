<?php
require __DIR__ . '/config.php';
require_teacher();

// teacher_id is set at login from /auth/me; re-pull once if a cold index left
// it empty (same self-heal as student.php).
$teacherId = (int) ($_SESSION['teacher_id'] ?? 0);
if ($teacherId <= 0) {
    load_me();
    $teacherId = (int) ($_SESSION['teacher_id'] ?? 0);
}

// Semester filter over the teacher's own plans (default: active semester).
$semesters = semesters();
// Default "all": the teacher endpoint's rows don't reliably carry semaster_id,
// so narrowing is opt-in (0 = all) rather than a default that could hide plans.
$selectedSemId = (int) ($_GET['semester'] ?? 0);

// Admin gate: teachers only see results once the admin releases them.
$resultsVisible = teacher_results_visible();

$groups   = group_index();
$allPlans = teacher_plans($teacherId);

// Filter to the chosen semester client-side (the teacher endpoint returns every
// semester). 0 = "ທັງໝົດ" (all).
$plans = $selectedSemId > 0
    ? array_values(array_filter($allPlans, fn ($p) =>
        (int) ($p['semaster_id'] ?? $p['semester_id'] ?? 0) === $selectedSemId))
    : $allPlans;

warm_plan_group_counts($plans, $groups);
$planMap = [];
foreach ($plans as $p) {
    $planMap[(int) ($p['id'] ?? 0)] = plan_names($p, $groups);
}

$planIds = array_values(array_filter(array_keys($planMap), fn ($id) => $id > 0));
$ok      = true;
$allRows = ($resultsVisible && $planIds)
    ? api_get_all('/evaluation-results?study_plan_ids=' . implode(',', $planIds), $ok)
    : [];
$error   = !$ok;

$reports = [];
if (!$error && $resultsVisible) {
    $reportMap = [];
    foreach (reports_from_rows($allRows, $planMap) as $report) {
        $reportMap[(int) $report['plan_id']] = $report;
    }
    foreach ($planMap as $planId => $names) {
        $reports[] = $reportMap[$planId] ?? [
            'plan_id'           => $planId,
            'teacher'           => $names['teacher'] ?? '-',
            'subject'           => $names['subject'] ?? '',
            'class'             => $names['class'] ?? '',
            'expected_students' => (int) ($names['expected_students'] ?? 0),
            'respondents'       => 0,
            'categories'        => [],
            'comments'          => [],
            'total'             => 0,
            'average'           => 0,
        ];
    }
}

// Group this teacher's reports by subject → classes (no teacher level: it's one
// teacher).
$bySubject = [];
foreach ($reports as $report) {
    $subject = trim((string) ($report['subject'] ?? '')) ?: '-';
    $bySubject[$subject][] = $report;
}
ksort($bySubject, SORT_NATURAL | SORT_FLAG_CASE);

$title = 'ຜົນການປະເມີນຂອງຂ້ອຍ';
// $resultsVisible tells the view to show "not released yet" vs "no data".
require __DIR__ . '/views/teacher.php';
