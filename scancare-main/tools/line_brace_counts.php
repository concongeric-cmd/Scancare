<?php
$path = $argv[1] ?? __DIR__ . '/../login.php';
$start = intval($argv[2] ?? 1);
$end = intval($argv[3] ?? $start);
$lines = file($path);
for ($i = $start; $i <= $end && $i <= count($lines); $i++) {
    $line = $lines[$i-1];
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    printf("%4d open=%d close=%d %s", $i, $open, $close, rtrim($line));
}
