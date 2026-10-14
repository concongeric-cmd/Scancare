<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
for ($i = 0; $i < count($tokens); $i++) {
    $tok = $tokens[$i];
    if (is_array($tok)) {
        if ($tok[0] === T_CATCH) {
            echo "FOUND CATCH at token $i, line {$tok[2]}\n";
            for ($j = max(0, $i-10); $j <= min(count($tokens)-1, $i+10); $j++) {
                $t = $tokens[$j];
                if (is_array($t)) {
                    echo sprintf("%4d: %s (%s)\n", $j, token_name($t[0]), str_replace("\n","\\n", $t[1]));
                } else {
                    echo sprintf("%4d: '%s'\n", $j, $t);
                }
            }
            echo "---\n";
        }
    }
}
