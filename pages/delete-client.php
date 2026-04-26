<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die('Invalid client ID.');
}

$clientId = (int) $_POST['id'];

$stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $clientId);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: clients.php");
    exit;
} else {
    $stmt->close();
    die('Failed to delete client.');
}