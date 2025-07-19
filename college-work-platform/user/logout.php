<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Logout the user
$auth->logout();

// Redirect to homepage with logout message
header('Location: index.php?logout=1');
exit;
?>
