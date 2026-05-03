<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/ctr_subscription-renew.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Subscription</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <h1>Renew Subscription</h1>

    <?php if ($message !== ''): ?>
        <p style="color: green;"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color: red;"><?= e($error) ?></p>
    <?php endif; ?>

    <h2>Current Subscription Details</h2>

    <p><strong>Client Name:</strong> <?= e($fullName) ?></p>
    <p><strong>Current Plan:</strong> <?= e($currentPlanName) ?></p>
    <p><strong>Current Start Date:</strong> <?= e($currentStartDate) ?></p>
    <p><strong>Current End Date:</strong> <?= e($currentEndDate) ?></p>
    <p><strong>Status:</strong> <?= e($currentStatus) ?></p>
    <p><strong>Subscription Token:</strong> <?= e($currentToken) ?></p>

    <hr>

    <h2>Renewal Form</h2>

    <form method="post">
        <input type="hidden" name="subscription_id" value="<?= e((string) $subscriptionId) ?>">

        <p>
            <label for="new_plan_id">Plan</label><br>
            <select name="new_plan_id" id="new_plan_id" required>
                <option value="">-- Select Plan --</option>

                <?php foreach ($plans as $plan): ?>
                    <option value="<?= e((string) $plan['id']) ?>" <?= (int) $plan['id'] === (int) $newPlanId ? 'selected' : '' ?>>
                        <?= e(formatPlanName($plan)) ?> - ₱<?= e(number_format((float) $plan['price'], 2)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="new_start_date">Start Date</label><br>
            <input type="date" name="new_start_date" id="new_start_date" value="<?= e($newStartDate) ?>" required>
        </p>

        <p>
            <label for="new_end_date">End Date</label><br>
            <input type="date" name="new_end_date" id="new_end_date" value="<?= e($newEndDate) ?>">
        </p>

        <p>
            <button type="submit">Save Renewal</button>
        </p>
    </form>

    <script>
        const plans = <?= json_encode($planJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function computeEndDate(startDate, passType) {
            if (!startDate || !passType) {
                return '';
            }

            const date = new Date(startDate + 'T00:00:00');

            if (passType === 'monthly') {
                date.setMonth(date.getMonth() + 1);
                date.setDate(date.getDate());
            }

            return formatDate(date);
        }

        function getSelectedPassType(planId) {
            const plan = plans.find(plan => String(plan.id) === String(planId));
            return plan ? plan.pass_type : '';
        }

        const planSelect = document.getElementById('new_plan_id');
        const startInput = document.getElementById('new_start_date');
        const endInput = document.getElementById('new_end_date');

        function refreshEndDate() {
            endInput.value = computeEndDate(
                startInput.value,
                getSelectedPassType(planSelect.value)
            );
        }

        planSelect.addEventListener('change', refreshEndDate);
        startInput.addEventListener('change', refreshEndDate);
    </script>
</body>

</html>