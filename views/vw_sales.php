<?php
declare(strict_types=1);

function salesE(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function salesLabel(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

function salesMoney(float $value): string
{
    return '&#8369;' . number_format($value, 2);
}

function salesValue(string $key, array $row): string
{
    $value = $row[$key] ?? '';

    if ($key === 'client_name') {
        return salesE((string) ($value ?: 'N/A'));
    }

    if ($key === 'transaction_type') {
        return salesE(salesLabel((string) $value));
    }

    if ($key === 'amount') {
        return salesMoney((float) $value);
    }

    if ($key === 'created_at') {
        return salesE(date('M d, Y h:i A', strtotime((string) $value)));
    }

    return salesE((string) $value);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h2>Sales</h2>

        <form method="GET" action="ctr_sales.php">
            <label for="date">Select Date:</label>
            <input type="date" id="date" name="date" value="<?= salesE($selectedDate) ?>">

            <label for="transaction_type">Transaction:</label>
            <select id="transaction_type" name="transaction_type">
                <option value="all">All</option>
                <?php foreach ($transactionTypes as $type): ?>
                    <option value="<?= salesE((string) $type) ?>" <?= $selectedTransaction === $type ? 'selected' : '' ?>>
                        <?= salesE(salesLabel((string) $type)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="item_name">Item:</label>
            <select id="item_name" name="item_name">
                <option value="all">All</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= salesE((string) $item) ?>" <?= $selectedItem === $item ? 'selected' : '' ?>>
                        <?= salesE((string) $item) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Filter</button>
        </form>

        <br>

        <div>
            <strong>Today:</strong> <?= salesMoney($todayTotal) ?><br>
            <strong>This Month:</strong> <?= salesMoney($monthlyTotal) ?><br>
            <strong>This Year:</strong> <?= salesMoney($yearlyTotal) ?><br>
            <strong>Selected Filter:</strong> <?= salesMoney($total) ?>
        </div>

        <br>

        <?php if (!empty($sales)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $label): ?>
                            <th><?= salesE((string) $label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <?php foreach ($columns as $key => $label): ?>
                                <td><?= salesValue((string) $key, $sale) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php include __DIR__ . '/../components/alert.php'; ?>
        <?php endif; ?>
    </div>
</body>

</html>
