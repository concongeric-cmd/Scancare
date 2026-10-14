<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$stack = [];
for ($i = 227; $i < 555; $i++) {
    $line = $lines[$i] ?? '';
    for ($j = 0; $j < strlen($line); $j++) {
        $ch = $line[$j];
        if ($ch === '{') {
            $stack[] = ['line' => $i+1, 'col' => $j+1, 'text' => trim($line)];
            printf("OPEN  %-4d %4d %s\n", count($stack), $i+1, trim($line));
        } elseif ($ch === '}') {
            if (empty($stack)) {
                echo "UNMATCHED CLOSE at line " . ($i+1) . " col " . ($j+1) . "\n";
                exit(1);
            }
            $open = array_pop($stack);
            printf("CLOSE %-4d %4d matches OPEN %4d at %-4d %s\n", count($stack)+1, $i+1, count($stack)+1, $open['line'], $open['text']);
        }
    }
}
echo "STACK REMAINING: " . count($stack) . "\n";
foreach ($stack as $entry) {
    echo "OPEN at line {$entry['line']} col {$entry['col']} snippet={$entry['text']}\n";
}
