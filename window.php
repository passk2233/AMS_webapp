<?php
require __DIR__ . '/config.php';
require_admin();

// ── Mutations (POST → redirect, PRG) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'open') {
        $openAt  = strtotime((string) ($_POST['open_time'] ?? ''));
        $closeAt = strtotime((string) ($_POST['close_time'] ?? ''));
        if (!$openAt || !$closeAt) {
            $_SESSION['flash'] = 'ກະລຸນາເລືອກເວລາເປີດ ແລະ ເວລາປິດ';
        } elseif ($closeAt <= $openAt) {
            $_SESSION['flash'] = 'ເວລາປິດຕ້ອງຊ້າກວ່າເວລາເປີດ';
        } else {
            $res = api('POST', '/open-evalu', [
                'study_plan_id' => null, // NULL = the global semester-wide gate
                'open_time'     => date('c', $openAt),
                'close_time'    => date('c', $closeAt),
                'inactive'      => 0,
            ]);
            if ($res['ok']) {
                // Same courtesy the mobile admin does: tell every student the
                // evaluation opened. Best-effort — the window row already exists.
                api('POST', '/notifications?audience=students', [
                    'title'   => 'ເປີດການປະເມີນອາຈານ',
                    'message' => 'ໄລຍະການປະເມີນອາຈານໄດ້ເປີດແລ້ວ. ກະລຸນາເຂົ້າປະເມີນລະຫວ່າງ '
                        . date('d/m/Y H:i', $openAt) . ' ຫາ ' . date('d/m/Y H:i', $closeAt) . '.',
                    'type'    => 'evaluation_open',
                ]);
                $_SESSION['flash'] = 'ເປີດການປະເມີນແລ້ວ ແລະ ສົ່ງແຈ້ງເຕືອນຫານັກສຶກສາແລ້ວ';
            } else {
                $_SESSION['flash'] = 'ເປີດການປະເມີນບໍ່ສຳເລັດ (' . (int) $res['status'] . ')';
            }
        }
    } elseif ($action === 'close') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            // The API's PUT loads the row before binding, so fields omitted
            // here (open_time, study_plan_id) keep their stored values.
            $res = api('PUT', '/open-evalu/' . $id, [
                'close_time' => date('c'),
                'inactive'   => 1,
            ]);
            $_SESSION['flash'] = $res['ok']
                ? 'ປິດການປະເມີນແລ້ວ'
                : 'ປິດການປະເມີນບໍ່ສຳເລັດ (' . (int) $res['status'] . ')';
        }
    } elseif ($action === 'visibility') {
        // Admin gate: whether teachers may see their own results. Same
        // /eval-settings endpoint the mobile admin switch uses.
        $visible = (int) ($_POST['visible'] ?? 0) === 1;
        $res     = api('PUT', '/eval-settings', [
            'teacher_results_visible' => $visible,
        ]);
        $_SESSION['flash'] = $res['ok']
            ? ($visible ? 'ເປີດເຜີຍຜົນໃຫ້ອາຈານແລ້ວ' : 'ປິດການເຜີຍຜົນຈາກອາຈານແລ້ວ')
            : 'ບັນທຶກການຕັ້ງຄ່າບໍ່ສຳເລັດ (' . (int) $res['status'] . ')';
    }
    header('Location: window.php');
    exit;
}

$flash = flash_take();

// Newest window first (API orders by open_time DESC); the head row is the gate
// the student page checks — same convention as the mobile admin screen.
$res     = api('GET', '/open-evalu?limit=10');
$error   = !$res['ok'];
$windows = $error ? [] : api_list($res['data']);
$current = $windows[0] ?? null;

$now    = time();
$isOpen = $current
    && (int) ($current['inactive'] ?? 0) === 0
    && (empty($current['open_time']) || strtotime($current['open_time']) <= $now)
    && (empty($current['close_time']) || strtotime($current['close_time']) >= $now);

// Teacher-visibility gate (fails closed on API error, matching the backend).
$resVis         = api('GET', '/eval-settings');
$teacherVisible = $resVis['ok']
    && !empty($resVis['data']['teacher_results_visible']);

$title = 'ໄລຍະການປະເມີນ';
require __DIR__ . '/views/window.php';
