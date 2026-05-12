<?php
require_once 'includes/auth.php';
session_destroy();
header('Location: /notes-platform/login.php');
exit;
?>