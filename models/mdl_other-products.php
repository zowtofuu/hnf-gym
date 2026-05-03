<?php

function getOtherProducts(PDO $pdo): array
{
    $sql = "SELECT 
                product_id,
                product_name,
                price,
                stock,
                image_path,
                created_at
            FROM other_products
            ORDER BY product_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOtherProductById(PDO $pdo, int $productId): ?array
{
    $sql = "SELECT 
                product_id,
                product_name,
                price,
                stock,
                image_path,
                created_at
            FROM other_products
            WHERE product_id = :product_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':product_id' => $productId
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    return $product ?: null;
}

function addOtherProduct(PDO $pdo, string $productName, float $price, int $stock, ?string $imagePath): bool
{
    $sql = "INSERT INTO other_products (
                product_name,
                price,
                stock,
                image_path
            ) VALUES (
                :product_name,
                :price,
                :stock,
                :image_path
            )";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':product_name' => $productName,
        ':price' => $price,
        ':stock' => $stock,
        ':image_path' => $imagePath
    ]);
}

function updateOtherProduct(PDO $pdo, int $productId, string $productName, float $price, int $stock, ?string $imagePath): bool
{
    $sql = "UPDATE other_products
            SET
                product_name = :product_name,
                price = :price,
                stock = :stock,
                image_path = :image_path
            WHERE product_id = :product_id";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':product_id' => $productId,
        ':product_name' => $productName,
        ':price' => $price,
        ':stock' => $stock,
        ':image_path' => $imagePath
    ]);
}

function deleteOtherProduct(PDO $pdo, int $productId): bool
{
    $sql = "DELETE FROM other_products
            WHERE product_id = :product_id";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':product_id' => $productId
    ]);
}

function buyOtherProduct(PDO $pdo, int $productId, int $quantity): bool
{
    if ($productId <= 0 || $quantity <= 0) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        $sql = "SELECT 
                    product_id,
                    product_name,
                    price,
                    stock
                FROM other_products
                WHERE product_id = :product_id
                FOR UPDATE";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':product_id' => $productId
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product || (int) $product['stock'] < $quantity) {
            $pdo->rollBack();
            return false;
        }

        $totalAmount = (float) $product['price'] * $quantity;

        $sql = "UPDATE other_products
                SET stock = stock - :quantity
                WHERE product_id = :product_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':quantity' => $quantity,
            ':product_id' => $productId
        ]);

        $sql = "INSERT INTO sales (
                    transaction_type,
                    reference_id,
                    client_id,
                    item_name,
                    quantity,
                    amount
                )
                VALUES (
                    'product',
                    :reference_id,
                    NULL,
                    :item_name,
                    :quantity,
                    :amount
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':reference_id' => $productId,
            ':item_name' => $product['product_name'],
            ':quantity' => $quantity,
            ':amount' => $totalAmount
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error buying other product: ' . $e->getMessage());
        return false;
    }
}