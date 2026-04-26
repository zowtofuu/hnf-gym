<?php
require_once __DIR__ . '/../config/database.php';

$errors = [];
$successMessage = '';

$subscriptionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($subscriptionId <= 0) {
    die('Invalid subscription ID.');
}

/*
|--------------------------------------------------------------------------
| AUTO UPDATE SUBSCRIPTION STATUS ACCORDINGLY
|--------------------------------------------------------------------------
| suspended stays suspended
| end date in the past = expired
| otherwise = active
*/
$autoStatusStmt = $conn->prepare("
    UPDATE subscriptions
    SET status = CASE
        WHEN status = 'suspended' THEN 'suspended'
        WHEN subscription_end < CURDATE() THEN 'expired'
        ELSE 'active'
    END
");
$autoStatusStmt->execute();
$autoStatusStmt->close();

/*
|--------------------------------------------------------------------------
| FETCH MEMBERSHIP PLANS
|--------------------------------------------------------------------------
*/
$plans = [];

$planStmt = $conn->prepare("
    SELECT id, plan_name
    FROM membership_plans
    ORDER BY plan_name ASC
");
$planStmt->execute();
$planResult = $planStmt->get_result();

while ($row = $planResult->fetch_assoc()) {
    $plans[] = $row;
}
$planStmt->close();

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $start = trim($_POST['subscription_start'] ?? '');
    $end = trim($_POST['subscription_end'] ?? '');
    $status = trim($_POST['status'] ?? '');

    $allowedStatuses = ['active', 'expired', 'suspended'];

    /*
    |--------------------------------------------------------------------------
    | DETECT IF SELECTED PLAN IS DAILY
    |--------------------------------------------------------------------------
    */
    $planName = '';
    $isDaily = false;

    if ($planId > 0) {
        $stmtPlan = $conn->prepare("
            SELECT plan_name
            FROM membership_plans
            WHERE id = ?
            LIMIT 1
        ");
        $stmtPlan->bind_param('i', $planId);
        $stmtPlan->execute();
        $resPlan = $stmtPlan->get_result()->fetch_assoc();
        $stmtPlan->close();

        if ($resPlan) {
            $planName = strtolower(trim((string) $resPlan['plan_name']));
            $isDaily = ($planName === 'daily');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */
    if ($planId <= 0) {
        $errors[] = 'Plan is required.';
    }

    if ($start === '') {
        $errors[] = 'Start date is required.';
    }

    if ($end === '') {
        $errors[] = 'End date is required.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Invalid status.';
    }

    /*
    |--------------------------------------------------------------------------
    | DAILY PLAN RULE
    |--------------------------------------------------------------------------
    */
    if ($isDaily && $start !== '') {
        $end = $start;
    }

    /*
    |--------------------------------------------------------------------------
    | DATE VALIDATION
    |--------------------------------------------------------------------------
    */
    if ($start !== '' && $end !== '' && strtotime($end) < strtotime($start)) {
        $errors[] = 'End date cannot be earlier than start date.';
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SET STATUS ACCORDINGLY BEFORE SAVE
    |--------------------------------------------------------------------------
    | If user sets suspended, keep it suspended.
    | Otherwise:
    |   past end date = expired
    |   today/future end date = active
    */
    if ($status !== 'suspended' && $end !== '') {
        if (strtotime($end) < strtotime(date('Y-m-d'))) {
            $status = 'expired';
        } else {
            $status = 'active';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE SUBSCRIPTION
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {
        $updateStmt = $conn->prepare("
            UPDATE subscriptions
            SET
                plan_id = ?,
                subscription_start = ?,
                subscription_end = ?,
                status = ?
            WHERE subscription_id = ?
            LIMIT 1
        ");

        $updateStmt->bind_param(
            'isssi',
            $planId,
            $start,
            $end,
            $status,
            $subscriptionId
        );

        if ($updateStmt->execute()) {
            $successMessage = 'Updated successfully.';

            /*
            |--------------------------------------------------------------------------
            | REFRESH STORED STATUS AFTER UPDATE
            |--------------------------------------------------------------------------
            */
            $refreshStmt = $conn->prepare("
                UPDATE subscriptions
                SET status = CASE
                    WHEN status = 'suspended' THEN 'suspended'
                    WHEN subscription_end < CURDATE() THEN 'expired'
                    ELSE 'active'
                END
                WHERE subscription_id = ?
                LIMIT 1
            ");
            $refreshStmt->bind_param('i', $subscriptionId);
            $refreshStmt->execute();
            $refreshStmt->close();
        } else {
            $errors[] = 'Update failed.';
        }

        $updateStmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CURRENT SUBSCRIPTION DATA
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        s.plan_id,
        s.subscription_start,
        s.subscription_end,
        s.status,
        c.first_name,
        c.last_name
    FROM subscriptions s
    LEFT JOIN clients c ON s.client_id = c.client_id
    WHERE s.subscription_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $subscriptionId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die('Subscription not found.');
}

/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/
$formPlan = $_POST['plan_id'] ?? $data['plan_id'];
$formStart = $_POST['subscription_start'] ?? $data['subscription_start'];
$formEnd = $_POST['subscription_end'] ?? $data['subscription_end'];
$formStatus = $_POST['status'] ?? $data['status'];

$clientName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

/*
|--------------------------------------------------------------------------
| KEEP DAILY END DATE SAME AS START DATE
|--------------------------------------------------------------------------
*/
$currentPlanName = '';

if ((int) $formPlan > 0) {
    $currentPlanStmt = $conn->prepare("
        SELECT plan_name
        FROM membership_plans
        WHERE id = ?
        LIMIT 1
    ");
    $formPlanId = (int) $formPlan;
    $currentPlanStmt->bind_param('i', $formPlanId);
    $currentPlanStmt->execute();
    $currentPlanRow = $currentPlanStmt->get_result()->fetch_assoc();
    $currentPlanStmt->close();

    if ($currentPlanRow) {
        $currentPlanName = strtolower(trim((string) $currentPlanRow['plan_name']));
        if ($currentPlanName === 'daily' && $formStart !== '') {
            $formEnd = $formStart;
        }
    }
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

<?php include '../components/navbar.php'; ?>

<h1>Edit Subscription</h1>

<hr>

<p><strong>Client:</strong> <?php echo htmlspecialchars($clientName); ?></p>

<hr>

<?php if ($successMessage !== ''): ?>
    <p>
        <strong><?php echo htmlspecialchars($successMessage); ?></strong>
    </p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div>
        <strong>Please fix the following:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST">
    <table cellpadding="8">
        <tr>
            <td><label for="plan_id"><strong>Plan</strong></label></td>
            <td>
                <select name="plan_id" id="plan_id" required>
                    <option value="">Select plan</option>
                    <?php foreach ($plans as $p): ?>
                        <option
                            value="<?php echo htmlspecialchars((string) $p['id']); ?>"
                            <?php echo (string) $formPlan === (string) $p['id'] ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars((string) $p['plan_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <tr>
            <td><label for="subscription_start"><strong>Start</strong></label></td>
            <td>
                <input
                    type="date"
                    name="subscription_start"
                    id="subscription_start"
                    value="<?php echo htmlspecialchars((string) $formStart); ?>"
                    required
                >
            </td>
        </tr>

        <tr>
            <td><label for="subscription_end"><strong>End</strong></label></td>
            <td>
                <input
                    type="date"
                    name="subscription_end"
                    id="subscription_end"
                    value="<?php echo htmlspecialchars((string) $formEnd); ?>"
                    required
                >
            </td>
        </tr>

        <tr>
            <td><label for="status"><strong>Status</strong></label></td>
            <td>
                <select name="status" id="status" required>
                    <option value="active" <?php echo $formStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $formStatus === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="suspended" <?php echo $formStatus === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </td>
        </tr>

        <tr>
            <td></td>
            <td>
                <button type="submit">Update</button>
            </td>
        </tr>
    </table>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const planSelect = document.getElementById('plan_id');
    const startInput = document.getElementById('subscription_start');
    const endInput = document.getElementById('subscription_end');
    const statusSelect = document.getElementById('status');

    function isDailyPlan() {
        const selectedOption = planSelect.options[planSelect.selectedIndex];
        if (!selectedOption) {
            return false;
        }

        return selectedOption.text.trim().toLowerCase() === 'daily';
    }

    function applyDailyRule() {
        if (isDailyPlan()) {
            endInput.value = startInput.value;
            endInput.readOnly = true;
        } else {
            endInput.readOnly = false;
        }
    }

    function autoStatus() {
        if (!endInput.value) {
            return;
        }

        if (statusSelect.value === 'suspended') {
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const endDate = new Date(endInput.value + 'T00:00:00');

        if (endDate < today) {
            statusSelect.value = 'expired';
        } else {
            statusSelect.value = 'active';
        }
    }

    planSelect.addEventListener('change', function () {
        applyDailyRule();
        autoStatus();
    });

    startInput.addEventListener('change', function () {
        if (isDailyPlan()) {
            endInput.value = startInput.value;
        }
        autoStatus();
    });

    endInput.addEventListener('change', autoStatus);

    statusSelect.addEventListener('change', function () {
        if (statusSelect.value !== 'suspended') {
            autoStatus();
        }
    });

    applyDailyRule();
    autoStatus();
});
</script>

</body>
</html>