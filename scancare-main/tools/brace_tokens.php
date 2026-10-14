<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
for ($i = 0; $i < count($tokens); $i++) {
    $tok = $tokens[$i];
    if (is_array($tok)) {
        if ($tok[0] === T_CURLY_OPEN || $tok[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
            // ignore
        }
    } else {
        if ($tok === '{' || $tok === '}') {
            $line = "unknown";
            if (is_array($tokens[$i-1]) && isset($tokens[$i-1][2])) {
                $line = $tokens[$i-1][2];
            }
            echo sprintf("%4d %s %s\n", $line, $tok, isset($tokens[$i+1]) && is_array($tokens[$i+1]) ? trim($tokens[$i+1][1]) : (is_string($tokens[$i+1]) ? $tokens[$i+1] : ''));
        }
    }
}
