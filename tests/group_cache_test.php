<?php
// Self-check for the caches that speed up the report pages. Run: php tests/group_cache_test.php
// No network: covers the shared roster-count cache (warm_plan_group_counts() fills
// it in parallel, group_student_count() reads it) and the cached_get/cache_forget
// file cache, by pre-seeding files and asserting reads never reach the API.
$tmp = sys_get_temp_dir() . '/ams_cache_test';
@mkdir($tmp);
putenv('GROUP_COUNT_CACHE=' . $tmp . '/counts.json');
putenv('GET_CACHE_DIR=' . $tmp);
@unlink(getenv('GROUP_COUNT_CACHE')); // start clean, never touch the real caches
require __DIR__ . '/../config.php';

$cache = &group_count_cache();
$cache[42] = 7;                         // pretend the parallel prefetch warmed group 42
assert(group_student_count(42) === 7);  // reads cache, must NOT hit the API

// Group 42 already cached → warm_plan_group_counts() touches no network and the
// count stays put (if the cache weren't shared by reference this would diverge).
warm_plan_group_counts([['id' => 1, 'student_group' => ['id' => 42]]], []);
assert(group_student_count(42) === 7);

// cached_get(): a fresh cache file is served verbatim, no API call.
$path = '/semasters?limit=20';
file_put_contents($tmp . '/ams_get_' . md5($path) . '.json', json_encode(['cached' => true]));
assert(cached_get($path, 86400) === ['cached' => true]);

// cache_forget() removes the file (next read would refetch).
cache_forget($path);
assert(!is_file($tmp . '/ams_get_' . md5($path) . '.json'));

echo "ok\n";
