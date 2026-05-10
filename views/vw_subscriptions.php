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
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">

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
                                    <a class="icon-button-plain"
                                        href="ctr_subscription-edit.php?id=<?= urlencode($row['subscription_id']) ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                            <path
                                                d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                        </svg>
                                    </a>
                                    <a class="icon-button-plain"   href="ctr_view-subcription-info.php?id=<?= urlencode($row['subscription_id']) ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                            <path
                                                d="M480-200q-135 0-245-67.5T65-446q-7-13-10-26.5T52-500q0-14 3-27.5T65-554q60-111 170-178.5T480-800q135 0 245 67.5T895-554q7 13 10 26.5t3 27.5q0 14-3 27.5T895-446q-60 111-170 178.5T480-200Zm-320.5 50.5Q144-146 127-160q-17-15-33-32t-29-36q-10-14-5.5-28.5T77-279q13-8 29-6.5t30 19.5q10 14 21.5 25t24.5 22q14 12 13.5 28T185-164q-10 11-25.5 14.5ZM480-280q115 0 209-59t144-161q-50-102-144-161t-209-59q-115 0-209 59T127-500q50 102 144 161t209 59ZM439-81q-2 15-14.5 26T390-47q-27-4-53.5-10T284-72q-20-7-26-23t-1-30q5-14 18.5-23t31.5-2q25 9 50.5 14.5T409-126q18 2 25 16t5 29Zm41-239q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm45 282q8-14 27-16 26-4 51-10t50-15q17-6 30.5 3t18.5 23q5 14-1.5 30T673-71q-26 9-52 14.5T568-47q-22 3-34.5-8T519-81q-2-15 6-29Zm237.5-80.5Q762-207 777-219t28-25.5q13-13.5 24-29.5 10-14 25.5-14t27.5 9q12 9 16 25t-10 35q-12 17-25.5 31T833-160q-17 14-33.5 11T773-163q-10-11-10.5-27.5ZM480-500Z" />
                                        </svg>
                                    </a>

                                    <?php if (($row['status'] ?? '') === 'expired'): ?>
                                        <a class="icon-button-plain" href="ctr_subscription-renew.php?id=<?= urlencode($row['subscription_id']) ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                                <path
                                                    d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-40q0-17 11.5-28.5T280-880q17 0 28.5 11.5T320-840v40h320v-40q0-17 11.5-28.5T680-880q17 0 28.5 11.5T720-840v40h40q33 0 56.5 23.5T840-720v200q0 17-11.5 28.5T800-480q-17 0-28.5-11.5T760-520v-40H200v400h240q17 0 28.5 11.5T480-120q0 17-11.5 28.5T440-80H200ZM760 0q-64 0-114.5-35.5T573-128q-5-11 2.5-21.5T595-160q14 0 25.5 8.5T639-130q18 32 50 51t71 19q58 0 99-41t41-99q0-58-41-99t-99-41q-29 0-54 10.5T662-300h28q13 0 21.5 8.5T720-270q0 13-8.5 21.5T690-240h-90q-17 0-28.5-11.5T560-280v-90q0-13 8.5-21.5T590-400q13 0 21.5 8.5T620-370v27q27-26 63-41.5t77-15.5q83 0 141.5 58.5T960-200q0 83-58.5 141.5T760 0ZM200-640h560v-80H200v80Zm0 0v-80 80Z" />
                                            </svg>
                                        </a>
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