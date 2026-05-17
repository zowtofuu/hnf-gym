<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_clients.php';

/**
 * HANDLE UPDATE (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

/**
 * LOAD CLIENT (GET)
 */
$clientId = (int) ($_GET['id'] ?? 0);
$client = null;

if ($clientId > 0) {
    $client = getClientById($pdo, $clientId);
}

require_once __DIR__ . '/../views/vw_update-client.php';