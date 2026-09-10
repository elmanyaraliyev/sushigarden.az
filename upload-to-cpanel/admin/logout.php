<?php
require_once __DIR__ . '/includes/auth.php';
sg_admin_logout();
header('Location: index.php');
exit;
