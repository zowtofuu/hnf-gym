<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Attendance List</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <div class="wrapper">
        <h2 class="legend">Attendance List</h2>

        <section style="display: flex; justify-content: space-between;">
            <form method="GET" action="../controllers/ctr_attendance.php">
                <input type="text" name="search" placeholder="Search client or contact"
                    value="<?= htmlspecialchars($filters['search']) ?>">

                <input type="date" name="date" value="<?= htmlspecialchars($filters['date']) ?>">

                <select name="membership_type">
                    <option value="all">All Membership Types</option>
                    <?php foreach ($membershipTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filters['membership_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars(formatLabel($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="pass_type">
                    <option value="all">All Pass Types</option>
                    <?php foreach ($passTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filters['pass_type'] === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars(formatLabel($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Filter</button>
                <a href="../controllers/ctr_attendance.php">Reset</a>
            </form>
            <section>
                <a href="../controllers/ctr_checkin.php">Check-in</a>
            </section>
        </section>
        <br>

        <?php if (!empty($attendanceList)): ?>

            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $label): ?>
                            <th><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($attendanceList as $attendance): ?>
                        <tr>
                            <?php foreach ($columns as $key => $label): ?>
                                <td>
                                    <?php
                                    $value = $attendance[$key] ?? '';

                                    if (in_array($key, ['membership_type', 'pass_type'], true)) {
                                        $value = formatLabel($value);
                                    }
                                    if ($key === 'attendance_date') {
                                        $value = formatReadableDate($value);
                                    }
                                    if ($key === 'check_in_time') {
                                        $value = formatReadableTime($value);
                                    }

                                    echo htmlspecialchars($value);
                                    ?>
                                </td>
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