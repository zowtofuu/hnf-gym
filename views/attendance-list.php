<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utility.php';

date_default_timezone_set('Asia/Manila');

/**
 * Sanitize search input
 */
function sanitize($value)
{
    return trim((string) $value);
}

/**
 * Validate date input
 */
function validateDate($value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return date('Y-m-d');
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);

    if ($date && $date->format('Y-m-d') === $value) {
        return $value;
    }

    return date('Y-m-d');
}

/**
 * Get attendance list using prepared statement
 */
function getAttendance($conn, $selectedDate, $search)
{
    $sql = "
        SELECT
            a.attendance_id,
            a.client_id,
            CONCAT(c.first_name, ' ', c.last_name) AS client_name,
            a.attendance_date,
            a.created_at
        FROM attendance a
        INNER JOIN clients c ON a.client_id = c.client_id
        WHERE a.attendance_date = ?
    ";

    $types = "s";
    $params = [$selectedDate];

    if ($search !== '') {
        $sql .= " AND (
            c.first_name LIKE ?
            OR c.last_name LIKE ?
            OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?
        )";

        $like = "%$search%";
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY a.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

/**
 * Column settings (edit here only)
 */
$hiddenColumns = [
    'attendance_id',
    'client_id',
];

$columnLabels = [
    'attendance_id' => 'Attendance ID',
    'client_id' => 'Client ID',
    'client_name' => 'Client Name',
    'attendance_date' => 'Date',
    'created_at' => 'Time Logged',
];

/**
 * Get filters
 */
$search = sanitize($_GET['search'] ?? '');
$selectedDate = validateDate($_GET['date'] ?? '');

/**
 * Fetch data
 */
$result = getAttendance($conn, $selectedDate, $search);

/**
 * Get columns dynamically
 */
$fields = mysqli_fetch_fields($result);
$allColumns = [];

foreach ($fields as $field) {
    $allColumns[] = $field->name;
}

/**
 * Filter visible columns
 */
$visibleColumns = array_values(array_filter($allColumns, function ($col) use ($hiddenColumns) {
    return !in_array($col, $hiddenColumns);
}));

/**
 * Fetch rows
 */
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance List</title>
    <link rel="stylesheet" href="../assets/css/index.css">

</head>

<body>
    <?php include '../components/navbar.php'; ?>
    <div class="wrapper">
        <h2>Attendance List</h2>
        <div class="flex space-between">
            <!-- FILTERS -->
            <form class="flex" method="GET">
                <input class="search-input" type="search" name="search" placeholder="Search client..."
                    value="<?= htmlspecialchars($search) ?>">

                <input class="date-input" type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">

                <button class="btn btn-icon" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#e3e3e3">
                        <path
                            d="M440-240q-17 0-28.5-11.5T400-280q0-17 11.5-28.5T440-320h80q17 0 28.5 11.5T560-280q0 17-11.5 28.5T520-240h-80ZM280-440q-17 0-28.5-11.5T240-480q0-17 11.5-28.5T280-520h400q17 0 28.5 11.5T720-480q0 17-11.5 28.5T680-440H280ZM160-640q-17 0-28.5-11.5T120-680q0-17 11.5-28.5T160-720h640q17 0 28.5 11.5T840-680q0 17-11.5 28.5T800-640H160Z" />
                    </svg>
                </button>
            </form>
            <div class="flex">
                <a class="btn btn-icon" href="checkin.manual.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M400-680h280q17 0 28.5 11.5T720-640q0 17-11.5 28.5T680-600H400q-17 0-28.5-11.5T360-640q0-17 11.5-28.5T400-680Zm0 120h280q17 0 28.5 11.5T720-520q0 17-11.5 28.5T680-480H400q-17 0-28.5-11.5T360-520q0-17 11.5-28.5T400-560Zm80 400H200h280ZM240-80q-50 0-85-35t-35-85v-80q0-17 11.5-28.5T160-320h80v-480q0-33 23.5-56.5T320-880h440q33 0 56.5 23.5T840-800v240q0 17-11.5 28.5T800-520q-17 0-28.5-11.5T760-560v-240H320v480h120q17 0 28.5 11.5T480-280q0 17-11.5 28.5T440-240H200v40q0 17 11.5 28.5T240-160h200q17 0 28.5 11.5T480-120q0 17-11.5 28.5T440-80H240Zm320-40v-66q0-8 3-15.5t9-13.5l209-208q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T903-300L695-92q-6 6-13.5 9T666-80h-66q-17 0-28.5-11.5T560-120Zm300-223-37-37 37 37ZM620-140h38l121-122-18-19-19-18-122 121v38Zm141-141-19-18 37 37-18-19Z"/></svg>
                </a>
                <a class="btn btn-icon" href="checkin.scanqr.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M120-680q-17 0-28.5-11.5T80-720v-120q0-17 11.5-28.5T120-880h120q17 0 28.5 11.5T280-840q0 17-11.5 28.5T240-800h-80v80q0 17-11.5 28.5T120-680Zm0 600q-17 0-28.5-11.5T80-120v-120q0-17 11.5-28.5T120-280q17 0 28.5 11.5T160-240v80h80q17 0 28.5 11.5T280-120q0 17-11.5 28.5T240-80H120Zm600 0q-17 0-28.5-11.5T680-120q0-17 11.5-28.5T720-160h80v-80q0-17 11.5-28.5T840-280q17 0 28.5 11.5T880-240v120q0 17-11.5 28.5T840-80H720Zm91.5-611.5Q800-703 800-720v-80h-80q-17 0-28.5-11.5T680-840q0-17 11.5-28.5T720-880h120q17 0 28.5 11.5T880-840v120q0 17-11.5 28.5T840-680q-17 0-28.5-11.5ZM700-200v-60h60v60h-60Zm0-120v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60-60v-60h60v60h-60Zm120-120v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60-60v-60h60v60h-60Zm40-140q-17 0-28.5-11.5T520-560v-160q0-17 11.5-28.5T560-760h160q17 0 28.5 11.5T760-720v160q0 17-11.5 28.5T720-520H560ZM240-200q-17 0-28.5-11.5T200-240v-160q0-17 11.5-28.5T240-440h160q17 0 28.5 11.5T440-400v160q0 17-11.5 28.5T400-200H240Zm0-320q-17 0-28.5-11.5T200-560v-160q0-17 11.5-28.5T240-760h160q17 0 28.5 11.5T440-720v160q0 17-11.5 28.5T400-520H240Zm20 260h120v-120H260v120Zm0-320h120v-120H260v120Zm320 0h120v-120H580v120Z"/></svg>
                </a>
            </div>
        </div>
        <?php if (empty($rows)): ?>
            <div class="message">
                <div>No records found.</div>
            </div>
        <?php else: ?>

            <!-- TABLE -->
            <table>
                <thead>
                    <tr>
                        <?php foreach ($visibleColumns as $col): ?>
                            <th>
                                <?= htmlspecialchars($columnLabels[$col] ?? ucwords(str_replace('_', ' ', $col))) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($visibleColumns as $col): ?>
                                <td>
                                    <?= htmlspecialchars($row[$col] ?? '') ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>