<?php
require __DIR__ . '/config.php';
require_admin();

// Evaluations run every semester — let the admin pick which one to view.
$semesters     = semesters();
$selectedSemId = (int) ($_GET['semester'] ?? 0);
if ($selectedSemId <= 0) {
    $selectedSemId = (int) (active_semester()['id'] ?? 0);
}

$reports = [];

// Names + scope come from the selected semester's study plans.
$planMap = [];
$groups  = group_index();
$plans   = study_plans($selectedSemId ?: null, null);
warm_plan_group_counts($plans, $groups); // parallel roster fetch, not one-by-one
foreach ($plans as $p) {
    $planMap[(int) ($p['id'] ?? 0)] = plan_names($p, $groups);
}

// Results for exactly this semester's plans, every page. The API caps a page at
// 200 rows and ignores a bigger ?limit, so the old single ?limit=1000 fetch saw
// only the first 200 global rows (oldest first, across all semesters) — plans
// past that showed a 0/- placeholder even though their evaluations existed.
$planIds = array_values(array_filter(array_keys($planMap), fn ($id) => $id > 0));
$ok      = true;
$allRows = $planIds
    ? api_get_all('/evaluation-results?study_plan_ids=' . implode(',', $planIds), $ok)
    : [];
$error   = !$ok;

if (!$error) {
    $reportMap = [];
    foreach (reports_from_rows($allRows, $planMap) as $report) {
        $reportMap[(int) $report['plan_id']] = $report;
    }
    // Only this semester's plans (placeholder when nobody has evaluated yet).
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
    usort($reports, function ($a, $b) {
        foreach (['teacher', 'subject', 'class'] as $key) {
            $cmp = strnatcasecmp((string) ($a[$key] ?? ''), (string) ($b[$key] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
        }
        return 0;
    });
}

$groups = [];
foreach ($reports as $report) {
    $teacher = trim((string) ($report['teacher'] ?? '')) ?: '-';
    $subject = trim((string) ($report['subject'] ?? '')) ?: '-';
    if (!isset($groups[$teacher])) {
        $groups[$teacher] = ['subjects' => []];
    }
    if (!isset($groups[$teacher]['subjects'][$subject])) {
        $groups[$teacher]['subjects'][$subject] = [];
    }
    $groups[$teacher]['subjects'][$subject][] = $report;
}

$title = 'ຜົນການປະເມີນ';
require __DIR__ . '/views/admin.php';
