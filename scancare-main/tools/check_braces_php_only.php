<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$bal = 0;
foreach ($lines as $i => $line) {
    if (strpos($line, '?>') !== false) {
        echo "STOP_AT_LINE: " . ($i+1) . "\n";
        break;
    }
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    $bal += $open - $close;
    if ($bal < 0) {
        echo "NEGATIVE BALANCE AT LINE " . ($i+1) . " (bal={$bal})\n";
        echo trim($line) . "\n";
        exit(0);
    }
}

echo "PHP-ONLY FINAL BALANCE: {$bal}\n";
