<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$start = intval($argv[1] ?? 1);
$end = intval($argv[2] ?? count($lines));
$open = 0;
$close = 0;
for ($i = $start; $i <= $end; $i++) {
    $line = $lines[$i-1];
    $open += substr_count($line, '{');
    $close += substr_count($line, '}');
}
echo "SEGMENT $start-$end OPEN=$open CLOSE=$close NET=" . ($open-$close) . "\n";
