<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Other Product</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h2>Add Other Product</h2>

        <?php if (!empty($error)): ?>
            <p><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="../controllers/ctr_add-other-product.php" method="POST" enctype="multipart/form-data">
            <label for="product_name">Product Name:</label>
            <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>

            <label for="price">Price:</label>
            <input type="number" id="price" name="price" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>

            <label for="stock">Stock:</label>
            <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>" required>

            <label for="image">Product Image:</label>
            <input type="file" id="image" name="image" accept="image/*">

            <button type="submit">
                Save Product
            </button>

            <a href="../controllers/ctr_other-products.php">
                Cancel
            </a>
        </form>
    </div>
</body>

</html>
