<?php
$file = 'administrator_panel.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);

$fixed = array();
foreach ($lines as $i => $line) {
    if (trim($line) === '<!') {
        // Replace with a generic comment
        $fixed[] = '                <!-- Section -->';
    } else {
        $fixed[] = $line;
    }
}

$newContent = implode("\n", $fixed);
file_put_contents($file, $newContent);
echo "Fixed all broken HTML comments in $file\n";
?>
