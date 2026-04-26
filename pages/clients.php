<?php
require_once __DIR__ . '/../config/database.php';

// Columns to hide
$hiddenColumns = ['client_id', 'created_at'];

// Column label mapping (DB → UI label)
$columnLabels = [
    'client_id' => 'ID',
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'created_at' => 'Created At'
];

// Get search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// FETCH DATA (Prepared Statement)
if ($search !== '') {
    $searchTerm = '%' . $search . '%';

    $stmt = $conn->prepare("
        SELECT * 
        FROM clients
        WHERE first_name LIKE ? OR last_name LIKE ?
    ");
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT * FROM clients");
}

// Execute query
$stmt->execute();

// Get result
$result = $stmt->get_result();

// Fetch all rows
$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

// Close statement
$stmt->close();

// DETERMINE VISIBLE COLUMNS
$columns = [];
if (!empty($clients)) {
    $columns = array_keys($clients[0]);
}

// Filter hidden columns
$visibleColumns = array_filter($columns, function ($col) use ($hiddenColumns) {
    return !in_array($col, $hiddenColumns, true);
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Clients List</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>

<body>
    <?php include '../components/navbar.php'; ?>
    <div class="wrapper">
        <h2 class="legend">Clients List</h2>

        <div class="flex space-between">
            <form class="flex" method="GET" action="">
                <input class="search-input" type="text" name="search"  placeholder="Search by first or last name"
                    value="<?php echo htmlspecialchars($search); ?>">

                <button class="btn btn-icon" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                        <path
                            d="M380-320q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l224 224q11 11 11 28t-11 28q-11 11-28 11t-28-11L532-372q-30 24-69 38t-83 14Zm0-80q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                    </svg>
                </button>

                <?php if ($search !== ''): ?>
                    <a class="btn btn-text" href="clients.php">Clear</a>
                <?php endif; ?>
            </form>

            <a class="btn btn-icon" href="add-client.php" title="Add Client">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#e3e3e3">
                    <path
                        d="M451.5-131.5Q440-143 440-160v-280H160q-17 0-28.5-11.5T120-480q0-17 11.5-28.5T160-520h280v-280q0-17 11.5-28.5T480-840q17 0 28.5 11.5T520-800v280h280q17 0 28.5 11.5T840-480q0 17-11.5 28.5T800-440H520v280q0 17-11.5 28.5T480-120q-17 0-28.5-11.5Z" />
                </svg>
            </a>
        </div>

        <?php if (empty($clients)): ?>
            <div class="message">
                <div>No clients found.</div>
            </div>
        <?php else: ?>
            <div>
                <table>

                    <thead>
                        <tr>
                            <?php foreach ($visibleColumns as $col): ?>
                                <th>
                                    <?php
                                    echo isset($columnLabels[$col])
                                        ? $columnLabels[$col]
                                        : $col;
                                    ?>
                                </th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <?php foreach ($visibleColumns as $col): ?>
                                    <td>
                                        <?php echo htmlspecialchars($client[$col]); ?>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <div class="flex">
                                        <a class="btn-plain btn-icon"
                                            href="edit-client.php?id=<?php echo urlencode((string) $client['client_id']); ?>"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                                width="24px" fill="#e3e3e3">
                                                <path
                                                    d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                            </svg>
                                        </a>
                                        <form action="delete-client.php" method="POST">
                                            <input type="hidden" name="id"
                                                value="<?php echo htmlspecialchars($client['client_id']); ?>">
                                            <button class="btn-plain" type="submit"
                                                onclick="return confirm('Are you sure you want to delete this client?');">
                                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e3e3e3"><path d="M280-120q-33 0-56.5-23.5T200-200v-520q-17 0-28.5-11.5T160-760q0-17 11.5-28.5T200-800h160q0-17 11.5-28.5T400-840h160q17 0 28.5 11.5T600-800h160q17 0 28.5 11.5T800-760q0 17-11.5 28.5T760-720v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM428.5-291.5Q440-303 440-320v-280q0-17-11.5-28.5T400-640q-17 0-28.5 11.5T360-600v280q0 17 11.5 28.5T400-280q17 0 28.5-11.5Zm160 0Q600-303 600-320v-280q0-17-11.5-28.5T560-640q-17 0-28.5 11.5T520-600v280q0 17 11.5 28.5T560-280q17 0 28.5-11.5ZM280-720v520-520Z"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>