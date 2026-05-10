<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Client</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <?php
    $errors = $errors ?? [];
    $old = $old ?? [];
    $plans = $plans ?? [];
    $annualMembershipFee = (float) ($annualMembershipFee ?? 0);

    $selectedMembershipType = (string) ($old['membership_type'] ?? 'non_member');
    $selectedPassType = (string) ($old['pass_type'] ?? 'daily');

    $membershipTypes = membershipTypeLabels();
    $passTypes = passTypeLabels();
    ?>

    <div class="wrapper">
        <h1>Add Client</h1>

        <?php if (!empty($errors)): ?>
            <div role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars((string) $error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="forms-center" method="POST" action="../controllers/ctr_add-client.php">

            <!-- FIRST NAME -->
            <div>
                <label for="first_name">First Name</label>
                <input
                    class="capitalize rounded-sm px8 py16 fv"
                    type="text"
                    name="first_name"
                    id="first_name"
                    value="<?= htmlspecialchars((string) ($old['first_name'] ?? '')) ?>"
                    required>
            </div>

            <!-- LAST NAME -->
            <div>
                <label for="last_name">Last Name</label>
                <input
                    class="capitalize rounded-sm px8 py16 fv"
                    type="text"
                    name="last_name"
                    id="last_name"
                    value="<?= htmlspecialchars((string) ($old['last_name'] ?? '')) ?>"
                    required>
            </div>

            <!-- CONTACT NUMBER -->
            <div>
                <label for="contact">Contact Number</label>
                <input
                    class="rounded-sm px8 py16 fv"
                    type="text"
                    name="contact"
                    id="contact"
                    value="<?= htmlspecialchars((string) ($old['contact'] ?? '')) ?>"
                    pattern="09[0-9]{9}"
                    maxlength="11"
                    inputmode="numeric"
                    placeholder="09XXXXXXXXX"
                    required>
            </div>

            <!-- MEMBERSHIP TYPE -->
            <div>
                <label for="membership_type">Membership Type</label>
                <select class="capitalize rounded-sm px8 py16 fv" name="membership_type" id="membership_type" required>
                    <?php foreach ($membershipTypes as $value => $label): ?>
                        <option
                            value="<?= htmlspecialchars((string) $value) ?>"
                            <?= $selectedMembershipType === (string) $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- PASS TYPE -->
            <div>
                <label for="pass_type">Pass Type</label>
                <select class="capitalize rounded-sm px8 py16 fv" name="pass_type" id="pass_type" required>
                    <?php foreach ($passTypes as $value => $label): ?>
                        <option
                            value="<?= htmlspecialchars((string) $value) ?>"
                            <?= $selectedPassType === (string) $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- PRICES -->
            <div>
                <span>Plan Fee: </span>
                <span id="plan_price">-</span>
            </div>

            <div>
                <span>Annual Membership Fee: </span>
                <span id="annual_fee">-</span>
            </div>

            <div>
                <strong>Total: </strong>
                <strong id="total_price">-</strong>
            </div>

            <!-- CONTROLS -->
            <div>
                <button type="submit">Add Client</button>
                <a href="../controllers/ctr_clients.php">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        const plans = <?= json_encode($plans, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const annualMembershipFee = <?= json_encode($annualMembershipFee, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        const membershipTypeInput = document.getElementById('membership_type');
        const passTypeInput = document.getElementById('pass_type');
        const planPriceOutput = document.getElementById('plan_price');
        const annualFeeOutput = document.getElementById('annual_fee');
        const totalPriceOutput = document.getElementById('total_price');

        function formatPeso(value) {
            const amount = Number(value || 0);

            return amount.toLocaleString('en-PH', {
                style: 'currency',
                currency: 'PHP'
            });
        }

        function updatePlanPrice() {
            const selectedPlan = plans.find((plan) => {
                return plan.membership_type === membershipTypeInput.value &&
                    plan.pass_type === passTypeInput.value;
            });

            if (!selectedPlan) {
                planPriceOutput.textContent = '-';
                annualFeeOutput.textContent = '-';
                totalPriceOutput.textContent = '-';
                return;
            }

            const planPrice = Number(selectedPlan.price || 0);

            const memberFee = membershipTypeInput.value === 'member'
                ? Number(annualMembershipFee || 0)
                : 0;

            const totalPrice = planPrice + memberFee;

            planPriceOutput.textContent = formatPeso(planPrice);
            annualFeeOutput.textContent = memberFee > 0 ? formatPeso(memberFee) : '-';
            totalPriceOutput.textContent = formatPeso(totalPrice);
        }

        membershipTypeInput.addEventListener('change', updatePlanPrice);
        passTypeInput.addEventListener('change', updatePlanPrice);

        updatePlanPrice();
    </script>
</body>

</html>