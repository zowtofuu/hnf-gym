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

        <?php if ($successMessage !== ''): ?>
            <p class="alert alert-success js-alert"><?= hnfSubscriptionEditE($successMessage) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger js-alert">Please fix the following:
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= hnfSubscriptionEditE($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="flex justify-center">
            <form class="client-form" method="POST" action="../controllers/ctr_subscription-edit.php">
                <div class="mb-md capitalize"><strong>Client:</strong> <?= hnfSubscriptionEditE($clientName) ?></div>
                <input type="hidden" name="subscription_id" value="<?= hnfSubscriptionEditE($subscriptionId) ?>">

                <div class="form-group">
                    <label for="membership_type">Membership Type</label>
                    <select class="capitalize rounded-sm px-md py-sm focus-visible" name="membership_type"
                        id="membership_type" required>
                        <?php foreach ($membershipTypes as $type): ?>
                            <option value="<?= hnfSubscriptionEditE($type['membership_type']) ?>"
                                <?= $membershipType === $type['membership_type'] ? 'selected' : '' ?>>
                                <?= hnfSubscriptionEditE(hnfSubscriptionEditLabel($type['membership_type'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!--  id="membership_start_group" -->
                <div class="form-group">
                    <label for="membership_start">Membership Start</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="membership_start"
                        id="membership_start" value="<?= hnfSubscriptionEditE($membershipStart) ?>">
                </div>

                <div class="form-group">
                    <label for="pass_type">Pass Type</label>
                    <select class="capitalize rounded-sm px-md py-sm focus-visible" name="pass_type" id="pass_type"
                        required>
                        <?php foreach ($passTypes as $pass): ?>
                            <option value="<?= hnfSubscriptionEditE($pass['pass_type']) ?>"
                                <?= $passType === $pass['pass_type'] ? 'selected' : '' ?>>
                                <?= hnfSubscriptionEditE(hnfSubscriptionEditLabel($pass['pass_type'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subscription_start">Subscription Start</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="subscription_start"
                        id="subscription_start" value="<?= hnfSubscriptionEditE($subscriptionStart) ?>" required>
                </div>

                <div class="form-group">
                    <label for="subscription_end">Subscription End</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" id="subscription_end"
                        value="<?= hnfSubscriptionEditE($subscriptionEnd) ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="capitalize rounded-sm px-md py-sm focus-visible" name="status" id="status" required>
                        <?php foreach ($allowedStatuses as $statusOption): ?>
                            <option value="<?= hnfSubscriptionEditE($statusOption) ?>" <?= $formStatus === $statusOption ? 'selected' : '' ?>>
                                <?= hnfSubscriptionEditE(hnfSubscriptionEditLabel($statusOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <a class="capitalize rounded-sm px-md py-sm btn-anchor btn-secondary"
                        href="../controllers/ctr_subscriptions.php">Cancel</a>
                    <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit" onclick="return confirm('Are you sure you want to EDIT this subscription?');">Update
                        Subscription</button>
                </div>
            </form>
        </div>
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
    <script>
        setTimeout(() => {
            document.querySelectorAll('.js-alert').forEach((alert) => {
                alert.style.display = 'none';
            });
        }, 2000);
    </script>
</body>

</html>