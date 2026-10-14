<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$bal = 0;
for ($i = 228; $i < 361; $i++) {
    $line = $lines[$i-1] ?? '';
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    $bal += $open - $close;
    printf("%4d open=%d close=%d bal=%d %s", $i, $open, $close, $bal, rtrim($line));
}
