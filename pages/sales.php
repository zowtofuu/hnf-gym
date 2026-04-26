<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utility.php';

date_default_timezone_set('Asia/Manila');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$selectedDate = isset($_GET['sale_date']) && $_GET['sale_date'] !== ''
    ? $_GET['sale_date']
    : date('Y-m-d');

// ========================================
// TOTAL FOR SELECTED DATE
// ========================================
$selectedDateTotal = 0.00;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM sales
    WHERE DATE(sale_date) = ?
");
$stmt->bind_param('s', $selectedDate);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$selectedDateTotal = (float) ($result['total'] ?? 0);
$stmt->close();

// ========================================
// TOTAL FOR TODAY
// ========================================
$today = date('Y-m-d');
$todayTotal = 0.00;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM sales
    WHERE DATE(sale_date) = ?
");
$stmt->bind_param('s', $today);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$todayTotal = (float) ($result['total'] ?? 0);
$stmt->close();

// ========================================
// TOTAL FOR THIS MONTH
// ========================================
$thisMonth = date('Y-m');
$thisMonthTotal = 0.00;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM sales
    WHERE DATE_FORMAT(sale_date, '%Y-%m') = ?
");
$stmt->bind_param('s', $thisMonth);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$thisMonthTotal = (float) ($result['total'] ?? 0);
$stmt->close();

// ========================================
// TOTAL FOR THIS YEAR
// ========================================
$thisYear = date('Y');
$thisYearTotal = 0.00;
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM sales
    WHERE YEAR(sale_date) = ?
");
$stmt->bind_param('s', $thisYear);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$thisYearTotal = (float) ($result['total'] ?? 0);
$stmt->close();

// ========================================
// SALES LIST FOR SELECTED DATE
// ========================================
$salesRows = [];

$listSql = "
    SELECT
        s.sale_id,
        s.sale_type,
        s.amount,
        s.sale_date,
        c.first_name,
        c.last_name,
        mp.plan_name
    FROM sales s
    INNER JOIN clients c
        ON c.client_id = s.client_id
    INNER JOIN membership_plans mp
        ON mp.id = s.plan_id
    WHERE DATE(s.sale_date) = ?
    ORDER BY s.sale_date DESC, s.sale_id DESC
";

$listStmt = $conn->prepare($listSql);
$listStmt->bind_param('s', $selectedDate);
$listStmt->execute();
$listResult = $listStmt->get_result();

while ($row = $listResult->fetch_assoc()) {
    $salesRows[] = $row;
}

$listStmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales</title>
</head>

<body>
    <?php include '../components/navbar.php'; ?>

    <div class="wrapper">
        <h1 class="legends">Sales</h1>
        <form method="GET" action="">
            <label for="sale_date">Select Date:</label>
            <input type="date" name="sale_date" id="sale_date" value="<?php echo e($selectedDate); ?>">
            <button type="submit">Filter</button>
        </form>

        <hr>

        <p><strong>Sales on <?php echo e(formatReadableDate($selectedDate)); ?>:</strong>
            ₱<?php echo number_format($selectedDateTotal, 2); ?></p>
        <p><strong>Sales today:</strong> ₱<?php echo number_format($todayTotal, 2); ?></p>
        <p><strong>Sales this month:</strong> ₱<?php echo number_format($thisMonthTotal, 2); ?></p>
        <p><strong>Sales this year:</strong> ₱<?php echo number_format($thisYearTotal, 2); ?></p>

        <hr>

        <h2>Sales Records for <?php echo e(formatReadableDate($selectedDate)); ?></h2>

        <?php if (empty($salesRows)): ?>
            <p>No sales found for this date.</p>
        <?php else: ?>
            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Client</th>
                        <th>Plan</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesRows as $row): ?>
                        <tr>
                            <td><?php echo (int) $row['sale_id']; ?></td>
                            <td><?php echo e(trim($row['first_name'] . ' ' . $row['last_name'])); ?></td>
                            <td><?php echo e((string) $row['plan_name']); ?></td>
                            <td><?php echo e((string) $row['sale_type']); ?></td>
                            <td>₱<?php echo number_format((float) $row['amount'], 2); ?></td>
                            <td><?php echo e((string) $row['sale_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>