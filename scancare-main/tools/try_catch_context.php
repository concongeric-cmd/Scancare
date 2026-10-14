<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
foreach ($tokens as $i => $tok) {
    if (is_array($tok) && ($tok[0] === T_TRY || $tok[0] === T_CATCH)) {
        $type = $tok[0] === T_TRY ? 'TRY' : 'CATCH';
        $line = $tok[2];
        echo "$type at token $i line $line\n";
        $start = max(0, $i-5);
        $end = min(count($tokens)-1, $i+15);
        for ($j = $start; $j <= $end; $j++) {
            $t = $tokens[$j];
            if (is_array($t)) {
                echo sprintf("%4d: %s '%s'\n", $j, token_name($t[0]), str_replace("\n","\\n", $t[1]));
            } else {
                echo sprintf("%4d: '%s'\n", $j, $t);
            }
        }
        echo "---\n";
    }
}
