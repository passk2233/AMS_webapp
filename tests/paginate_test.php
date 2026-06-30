<?php
// Self-check for api_get_all() paging. Run: php tests/paginate_test.php
// No network: the page fetcher is injected, so this only exercises the loop's
// termination (meta.total_pages), merging across pages, and the error flag.
require __DIR__ . '/../config.php';

// Build a fake API page of `n` rows inside a {data, meta} envelope.
$page = fn (int $n, int $totalPages) => [
    'ok'   => true,
    'data' => ['data' => array_fill(0, $n, ['x' => 1]),
               'meta' => ['total_pages' => $totalPages]],
];

// 3 full pages reported by meta → all three fetched and merged (200*2 + 50).
$calls = 0;
$rows  = api_get_all('/x', $ok, function (int $p) use (&$calls, $page) {
    $calls++;
    return $p < 3 ? $page(200, 3) : $page(50, 3);
});
assert($ok === true);
assert($calls === 3);
assert(count($rows) === 450);

// No meta on the envelope → stop after the first page (degrade, never loop).
$calls = 0;
$rows  = api_get_all('/x', $ok, function (int $p) use (&$calls) {
    $calls++;
    return ['ok' => true, 'data' => ['nope' => array_fill(0, 200, 1)]];
});
assert($calls === 1 && $rows === []); // no list under data → empty, one call only

// First page fails → ok=false, no rows.
$rows = api_get_all('/x', $ok, fn (int $p) => ['ok' => false, 'data' => null]);
assert($ok === false && $rows === []);

// Runaway guard: meta always claims more pages → capped at 100 calls, no hang.
$calls = 0;
$rows  = api_get_all('/x', $ok, function (int $p) use (&$calls, $page) {
    $calls++;
    return $page(200, 99999);
});
assert($calls === 100);

echo "ok\n";
