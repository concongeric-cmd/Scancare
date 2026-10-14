<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
$balance = 0;
$lineBalance = [];
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $line = $tok[2];
        if (in_array($tok[0], [T_COMMENT, T_DOC_COMMENT, T_START_HEREDOC, T_END_HEREDOC, T_INLINE_HTML, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_STRING_VARNAME, T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
            continue;
        }
        $content = $tok[1];
    } else {
        $content = $tok;
        $line = null;
    }
    for ($i = 0; $i < strlen($content); $i++) {
        $ch = $content[$i];
        if ($ch === '{') {
            $balance++;
            if ($line !== null) {
                $lineBalance[$line] = $balance;
            }
        } elseif ($ch === '}') {
            $balance--;
            if ($line !== null) {
                $lineBalance[$line] = $balance;
            }
        }
    }
}
for ($ln = 520; $ln <= 555; $ln++) {
    if (isset($lineBalance[$ln])) {
        echo sprintf("%4d bal=%d\n", $ln, $lineBalance[$ln]);
    }
}
echo "FINAL BALANCE=$balance\n";
