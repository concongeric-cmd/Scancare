<?php
$file = __DIR__ . '/range_snippet.php';
$content = file_get_contents($file);
$before = "}\n            } catch (Exception $e) {";
$after = "}\n                } catch (Exception $e) {";
if (strpos($content, $before) === false) {
    echo "Pattern not found\n";
    exit(1);
}
file_put_contents($file, str_replace($before, $after, $content));
echo "Inserted extra brace into snippet\n";
