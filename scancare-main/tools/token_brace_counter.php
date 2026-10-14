<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
$bal = 0;
$line = 1;
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $line = $tok[2];
        if ($tok[0] === T_START_HEREDOC) {
            // In heredoc, token_get_all still handles braces as T_STRING, so okay.
        }
    } else {
        if ($tok === '{') {
            $bal++;
            if ($line >= 228 && $line <= 560) {
                echo sprintf("%4d open bal=%d\n", $line, $bal);
            }
        } elseif ($tok === '}') {
            $bal--;
            if ($line >= 228 && $line <= 560) {
                echo sprintf("%4d close bal=%d\n", $line, $bal);
            }
        }
    }
}
echo "FINAL BALANCE=$bal\n";
