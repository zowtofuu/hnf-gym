<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Other Product</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper flex justify-center">

        <?php if (!empty($error)): ?>
            <p><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form class="client-form" action="../controllers/ctr_add-other-product.php" method="POST"
            enctype="multipart/form-data">
            <h3 class="legend">Add Other Product</h3>
            <div class="form-group">
                <label for="product_name">Product Name:</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" id="product_name"
                    name="product_name" value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="number" id="price" name="price"
                    min="0.01" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock:</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="number" id="stock" name="stock"
                    min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="image">Product Image:</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="file" id="image" name="image"
                    accept="image/*">
            </div>
            <div class="form-actions">
                <a class="capitalize rounded-sm px-md py-sm btn-anchor btn-secondary" href="../controllers/ctr_other-products.php">
                    Cancel
                </a>
                <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit"
                    type="submit">
                    Save Product
                </button>
            </div>

        </form>
    </div>
</body>

</html>