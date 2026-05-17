<?php ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renewal</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <div class="wrapper">
        <?php if ($successMessage !== ''): ?>
            <p class="alert alert-success js-alert"><?= hnfRenewalEscape($successMessage) ?></p>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <p class="alert alert-danger js-alert"><?= hnfRenewalEscape($errorMessage) ?></p>
        <?php endif; ?>
        <section class="flex justify-center">
            <form class="client-form" method="POST">
                <div class="flex flex-wrap justify-between items-baseline">
                    <h3 class="legend">Renewal</h3>
                    <p class="capitalize mb-sm">Name: <span
                            class="muted-text"><?= hnfRenewalEscape($clientName) ?></span>
                    </p>
                </div>
                <!-- information -->
                <div class="form-group">

                    <section>
                        <p class="capitalize">Status:</p>
                        <div>
                            <span
                                class="badge <?= $membershipStatus === 'Active' ? 'badge-active' : 'badge-expired' ?>">Membership:
                                <span><?= hnfRenewalEscape($membershipStatus) ?></span></span>
                            <span class="badge <?= $passStatus === 'Active' ? 'badge-active' : 'badge-expired' ?>">Pass:
                                <span><?= hnfRenewalEscape($passStatus) ?></span></span>
                        </div>
                    </section>
                </div>

                <!-- prices -->
                <div class="form-group">
                    <p class="capitalize">Price:</p>
                    <div class="flex flex-wrap gap-sm">
                        <span class="badge badge-active">Membership Fee: ₱<span id="membershipFee">0.00</span></span>
                        <span class="badge badge-active">Pass Fee: ₱ <span id="passFee">0.00</span></span>
                        <span class="badge badge-yellow">Total Price: ₱<span id="totalPrice">0.00</span></span>
                    </div>
                </div>

                <!-- membership -->
                <div class="form-group">
                    <select class="capitalize rounded-sm px-md py-sm focus-visible" name="membership_type"
                        id="membershipType">
                        <option value="">select membership type</option>

                        <?php foreach ($membershipTypes as $value => $label): ?>
                            <option value="<?= hnfRenewalEscape($value) ?>">
                                <?= hnfRenewalEscape($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="">Membership Start Date</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="membership_start"
                        id="membershipStart" value="<?= hnfRenewalEscape($currentMembershipStart) ?>">
                </div>

                <div class="form-group border-bottom mb-md pb-md">
                    <label for="">Membership End Date</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="membership_end"
                        id="membershipEnd" value="<?= hnfRenewalEscape($currentMembershipEnd) ?>">
                </div>

                <div class="form-group">
                    <select class="capitalize rounded-sm px-md py-sm focus-visible" name="pass_type" id="passType">
                        <option value="">select pass type</option>

                        <?php foreach ($passTypes as $value => $label): ?>
                            <option value="<?= hnfRenewalEscape($value) ?>">
                                <?= hnfRenewalEscape($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="">Subscription Start Date</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="subscription_start"
                        id="subscriptionStart" value="<?= hnfRenewalEscape($currentSubscriptionStart) ?>">
                </div>

                <div class="form-group">
                    <label for="">Subscription End Date</label>
                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date" name="subscription_end"
                        id="subscriptionEnd" value="<?= hnfRenewalEscape($currentSubscriptionEnd) ?>">
                </div>

                <div class="form-actions">
                    <a class="capitalize rounded-sm px-md py-sm btn-anchor btn-secondary"
                        href="../controllers/ctr_subscriptions.php">Cancel</a>
                    <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit"
                        type="submit" onclick="return confirm('Are you sure you want to save this renewal?');">Save
                        Renewal</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        // auto refresh ui
        const plans = <?= json_encode($plansForJs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        const annualMembershipFee = <?= json_encode($annualMembershipFee) ?>;
        const currentMembershipType = <?= json_encode($subscription['membership_type']) ?>;
        const today = <?= json_encode(date('Y-m-d')) ?>;

        const membershipType = document.getElementById('membershipType');
        const passType = document.getElementById('passType');

        const membershipStart = document.getElementById('membershipStart');
        const membershipEnd = document.getElementById('membershipEnd');

        const subscriptionStart = document.getElementById('subscriptionStart');
        const subscriptionEnd = document.getElementById('subscriptionEnd');

        const membershipFee = document.getElementById('membershipFee');
        const passFee = document.getElementById('passFee');
        const totalPrice = document.getElementById('totalPrice');

        const isMembershipLocked = <?= json_encode($isMembershipLocked) ?>;
        const isPassLocked = <?= json_encode($isPassLocked) ?>;

        function formatMoney(amount) {
            return Number(amount).toFixed(2);
        }

        function addOneYear(dateValue) {
            const date = new Date(dateValue + 'T00:00:00');
            date.setFullYear(date.getFullYear() + 1);

            return date.toISOString().slice(0, 10);
        }

        function addOneMonth(dateValue) {
            const date = new Date(dateValue + 'T00:00:00');
            date.setMonth(date.getMonth() + 1);

            return date.toISOString().slice(0, 10);
        }

        function refreshMembershipDates() {
            if (membershipType.value === '') {
                return;
            }

            if (membershipStart.value === '') {
                membershipStart.value = today;
            }

            if (membershipType.value === 'member') {
                membershipEnd.value = addOneYear(membershipStart.value);
            } else {
                membershipEnd.value = '';
            }
        }

        function refreshPassDates() {
            if (passType.value === '') {
                return;
            }

            if (subscriptionStart.value === '') {
                subscriptionStart.value = today;
            }

            if (passType.value === 'monthly') {
                subscriptionEnd.value = addOneMonth(subscriptionStart.value);
            } else {
                subscriptionEnd.value = subscriptionStart.value;
            }
        }

        function refreshPrice() {
            const selectedMembershipType = membershipType.value;
            const selectedPassType = passType.value;

            const finalMembershipType = selectedMembershipType !== ''
                ? selectedMembershipType
                : currentMembershipType;

            let membershipAmount = 0;
            let passAmount = 0;

            if (selectedMembershipType === 'member') {
                membershipAmount = Number(annualMembershipFee);
            }

            if (
                selectedPassType !== '' &&
                plans[finalMembershipType] &&
                plans[finalMembershipType][selectedPassType]
            ) {
                passAmount = Number(plans[finalMembershipType][selectedPassType]);
            }

            membershipFee.textContent = formatMoney(membershipAmount);
            passFee.textContent = formatMoney(passAmount);
            totalPrice.textContent = formatMoney(membershipAmount + passAmount);
        }

        membershipType.addEventListener('change', function () {
            refreshMembershipDates();
            refreshPrice();
        });

        passType.addEventListener('change', function () {
            refreshPassDates();
            refreshPrice();
        });

        membershipStart.addEventListener('change', function () {
            refreshMembershipDates();
        });

        subscriptionStart.addEventListener('change', function () {
            refreshPassDates();
        });

        refreshPrice();
        if (isMembershipLocked) {
            membershipType.disabled = true;
            membershipStart.disabled = true;
            membershipEnd.disabled = true;
        }

        if (isPassLocked) {
            passType.disabled = true;
            subscriptionStart.disabled = true;
            subscriptionEnd.disabled = true;
        }
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