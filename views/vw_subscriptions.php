<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions</title>
</head>

<body>

    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h2>Subscriptions</h2>

        <form method="GET" action="ctr_subscriptions.php">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Search...">

            <select name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="expiring" <?= $status === 'expiring' ? 'selected' : '' ?>>Expiring</option>
            </select>

            <button type="submit">Apply</button>
            <a href="ctr_subscriptions.php">Reset</a>
        </form>

        <br>

        <div>
            <strong>Total:</strong> <?= htmlspecialchars((string) ($counts['total'] ?? 0)) ?><br>
            <strong>Active:</strong> <?= htmlspecialchars((string) ($counts['active'] ?? 0)) ?><br>
            <strong>Expired:</strong> <?= htmlspecialchars((string) ($counts['expired'] ?? 0)) ?><br>
            <strong>Suspended:</strong> <?= htmlspecialchars((string) ($counts['suspended'] ?? 0)) ?><br>
            <strong>Expiring (7 days):</strong> <?= htmlspecialchars((string) ($counts['expiring_soon'] ?? 0)) ?><br>
        </div>

        <br>

        <?php
        $columns = [
            'client_name' => 'Client',
            'membership_type' => 'Membership',
            'pass_type' => 'Pass',
            'price' => 'Price',
            'membership_start' => 'Membership Start',
            'membership_end' => 'Membership End',
            'subscription_start' => 'Pass Start',
            'subscription_end' => 'Pass End',
            'status' => 'Status'
        ];
        ?>
        <div class="table-wrapper">
            <?php if (!empty($subscriptions)): ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $label): ?>
                                <th><?= htmlspecialchars($label) ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($subscriptions as $row): ?>
                            <tr>
                                <?php foreach ($columns as $key => $label): ?>
                                    <td>
                                        <?php
                                        $value = $row[$key] ?? null;

                                        switch ($key) {
                                            case 'membership_type':
                                                echo htmlspecialchars(membershipTypeLabel($value));
                                                break;

                                            case 'pass_type':
                                                echo htmlspecialchars(passTypeLabel($value));
                                                break;

                                            case 'price':
                                                echo '₱' . number_format((float) $value, 2);
                                                break;

                                            case 'membership_start':
                                            case 'membership_end':
                                                echo $value ? htmlspecialchars(formatReadableDate($value)) : 'N/A';
                                                break;

                                            case 'subscription_start':
                                            case 'subscription_end':
                                                echo htmlspecialchars(formatReadableDate($value));
                                                break;

                                            case 'status':
                                                echo htmlspecialchars(ucfirst($value));
                                                break;

                                            default:
                                                echo htmlspecialchars((string) $value);
                                                break;
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>

                                <!-- Actions column (manual on purpose) -->
                                <td>
                                    <a href="ctr_subscription-edit.php?id=<?= urlencode($row['subscription_id']) ?>">Edit</a>

                                    <?php if (($row['status'] ?? '') === 'expired'): ?>
                                        <a href="ctr_subscription-renew.php?id=<?= urlencode($row['subscription_id']) ?>">Renew</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php include '../components/alert.php'; ?>
        <?php endif; ?>

    </div>

</body>

</html>