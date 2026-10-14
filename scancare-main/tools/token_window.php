<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
foreach ($tokens as $i => $tok) {
    if (is_array($tok)) {
        if ($tok[2] >= 320 && $tok[2] <= 360) {
            echo sprintf("%4d %s '%s'\n", $tok[2], token_name($tok[0]), str_replace("\n","\\n", $tok[1]));
        }
    } else {
        // Not tokenized with line info
    }
}
