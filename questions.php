<?php
require __DIR__ . '/config.php';
require_admin();

// ── Mutations (POST → redirect, PRG) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $q      = trim((string) ($_POST['question'] ?? ''));
    $cat    = trim((string) ($_POST['category'] ?? ''));
    $active = (int) ($_POST['is_active'] ?? 1);
    $body   = ['question' => $q, 'category' => $cat !== '' ? $cat : null, 'is_active' => $active];

    if ($action === 'create' && $q !== '') {
        api('POST', '/evaluation-questions', $body);
        $_SESSION['flash'] = 'ເພີ່ມຄຳຖາມແລ້ວ';
    } elseif ($action === 'update' && $id > 0 && $q !== '') {
        api('PUT', '/evaluation-questions/' . $id, $body);
        $_SESSION['flash'] = 'ບັນທຶກການແກ້ໄຂແລ້ວ';
    } elseif ($action === 'toggle' && $id > 0) {
        api('PUT', '/evaluation-questions/' . $id, $body);
        $_SESSION['flash'] = $active ? 'ເປີດໃຊ້ງານຄຳຖາມແລ້ວ' : 'ປິດໃຊ້ງານຄຳຖາມແລ້ວ';
    } elseif ($action === 'delete' && $id > 0) {
        api('DELETE', '/evaluation-questions/' . $id);
        $_SESSION['flash'] = 'ລຶບຄຳຖາມແລ້ວ';
    }
    header('Location: questions.php');
    exit;
}

$flash     = flash_take();
$questions = api_list(api('GET', '/evaluation-questions?limit=500')['data']);

// Prefill the form when editing.
$edit = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    foreach ($questions as $q) {
        if ((int) $q['eva_question_id'] === $editId) {
            $edit = $q;
            break;
        }
    }
}

// Existing categories (hint).
$cats = [];
foreach ($questions as $q) {
    $c = trim((string) ($q['category'] ?? ''));
    if ($c !== '') {
        $cats[$c] = true;
    }
}

// Group by category, preserving first-seen order.
$order = [];
$byCat = [];
foreach ($questions as $q) {
    $c = trim((string) ($q['category'] ?? ''));
    $c = $c === '' ? 'ອື່ນໆ' : $c;
    if (!isset($byCat[$c])) {
        $order[]   = $c;
        $byCat[$c] = [];
    }
    $byCat[$c][] = $q;
}

$title = 'ຈັດການຄຳຖາມ';
require __DIR__ . '/views/questions.php';
