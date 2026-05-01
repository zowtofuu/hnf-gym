<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <div class="wrapper">
        <h2 class="legend">Clients</h2>

        <!-- CONTROLS -->
        <section>
            <form method="GET" action="../controllers/ctr_clients.php">
                <input type="search" name="search" placeholder="Search client..."
                    value="<?= htmlspecialchars($searchTerm ?? '') ?>">

                <input type="submit" value="Search">

                <a href="../controllers/ctr_clients.php">Clear</a>
            </form>
            <a href="../controllers/ctr_add-client.php">Add Client</a>
        </section>

        <!-- TABLE -->
        <section>
            <?php
            $columnsToHide = ['client_id', 'created_at'];

            $columnLabels = [
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'contact' => 'Contact Number'
            ];

            $visibleColumns = [];

            if (!empty($clients)) {
                $visibleColumns = array_keys($clients[0]);

                $visibleColumns = array_filter($visibleColumns, function ($column) use ($columnsToHide) {
                    return !in_array($column, $columnsToHide, true);
                });
            }
            ?>

            <table>
                <thead>
                    <tr>
                        <?php foreach ($visibleColumns as $column): ?>
                            <th>
                                <?= htmlspecialchars($columnLabels[$column] ?? ucwords(str_replace('_', ' ', $column))) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($clients)): ?>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <?php foreach ($visibleColumns as $column): ?>
                                    <td>
                                        <?= htmlspecialchars($client[$column] ?? '') ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php include __DIR__ . '/../components/alert.php'; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>

</html>