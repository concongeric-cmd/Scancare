<?php
session_start();


echo "<h1>Session Debug Test</h1>";
echo "<hr>";

echo "<h2>Current Session Variables:</h2>";
echo "<pre>";
print_r($_SERVER['HTTP_COOKIE'] ?? 'No cookies');
echo "\n\n";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";

echo "<h2>Session ID:</h2>";
echo "<p>" . session_id() . "</p>";

echo "<hr>";

echo "<h2>What to do:</h2>";
echo "1. Go to <strong>login.php</strong> and log in as a resident<br/>";
echo "2. You should see the session variables above<br/>";
echo "3. Then visit <strong>dashboard.php</strong> and check if the session persists<br/><br/>";
echo '<a href="login.php" style="background: #454B1B; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">Back to Login</a> | ';
echo '<a href="dashboard.php" style="background: #454B1B; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">Test Dashboard</a>';
echo "<hr>";
echo "<h2>If you see redirect loop, check:</h2>";
echo "1. Does dashboard.php have <code>require_once 'db_config.php';</code> at the top?<br/>";
echo "2. Is there a PHP error or warning being triggered?<br/>";
echo "3. Check browser console (F12) for redirect chain<br/>";
?>

