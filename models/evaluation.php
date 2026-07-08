<?php
declare(strict_types=1);

// ── Academic helpers ─────────────────────────────────────────────────────────
/** Active semester: range contains today, else status==1, else first. Cached
 *  for the request (also read by study_year_name() while building class names). */
function active_semester(): ?array
{
    static $resolved = false;
    static $sem = null;
    if ($resolved) {
        return $sem;
    }
    $resolved = true;

    $all = api_list(cached_get('/semasters?limit=20', 86400));
    if (!$all) {
        return $sem;
    }
    $now = time();
    foreach ($all as $s) {
        $start = !empty($s['start_date']) ? strtotime((string) $s['start_date']) : null;
        $end   = !empty($s['end_date']) ? strtotime((string) $s['end_date']) : null;
        if ($start && $end && $now >= $start && $now <= $end + 86400) {
            return $sem = $s;
        }
    }
    foreach ($all as $s) {
        if ((int) ($s['status'] ?? 0) === 1) {
            return $sem = $s;
        }
    }
    return $sem = $all[0];
}

/** Is the evaluation window open right now? Authoritative definition, same as
 *  the admin window screen ($isOpen): the newest window must be active AND the
 *  clock must be within [open_time, close_time]. Checking the time here — not
 *  just inactive=0 — is what keeps a student out once close_time passes even if
 *  the admin never clicked "close". Fails closed on API error. */
function eval_window_open(): bool
{
    $res = api('GET', '/open-evalu?limit=1');
    if (!$res['ok']) {
        return false;
    }
    $w = api_list($res['data'])[0] ?? null;
    if (!$w || (int) ($w['inactive'] ?? 0) !== 0) {
        return false;
    }
    $now = time();
    return (empty($w['open_time'])  || strtotime((string) $w['open_time'])  <= $now)
        && (empty($w['close_time']) || strtotime((string) $w['close_time']) >= $now);
}

/** Admin gate: may teachers see their own results? Same /eval-settings switch
 *  the admin window screen sets. Fails closed on API error, matching the backend. */
function teacher_results_visible(): bool
{
    $res = api('GET', '/eval-settings');
    return $res['ok'] && !empty($res['data']['teacher_results_visible']);
}

/** All semesters, newest first (for the admin semester filter). Cached per request. */
function semesters(): array
{
    static $list = null;
    if ($list !== null) {
        return $list;
    }
    $list = api_list(cached_get('/semasters?limit=50', 86400));
    usort($list, function ($a, $b) {
        $cmp = (int) ($b['year'] ?? 0) <=> (int) ($a['year'] ?? 0);
        return $cmp !== 0 ? $cmp : ((int) ($b['term'] ?? 0) <=> (int) ($a['term'] ?? 0));
    });
    return $list;
}

/** Human label for a semester, e.g. "ສົກ 2025-1" (year-term). */
function semester_label(?array $s): string
{
    if (!is_array($s)) {
        return '';
    }
    $year = $s['year'] ?? '';
    $term = $s['term'] ?? '';
    return $year !== '' ? trim('ສົກ ' . $year . ($term !== '' ? '-' . $term : '')) : '';
}

/** Rewrite a group name's intake year to the current study-year level:
 *  "ນັກສຶກສາໄອທີ ປີ 2022 ຫ້ອງ 1" → "… ປີ 4 …" against academic year 2025.
 *  Names without a "ປີ YYYY" (e.g. a group code) are returned unchanged. */
function study_year_name(string $name): string
{
    $year = (int) (active_semester()['year'] ?? 0);
    if ($year <= 0) {
        // Fallback: the Lao academic year turns over ~September.
        $year = (int) date('n') >= 9 ? (int) date('Y') : (int) date('Y') - 1;
    }
    return preg_replace_callback('/ປີ\s*(\d{4})/u', function ($m) use ($year) {
        $level = $year - (int) $m[1] + 1;
        // ponytail: 4-year program hardcoded; past year 4 the student has graduated
        if ($level > 4) {
            return 'ຈົບແລ້ວ (ປີ ' . $m[1] . ')';
        }
        return 'ປີ ' . ($level >= 1 ? $level : 1);
    }, $name);
}

function study_plans(?int $semesterId, ?int $groupId): array
{
    $q = 'limit=200';
    if ($groupId) {
        $q .= '&std_group_id=' . $groupId;
    }
    if ($semesterId) {
        $q .= '&semaster_id=' . $semesterId;
    }
    return api_list(cached_get('/study-plans?' . $q, 600));
}

/** A teacher's own study plans (all semesters), flat rows like study_plans().
 *  Reads /study-plans/teacher/{id}; empty for an unknown/zero id. */
function teacher_plans(int $teacherId): array
{
    if ($teacherId <= 0) {
        return [];
    }
    return api_list(cached_get('/study-plans/teacher/' . $teacherId, 600));
}

function positive_int_or_null($value): ?int
{
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (is_float($value)) {
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }
    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed !== '' && ctype_digit($trimmed)) {
            $int = (int) $trimmed;
            return $int > 0 ? $int : null;
        }
    }
    return null;
}

function student_group_size(?array $group): ?int
{
    if (!$group) {
        return null;
    }

    $keys = [
        'student_count',
        'students_count',
        'student_total',
        'students_total',
        'total_students',
        'total_student',
        'num_students',
        'std_count',
        'std_total',
        'std_amount',
        'std_qty',
        'total_std',
        'total_std_count',
        'student_amount',
        'student_qty',
        'number_of_students',
        'member_count',
        'members_count',
        'total_member',
        'quantity',
        'amount',
        'qty',
        'count',
        'total',
    ];
    foreach ($keys as $key) {
        if (array_key_exists($key, $group)) {
            $count = positive_int_or_null($group[$key]);
            if ($count !== null) {
                return $count;
            }
        }
    }

    foreach (['students', 'student', 'members'] as $key) {
        if (isset($group[$key]) && is_array($group[$key]) && array_is_list($group[$key])) {
            return count($group[$key]);
        }
    }

    return null;
}

/** id → student-group object, fetched once per request. Holds std_group_name
 *  (the translated study-plan only carries std_group_code) and any roster size. */
function group_index(): array
{
    static $idx = null;
    if ($idx !== null) {
        return $idx;
    }
    $idx = [];
    foreach (api_list(cached_get('/student-groups?limit=500', 86400)) as $g) {
        $id = (int) ($g['id'] ?? 0);
        if ($id > 0) {
            $idx[$id] = $g;
        }
    }
    return $idx;
}

/** Shared roster-count cache (group id → size), returned by reference so both
 *  group_student_count() and warm_plan_group_counts() read/write the same store.
 *  Hydrated once per request from a cross-request file cache. ponytail: file +
 *  1-day TTL — rosters are stable within a semester; delete the file (or drop the
 *  TTL) if you ever need same-day roster edits to show up. */
function &group_count_cache(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $f = group_count_cache_file();
        if (is_file($f) && time() - filemtime($f) < 86400) {
            $saved = json_decode((string) file_get_contents($f), true);
            if (is_array($saved)) {
                foreach ($saved as $k => $v) {
                    $cache[(int) $k] = (int) $v;
                }
            }
        }
    }
    return $cache;
}

/** Path of the cross-request roster-count cache (override for tests via env). */
function group_count_cache_file(): string
{
    return getenv('GROUP_COUNT_CACHE') ?: sys_get_temp_dir() . '/ams_group_counts.json';
}

/** Persist the in-memory roster counts so the next request skips the API.
 *  ponytail: last-writer-wins, no atomic rename — fine for a count cache. */
function persist_group_counts(): void
{
    $cache = &group_count_cache();
    @file_put_contents(group_count_cache_file(), json_encode($cache), LOCK_EX);
}

/** Students in a group, counted from its roster. 0 if unknown. Cached per id.
 *  ponytail: one /students call per distinct group; if the group list returns a
 *  count field, student_group_size() reads it first and this never fires. Call
 *  warm_plan_group_counts() first to fill the cache in parallel and skip the call. */
function group_student_count(int $groupId): int
{
    $cache = &group_count_cache();
    if ($groupId <= 0) {
        return 0;
    }
    if (!isset($cache[$groupId])) {
        $roster = api_list(api('GET', '/student-groups/' . $groupId . '/students?limit=500')['data']);
        $cache[$groupId] = count($roster);
    }
    return $cache[$groupId];
}

/** Pre-warm group_student_count() for every group referenced by $plans, fetching
 *  the rosters in parallel (curl_multi) instead of one-by-one — turns N sequential
 *  ~700ms calls into one batch ≈ the slowest call. Groups whose size is already
 *  known (cache or a count field on the group) are skipped. Call once before a
 *  plan_names() loop. ponytail: parallel transport only, same per-group count();
 *  becomes a no-op the day the group list carries a size field. */
function warm_plan_group_counts(array $plans, array $groupIndex): void
{
    $cache = &group_count_cache();
    $need = [];
    foreach ($plans as $p) {
        $g   = is_array($p['student_group'] ?? null) ? $p['student_group'] : [];
        $gid = (int) ($g['id'] ?? $p['std_group_id'] ?? 0);
        if ($gid <= 0 || isset($cache[$gid]) || isset($need[$gid])) {
            continue;
        }
        if (student_group_size($groupIndex[$gid] ?? null) === null) {
            $need[$gid] = true;
        }
    }
    $paths = [];
    foreach (array_keys($need) as $gid) {
        $paths[$gid] = '/student-groups/' . $gid . '/students?limit=500';
    }
    if (!$paths) {
        return;
    }
    foreach (api_multi_get($paths) as $gid => $data) {
        $cache[$gid] = count(api_list($data));
    }
    persist_group_counts(); // next request reads counts from disk, skips the API
}

/** Teacher/subject/class names from a study plan's nested objects. $groupIndex
 *  (from group_index()) supplies the group name + size the plan row lacks. */
function plan_names(array $p, array $groupIndex = []): array
{
    $t = $p['teacher'] ?? null;
    $s = $p['subject'] ?? null;
    $g = is_array($p['student_group'] ?? null) ? $p['student_group'] : [];
    $gid  = (int) ($g['id'] ?? $p['std_group_id'] ?? 0);
    $full = $groupIndex[$gid] ?? $g;   // full group (with name/size) when available

    $teacher = '';
    if ($t) {
        $teacher = trim(($t['name_lao'] ?? '') . ' ' . ($t['surname_lao'] ?? ''));
        if ($teacher === '') {
            $teacher = (string) ($t['name_eng'] ?? '');
        }
        // Prepend the academic title (ຮສ./ປອ./…) when the API supplies one.
        $title = trim((string) ($t['name_title'] ?? $t['title_lao'] ?? ''));
        if ($teacher !== '' && $title !== '') {
            $teacher = $title . ' ' . $teacher;
        }
    }

    $size = student_group_size($full ?: null);
    if ($size === null && $gid > 0) {
        $size = group_student_count($gid);
    }

    return [
        'teacher'           => $teacher !== '' ? $teacher : '-',
        'subject'           => (string) ($s['name_lao'] ?? $s['name_eng'] ?? '-'),
        'class'             => study_year_name((string) ($full['std_group_name'] ?? $g['std_group_name'] ?? $g['std_group_code'] ?? '')),
        'expected_students' => $size ?? 0,
    ];
}

// ── Evaluation aggregation ───────────────────────────────────────────────────
/**
 * Fold /evaluation-results rows into one report per study plan: per-question
 * average grouped by category, using stored 1..10 scores. $planMap maps
 * study_plan_id → ['teacher'=>,'subject'=>,'class'=>] for real names.
 * $questionMap maps eva_question_id → ['question'=>,'category'=>] so the report
 * shows real text/category (and canonical order) instead of "ຄຳຖາມ #id"/"ອື່ນໆ"
 * — result rows often arrive without the eva_question object embedded.
 */
function reports_from_rows(array $rows, array $planMap = [], array $questionMap = []): array
{
    $byPlan = [];
    foreach ($rows as $r) {
        $byPlan[(int) ($r['study_plan_id'] ?? 0)][] = $r;
    }

    $reports = [];
    foreach ($byPlan as $planId => $pr) {
        $sum = $cnt = $text = $cat = [];
        $order = [];
        $comments = [];
        $students = [];

        foreach ($pr as $r) {
            $c = trim((string) ($r['comment'] ?? ''));
            if ($c !== '') {
                $comments[$c] = true;
            }
            if (isset($r['student_id']) && $r['student_id'] !== null && $r['student_id'] !== '') {
                $students[(string) $r['student_id']] = true;
            }
            if (!isset($r['score']) || $r['score'] === null) {
                continue;
            }
            $qid = (int) ($r['eva_question_id'] ?? 0);
            if (!isset($sum[$qid])) {
                $order[]    = $qid;
                $sum[$qid]  = 0;
                $cnt[$qid]  = 0;
            }
            $sum[$qid] += (int) $r['score'];
            $cnt[$qid]++;
            $meta       = $questionMap[$qid] ?? ($r['eva_question'] ?? null);
            $text[$qid] = (string) ($meta['question'] ?? ('ຄຳຖາມ #' . $qid));
            $cat[$qid]  = (string) ($meta['category'] ?? 'ອື່ນໆ');
        }

        // Order questions by the canonical question list when supplied, so the
        // report reads top-to-bottom like the evaluate form (category 1, 2, 3…).
        if ($questionMap) {
            $pos = array_flip(array_keys($questionMap));
            usort($order, fn ($a, $b) => ($pos[$a] ?? PHP_INT_MAX) <=> ($pos[$b] ?? PHP_INT_MAX));
        }

        $catOrder = [];
        $catQs    = [];
        foreach ($order as $qid) {
            $c = $cat[$qid] !== '' ? $cat[$qid] : 'ອື່ນໆ';
            if (!isset($catQs[$c])) {
                $catOrder[] = $c;
                $catQs[$c]  = [];
            }
            $catQs[$c][] = $qid;
        }

        $categories = [];
        $total      = 0.0;
        $qtotal     = 0;
        $maxAnswers = 0;
        foreach ($catOrder as $c) {
            $lines = [];
            foreach ($catQs[$c] as $qid) {
                $avg     = $cnt[$qid] > 0 ? ($sum[$qid] / $cnt[$qid]) : 0;
                $lines[] = ['text' => $text[$qid], 'score' => $avg];
                $total  += $avg;
                $qtotal++;
                $maxAnswers = max($maxAnswers, $cnt[$qid]);
            }
            $categories[] = ['title' => $c, 'lines' => $lines];
        }

        $lite        = $planMap[$planId] ?? null;
        $respondents = count($students) > 0 ? count($students) : $maxAnswers;
        $reports[]   = [
            'plan_id'           => $planId,
            'teacher'           => $lite['teacher'] ?? ('ແຜນການສອນ #' . $planId),
            'subject'           => $lite['subject'] ?? '',
            'class'             => $lite['class'] ?? '',
            'expected_students' => (int) ($lite['expected_students'] ?? 0),
            'respondents'       => $respondents,
            'categories'        => $categories,
            'comments'          => array_keys($comments),
            'total'             => $total,
            'average'           => $qtotal > 0 ? $total / $qtotal : 0,
        ];
    }

    usort($reports, fn ($a, $b) => $b['average'] <=> $a['average']);
    return $reports;
}

/** 0..10 score band class for coloring (matches the legend). */
function score_class(float $s): string
{
    if ($s >= 9) {
        return 's9';
    }
    if ($s >= 7) {
        return 's7';
    }
    if ($s >= 5) {
        return 's5';
    }
    return 's0';
}

/** Verdict for an average score (same bands as score_class). */
function score_verdict(float $s): string
{
    if ($s >= 9) {
        return 'ການສອນມີຄຸນນະພາບດີຫຼາຍ';
    }
    if ($s >= 7) {
        return 'ການສອນມີຄຸນນະພາບດີ';
    }
    if ($s >= 5) {
        return 'ການສອນມີຄຸນນະພາບພໍໃຊ້';
    }
    return 'ການສອນຍັງບໍ່ມີຄຸນນະພາບພຽງພໍ ຕ້ອງໄດ້ປັບປຸງເພີ່ມ';
}

/** Scoring legend rows shown under the report. */
function score_legend(): array
{
    return [
        'ຄະແນນ 9 ຫາ 10 ໝາຍເຖິງ ການສອນມີຄຸນນະພາບດີຫຼາຍ',
        'ຄະແນນ 7 ຫາ 8 ໝາຍເຖິງ ການສອນມີຄຸນນະພາບດີ',
        'ຄະແນນ 5 ຫາ 6 ໝາຍເຖິງ ການສອນມີຄຸນນະພາບພໍໃຊ້',
        'ຄະແນນ 0 ຫາ 4 ໝາຍເຖິງ ການສອນຍັງບໍ່ມີຄຸນນະພາບພຽງພໍ ຕ້ອງໄດ້ປັບປຸງເພີ່ມ',
    ];
}

// ── Admin list display helpers ───────────────────────────────────────────────
function badge_class(float $s): string
{
    if ($s >= 9) return 'b9';
    if ($s >= 7) return 'b7';
    if ($s >= 5) return 'b5';
    return 'b0';
}

function completion_label(array $report): string
{
    $done     = (int) ($report['respondents'] ?? 0);
    $expected = (int) ($report['expected_students'] ?? 0);
    return $done . '/' . ($expected > 0 ? $expected : '-');
}

function completion_percent(array $report): int
{
    $done     = (int) ($report['respondents'] ?? 0);
    $expected = (int) ($report['expected_students'] ?? 0);
    if ($expected <= 0) {
        return 0;
    }
    return min(100, (int) round(($done / $expected) * 100));
}
