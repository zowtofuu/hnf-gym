<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utility.php';

/**
 * Escape output for HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function computeEndDate(string $startDate, string $planName): string
{
    $plan = strtolower(trim($planName));
    $date = new DateTime($startDate);

    if ($plan === 'daily') {
        return $date->format('Y-m-d');
    }

    if ($plan === 'monthly') {
        $date->modify('+1 month');
        $date->modify('-1 day');
        return $date->format('Y-m-d');
    }

    return $date->format('Y-m-d');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection not available.');
}

$message = '';
$error = '';

$subscriptionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriptionId = filter_input(INPUT_POST, 'subscription_id', FILTER_VALIDATE_INT);
}

if (!$subscriptionId) {
    die('Invalid or missing subscription ID.');
}

/*
|--------------------------------------------------------------------------
| Load available plans
|--------------------------------------------------------------------------
*/
$plans = [];

$plansSql = "
    SELECT id, plan_name, price
    FROM membership_plans
    ORDER BY plan_name ASC
";
$plansStmt = $conn->prepare($plansSql);

if (!$plansStmt) {
    die('Failed to prepare plans query: ' . $conn->error);
}

$plansStmt->execute();
$plansResult = $plansStmt->get_result();

while ($row = $plansResult->fetch_assoc()) {
    $plans[] = $row;
}

$plansStmt->close();

/*
|--------------------------------------------------------------------------
| Load current subscription details
|--------------------------------------------------------------------------
*/
$subscriptionSql = "
    SELECT
        s.subscription_id,
        s.client_id,
        s.plan_id,
        s.subscription_start,
        s.subscription_end,
        s.subscription_token,
        s.status,
        c.first_name,
        c.last_name,
        mp.plan_name,
        mp.price
    FROM subscriptions s
    INNER JOIN clients c
        ON c.client_id = s.client_id
    INNER JOIN membership_plans mp
        ON mp.id = s.plan_id
    WHERE s.subscription_id = ?
    LIMIT 1
";

$subscriptionStmt = $conn->prepare($subscriptionSql);
if (!$subscriptionStmt) {
    die('Failed to prepare subscription query: ' . $conn->error);
}

$subscriptionStmt->bind_param('i', $subscriptionId);
$subscriptionStmt->execute();
$subscriptionResult = $subscriptionStmt->get_result();
$subscription = $subscriptionResult->fetch_assoc();
$subscriptionStmt->close();

if (!$subscription) {
    die('Subscription not found.');
}

$fullName = trim(
    ((string) ($subscription['first_name'] ?? '')) . ' ' .
    ((string) ($subscription['last_name'] ?? ''))
);

$oldPlanId = (int) $subscription['plan_id'];
$oldPlanName = (string) $subscription['plan_name'];
$oldStartDate = (string) $subscription['subscription_start'];
$oldEndDate = (string) $subscription['subscription_end'];
$oldStatus = (string) $subscription['status'];
$currentToken = (string) $subscription['subscription_token'];

$newPlanId = $oldPlanId;
$newStartDate = date('Y-m-d');
$newEndDate = computeEndDate($newStartDate, $oldPlanName);

/*
|--------------------------------------------------------------------------
| Handle submit
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPlanId = filter_input(INPUT_POST, 'new_plan_id', FILTER_VALIDATE_INT);
    $newStartDate = trim((string) ($_POST['new_start_date'] ?? ''));
    $newEndDate = trim((string) ($_POST['new_end_date'] ?? ''));

    if (!$newPlanId) {
        $error = 'Please select a plan.';
    } elseif ($newStartDate === '' || $newEndDate === '') {
        $error = 'Please complete the renewal dates.';
    } else {
        $selectedPlanSql = "
            SELECT id, plan_name, price
            FROM membership_plans
            WHERE id = ?
            LIMIT 1
        ";
        $selectedPlanStmt = $conn->prepare($selectedPlanSql);

        if (!$selectedPlanStmt) {
            die('Failed to prepare selected plan query: ' . $conn->error);
        }

        $selectedPlanStmt->bind_param('i', $newPlanId);
        $selectedPlanStmt->execute();
        $selectedPlanResult = $selectedPlanStmt->get_result();
        $selectedPlan = $selectedPlanResult->fetch_assoc();
        $selectedPlanStmt->close();

        if (!$selectedPlan) {
            $error = 'Selected plan does not exist.';
        } else {
            $renewalAmount = (float) $selectedPlan['price'];

            $conn->begin_transaction();

            try {
                $historySql = "
                    INSERT INTO subscriptions_history (
                        subscription_id,
                        client_id,
                        plan_id,
                        subscription_start,
                        subscription_end,
                        subscription_token,
                        status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ";

                $historyStmt = $conn->prepare($historySql);
                if (!$historyStmt) {
                    throw new Exception('Failed to prepare history insert: ' . $conn->error);
                }

                $currentSubscriptionId = (int) $subscription['subscription_id'];
                $currentClientId = (int) $subscription['client_id'];
                $currentPlanId = (int) $subscription['plan_id'];
                $currentStart = (string) $subscription['subscription_start'];
                $currentEnd = (string) $subscription['subscription_end'];
                $currentToken = (string) $subscription['subscription_token'];
                $currentStatus = (string) $subscription['status'];

                $historyStmt->bind_param(
                    'iiissss',
                    $currentSubscriptionId,
                    $currentClientId,
                    $currentPlanId,
                    $currentStart,
                    $currentEnd,
                    $currentToken,
                    $currentStatus
                );

                if (!$historyStmt->execute()) {
                    $historyError = $historyStmt->error;
                    $historyStmt->close();
                    throw new Exception('Failed to save subscription history: ' . $historyError);
                }

                $historyStmt->close();

                $updateSql = "
                    UPDATE subscriptions
                    SET
                        plan_id = ?,
                        subscription_start = ?,
                        subscription_end = ?,
                        status = 'active'
                    WHERE subscription_id = ?
                    LIMIT 1
                ";

                $updateStmt = $conn->prepare($updateSql);
                if (!$updateStmt) {
                    throw new Exception('Failed to prepare subscription update: ' . $conn->error);
                }

                $updateStmt->bind_param(
                    'issi',
                    $newPlanId,
                    $newStartDate,
                    $newEndDate,
                    $subscriptionId
                );

                if (!$updateStmt->execute()) {
                    $updateError = $updateStmt->error;
                    $updateStmt->close();
                    throw new Exception('Failed to renew subscription: ' . $updateError);
                }

                $updateStmt->close();

                // ========================================
                // INSERT RENEWAL SALE
                // ========================================
                $salesSql = "
                    INSERT INTO sales (
                        client_id,
                        subscription_id,
                        plan_id,
                        amount,
                        sale_type
                    ) VALUES (?, ?, ?, ?, 'renewal')
                ";

                $salesStmt = $conn->prepare($salesSql);
                if (!$salesStmt) {
                    throw new Exception('Failed to prepare sales insert: ' . $conn->error);
                }

                $clientIdForSale = (int) $subscription['client_id'];

                $salesStmt->bind_param(
                    'iiid',
                    $clientIdForSale,
                    $subscriptionId,
                    $newPlanId,
                    $renewalAmount
                );

                if (!$salesStmt->execute()) {
                    $salesError = $salesStmt->error;
                    $salesStmt->close();
                    throw new Exception('Failed to save renewal sale: ' . $salesError);
                }

                $salesStmt->close();

                $conn->commit();
                $message = 'Subscription renewed successfully.';

                $reloadStmt = $conn->prepare($subscriptionSql);
                if ($reloadStmt) {
                    $reloadStmt->bind_param('i', $subscriptionId);
                    $reloadStmt->execute();
                    $reloadResult = $reloadStmt->get_result();
                    $subscription = $reloadResult->fetch_assoc();
                    $reloadStmt->close();

                    if ($subscription) {
                        $fullName = trim(
                            ((string) ($subscription['first_name'] ?? '')) . ' ' .
                            ((string) ($subscription['last_name'] ?? ''))
                        );

                        $oldPlanId = (int) $subscription['plan_id'];
                        $oldPlanName = (string) $subscription['plan_name'];
                        $oldStartDate = (string) $subscription['subscription_start'];
                        $oldEndDate = (string) $subscription['subscription_end'];
                        $oldStatus = (string) $subscription['status'];
                        $currentToken = (string) $subscription['subscription_token'];

                        $newPlanId = $oldPlanId;
                        $newStartDate = date('Y-m-d');
                        $newEndDate = computeEndDate($newStartDate, $oldPlanName);
                    }
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$planJs = [];
foreach ($plans as $plan) {
    $planJs[] = [
        'id' => (int) $plan['id'],
        'plan_name' => (string) $plan['plan_name'],
    ];
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
    <?php include '../components/navbar.php'; ?>

    <h1>Renew Subscription</h1>

    <?php if ($message !== ''): ?>
        <p style="color: green;"><?php echo e($message); ?></p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p style="color: red;"><?php echo e($error); ?></p>
    <?php endif; ?>

    <h2>Current Subscription Details</h2>
    <p><strong>Client Name:</strong> <?php echo e($fullName); ?></p>
    <p><strong>Current Plan:</strong> <?php echo e($oldPlanName); ?></p>
    <p><strong>Current Start Date:</strong> <?php echo e($oldStartDate); ?></p>
    <p><strong>Current End Date:</strong> <?php echo e($oldEndDate); ?></p>
    <p><strong>Status:</strong> <?php echo e($oldStatus); ?></p>
    <p><strong>Subscription Token:</strong> <?php echo e($currentToken); ?></p>

    <hr>

    <h2>Renewal Form</h2>

    <form method="post" action="">
        <input type="hidden" name="subscription_id" value="<?php echo e((string) $subscriptionId); ?>">

        <p>
            <label for="new_plan_id">Plan</label><br>
            <select name="new_plan_id" id="new_plan_id" required>
                <option value="">-- Select Plan --</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo e((string) $plan['id']); ?>" <?php echo ((int) $plan['id'] === (int) $newPlanId) ? 'selected' : ''; ?>>
                        <?php echo e((string) $plan['plan_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="new_start_date">Start Date</label><br>
            <input type="date" name="new_start_date" id="new_start_date" value="<?php echo e($newStartDate); ?>"
                required>
        </p>

        <p>
            <label for="new_end_date">End Date</label><br>
            <input type="date" name="new_end_date" id="new_end_date" value="<?php echo e($newEndDate); ?>" required>
        </p>

        <p>
            <button type="submit">Save Renewal</button>
        </p>
    </form>

    <script>
        const plans = <?php echo json_encode($planJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function computeEndDate(startDate, planName) {
            if (!startDate || !planName) {
                return '';
            }

            const plan = String(planName).trim().toLowerCase();
            const date = new Date(startDate + 'T00:00:00');

            if (plan === 'daily') {
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            if (plan === 'monthly') {
                date.setMonth(date.getMonth() + 1);
                date.setDate(date.getDate() - 1);

                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');

                return `${yyyy}-${mm}-${dd}`;
            }

            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');

            return `${yyyy}-${mm}-${dd}`;
        }

        function getSelectedPlanName(planId) {
            const found = plans.find(plan => String(plan.id) === String(planId));
            return found ? found.plan_name : '';
        }

        const planSelect = document.getElementById('new_plan_id');
        const startInput = document.getElementById('new_start_date');
        const endInput = document.getElementById('new_end_date');

        function refreshEndDate() {
            const planName = getSelectedPlanName(planSelect.value);
            const computed = computeEndDate(startInput.value, planName);

            if (computed) {
                endInput.value = computed;
            }
        }

        planSelect.addEventListener('change', refreshEndDate);
        startInput.addEventListener('change', refreshEndDate);
    </script>

</body>

</html>