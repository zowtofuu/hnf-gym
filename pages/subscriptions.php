<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ .'/../config/utility.php';

/*
|--------------------------------------------------------------------------
| AUTO UPDATE STATUS ACCORDINGLY
|--------------------------------------------------------------------------
| suspended stays suspended
| past end date = expired
| otherwise = active
*/
$updateStmt = $conn->prepare("
    UPDATE subscriptions
    SET status = CASE
        WHEN status = 'suspended' THEN 'suspended'
        WHEN subscription_end < CURDATE() THEN 'expired'
        ELSE 'active'
    END
");
$updateStmt->execute();
$updateStmt->close();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

/*
|--------------------------------------------------------------------------
| SUMMARY COUNTS
|--------------------------------------------------------------------------
*/
$stmtTotal = $conn->prepare("SELECT COUNT(*) AS total_count FROM subscriptions");
$stmtTotal->execute();
$totalSubscriptions = $stmtTotal->get_result()->fetch_assoc()['total_count'] ?? 0;
$stmtTotal->close();

$stmtActive = $conn->prepare("SELECT COUNT(*) AS active_count FROM subscriptions WHERE status = 'active'");
$stmtActive->execute();
$activeSubscriptions = $stmtActive->get_result()->fetch_assoc()['active_count'] ?? 0;
$stmtActive->close();

$stmtExpired = $conn->prepare("SELECT COUNT(*) AS expired_count FROM subscriptions WHERE status = 'expired'");
$stmtExpired->execute();
$expiredSubscriptions = $stmtExpired->get_result()->fetch_assoc()['expired_count'] ?? 0;
$stmtExpired->close();

$stmtSuspended = $conn->prepare("SELECT COUNT(*) AS suspended_count FROM subscriptions WHERE status = 'suspended'");
$stmtSuspended->execute();
$suspendedSubscriptions = $stmtSuspended->get_result()->fetch_assoc()['suspended_count'] ?? 0;
$stmtSuspended->close();

$stmtExpiringSoon = $conn->prepare("
    SELECT COUNT(*) AS expiring_soon_count
    FROM subscriptions
    WHERE status = 'active'
      AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$stmtExpiringSoon->execute();
$expiringSoonSubscriptions = $stmtExpiringSoon->get_result()->fetch_assoc()['expiring_soon_count'] ?? 0;
$stmtExpiringSoon->close();

/*
|--------------------------------------------------------------------------
| TABLE DATA
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        s.subscription_id,
        s.client_id,
        c.first_name,
        c.last_name,
        mp.plan_name AS plan,
        s.subscription_start,
        s.subscription_end,
        s.subscription_token,
        s.status,
        s.created_at
    FROM subscriptions s
    LEFT JOIN clients c ON s.client_id = c.client_id
    LEFT JOIN membership_plans mp ON s.plan_id = mp.id
    WHERE (
        c.first_name LIKE ?
        OR c.last_name LIKE ?
        OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?
        OR COALESCE(s.subscription_token, '') LIKE ?
    )
";

$searchLike = '%' . $search . '%';
$params = [$searchLike, $searchLike, $searchLike, $searchLike];
$types = 'ssss';

if ($status === 'active') {
    $sql .= " AND s.status = 'active' ";
} elseif ($status === 'expired') {
    $sql .= " AND s.status = 'expired' ";
} elseif ($status === 'suspended') {
    $sql .= " AND s.status = 'suspended' ";
} elseif ($status === 'expiring') {
    $sql .= " AND s.status = 'active'
              AND s.subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ";
}

$sql .= " ORDER BY s.subscription_id DESC ";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();
$subscriptions = [];

while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions</title>
</head>

<body>

    <?php include '../components/navbar.php'; ?>
    <div class="wrapper">

        <h1>Subscriptions</h1>

        <h2>Overview</h2>
        <p><strong>Total:</strong> <?php echo $totalSubscriptions; ?></p>
        <p><strong>Active:</strong> <?php echo $activeSubscriptions; ?></p>
        <p><strong>Expired:</strong> <?php echo $expiredSubscriptions; ?></p>
        <p><strong>Suspended:</strong> <?php echo $suspendedSubscriptions; ?></p>
        <p><strong>Expiring (7 days):</strong> <?php echo $expiringSoonSubscriptions; ?></p>

        <h2>Search and Filter</h2>
        <form method="GET">
            <input class="search-input" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search...">

            <select class="date-input" name="status">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                <option value="expiring" <?php echo $status === 'expiring' ? 'selected' : ''; ?>>Expiring</option>
            </select>

            <button class="btn btn-txt" type="submit">Apply</button>
            <a class="btn btn-txt" href="subscriptions.php">Reset</a>
        </form>

        <h2>Subscription List</h2>

        <?php if (!empty($subscriptions)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Plan</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $s): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))); ?>
                            </td>

                            <td><?php echo htmlspecialchars((string) $s['plan']); ?></td>
                            <td><?php echo htmlspecialchars(formatReadableDate((string) $s['subscription_start'])); ?></td>
                            <td><?php echo htmlspecialchars(formatReadableDate((string) $s['subscription_end'])); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst((string) $s['status'])); ?></td>

                            <td>
                                <div class="flex">
                                    <a class="btn-plain"
                                        href="subscription.edit.php?id=<?php echo urlencode((string) $s['subscription_id']); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                            width="24px" fill="#e3e3e3">
                                            <path
                                                d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                        </svg>
                                    </a>
                                    <a class="btn-plain"
                                        href="subscription.viewid.php?id=<?php echo urlencode((string) $s['subscription_id']); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                            width="24px" fill="#e3e3e3">
                                            <path
                                                d="M480-200q-135 0-245-67.5T65-446q-7-13-10-26.5T52-500q0-14 3-27.5T65-554q60-111 170-178.5T480-800q135 0 245 67.5T895-554q7 13 10 26.5t3 27.5q0 14-3 27.5T895-446q-60 111-170 178.5T480-200Zm-320.5 50.5Q144-146 127-160q-17-15-33-32t-29-36q-10-14-5.5-28.5T77-279q13-8 29-6.5t30 19.5q10 14 21.5 25t24.5 22q14 12 13.5 28T185-164q-10 11-25.5 14.5ZM480-280q115 0 209-59t144-161q-50-102-144-161t-209-59q-115 0-209 59T127-500q50 102 144 161t209 59ZM439-81q-2 15-14.5 26T390-47q-27-4-53.5-10T284-72q-20-7-26-23t-1-30q5-14 18.5-23t31.5-2q25 9 50.5 14.5T409-126q18 2 25 16t5 29Zm41-239q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm45 282q8-14 27-16 26-4 51-10t50-15q17-6 30.5 3t18.5 23q5 14-1.5 30T673-71q-26 9-52 14.5T568-47q-22 3-34.5-8T519-81q-2-15 6-29Zm237.5-80.5Q762-207 777-219t28-25.5q13-13.5 24-29.5 10-14 25.5-14t27.5 9q12 9 16 25t-10 35q-12 17-25.5 31T833-160q-17 14-33.5 11T773-163q-10-11-10.5-27.5ZM480-500Z" />
                                        </svg>
                                    </a>
                                    <?php if (isset($s['status']) && $s['status'] === 'expired'): ?>
                                        <a class="btn-plain"
                                            href="subscription.renew.php?id=<?php echo urlencode((string) $s['subscription_id']); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                                width="24px" fill="#e3e3e3">
                                                <path
                                                    d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-40q0-17 11.5-28.5T280-880q17 0 28.5 11.5T320-840v40h320v-40q0-17 11.5-28.5T680-880q17 0 28.5 11.5T720-840v40h40q33 0 56.5 23.5T840-720v200q0 17-11.5 28.5T800-480q-17 0-28.5-11.5T760-520v-40H200v400h240q17 0 28.5 11.5T480-120q0 17-11.5 28.5T440-80H200ZM760 0q-64 0-114.5-35.5T573-128q-5-11 2.5-21.5T595-160q14 0 25.5 8.5T639-130q18 32 50 51t71 19q58 0 99-41t41-99q0-58-41-99t-99-41q-29 0-54 10.5T662-300h28q13 0 21.5 8.5T720-270q0 13-8.5 21.5T690-240h-90q-17 0-28.5-11.5T560-280v-90q0-13 8.5-21.5T590-400q13 0 21.5 8.5T620-370v27q27-26 63-41.5t77-15.5q83 0 141.5 58.5T960-200q0 83-58.5 141.5T760 0ZM200-640h560v-80H200v80Zm0 0v-80 80Z" />
                                            </svg>
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