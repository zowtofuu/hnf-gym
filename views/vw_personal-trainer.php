<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Training</title>
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <div class="wrapper">
        <h2>Personal Trainer</h2>
        <br>

        <section>
            <form method="POST">
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['client_id'] ?>">
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="session_type" required>
                    <option value="1">1 Session (₱250)</option>
                    <option value="14">14 Sessions (₱2800)</option>
                </select>

                <button type="submit" name="buy">Buy Session</button>
            </form>
        </section>

        <?php
        $columns = [
            'client_name' => 'Client',
            'remaining_sessions' => 'Remaining Sessions'
        ];
        ?>

        <div class="table-wrapper">
            <?php if (!empty($sessions)): ?>
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
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <?php foreach ($columns as $key => $label): ?>
                                    <td>
                                        <?= htmlspecialchars((string) ($session[$key] ?? '')) ?>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <form method="POST" action="ctr_personal-training.php">
                                        <input type="hidden" name="training_id"
                                            value="<?= htmlspecialchars((string) $session['id']) ?>">

                                        <button type="submit" name="use">
                                            Use Session
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <?php include __DIR__ . '/../components/alert.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>