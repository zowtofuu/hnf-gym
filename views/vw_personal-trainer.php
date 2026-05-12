<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Training</title>
</head>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const clientSelect = document.getElementById('client_id');
        const buyButton = document.getElementById('buySessionBtn');

        function updateBuyButtonState() {
            const selectedOption = clientSelect.options[clientSelect.selectedIndex];

            if (!selectedOption || selectedOption.value === '') {
                buyButton.disabled = true;
                return;
            }

            const hasActiveSession = selectedOption.dataset.hasActiveSession === '1';

            buyButton.disabled = hasActiveSession;
        }

        clientSelect.addEventListener('change', updateBuyButtonState);

        updateBuyButtonState();
    });
</script>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>
    <div class="wrapper">
        <h3 class="legend">Personal Trainer</h3>
        <section class="flex flex-wrap pb-md">
            <form method="POST" id="buySessionForm">
                <select  class="capitalize rounded-sm px-md py-sm focus-visible" name="client_id" id="client_id" required>
                    <option value="">Select Client</option>

                    <?php foreach ($clients as $client): ?>
                        <option value="<?= htmlspecialchars((string) $client['client_id']) ?>"
                            data-has-active-session="<?= htmlspecialchars((string) $client['has_active_session']) ?>">
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select  class="capitalize rounded-sm px-md py-sm focus-visible" name="session_type" required>
                    <option value="1">1 Session (₱250)</option>
                    <option value="14">14 Sessions (₱2800)</option>
                </select>

                <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit" name="buy" id="buySessionBtn" onclick="return confirm('Are you sure you want to BUY the selected session?');">
                    Buy Session
                </button>
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
                                    <form method="POST" action="../controllers/ctr_personal-trainer.php">
                                        <input type="hidden" name="training_id"
                                            value="<?= htmlspecialchars((string) $session['id']) ?>">

                                        <?php if ((int) $session['has_attendance_today'] === 0): ?>
                                            <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="button" disabled>
                                                No Attendance Today
                                            </button>

                                        <?php elseif ((int) $session['used_today'] === 1): ?>
                                            <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="button" disabled>
                                                Already Used Today
                                            </button>

                                        <?php else: ?>
                                            <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary" type="submit" name="use">
                                                Use Session
                                            </button>
                                        <?php endif; ?>
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