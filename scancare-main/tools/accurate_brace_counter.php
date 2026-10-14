<?php
$path = $argv[1] ?? __DIR__ . '/../login.php';
$source = file_get_contents($path);
$tokens = token_get_all($source);
$balance = 0;
$line = 1;
$printLines = [];
foreach ($tokens as $tok) {
    if (is_array($tok)) {
        $line = $tok[2];
        if (in_array($tok[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_START_HEREDOC, T_END_HEREDOC, T_INLINE_HTML])) {
            continue;
        }
        $content = $tok[1];
    } else {
        $content = $tok;
    }
    for ($i = 0; $i < strlen($content); $i++) {
        $ch = $content[$i];
        if ($ch === '{') {
            $balance++;
            if ($line >= 228 && $line <= 555) {
                $printLines[] = ["line" => $line, "type" => "open", "bal" => $balance, "text" => $content];
            }
        } elseif ($ch === '}') {
            $balance--;
            if ($line >= 228 && $line <= 555) {
                $printLines[] = ["line" => $line, "type" => "close", "bal" => $balance, "text" => $content];
            }
        }
    }
}
echo "FINAL BALANCE=$balance\n";
foreach ($printLines as $entry) {
    if ($entry['bal'] < 0 || $entry['line'] >= 520) {
        echo sprintf("%4d %5s bal=%3d text=%s\n", $entry['line'], $entry['type'], $entry['bal'], trim($entry['text']));
    }
}
