<?php
require_once __DIR__ . '/includes/customer_auth.php';
sg_customer_logout();
header('Location: index.php');
exit;
