<?php
require __DIR__ . '/config.php';
require_student();

$studentId = (int) ($_SESSION['student_id'] ?? 0);
$planId    = (int) ($_GET['plan'] ?? $_POST['plan'] ?? 0);
if ($planId <= 0) {
    header('Location: student.php');
    exit;
}

// Never write a result against an unresolved student (would corrupt data).
// Re-pull /auth/me once if the session id is missing; bounce to the linking
// screen if it still can't be resolved. Mirrors the self-heal on student.php.
if ($studentId <= 0) {
    load_me();
    $studentId = (int) ($_SESSION['student_id'] ?? 0);
    if ($studentId <= 0) {
        header('Location: student.php');
        exit;
    }
}

$questions = api_list(api('GET', '/evaluation-questions?is_active=1')['data']);

// Plan header info (best-effort) — fetched up front so it's available for the
// success flash and for re-rendering the form after a validation error.
// In gateway mode /study-plans/{id} hits the empty local table (404), so the
// names are read from the translated list instead — the same source as student.php.
$groups = group_index();
$sem    = active_semester();
$groupId = (int) ($_SESSION['std_group_id'] ?? 0);
$plan   = null;
foreach (study_plans($sem['id'] ?? null, $groupId ?: null) as $p) {
    if ((int) ($p['id'] ?? 0) === $planId) {
        $plan = $p;
        break;
    }
}
$names = $plan ? plan_names($plan, $groups) : ['teacher' => '-', 'subject' => 'ປະເມີນອາຈານ', 'class' => ''];

$error   = '';
$missing = [];   // eva_question_ids that were left un-scored or out of range

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scores  = $_POST['score'] ?? [];
    $comment = trim((string) ($_POST['comment'] ?? ''));

    foreach ($questions as $q) {
        $v = (int) ($scores[$q['eva_question_id']] ?? 0);
        if ($v < 1 || $v > 10) {
            $missing[] = (int) $q['eva_question_id'];
        }
    }

    if (count($questions) === 0 || count($missing) > 0) {
        $error = 'ກະລຸນາໃຫ້ຄະແນນຂໍ້ທີ່ໝາຍສີແດງກ່ອນສົ່ງ';
    } else {
        // ponytail: non-atomic — one POST per question. A mid-loop failure can
        // leave a partial record; true atomicity needs a batch endpoint on the
        // Go API. The submit button is disabled client-side to stop double-taps,
        // which is the common cause of duplicate/partial writes in practice.
        $sent  = true;
        $first = true;
        foreach ($questions as $q) {
            $qid = (int) $q['eva_question_id'];
            $res = api('POST', '/evaluation-results', [
                'study_plan_id'  => $planId,
                'student_id'     => $studentId,
                'eva_question_id' => $qid,
                'score'          => (int) $scores[$qid],
                'comment'        => ($first && $comment !== '') ? $comment : null,
            ]);
            $first = false;
            if (!$res['ok']) {
                $sent = false;
                break;
            }
        }
        if ($sent) {
            $_SESSION['flash'] = 'ສົ່ງການປະເມີນ ' . $names['subject'] . ' ສຳເລັດ';
            header('Location: student.php');
            exit;
        }
        $error = 'ສົ່ງການປະເມີນບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່';
    }
}

$title = 'ປະເມີນອາຈານ';
require __DIR__ . '/views/evaluate.php';
