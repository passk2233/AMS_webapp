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
    return [
        'ok'     => $code >= 200 && $code < 300,
        'status' => $code,
        'data'   => json_decode($raw, true),
        'error'  => null,
    ];
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
