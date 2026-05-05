<?php
declare(strict_types=1);

function hnfSubscriptionEditE(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hnfSubscriptionEditLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subscription</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h1>Edit Subscription</h1>

        <p><strong>Client:</strong> <?= hnfSubscriptionEditE($clientName) ?></p>

        <?php if ($successMessage !== ''): ?>
            <p><strong><?= hnfSubscriptionEditE($successMessage) ?></strong></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div>
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= hnfSubscriptionEditE($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="../controllers/ctr_subscription-edit.php">
            <input type="hidden" name="subscription_id" value="<?= hnfSubscriptionEditE($subscriptionId) ?>">

            <p>
                <label for="membership_type">Membership Type</label><br>
                <select name="membership_type" id="membership_type" required>
                    <?php foreach ($membershipTypes as $type): ?>
                        <option value="<?= hnfSubscriptionEditE($type['membership_type']) ?>"
                            <?= $membershipType === $type['membership_type'] ? 'selected' : '' ?>>
                            <?= hnfSubscriptionEditE(hnfSubscriptionEditLabel($type['membership_type'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p id="membership_start_group">
                <label for="membership_start">Membership Start</label><br>
                <input type="date" name="membership_start" id="membership_start"
                    value="<?= hnfSubscriptionEditE($membershipStart) ?>">
            </p>

            <p>
                <label for="pass_type">Pass Type</label><br>
                <select name="pass_type" id="pass_type" required>
                    <?php foreach ($passTypes as $pass): ?>
                        <option value="<?= hnfSubscriptionEditE($pass['pass_type']) ?>"
                            <?= $passType === $pass['pass_type'] ? 'selected' : '' ?>>
                            <?= hnfSubscriptionEditE(hnfSubscriptionEditLabel($pass['pass_type'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="subscription_start">Subscription Start</label><br>
                <input type="date" name="subscription_start" id="subscription_start"
                    value="<?= hnfSubscriptionEditE($subscriptionStart) ?>" required>
            </p>

            <p>
                <label for="subscription_end">Subscription End</label><br>
                <input type="date" id="subscription_end"
                    value="<?= hnfSubscriptionEditE($subscriptionEnd) ?>" readonly>
            </p>

            <p>
                <label for="status">Status</label><br>
                <select name="status" id="status" required>
                    <?php foreach ($allowedStatuses as $statusOption): ?>
                        <option value="<?= hnfSubscriptionEditE($statusOption) ?>"
                            <?= $formStatus === $statusOption ? 'selected' : '' ?>>
                            <?= hnfSubscriptionEditE(hnfSubscriptionEditLabel($statusOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <button type="submit">Update Subscription</button>
                <a href="../controllers/ctr_subscriptions.php">Cancel</a>
            </p>
        </form>
    </div>

    <script>
        const membershipType = document.getElementById('membership_type');
        const membershipStartGroup = document.getElementById('membership_start_group');
        const membershipStart = document.getElementById('membership_start');
        const passType = document.getElementById('pass_type');
        const subscriptionStart = document.getElementById('subscription_start');
        const subscriptionEnd = document.getElementById('subscription_end');
        const statusSelect = document.getElementById('status');

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function computeEndDate(startDate, selectedPassType) {
            if (!startDate) {
                return '';
            }

            const date = new Date(startDate + 'T00:00:00');

            if (selectedPassType === 'monthly') {
                date.setMonth(date.getMonth() + 1);
                date.setDate(date.getDate() - 1);
            }

            return formatDate(date);
        }

        function refreshMembershipField() {
            const isMember = membershipType.value === 'member';

            membershipStartGroup.style.display = isMember ? 'block' : 'none';
            membershipStart.required = isMember;

            if (!isMember) {
                membershipStart.value = '';
            }
        }

        function refreshStatus() {
            if (!subscriptionEnd.value || statusSelect.value === 'suspended') {
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const endDate = new Date(subscriptionEnd.value + 'T00:00:00');

            statusSelect.value = endDate < today ? 'expired' : 'active';
        }

        function refreshSubscriptionEnd() {
            subscriptionEnd.value = computeEndDate(subscriptionStart.value, passType.value);
            refreshStatus();
        }

        membershipType.addEventListener('change', refreshMembershipField);
        passType.addEventListener('change', refreshSubscriptionEnd);
        subscriptionStart.addEventListener('change', refreshSubscriptionEnd);
        statusSelect.addEventListener('change', refreshStatus);

        refreshMembershipField();
        refreshSubscriptionEnd();
    </script>
</body>

</html>