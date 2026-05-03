<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_other-products.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ctr_other-products.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if ($productId === false || $productId === null || $productId <= 0 || $quantity === false || $quantity === null || $quantity <= 0) {
    header('Location: ctr_other-products.php?error=invalid');
    exit;
}

$isBought = buyOtherProduct($pdo, $productId, $quantity);

if ($isBought) {
    header('Location: ctr_other-products.php?success=bought');
    exit;
}

header('Location: ctr_other-products.php?error=stock');
exit;
