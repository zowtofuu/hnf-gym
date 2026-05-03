<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Other Product</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h2>Edit Other Product</h2>

        <?php if (!empty($error)): ?>
            <p><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="../controllers/ctr_edit-other-product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">

            <label for="product_name">Product Name:</label>
            <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($_POST['product_name'] ?? $product['product_name']) ?>" required>

            <label for="price">Price:</label>
            <input type="number" id="price" name="price" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>" required>

            <label for="stock">Stock:</label>
            <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>" required>

            <?php if (!empty($product['image_path'])): ?>
                <p>Current Image:</p>
                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" width="150">
            <?php endif; ?>

            <label for="image">Change Product Image:</label>
            <input type="file" id="image" name="image" accept="image/*">

            <button type="submit">
                Update Product
            </button>

            <a href="../controllers/ctr_other-products.php">
                Cancel
            </a>
        </form>
    </div>
</body>

</html>
