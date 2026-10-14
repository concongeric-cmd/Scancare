<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
for ($i = 1; $i <= count($lines); $i++) {
    $line = $lines[$i-1];
    if (strpos($line,'{') !== false || strpos($line,'}') !== false) {
        if ($i >= 228 && $i <= 560) {
            printf("%4d: %s", $i, $line);
        }
    }
}
