<?php
require_once __DIR__ . '/../config/database.php';

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedTransaction = $_GET['transaction_type'] ?? 'all';
$selectedItem = $_GET['item_name'] ?? 'all';

$columns = [
    'client_name' => 'Client',
    'transaction_type' => 'Transaction',
    'item_name' => 'Item',
    'quantity' => 'Qty',
    'amount' => 'Amount',
    'created_at' => 'Date'
];

function formatLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

function formatSalesValue(string $key, array $row): string
{
    $value = $row[$key] ?? '';

    if ($key === 'client_name') {
        return $value ?: 'N/A';
    }

    if ($key === 'transaction_type') {
        return formatLabel($value);
    }

    if ($key === 'amount') {
        return '₱' . number_format((float) $value, 2);
    }

    if ($key === 'created_at') {
        return date('M d, Y h:i A', strtotime($value));
    }

    return (string) $value;
}

$transactionStmt = $pdo->prepare("
    SELECT DISTINCT transaction_type
    FROM sales
    ORDER BY transaction_type ASC
");
$transactionStmt->execute();
$transactionTypes = $transactionStmt->fetchAll(PDO::FETCH_ASSOC);

$itemStmt = $pdo->prepare("
    SELECT DISTINCT item_name
    FROM sales
    ORDER BY item_name ASC
");
$itemStmt->execute();
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$where = ["DATE(s.created_at) = :date"];
$params = ['date' => $selectedDate];

if ($selectedTransaction !== 'all') {
    $where[] = "s.transaction_type = :transaction_type";
    $params['transaction_type'] = $selectedTransaction;
}

if ($selectedItem !== 'all') {
    $where[] = "s.item_name = :item_name";
    $params['item_name'] = $selectedItem;
}

$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT 
        CONCAT(c.first_name, ' ', c.last_name) AS client_name,
        s.transaction_type,
        s.item_name,
        s.quantity,
        s.amount,
        s.created_at
    FROM sales s
    LEFT JOIN clients c ON s.client_id = c.client_id
    WHERE $whereSql
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtTotal = $pdo->prepare("
    SELECT SUM(s.amount)
    FROM sales s
    WHERE $whereSql
");
$stmtTotal->execute($params);
$total = $stmtTotal->fetchColumn() ?? 0;

$todayTotal = $pdo->query("
    SELECT SUM(amount)
    FROM sales
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn() ?? 0;

$monthlyTotal = $pdo->query("
    SELECT SUM(amount)
    FROM sales
    WHERE YEAR(created_at) = YEAR(CURDATE())
    AND MONTH(created_at) = MONTH(CURDATE())
")->fetchColumn() ?? 0;

$yearlyTotal = $pdo->query("
    SELECT SUM(amount)
    FROM sales
    WHERE YEAR(created_at) = YEAR(CURDATE())
")->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Sales</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
<div class="wrapper">
    <h2>Sales</h2>

    <form method="GET">
        <label>Select Date:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">

        <label>Transaction:</label>
        <select name="transaction_type">
            <option value="all">All</option>
            <?php foreach ($transactionTypes as $type): ?>
                <option value="<?= htmlspecialchars($type['transaction_type']) ?>"
                    <?= $selectedTransaction === $type['transaction_type'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(formatLabel($type['transaction_type'])) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Item:</label>
        <select name="item_name">
            <option value="all">All</option>
            <?php foreach ($items as $item): ?>
                <option value="<?= htmlspecialchars($item['item_name']) ?>" <?= $selectedItem === $item['item_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($item['item_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Filter</button>
    </form>

    <br>

    <div>
        <strong>Today:</strong> ₱<?= number_format((float) $todayTotal, 2) ?><br>
        <strong>This Month:</strong> ₱<?= number_format((float) $monthlyTotal, 2) ?><br>
        <strong>This Year:</strong> ₱<?= number_format((float) $yearlyTotal, 2) ?><br>
        <strong>Selected Filter:</strong> ₱<?= number_format((float) $total, 2) ?>
    </div>

    <br>

    <?php if (!empty($sales)): ?>
        <table>
            <thead>
                <tr>
                    <?php foreach ($columns as $label): ?>
                        <th><?= htmlspecialchars($label) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <?php foreach ($columns as $key => $label): ?>
                            <td><?= htmlspecialchars(formatSalesValue($key, $sale)) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php include '../components/alert.php'; ?>
    <?php endif; ?>
    </div>
</body>

</html>