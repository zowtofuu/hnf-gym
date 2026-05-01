<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utility.php';
require_once __DIR__ . '/../models/mdl_subscriptions.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? 'all');

updateSubscriptionStatuses($pdo);

$counts = getSubscriptionCounts($pdo);
$subscriptions = getSubscriptions($pdo, $search, $status);

require_once __DIR__ . '/../views/vw_subscriptions.php';