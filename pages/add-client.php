<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$status = '';

// ========================================
// LOAD MEMBERSHIP PLANS FOR DROPDOWN
// ========================================
$plans = [];

$planQuery = "SELECT id, plan_name, price FROM membership_plans ORDER BY plan_name ASC";
$planResult = $conn->query($planQuery);

if ($planResult) {
    while ($row = $planResult->fetch_assoc()) {
        $plans[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ========================================
    // CLIENT INPUTS
    // ========================================
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';

    // ========================================
    // SUBSCRIPTION INPUTS
    // ========================================
    $planId = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : 0;
    $subscriptionStart = isset($_POST['subscription_start']) ? trim($_POST['subscription_start']) : '';
    $subscriptionEnd = isset($_POST['subscription_end']) ? trim($_POST['subscription_end']) : '';

    // ========================================
    // DETECT PLAN NAME + PRICE
    // ========================================
    $planName = '';
    $planPrice = 0.00;
    $isDaily = false;

    if ($planId > 0) {
        $stmtPlan = $conn->prepare("
            SELECT plan_name, price
            FROM membership_plans
            WHERE id = ?
            LIMIT 1
        ");

        if ($stmtPlan) {
            $stmtPlan->bind_param('i', $planId);
            $stmtPlan->execute();
            $resPlan = $stmtPlan->get_result()->fetch_assoc();
            $stmtPlan->close();

            if ($resPlan) {
                $planName = strtolower(trim((string) $resPlan['plan_name']));
                $planPrice = (float) $resPlan['price'];
                $isDaily = ($planName === 'daily');
            }
        }
    }

    // ========================================
    // DAILY RULE: END = START
    // ========================================
    if ($isDaily && $subscriptionStart !== '') {
        $subscriptionEnd = $subscriptionStart;
    }

    // ========================================
    // BASIC VALIDATION
    // ========================================
    if (
        $firstName === '' ||
        $lastName === '' ||
        $planId <= 0 ||
        $subscriptionStart === '' ||
        $subscriptionEnd === ''
    ) {
        $status = 'error';
        $message = 'First name, last name, plan, subscription start, and subscription end are required.';
    } elseif ($subscriptionEnd < $subscriptionStart) {
        $status = 'error';
        $message = 'Subscription end date cannot be earlier than subscription start date.';
    } else {

        // ========================================
        // START TRANSACTION
        // ========================================
        $conn->begin_transaction();

        try {
            // ========================================
            // INSERT CLIENT
            // ========================================
            $clientStmt = $conn->prepare("
                INSERT INTO clients (first_name, last_name)
                VALUES (?, ?)
            ");

            if (!$clientStmt) {
                throw new Exception('Client prepare failed: ' . $conn->error);
            }

            $clientStmt->bind_param("ss", $firstName, $lastName);

            if (!$clientStmt->execute()) {
                throw new Exception('Client execute failed: ' . $clientStmt->error);
            }

            $clientId = $clientStmt->insert_id;
            $clientStmt->close();

            // ========================================
            // GENERATE UNIQUE SUBSCRIPTION TOKEN
            // ========================================
            $subscriptionToken = bin2hex(random_bytes(16));

            // ========================================
            // INSERT SUBSCRIPTION
            // ========================================
            $subscriptionStmt = $conn->prepare("
                INSERT INTO subscriptions (
                    client_id,
                    plan_id,
                    subscription_start,
                    subscription_end,
                    subscription_token,
                    status
                )
                VALUES (?, ?, ?, ?, ?, 'active')
            ");

            if (!$subscriptionStmt) {
                throw new Exception('Subscription prepare failed: ' . $conn->error);
            }

            $subscriptionStmt->bind_param(
                "iisss",
                $clientId,
                $planId,
                $subscriptionStart,
                $subscriptionEnd,
                $subscriptionToken
            );

            if (!$subscriptionStmt->execute()) {
                throw new Exception('Subscription execute failed: ' . $subscriptionStmt->error);
            }

            $subscriptionId = $subscriptionStmt->insert_id;
            $subscriptionStmt->close();

            // ========================================
            // INSERT SALES RECORD
            // ========================================
            $salesStmt = $conn->prepare("
                INSERT INTO sales (
                    client_id,
                    subscription_id,
                    plan_id,
                    amount,
                    sale_type
                )
                VALUES (?, ?, ?, ?, 'new_subscription')
            ");

            if (!$salesStmt) {
                throw new Exception('Sales prepare failed: ' . $conn->error);
            }

            $salesStmt->bind_param(
                "iiid",
                $clientId,
                $subscriptionId,
                $planId,
                $planPrice
            );

            if (!$salesStmt->execute()) {
                throw new Exception('Sales execute failed: ' . $salesStmt->error);
            }

            $salesStmt->close();

            // ========================================
            // COMMIT
            // ========================================
            $conn->commit();

            header("Location: clients.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $status = 'error';
            $message = $e->getMessage();
        }
    }
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
    <?php include '../components/navbar.php'; ?>

    <div class="wrapper">
        <h2 class="legend">Add Client</h2>

        <?php if (!empty($message)): ?>
            <p>
                <strong><?php echo strtoupper($status); ?>:</strong>
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="flex px">
                <div>
                    <label for="first_name">First Name:</label><br>
                    <input class="search-input" type="text" id="first_name" name="first_name"
                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                        required>
                </div>

                <div>
                    <label for="last_name">Last Name:</label><br>
                    <input class="search-input" type="text" id="last_name" name="last_name"
                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                        required>
                </div>
            </div>

            <div class="rounded-container p1">
                <h3 class="px">Subscription Details</h3>

                <div>
                    <label for="plan_id">Membership Plan:</label><br>
                    <select class="date-input" id="plan_id" name="plan_id" required>
                        <option value="">Select Plan</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo (int) $plan['id']; ?>" <?php echo (isset($_POST['plan_id']) && (int) $_POST['plan_id'] === (int) $plan['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($plan['plan_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <br>

                <div>
                    <label for="subscription_start">Subscription Start:</label><br>
                    <input class="date-input" type="date" id="subscription_start" name="subscription_start"
                        value="<?php echo isset($_POST['subscription_start']) ? htmlspecialchars($_POST['subscription_start']) : ''; ?>"
                        required>
                </div>

                <br>

                <div>
                    <label for="subscription_end">Subscription End:</label><br>
                    <input class="date-input" type="date" id="subscription_end" name="subscription_end"
                        value="<?php echo isset($_POST['subscription_end']) ? htmlspecialchars($_POST['subscription_end']) : ''; ?>"
                        required>
                </div>
            </div>

            <br>

            <button class="btn btn-text" type="submit">Add Client</button>
        </form>
    </div>
</body>

</html>