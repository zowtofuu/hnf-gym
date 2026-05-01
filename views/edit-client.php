<?php
require_once __DIR__ . '/../config/database.php';

// Validate client ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid client ID.');
}

$clientId = (int) $_GET['id'];
$error = '';
$success = '';

// Fetch current client data
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

if (!$client) {
    die('Client not found.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');

    if ($firstName === '' || $lastName === '') {
        $error = 'First name and last name are required.';
    } else {
        $stmt = $conn->prepare("
            UPDATE clients
            SET first_name = ?, last_name = ?
            WHERE client_id = ?
        ");
        $stmt->bind_param("ssi", $firstName, $lastName, $clientId);

        if ($stmt->execute()) {
            header("Location: clients.php");
            exit;
        } else {
            $error = 'Failed to update client.';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Client</title>
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <h2>Edit Client</h2>

    <?php if ($error !== ''): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div>
            <label for="first_name">First Name:</label><br>
            <input
                type="text"
                id="first_name"
                name="first_name"
                value="<?php echo htmlspecialchars($client['first_name']); ?>"
                required
            >
        </div>

        <br>

        <div>
            <label for="last_name">Last Name:</label><br>
            <input
                type="text"
                id="last_name"
                name="last_name"
                value="<?php echo htmlspecialchars($client['last_name']); ?>"
                required
            >
        </div>

        <br>

        <button type="submit">Update Client</button>
        <a href="clients.php">Cancel</a>
    </form>
</body>
</html>