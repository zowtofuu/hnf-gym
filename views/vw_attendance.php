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
                <input class="capitalize rounded-sm px8 py16 fv" type="text" name="search"
                    placeholder="Search client or contact" value="<?= htmlspecialchars($filters['search']) ?>">

                <input class="capitalize rounded-sm px8 py16 fv" type="date" name="date"
                    value="<?= htmlspecialchars($filters['date']) ?>">

                <select class="capitalize rounded-sm px8 py16 fv" name="membership_type">
                    <option value="all">All Membership Types</option>
                    <?php foreach ($membershipTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filters['membership_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars(formatLabel($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select class="capitalize rounded-sm px8 py16 fv" name="pass_type">
                    <option value="all">All Pass Types</option>
                    <?php foreach ($passTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filters['pass_type'] === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars(formatLabel($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="capitalize rounded-sm px8 py16 cursor-pointer btn-primary" type="submit">Filter</button>
                <a class="capitalize rounded-sm px8 py16 btn-anchor btn-secondary"
                    href="../controllers/ctr_attendance.php">Reset</a>
            </form>
            <section>
                <a class="icon-button" href="../controllers/ctr_checkin.php" title="Check-in" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" aria-hidden="true">
                        <path
                            d="M120-680q-17 0-28.5-11.5T80-720v-120q0-17 11.5-28.5T120-880h120q17 0 28.5 11.5T280-840q0 17-11.5 28.5T240-800h-80v80q0 17-11.5 28.5T120-680Zm0 600q-17 0-28.5-11.5T80-120v-120q0-17 11.5-28.5T120-280q17 0 28.5 11.5T160-240v80h80q17 0 28.5 11.5T280-120q0 17-11.5 28.5T240-80H120Zm600 0q-17 0-28.5-11.5T680-120q0-17 11.5-28.5T720-160h80v-80q0-17 11.5-28.5T840-280q17 0 28.5 11.5T880-240v120q0 17-11.5 28.5T840-80H720Zm91.5-611.5Q800-703 800-720v-80h-80q-17 0-28.5-11.5T680-840q0-17 11.5-28.5T720-880h120q17 0 28.5 11.5T880-840v120q0 17-11.5 28.5T840-680q-17 0-28.5-11.5ZM700-200v-60h60v60h-60Zm0-120v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60-60v-60h60v60h-60Zm120-120v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60-60v-60h60v60h-60Zm40-140q-17 0-28.5-11.5T520-560v-160q0-17 11.5-28.5T560-760h160q17 0 28.5 11.5T760-720v160q0 17-11.5 28.5T720-520H560ZM240-200q-17 0-28.5-11.5T200-240v-160q0-17 11.5-28.5T240-440h160q17 0 28.5 11.5T440-400v160q0 17-11.5 28.5T400-200H240Zm0-320q-17 0-28.5-11.5T200-560v-160q0-17 11.5-28.5T240-760h160q17 0 28.5 11.5T440-720v160q0 17-11.5 28.5T400-520H240Zm20 260h120v-120H260v120Zm0-320h120v-120H260v120Zm320 0h120v-120H580v120Z" />
                    </svg>
                </a>
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