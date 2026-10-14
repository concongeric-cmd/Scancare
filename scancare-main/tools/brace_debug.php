<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$bal = 0;
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    if ($open || $close) {
        $bal += $open - $close;
        printf("%4d BAL=%3d  +%d -%d  %s", $i+1, $bal, $open, $close, rtrim($line));
    }
}
