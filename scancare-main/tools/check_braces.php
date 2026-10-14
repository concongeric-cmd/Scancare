<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$bal = 0;
foreach ($lines as $i => $line) {
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    $bal += $open - $close;
    if ($bal < 0) {
        echo "NEGATIVE BALANCE AT LINE " . ($i+1) . " (bal={$bal})\n";
        echo "" . ($i+1) . ": " . trim($line) . "\n";
        break;
    }
}

// print final balance and last 10 lines for context
if ($bal >= 0) {
    echo "FINAL BALANCE: {$bal}\n";
}

echo "--- CONTEXT AROUND LINE 550 ---\n";
$start = max(0, 540);
for ($j = $start; $j < min(count($lines), $start+40); $j++) {
    $ln = $j+1;
    printf("%4d: %s", $ln, $lines[$j]);
}
