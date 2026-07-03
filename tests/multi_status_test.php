<?php
// Self-check for api_multi_get()'s $status out-param. Run: php tests/multi_status_test.php
// Regression: the curl_multi loop once reused $status as its curl_multi_exec()
// result, clobbering the out-param array with an int — every batching page then
// fataled with "Cannot use a scalar value as an array" (api.php:173, 2026-07-03).
// No live API needed: an unroutable API_BASE still exercises the whole function;
// failed transfers must yield null bodies and integer statuses, never a fatal.
putenv('API_URL=http://127.0.0.1:9/api/v1'); // discard port → instant refusal
require __DIR__ . '/../config.php';

$out = api_multi_get(['a' => '/x', 'b' => '/y'], $status);

assert(is_array($status));
assert(array_keys($status) === ['a', 'b']);
assert($status['a'] === 0 && $status['b'] === 0); // no HTTP response → status 0
assert($out === ['a' => null, 'b' => null]);

echo "ok\n";
