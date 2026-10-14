<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$stack = [];
$target = intval($argv[1] ?? 555);
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
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
    if ($i+1 === $target) {
        echo "STACK AT LINE {$target}: " . count($stack) . " entries\n";
        foreach ($stack as $entry) {
            echo "OPEN at line " . $entry['line'] . " snippet='" . $entry['snippet'] . "'\n";
        }
        exit(0);
    }
}
