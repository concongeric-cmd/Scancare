<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$start = intval($argv[1] ?? 1);
$end = intval($argv[2] ?? $start);
for ($i = $start; $i <= $end && $i <= count($lines); $i++) {
    printf("%4d: %s", $i, $lines[$i-1]);
}
