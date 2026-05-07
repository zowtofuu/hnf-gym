<?php

$planPrices = [];

foreach ($planOptions as $plan) {
    $planPrices[(string) $plan['membership_type']][(string) $plan['pass_type']] = (float) $plan['price'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Client</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h1>Add Client</h1>

        <?php if (!empty($errors)): ?>
            <div>
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= sanitize((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p><strong><?= sanitize($success) ?></strong></p>
        <?php endif; ?>

        <form method="POST" action="../controllers/ctr_add-client.php">
            <p>
                <label for="first_name">First Name</label><br>
                <input type="text" name="first_name" id="first_name"
                    value="<?= sanitize((string) ($_POST['first_name'] ?? '')) ?>" required>
            </p>

            <p>
                <label for="last_name">Last Name</label><br>
                <input type="text" name="last_name" id="last_name"
                    value="<?= sanitize((string) ($_POST['last_name'] ?? '')) ?>" required>
            </p>

            <p>
                <label for="contact">Contact Number</label><br>
                <input type="text" name="contact" id="contact"
                    value="<?= sanitize((string) ($_POST['contact'] ?? '')) ?>" pattern="09[0-9]{9}" maxlength="11"
                    inputmode="numeric" placeholder="09XXXXXXXXX" required>
            </p>

            <p>
                <label for="membership_type">Membership Type</label><br>
                <select name="membership_type" id="membership_type" required>
                    <option value="">Select membership type</option>
                    <?php foreach ($membershipTypes as $value => $label): ?>
                        <option value="<?= sanitize((string) $value) ?>" <?= $selectedMembershipType === (string) $value ? 'selected' : '' ?>>
                            <?= sanitize((string) $label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="pass_type">Pass Type</label><br>
                <select name="pass_type" id="pass_type" required>
                    <option value="">Select pass type</option>
                    <?php foreach ($passTypes as $value => $label): ?>
                        <option value="<?= sanitize((string) $value) ?>" <?= $selectedPassType === (string) $value ? 'selected' : '' ?>>
                            <?= sanitize((string) $label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <strong>Price:</strong> <span id="plan_price">-</span>
            </p>

            <p>
                <button type="submit">Add Client</button>
                <a href="../controllers/ctr_clients.php">Cancel</a>
            </p>
        </form>
    </div>

    <script>
        const planPrices = <?= json_encode($planPrices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const membershipSelect = document.getElementById('membership_type');
        const passSelect = document.getElementById('pass_type');
        const priceOutput = document.getElementById('plan_price');

        function formatPrice(price) {
            return '&#8369;' + Number(price).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function refreshPrice() {
            const pricesByPass = planPrices[membershipSelect.value] || {};
            const price = pricesByPass[passSelect.value];

            priceOutput.innerHTML = price ? formatPrice(price) : '-';
        }

        membershipSelect.addEventListener('change', refreshPrice);
        passSelect.addEventListener('change', refreshPrice);
        refreshPrice();

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