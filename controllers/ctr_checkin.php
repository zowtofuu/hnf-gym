<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_checkin.php';

$selectedDate = date('Y-m-d');
$clients = getAllClients($pdo);

function jsonResponse(array $data): void
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function isAjaxRequest(): bool
{
    return isset($_POST['ajax']) && $_POST['ajax'] === '1';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedDate = $_POST['attendance_date'] ?? date('Y-m-d');
    $useSession = isset($_POST['use_session']) && $_POST['use_session'] === '1';

    $clientId = 0;

    if (!empty($_POST['qr_token'])) {
        $clientId = getClientIdByToken($pdo, trim($_POST['qr_token']));
    } else {
        $clientId = (int)($_POST['client_id'] ?? 0);
    }

    if ($clientId <= 0) {
        $response = [
            'status' => 'error',
            'message' => 'Invalid client or QR code.',
            'data' => null
        ];

        if (isAjaxRequest()) {
            jsonResponse($response);
        }
    }

    if ($clientId > 0) {
        $response = processCheckin($pdo, $clientId, $selectedDate, $useSession);

        if (isAjaxRequest()) {
            jsonResponse($response);
        }
    }
}

require_once __DIR__ . '/../views/vw_checkin.php';