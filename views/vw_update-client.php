<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Client</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <?php if (!isset($client)): ?>
            <?php include '../components/alert.php'; ?>
        <?php else: ?>

            <form action="../controllers/ctr_update-client.php" method="POST">
                <input type="hidden" name="client_id" value="<?= htmlspecialchars($client['client_id']) ?>">

                <label>First Name:</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($client['first_name']) ?>" required>

                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($client['last_name']) ?>" required>

                <label>Contact:</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($client['contact']) ?>" required>

                <button type="submit">Update Client</button>
                <a href="../controllers/ctr_clients.php">Cancel</a>
            </form>

        <?php endif; ?>
    </div>
</body>

</html>