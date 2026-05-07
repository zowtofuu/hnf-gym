<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Other Products</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h2>Other Products</h2>

        <?php if ($success === 'added'): ?>
            <p>Product added successfully.</p>
        <?php elseif ($success === 'updated'): ?>
            <p>Product updated successfully.</p>
        <?php elseif ($success === 'deleted'): ?>
            <p>Product deleted successfully.</p>
        <?php elseif ($success === 'bought'): ?>
            <p>Product stock updated successfully.</p>
        <?php endif; ?>

        <?php if ($error === 'invalid'): ?>
            <p>Invalid request.</p>
        <?php elseif ($error === 'stock'): ?>
            <p>Not enough stock or product was not found.</p>
        <?php elseif ($error === 'not_found'): ?>
            <p>Product was not found.</p>
        <?php endif; ?>

        <section>
            <a href="../controllers/ctr_add-other-product.php">
                Add New Product
            </a>
        </section>

        <section class="card-wrapper">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $imagePath = !empty($product['image_path'])
                        ? $product['image_path']
                        : '../assets/images/product-placeholder.png';
                    $stock = (int) $product['stock'];
                    ?>

                    <div class="card">
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" width="150">

                        <h3><?= htmlspecialchars(ucwords($product['product_name'])) ?></h3>

                        <p>
                            Product Price: ₱<?= number_format((float) $product['price'], 2) ?>
                        </p>

                        <p>
                            Stock: <?= htmlspecialchars((string) $stock) ?>
                        </p>

                        <form action="../controllers/ctr_buy-other-product.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">

                            <label for="quantity_<?= htmlspecialchars($product['product_id']) ?>">Quantity:</label>
                            <input type="number" id="quantity_<?= htmlspecialchars($product['product_id']) ?>" name="quantity" min="1" max="<?= htmlspecialchars((string) $stock) ?>" value="1" <?= $stock <= 0 ? 'disabled' : '' ?> required>

                            <button type="submit" <?= $stock <= 0 ? 'disabled' : '' ?> onclick="return confirm('Are you sure you want to buy this product?');">
                                Buy
                            </button>
                        </form>

                        <a href="../controllers/ctr_edit-other-product.php?id=<?= htmlspecialchars($product['product_id']) ?>">
                            Edit
                        </a>

                        <form action="../controllers/ctr_delete-other-product.php" method="POST" onsubmit="return confirm('Delete this product?');">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">

                            <button type="submit">
                                Delete
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>
