<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_other-products.php';

function createProductImageName(string $productName, string $originalName): string
{
    $safeName = strtolower(trim($productName));
    $safeName = preg_replace('/[^a-z0-9]+/', '-', $safeName);
    $safeName = trim($safeName, '-');

    if ($safeName === '') {
        $safeName = 'product';
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    return $safeName . '_' . date('YmdHis') . '.' . $extension;
}

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

function uploadProductImage(array $image, string $productName, string &$error): ?string
{
    if (empty($image['name'])) {
        return null;
    }

    if ($image['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed.';
        return null;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.';
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = createProductImageName($productName, $image['name']);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
        $error = 'Could not save uploaded image.';
        return null;
    }

    return '../assets/uploads/' . $fileName;
}

$productId = (int) ($_GET['id'] ?? $_POST['product_id'] ?? 0);

if ($productId <= 0) {
    header('Location: ctr_other-products.php?error=invalid');
    exit;
}

$product = getOtherProductById($pdo, $productId);

if (!$product) {
    header('Location: ctr_other-products.php?error=not_found');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['product_name'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $imagePath = $product['image_path'] ?? null;

    if ($productName === '' || $price === false || $price <= 0 || $stock === false || $stock < 0) {
        $error = 'Please enter valid product details.';
    } else {
        $newImagePath = uploadProductImage($_FILES['image'] ?? [], $productName, $error);

        if ($error === '' && $newImagePath !== null) {
            deleteProductImage($imagePath);
            $imagePath = $newImagePath;
        }

        if ($error === '') {
            $isUpdated = updateOtherProduct($pdo, $productId, $productName, (float) $price, (int) $stock, $imagePath);

            if ($isUpdated) {
                header('Location: ctr_other-products.php?success=updated');
                exit;
            }

            $error = 'Product was not updated.';
        }
    }
}

require_once __DIR__ . '/../views/vw_edit-other-product.php';
