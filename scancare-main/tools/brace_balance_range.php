<?php
$path = $argv[1] ?? __DIR__ . '/../login.php';
$start = intval($argv[2] ?? 1);
$end = intval($argv[3] ?? PHP_INT_MAX);
$source = file_get_contents($path);
$tokens = token_get_all($source);
$balance = 0;
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $line = $tok[2];
        if ($line < $start || $line > $end) {
            continue;
        }
        if (in_array($tok[0], [T_COMMENT, T_DOC_COMMENT, T_START_HEREDOC, T_END_HEREDOC, T_INLINE_HTML])) {
            continue;
        }
        $content = $tok[1];
    } else {
        $content = $tok;
        // line is not available; cannot filter by line
        continue;
    }
    for ($i = 0; $i < strlen($content); $i++) {
        $ch = $content[$i];
        if ($ch === '{') {
            $balance++;
        } elseif ($ch === '}') {
            $balance--;
        }
    }
}
echo "BALANCE {$start}-{$end} = {$balance}\n";
