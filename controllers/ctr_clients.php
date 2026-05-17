<?php
require_once __DIR__ . '/../models/mdl_clients.php';
require_once __DIR__ . '/../config/utility.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_client'])) {
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if ($clientId > 0 && $firstName !== '' && $lastName !== '' && $birthday !== '' && $contact !== '') {
        updateClient($pdo, $clientId, $firstName, $lastName, $birthday, $contact);
    }

    header('Location: ctr_clients.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'])) {
    $clientId = (int) ($_POST['client_id'] ?? 0);

    if ($clientId > 0) {
        deleteClient($pdo, $clientId);
    }

    header('Location: ctr_clients.php');
    exit;
}

$searchTerm = trim($_GET['search'] ?? '');
$clients = ($searchTerm !== '') ? searchClients($pdo, $searchTerm) : getAllClients($pdo);

require_once __DIR__ . '/../views/vw_clients.php';