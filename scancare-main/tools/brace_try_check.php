<?php
$path = __DIR__ . '/../login.php';
$lines = file($path);
$bal = 0;
$tryStack = [];
for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $trim = trim($line);
    if (preg_match('/\btry\s*\{/', $trim)) {
        $tryStack[] = ['line' => $i+1, 'bal_before' => $bal];
    }
    $open = substr_count($line, '{');
    $close = substr_count($line, '}');
    $bal += $open - $close;
    if ($trim === '} catch (Exception $e) {') {
        $try = array_pop($tryStack);
        echo "CATCH at " . ($i+1) . " matches TRY at " . ($try['line']) . " with bal_before=" . $try['bal_before'] . " current_bal=" . $bal . "\n";
    }
}
echo "FINAL BALANCE: $bal\n";
if (!empty($tryStack)) {
    foreach ($tryStack as $t) {
        echo "UNMATCHED TRY at {$t['line']} bal_before={$t['bal_before']}\n";
    }
}
