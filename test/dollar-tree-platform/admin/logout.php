<?php
require_once __DIR__ . '/../config.php';
if(isset($_SESSION['admin_id'])) log_activity($_SESSION['admin_id'],'admin_logout','Admin logged out');
session_destroy();
header('Location: login.php');
exit;
