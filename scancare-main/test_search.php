<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'bhw';

include 'db_config.php';
$pdo = getDatabaseConnection();


$_POST['search_term'] = 'Earl';


include 'search_patient_process.php';
?>

