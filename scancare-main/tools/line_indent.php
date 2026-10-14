<?php
$path = $argv[1] ?? __DIR__ . '/../login.php';
$start = intval($argv[2] ?? 1);
$end = intval($argv[3] ?? $start);
$lines = file($path);
for ($i = $start; $i <= $end && $i <= count($lines); $i++) {
    $line = $lines[$i-1];
    $indent = strlen($line) - strlen(ltrim($line, " \t"));
    printf("%4d %3d %s", $i, $indent, rtrim($line));
}
