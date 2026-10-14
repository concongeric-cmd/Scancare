<?php
$path = __DIR__ . '/../login.php';
$source = file_get_contents($path);
// Remove single-line comments and strings roughly
$tokens = token_get_all($source);
$clean = '';
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        switch ($tok[0]) {
            case T_COMMENT:
            case T_DOC_COMMENT:
            case T_CONSTANT_ENCAPSED_STRING:
            case T_ENCAPSED_AND_WHITESPACE:
            case T_START_HEREDOC:
            case T_END_HEREDOC:
            case T_INLINE_HTML:
                $clean .= str_repeat(' ', strlen($tok[1]));
                break;
            default:
                $clean .= $tok[1];
                break;
        }
    } else {
        $clean .= $tok;
    }
}
$lines = explode("\n", $clean);
$bal = 0;
for ($i = 0; $i < count($lines); $i++) {
    $lineNum = $i + 1;
    if ($lineNum < 228 || $lineNum > 555) continue;
    $open = substr_count($lines[$i], '{');
    $close = substr_count($lines[$i], '}');
    $bal += $open - $close;
    if ($bal < 0) {
        echo "NEGATIVE at $lineNum open=$open close=$close bal=$bal line=" . trim($lines[$i]) . "\n";
        break;
    }
}
echo "FINAL BALANCE=$bal\n";
