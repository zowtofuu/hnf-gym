<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_other-products.php';

$products = getOtherProducts($pdo);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

require_once __DIR__ . '/../views/vw_other-products.php';
