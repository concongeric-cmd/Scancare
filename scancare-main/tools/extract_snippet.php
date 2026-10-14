<?php
$input = $argv[1] ?? '';
$start = intval($argv[2] ?? 1);
$end = intval($argv[3] ?? 1);
if (!$input) {
    echo "Usage: php extract_snippet.php <file> <start> <end>\n";
    exit(1);
}
$lines = file($input);
$out = __DIR__ . '/snippet.php';
$snippet = array_slice($lines, $start-1, $end-$start+1);
$prefix = "<?php\n";
file_put_contents($out, $prefix . implode('', $snippet));
echo "Wrote snippet to $out\n";
