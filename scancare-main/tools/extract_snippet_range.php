<?php
$path = $argv[1] ?? '';
$start = intval($argv[2] ?? 1);
$end = intval($argv[3] ?? $start);
if (!$path || !file_exists($path)) {
    echo "Usage: php extract_snippet_range.php <file> <start> <end>\n";
    exit(1);
}
$lines = file($path);
$out = __DIR__ . '/range_snippet.php';
$snippet = array_slice($lines, $start-1, $end-$start+1);
$prefix = "<?php\n";
file_put_contents($out, $prefix . implode('', $snippet));
echo "Wrote $out\n";
