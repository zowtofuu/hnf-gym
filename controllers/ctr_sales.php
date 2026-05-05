<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function isValidSalesDate(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
}

function fetchSalesFilterValues(PDO $pdo, string $column): array
{
    $allowedColumns = ['transaction_type', 'item_name'];

    if (!in_array($column, $allowedColumns, true)) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT {$column}
        FROM sales
        ORDER BY {$column} ASC
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$selectedDate = trim((string) ($_GET['date'] ?? date('Y-m-d')));

if (!isValidSalesDate($selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$selectedTransaction = trim((string) ($_GET['transaction_type'] ?? 'all'));
$selectedItem = trim((string) ($_GET['item_name'] ?? 'all'));
$selectedDateEnd = (new DateTimeImmutable($selectedDate))->modify('+1 day')->format('Y-m-d');

$transactionTypes = fetchSalesFilterValues($pdo, 'transaction_type');
$items = fetchSalesFilterValues($pdo, 'item_name');

if ($selectedTransaction !== 'all' && !in_array($selectedTransaction, $transactionTypes, true)) {
    $selectedTransaction = 'all';
}

if ($selectedItem !== 'all' && !in_array($selectedItem, $items, true)) {
    $selectedItem = 'all';
}

$columns = [
    'client_name' => 'Client',
    'transaction_type' => 'Transaction',
    'item_name' => 'Item',
    'quantity' => 'Qty',
    'amount' => 'Amount',
    'created_at' => 'Date',
];

$where = [
    's.created_at >= :date_start',
    's.created_at < :date_end',
];
$params = [
    ':date_start' => $selectedDate,
    ':date_end' => $selectedDateEnd,
];

if ($selectedTransaction !== 'all') {
    $where[] = 's.transaction_type = :transaction_type';
    $params[':transaction_type'] = $selectedTransaction;
}

if ($selectedItem !== 'all') {
    $where[] = 's.item_name = :item_name';
    $params[':item_name'] = $selectedItem;
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
    WHERE {$whereSql}
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtTotal = $pdo->prepare("
    SELECT COALESCE(SUM(s.amount), 0)
    FROM sales s
    WHERE {$whereSql}
");
$stmtTotal->execute($params);
$total = (float) $stmtTotal->fetchColumn();

$todayTotal = (float) $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM sales
    WHERE created_at >= CURDATE()
      AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
")->fetchColumn();

$monthlyTotal = (float) $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM sales
    WHERE YEAR(created_at) = YEAR(CURDATE())
      AND MONTH(created_at) = MONTH(CURDATE())
")->fetchColumn();

$yearlyTotal = (float) $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM sales
    WHERE YEAR(created_at) = YEAR(CURDATE())
")->fetchColumn();

require_once __DIR__ . '/../views/vw_sales.php';
