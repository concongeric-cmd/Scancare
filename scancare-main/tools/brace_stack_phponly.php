<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$stack = [];
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    if (strpos($line, '?>') !== false) {
        break;
    }
    for ($j = 0; $j < strlen($line); $j++) {
        $ch = $line[$j];
        if ($ch === '{') {
            $stack[] = ['line' => $i+1, 'pos' => $j, 'snippet' => trim($line)];
        } elseif ($ch === '}') {
            if (empty($stack)) {
                echo "UNMATCHED CLOSE at line " . ($i+1) . "\n";
                exit(1);
            }
            array_pop($stack);
        }
    }
}
echo "STACK SIZE: " . count($stack) . "\n";
if (count($stack) > 0) {
    foreach ($stack as $entry) {
        echo "OPEN at line " . $entry['line'] . " snippet='" . $entry['snippet'] . "'\n";
    }
}
