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

// Results for the requested plans only, every page (the API caps a page at 200
// rows and ignores a bigger ?limit, so the old single ?limit=5000 fetch returned
// only the first 200 rows — most reports came back empty for a large selection).
$allRows = api_get_all('/evaluation-results?study_plan_ids=' . implode(',', $planIds));

// Build the plan name map for the requested plans.
$groups  = group_index();
$sem     = active_semester();
$planMap = [];
$wanted  = array_values(array_filter(
    study_plans($sem['id'] ?? null, null),
    fn ($p) => in_array((int) ($p['id'] ?? 0), $planIds, true)
));
warm_plan_group_counts($wanted, $groups); // parallel roster fetch, not one-by-one
foreach ($wanted as $p) {
    $planMap[(int) ($p['id'] ?? 0)] = plan_names($p, $groups);
}

// Real question text + category.
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

$allReports = reports_from_rows($allRows, $planMap, $questionMap);

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
