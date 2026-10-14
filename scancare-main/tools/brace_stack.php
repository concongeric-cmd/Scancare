<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$stack = [];
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $str = trim($line);
    for ($j = 0; $j < strlen($line); $j++) {
        $ch = $line[$j];
        if ($ch === '{') {
            $stack[] = ['line' => $i+1, 'pos' => $j, 'snippet' => trim($line)];
        } elseif ($ch === '}') {
            if (empty($stack)) {
                echo "UNMATCHED CLOSE at line " . ($i+1) . "\n";
                exit(1);
            }
            $open = array_pop($stack);
            if ($open['line'] <= 231 && ($i+1) >= 231 && ($i+1) <= 555) {
                // print some close events within range
            }
            if (($i+1) === 554) {
                echo "CLOSE at 554 matches open at {$open['line']} snippet='{$open['snippet']}'\n";
            }
            if (($i+1) === 555) {
                // no close at 555 actually, catch line does not close
            }
        }
    }
}
echo "STACK SIZE: " . count($stack) . "\n";
if (count($stack) > 0) {
    echo "UNMATCHED OPEN BRACES (oldest->newest):\n";
    foreach ($stack as $item) {
        echo "line {$item['line']} pos {$item['pos']} snippet='{$item['snippet']}'\n";
    }
}
