<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Client</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper flex justify-center">
        <?php if (!isset($client)): ?>
            <?php include '../components/alert.php'; ?>
        <?php else: ?>

            <form class="client-form" action="../controllers/ctr_update-client.php" method="POST">
                <input type="hidden" name="client_id" value="<?= htmlspecialchars($client['client_id']) ?>">

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" id="first_name" name="first_name"
                        value="<?= htmlspecialchars($client['first_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" id="last_name" name="last_name"
                        value="<?= htmlspecialchars($client['last_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" id="contact" name="contact"
                        value="<?= htmlspecialchars($client['contact']) ?>" pattern="09[0-9]{9}" maxlength="11"
                        inputmode="numeric" placeholder="09XXXXXXXXX" required>
                    <small>Format: 09XXXXXXXXX</small>
                </div>

                <div class="form-actions">
                    <a class="capitalize rounded-sm px-md py-sm btn-anchor btn-secondary" href="../controllers/ctr_clients.php">Cancel</a>
                    <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit">Update
                        Client</button>
                </div>
            </form>

        <?php endif; ?>
    </div>
    <script>
        const contactInput = document.getElementById('contact');

        contactInput.addEventListener('input', function () {
            this.value = this.value
                .replace(/\D/g, '')
                .slice(0, 11);

            if (!this.value.startsWith('09')) {
                if (this.value.length >= 2) {
                    this.value = '09';
                }
            }
        });
    </script>
</body>

</html>