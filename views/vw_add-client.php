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
        <h4 class="legend">Add New Client</h4>

        <?php if (!empty($success)): ?>
            <p><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="ctr_add-client.php" method="POST">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name"
                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name"
                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>

            <label for="contact">Contact Number:</label>
            <input type="tel" id="contact" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>"
                required> <hr>

            <label for="membership_type">Membership Type:</label>
            <select id="membership_type" name="membership_type" required>
                <?php foreach ($membershipTypes as $type): ?>
                    <?php
                    $membershipValue = $type['membership_type'];
                    $selectedMembership = $_POST['membership_type'] ?? 'non_member';
                    ?>
                    <option value="<?= htmlspecialchars($membershipValue) ?>" <?= $selectedMembership === $membershipValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $membershipValue))) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="pass_type">Pass Type:</label>
            <select id="pass_type" name="pass_type" required>
                <?php foreach ($passTypes as $pass): ?>
                    <?php
                    $passValue = $pass['pass_type'];
                    $selectedPass = $_POST['pass_type'] ?? 'daily';
                    ?>
                    <option value="<?= htmlspecialchars($passValue) ?>" <?= $selectedPass === $passValue ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $passValue))) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="subscription_start">Subscription Start:</label>
            <input type="date" name="subscription_start" id="subscription_start"
                value="<?= htmlspecialchars($_POST['subscription_start'] ?? date('Y-m-d')) ?>" required>

            <label for="subscription_end">Subscription End:</label>
            <input type="date" name="subscription_end" id="subscription_end">

            <button type="submit">Add Client</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('subscription_start');
            const endInput = document.getElementById('subscription_end');
            const passSelect = document.getElementById('pass_type');

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }

            function getTodayDateOnly() {
                const now = new Date();

                return new Date(
                    now.getFullYear(),
                    now.getMonth(),
                    now.getDate()
                );
            }

            function updateDates() {
                const passType = passSelect.value;
                const startDate = getTodayDateOnly();
                const endDate = new Date(startDate);

                if (passType === 'monthly') {
                    endDate.setMonth(endDate.getMonth() + 1);
                }

                // daily stays same day
                startInput.value = formatDate(startDate);
                endInput.value = formatDate(endDate);
            }

            passSelect.addEventListener('change', updateDates);
            passSelect.addEventListener('input', updateDates);

            updateDates();
        });
    </script>
</body>

</html>