<?php
require __DIR__ . '/config.php';
require_admin();

// Evaluations run every semester — let the admin pick which one to view.
$semesters     = semesters();
$selectedSemId = (int) ($_GET['semester'] ?? 0);
if ($selectedSemId <= 0) {
    $selectedSemId = (int) (active_semester()['id'] ?? 0);
}

$res     = api('GET', '/evaluation-results?limit=1000');
$error   = !$res['ok'];
$reports = [];

if (!$error) {
    // Names + scope come from the selected semester's study plans.
    $planMap = [];
    $groups  = group_index();
    $plans   = study_plans($selectedSemId ?: null, null);
    warm_plan_group_counts($plans, $groups); // parallel roster fetch, not one-by-one
    foreach ($plans as $p) {
        $planMap[(int) ($p['id'] ?? 0)] = plan_names($p, $groups);
    }
    $reportMap = [];
    foreach (reports_from_rows(api_list($res['data']), $planMap) as $report) {
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
