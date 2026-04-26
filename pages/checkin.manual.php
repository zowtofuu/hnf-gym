<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$selectedClientId = '';
$selectedDate = date('Y-m-d');

/**
 * Fetch all clients for dropdown
 */
$clients = [];

$stmt = $conn->prepare("
    SELECT client_id, first_name, last_name
    FROM clients
    ORDER BY first_name ASC, last_name ASC
");

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

$stmt->close();

/**
 * Handle form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClientId = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
    $selectedDate = isset($_POST['attendance_date']) && $_POST['attendance_date'] !== ''
        ? $_POST['attendance_date']
        : date('Y-m-d');

    $dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);

    if ($selectedClientId <= 0) {
        $message = 'Please select a client.';
    } elseif (!$dateObject || $dateObject->format('Y-m-d') !== $selectedDate) {
        $message = 'Invalid attendance date.';
    } else {
        /**
         * Step 1: Verify the client exists
         */
        $stmt = $conn->prepare("
            SELECT client_id, first_name, last_name
            FROM clients
            WHERE client_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("i", $selectedClientId);
        $stmt->execute();
        $clientResult = $stmt->get_result();
        $client = $clientResult->fetch_assoc();
        $stmt->close();

        if (!$client) {
            $message = 'Client not found.';
        } else {
            /**
             * Step 2: Check if client has an active subscription for the selected date
             */
            $stmt = $conn->prepare("
            SELECT subscription_id
            FROM subscriptions
            WHERE client_id = ?
            AND status = 'active'
            AND ? BETWEEN subscription_start AND subscription_end
            LIMIT 1
            ");

            if (!$stmt) {
                die('Prepare failed: ' . $conn->error);
            }

            $stmt->bind_param("is", $selectedClientId, $selectedDate);
            $stmt->execute();
            $subscriptionResult = $stmt->get_result();
            $activeSubscription = $subscriptionResult->fetch_assoc();
            $stmt->close();

            if (!$activeSubscription) {
                $message = 'No active subscription for the selected date.';
            } else {
                /**
                 * Step 3: Check if attendance already exists
                 */
                $stmt = $conn->prepare("
                    SELECT attendance_id
                    FROM attendance
                    WHERE client_id = ? AND attendance_date = ?
                    LIMIT 1
                ");

                if (!$stmt) {
                    die('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param("is", $selectedClientId, $selectedDate);
                $stmt->execute();
                $attendanceResult = $stmt->get_result();
                $existingAttendance = $attendanceResult->fetch_assoc();
                $stmt->close();

                if ($existingAttendance) {
                    $message = 'Client already checked in on this date.';
                } else {
                    /**
                     * Step 4: Insert attendance
                     */
                    $checkInTime = date('H:i:s');

                    $stmt = $conn->prepare("
                        INSERT INTO attendance (client_id, attendance_date, check_in_time)
                        VALUES (?, ?, ?)
                    ");

                    if (!$stmt) {
                        die('Prepare failed: ' . $conn->error);
                    }

                    $stmt->bind_param("iss", $selectedClientId, $selectedDate, $checkInTime);

                    if ($stmt->execute()) {
                        $fullName = $client['first_name'] . ' ' . $client['last_name'];
                        $message = 'Check-in successful for ' . $fullName . '.';
                    } else {
                        if ($conn->errno === 1062) {
                            $message = 'Client already checked in on this date.';
                        } else {
                            $message = 'Failed to save attendance.';
                        }
                    }

                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Check-in</title>
</head>

<body>
    <?php include '../components/navbar.php'; ?>
    <div class="wrapper">
        <h2 class="legend">Manual Check-in</h2>

        <?php if ($message !== ''): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form class="rounded-container p1" method="POST" action="">
            <div class="px">
                <label for="client_id">Select Client</label><br>
                <select class="date-input" name="client_id" id="client_id" required>
                    <option value="">-- Select Client --</option>

                    <?php foreach ($clients as $clientRow): ?>
                        <?php
                        $clientId = (int) $clientRow['client_id'];
                        $fullName = $clientRow['first_name'] . ' ' . $clientRow['last_name'];
                        $isSelected = ((string) $selectedClientId === (string) $clientId) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $clientId; ?>" <?php echo $isSelected; ?>>
                            <?php echo htmlspecialchars($fullName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="px">
                <label for="attendance_date">Check-in Date</label><br>
                <input class="date-input" type="date" name="attendance_date" id="attendance_date"
                    value="<?php echo htmlspecialchars($selectedDate); ?>" required>
            </div>
            <button class="btn btn-text" type="submit">Check In</button>
        </form>
    </div>
</body>

</html>