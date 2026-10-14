<?php
$file = 'administrator_panel.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);

$fixed = array();
$i = 0;
while ($i < count($lines)) {
    $line = $lines[$i];
    
    // Check if this is a<!-- Section --> comment followed by description lines
    if (strpos($line, '<!-- Section -->') !== false) {
        // Add the comment
        $fixed[] = $line;
        $i++;
        
        // Skip/remove the next 1-3 lines if they contain descriptive text (all uppercase, equals signs, spaces)
        while ($i < count($lines)) {
            $next_line = $lines[$i];
            $trimmed = trim($next_line);
            
            // Check if the line is likely a description (uppercase words or equals signs or spaces only)
            if (empty($trimmed) || 
                preg_match('/^[A-Z\s=]+$/', $trimmed) ||
                strpos($trimmed, '====') !== false) {
                // Skip this line
                $i++;
            } else {
                // This is actual content, stop skipping
                break;
            }
        }
    } else {
        $fixed[] = $line;
        $i++;
    }
}

$newContent = implode("\n", $fixed);
file_put_contents($file, $newContent);
echo "Fixed all broken HTML comments and removed description text in $file\n";
?>
