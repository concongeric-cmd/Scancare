<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$chars = ['(' => 0, ')' => 0, '[' => 0, ']' => 0, '{' => 0, '}' => 0];
for ($i = 228; $i <= 555; $i++) {
    if (!isset($lines[$i-1])) continue;
    $line = $lines[$i-1];
    foreach ($chars as $ch => $count) {
        $chars[$ch] += substr_count($line, $ch);
    }
}
echo json_encode($chars) . "\n";
