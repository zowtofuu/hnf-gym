<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_other-products.php';

function getProductImageFullPath(string $imagePath): string
{
    $imagePath = preg_replace('#^\.\./#', '', $imagePath);

    return __DIR__ . '/../' . $imagePath;
}

function deleteProductImage(?string $imagePath): void
{
    if (empty($imagePath)) {
        return;
    }

    $fullPath = getProductImageFullPath($imagePath);

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ctr_other-products.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if ($productId === false || $productId === null || $productId <= 0) {
    header('Location: ctr_other-products.php?error=invalid');
    exit;
}

$product = getOtherProductById($pdo, $productId);

if ($product) {
    deleteProductImage($product['image_path'] ?? null);
    deleteOtherProduct($pdo, $productId);
}

header('Location: ctr_other-products.php?success=deleted');
exit;
