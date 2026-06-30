<?php
require __DIR__ . '/config.php';
require_admin();

// Accept comma-separated plan IDs, e.g. ?plans=1,2,3
$raw = trim((string) ($_GET['plans'] ?? ''));
if ($raw === '') {
    header('Location: admin.php');
    exit;
}
$planIds = array_values(array_unique(array_filter(
    array_map('intval', explode(',', $raw)),
    fn ($id) => $id > 0
)));
if (count($planIds) === 0) {
    header('Location: admin.php');
    exit;
}

// Fetch all evaluation results once (limit high enough for bulk).
$res = api('GET', '/evaluation-results?limit=5000');
$allRows = $res['ok'] ? api_list($res['data']) : [];

// Build the plan name map for the requested plans.
$groups  = group_index();
$sem     = active_semester();
$planMap = [];
foreach (study_plans($sem['id'] ?? null, null) as $p) {
    $pid = (int) ($p['id'] ?? 0);
    if (in_array($pid, $planIds, true)) {
        $planMap[$pid] = plan_names($p, $groups);
    }
}

// Real question text + category.
$questionMap = [];
foreach (api_list(api('GET', '/evaluation-questions?limit=500')['data']) as $q) {
    $qid = (int) ($q['eva_question_id'] ?? 0);
    if ($qid > 0) {
        $questionMap[$qid] = [
            'question' => (string) ($q['question'] ?? ''),
            'category' => (string) ($q['category'] ?? ''),
        ];
    }
}

// Filter rows to only the requested plans.
$filteredRows = array_filter($allRows, fn ($r) =>
    in_array((int) ($r['study_plan_id'] ?? 0), $planIds, true)
);

$allReports = reports_from_rows(array_values($filteredRows), $planMap, $questionMap);

// Sort reports in the same order as the requested plan IDs.
$posMap = array_flip($planIds);
usort($allReports, function ($a, $b) use ($posMap) {
    $pa = $posMap[(int) $a['plan_id']] ?? PHP_INT_MAX;
    $pb = $posMap[(int) $b['plan_id']] ?? PHP_INT_MAX;
    return $pa <=> $pb;
});

// Build a descriptive PDF name from the label query parameter.
$pdfLabel = trim((string) ($_GET['label'] ?? ''));
$sem      = $sem ?? active_semester();
$semLabel = semester_label($sem);
$pdfParts = array_filter([$pdfLabel, date('d-m-Y'), $semLabel], fn ($p) => trim((string) $p) !== '');
$pdfName  = trim((string) preg_replace('/\s+/u', ' ', implode(' ', $pdfParts)));

$title = 'ບົດລາຍງານລວມ';
require __DIR__ . '/views/report_bulk.php';
