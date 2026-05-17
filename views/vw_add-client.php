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

    <div class="wrapper flex justify-center">
        <?php if (!empty($errors)): ?>
            <div role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars((string) $error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="client-form" method="POST" action="../controllers/ctr_add-client.php">

            <!-- FIRST NAME -->
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" name="first_name" id="first_name"
                    value="<?= htmlspecialchars((string) ($old['first_name'] ?? '')) ?>" required>
            </div>

            <!-- LAST NAME -->
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" name="last_name" id="last_name"
                    value="<?= htmlspecialchars((string) ($old['last_name'] ?? '')) ?>" required>
            </div>

            <!-- CONTACT NUMBER -->
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" name="contact" id="contact"
                    value="<?= htmlspecialchars((string) ($old['contact'] ?? '')) ?>" pattern="09[0-9]{9}"
                    maxlength="11" inputmode="numeric" placeholder="09XXXXXXXXX" required>
            </div>

            <div class="form-group">
                <label for="birthday">Birthday</label>
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="birthday" id="birthday"
                    value="<?= htmlspecialchars((string) ($old['birthday'] ?? '')) ?>" required>
            </div>

            <!-- MEMBERSHIP TYPE -->
            <div class="form-group">
                <label for="membership_type">Membership Type</label>
                <select class="capitalize rounded-sm px-md py-sm focus-visible" name="membership_type" id="membership_type" required>
                    <?php foreach ($membershipTypes as $value => $label): ?>
                        <option value="<?= htmlspecialchars((string) $value) ?>" <?= $selectedMembershipType === (string) $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- PASS TYPE -->
            <div class="form-group">
                <label for="pass_type">Pass Type</label>
                <select class="capitalize rounded-sm px-md py-sm focus-visible" name="pass_type" id="pass_type" required>
                    <?php foreach ($passTypes as $value => $label): ?>
                        <option value="<?= htmlspecialchars((string) $value) ?>" <?= $selectedPassType === (string) $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- PRICES -->
            <div class="flex gap-xsm"">
                <span class="badge badge-active">Plan Fee: <span id="plan_price">-</span></span>
                <span class="badge badge-active">Membership Fee: <span id="annual_fee">-</span></span>
                <span class="badge badge-yellow">Total: <span id="total_price">-</span></span>
            </div>

            <!-- CONTROLS -->
            <div class="form-actions">
                <a class="capitalize rounded-sm px-md py-sm btn-anchor btn-secondary" href="../controllers/ctr_clients.php">Cancel</a>
                <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit" type="submit">Add Client</button>
            </div>
        </form>
    </div>
    </div>
    </div>

    <script>
        const plans = <?= json_encode($plans, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const annualMembershipFee = <?= json_encode($annualMembershipFee, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        const membershipTypeInput = document.getElementById('membership_type');
        const passTypeInput = document.getElementById('pass_type');
        const planPriceOutput = document.getElementById('plan_price');
        const annualFeeOutput = document.getElementById('annual_fee');
        const totalPriceOutput = document.getElementById('total_price');

        const contactInput = document.getElementById('contact');

        contactInput.addEventListener('input', function () {
            let value = contactInput.value.replace(/\D/g, '');

            if (value.length >= 2 && value.slice(0, 2) !== '09') {
                value = '09' + value.slice(2);
            }

            if (value.length > 11) {
                value = value.slice(0, 11);
            }

            contactInput.value = value;
        });

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