<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$_SESSION = [];
session_destroy();
setcookie('sb_remember', '', time()-3600, '/');
header('Location: ' . SITE_URL . '/login.php');
exit;
?>
