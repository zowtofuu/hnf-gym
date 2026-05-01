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

        <h1>Subscriptions</h1>

        <h2>Overview</h2>
        <p><strong>Total:</strong> <?= htmlspecialchars((string) ($counts['total'] ?? 0)) ?></p>
        <p><strong>Active:</strong> <?= htmlspecialchars((string) ($counts['active'] ?? 0)) ?></p>
        <p><strong>Expired:</strong> <?= htmlspecialchars((string) ($counts['expired'] ?? 0)) ?></p>
        <p><strong>Suspended:</strong> <?= htmlspecialchars((string) ($counts['suspended'] ?? 0)) ?></p>
        <p><strong>Expiring (7 days):</strong> <?= htmlspecialchars((string) ($counts['expiring_soon'] ?? 0)) ?></p>

        <h2>Search and Filter</h2>

        <form method="GET" action="ctr_subscriptions.php">
            <input class="search-input" type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Search...">

            <select class="date-input" name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="expiring" <?= $status === 'expiring' ? 'selected' : '' ?>>Expiring</option>
            </select>

            <button class="btn btn-txt" type="submit">Apply</button>
            <a class="btn btn-txt" href="ctr_subscriptions.php">Reset</a>
        </form>

        <h2>Subscription List</h2>

        <?php if (!empty($subscriptions)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Membership Type</th>
                        <th>Pass Type</th>
                        <th>Price</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars((string) ($subscription['client_name'] ?? '')) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($subscription['membership_type'] ?? '')))) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($subscription['pass_type'] ?? '')))) ?>
                            </td>

                            <td>
                                ₱<?= htmlspecialchars(number_format((float) ($subscription['price'] ?? 0), 2)) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(formatReadableDate((string) $subscription['subscription_start'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(formatReadableDate((string) $subscription['subscription_end'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(ucfirst((string) $subscription['status'])) ?>
                            </td>

                            <td>
                                <div class="flex">
                                    <a class="btn-plain"
                                        href="subscription.edit.php?id=<?= urlencode((string) $subscription['subscription_id']) ?>">
                                        Edit
                                    </a>

                                    <a class="btn-plain"
                                        href="subscription.viewid.php?id=<?= urlencode((string) $subscription['subscription_id']) ?>">
                                        View
                                    </a>

                                    <?php if (($subscription['status'] ?? '') === 'expired'): ?>
                                        <a class="btn-plain"
                                            href="subscription.renew.php?id=<?= urlencode((string) $subscription['subscription_id']) ?>">
                                            Renew
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No subscriptions found.</p>
        <?php endif; ?>

    </div>

</body>

</html>