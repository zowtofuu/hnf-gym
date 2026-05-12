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

        <section class="flex justify-between mb-md">
            <h3 class="legend">Other Products</h3>
            <a class="icon-button" href="../controllers/ctr_add-other-product.php" title="Add Product">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                    <path
                        d="M451.5-131.5Q440-143 440-160v-280H160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520h280v-280q0-17 11.5-28.5T480-840q17 0 28.5 11.5T520-800v280h280q17 0 28.5 11.5T840-480q0 17-11.5 28.5T800-440H520v280q0 17-11.5 28.5T480-120q-17 0-28.5-11.5Z" />
                </svg>
            </a>
        </section>
        
        <?php if ($success === 'added'): ?>
            <p class="alert alert-success js-alert">Product added successfully.</p>
        <?php elseif ($success === 'updated'): ?>
            <p class="alert alert-success js-alert">Product updated successfully.</p>
        <?php elseif ($success === 'deleted'): ?>
            <p class="alert alert-success js-alert">Product deleted successfully.</p>
        <?php elseif ($success === 'bought'): ?>
            <p class="alert alert-success js-alert">Product stock updated successfully.</p>
        <?php endif; ?>

        <?php if ($error === 'invalid'): ?>
            <p class="alert alert-danger js-alert">Invalid request.</p>
        <?php elseif ($error === 'stock'): ?>
            <p class="alert alert-danger js-alert">Not enough stock or product was not found.</p>
        <?php elseif ($error === 'not_found'): ?>
            <p class="alert alert-danger js-alert">Product was not found.</p>
        <?php endif; ?>

        <section class="card-wrapper gap-md">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $imagePath = !empty($product['image_path'])
                        ? $product['image_path']
                        : '../assets/images/product-placeholder.png';

                    $stock = (int) $product['stock'];
                    $isOutOfStock = $stock <= 0;
                    ?>

                    <div class="product-card">
                        <div class="product-image-wrapper mb-md">
                            <img class="product-image" src="<?= htmlspecialchars($imagePath) ?>"
                                alt="<?= htmlspecialchars($product['product_name']) ?>">
                        </div>

                        <div class="product-content mb-md">
                            <p class="product-name capitalize mb-xsm">
                                <?= htmlspecialchars(ucwords($product['product_name'])) ?>
                            </p>

                            <div class="flex justify-between">
                                <span class="badge <?= $isOutOfStock ? 'badge-expired' : 'badge-active' ?>">
                                    Stock: <?= htmlspecialchars((string) $stock) ?>
                                </span>

                                <span class="badge badge-yellow">
                                    Price: ₱<?= number_format((float) $product['price'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <div class="pb-sm border-bottom">
                            <form action="../controllers/ctr_buy-other-product.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
                                <label class="mb-xsm" for="quantity_<?= htmlspecialchars($product['product_id']) ?>">
                                    Quantity
                                </label>
                                <div class="flex gap-sm">
                                    <input class="capitalize rounded-sm px-md py-sm focus-visible basis-1" type="number"
                                        id="quantity_<?= htmlspecialchars($product['product_id']) ?>" name="quantity" min="1"
                                        max="<?= htmlspecialchars((string) $stock) ?>" value="1" <?= $isOutOfStock ? 'disabled' : '' ?>required>
                                    <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit"
                                        <?= $isOutOfStock ? 'disabled' : '' ?>
                                        onclick="return confirm('Are you sure you want to BUY this product?');">
                                        Buy
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="flex justify-end">
                            <a class="icon-button-plain" title="Edit Product"
                                href="../controllers/ctr_edit-other-product.php?id=<?= htmlspecialchars($product['product_id']) ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                    <path
                                        d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                </svg>
                            </a>

                            <form class="form-as-button danger" title="Delete Product"
                                action="../controllers/ctr_delete-other-product.php" method="POST">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
                                <button type="submit"
                                    onclick="return confirm('Are you sure you want to DELETE this product?');">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                        <path
                                            d="M280-120q-33 0-56.5-23.5T200-200v-520q-17 0-28.5-11.5T160-760q0-17 11.5-28.5T200-800h160q0-17 11.5-28.5T400-840h160q17 0 28.5 11.5T600-800h160q17 0 28.5 11.5T800-760q0 17-11.5 28.5T760-720v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM428.5-291.5Q440-303 440-320v-280q0-17-11.5-28.5T400-640q-17 0-28.5 11.5T360-600v280q0 17 11.5 28.5T400-280q17 0 28.5-11.5Zm160 0Q600-303 600-320v-280q0-17-11.5-28.5T560-640q-17 0-28.5 11.5T520-600v280q0 17 11.5 28.5T560-280q17 0 28.5-11.5ZM280-720v520-520Z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php include __DIR__ . '/../components/alert.php'; ?>
            <?php endif; ?>
        </section>
    </div>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.js-alert').forEach((alert) => {
                alert.style.display = 'none';
            });
        }, 2000);
    </script>
</body>

</html>