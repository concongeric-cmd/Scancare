<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
$balance = 0;
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $line = $tok[2];
        if (in_array($tok[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_START_HEREDOC, T_END_HEREDOC, T_INLINE_HTML])) {
            continue;
        }
        $content = $tok[1];
    } else {
        $line = null;
        $content = $tok;
    }
    for ($i = 0; $i < strlen($content); $i++) {
        $ch = $content[$i];
        if ($ch === '{') {
            $balance++;
            if ($line !== null && $line >= 228 && $line <= 555) {
                printf("%4d OPEN  bal=%d content=%s\n", $line, $balance, trim($content));
            }
        } elseif ($ch === '}') {
            $balance--;
            if ($line !== null && $line >= 228 && $line <= 555) {
                printf("%4d CLOSE bal=%d content=%s\n", $line, $balance, trim($content));
            }
        }
    }
}
printf("FINAL BALANCE=%d\n", $balance);
