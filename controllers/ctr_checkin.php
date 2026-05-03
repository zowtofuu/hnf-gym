<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_checkin.php';

$message = '';
$selectedDate = date('Y-m-d');

$clients = getAllClients($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selectedDate = $_POST['attendance_date'] ?? date('Y-m-d');
    $clientId = 0;

    // QR first
    if (!empty($_POST['qr_token'])) {
        $clientId = getClientIdByToken($pdo, $_POST['qr_token']);

        if (!$clientId) {
            $message = 'Invalid QR code.';
        }
    } else {
        $clientId = (int) ($_POST['client_id'] ?? 0);
    }

    if ($clientId > 0 && $message === '') {

        $subscription = getActiveSubscription($pdo, $clientId, $selectedDate);

        if (!$subscription) {
            $message = 'No valid subscription.';

        } elseif (attendanceExists($pdo, $clientId, $selectedDate)) {
            $message = 'Already checked in.';

        } elseif (insertAttendance($pdo, $clientId, $selectedDate)) {
            $today = new DateTime($selectedDate);
            $endDate = new DateTime($subscription['subscription_end']);

            $remainingDays = $today->diff($endDate)->days;

            $message = 'Check-in successful. Remaining subscription days: ' . $remainingDays . ' day' . ($remainingDays === 1 ? '' : 's') . '.';

        } else {
            $message = 'Insert failed.';
        }
    }
}

require_once __DIR__ . '/../views/vw_checkin.php';