<?php
declare(strict_types=1);

/**
 * Call the Go API. $path includes any query string. Returns
 * ['ok'=>bool,'status'=>int,'data'=>mixed,'error'=>?string].
 * The browser never sees this request — it runs server-side, so CORS does not
 * apply.
 */
function api(string $method, string $path, ?array $body = null, bool $auth = true): array
{
    $ch = curl_init(API_BASE . $path);
    $headers = ['Accept: application/json'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($auth && !empty($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 25,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $cerr ?: 'connection failed'];
    }

    // Expired/invalid JWT → 401 ("invalid or expired token", auth middleware).
    // On an authenticated call that means this session's token is dead, so clear
    // it and bounce to login instead of letting every page render broken/empty.
    // auth=false calls (login) and token-less sessions skip this, so a wrong-
    // password 401 stays on the login form. Controllers call api() before any
    // output, so the redirect header is always safe to send.
    if ($code === 401 && $auth && !empty($_SESSION['token'])) {
        session_unset();
        header('Location: login.php?expired=1');
        exit;
    }

    return [
        'ok'     => $code >= 200 && $code < 300,
        'status' => $code,
        'data'   => json_decode($raw, true),
        'error'  => null,
    ];
}

/**
 * GET with a cross-request file cache, for stable reference data only
 * (semesters, student-groups, study-plans). Returns the same shape as
 * api()['data']. Live data (evaluation-results) must NOT go through here.
 * ponytail: file cache keyed by path hash; on API error it serves a stale file
 * rather than blanking the page. Clear /tmp/ams_get_* (or set GET_CACHE_DIR) to
 * invalidate; tune freshness with the per-call $ttl.
 */
function cached_get(string $path, int $ttl)
{
    $dir = getenv('GET_CACHE_DIR') ?: sys_get_temp_dir();
    $f   = $dir . '/ams_get_' . md5($path) . '.json';
    if (is_file($f) && time() - filemtime($f) < $ttl) {
        $hit = json_decode((string) file_get_contents($f), true);
        if ($hit !== null) {
            return $hit;
        }
    }
    $res = api('GET', $path);
    if ($res['ok']) {
        @file_put_contents($f, json_encode($res['data']), LOCK_EX);
        return $res['data'];
    }
    if (is_file($f)) { // API down → stale beats blank
        $stale = json_decode((string) file_get_contents($f), true);
        if ($stale !== null) {
            return $stale;
        }
    }
    return $res['data'];
}

/** Drop a cached_get() entry so the next read refetches (call after a mutation). */
function cache_forget(string $path): void
{
    $dir = getenv('GET_CACHE_DIR') ?: sys_get_temp_dir();
    @unlink($dir . '/ams_get_' . md5($path) . '.json');
}

/**
 * Fetch every page of a paginated list endpoint, merged into one row list. The
 * API caps page size at 200 (handlers.maxLimit) and silently ignores a larger
 * ?limit, so any caller that needs the whole set MUST page — a single ?limit=1000
 * only ever returns the first 200 rows. Reads meta.total_pages from the envelope;
 * stops there (or after one page when there is no meta). $ok reports the first
 * page's success so callers can keep an error state. $fetch is the page fetcher
 * (page → api()-shaped result) and defaults to the real API — it exists as a seam
 * for tests. ponytail: 100-page (20k-row) runaway guard — raise it the day one
 * listing legitimately exceeds that.
 */
function api_get_all(string $path, ?bool &$ok = null, ?callable $fetch = null): array
{
    if ($fetch === null) {
        $sep   = strpos($path, '?') === false ? '?' : '&';
        $fetch = fn (int $page) => api('GET', $path . $sep . 'limit=200&page=' . $page);
    }
    $all   = [];
    $page  = 1;
    $pages = 1;
    $ok    = true;
    while ($page <= $pages && $page <= 100) {
        $res = $fetch($page);
        if (!($res['ok'] ?? false)) {
            if ($page === 1) {
                $ok = false;
            }
            break;
        }
        $all   = array_merge($all, api_list($res['data'] ?? null));
        $pages = (int) ($res['data']['meta']['total_pages'] ?? $page);
        $page++;
    }
    return $all;
}

/** Unwrap a list response: bare array or {"data":[...]}. */
function api_list($data): array
{
    if (is_array($data)) {
        if (array_is_list($data)) {
            return $data;
        }
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }
    }
    return [];
}
