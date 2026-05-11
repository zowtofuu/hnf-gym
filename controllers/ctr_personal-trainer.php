<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_personal-trainer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['buy'])) {
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $type = $_POST['session_type'] ?? '';

        if ($clientId > 0 && !clientHasActiveSession($pdo, $clientId)) {
            if ($type === '1') {
                buySession($pdo, $clientId, 1, 250);
            }

            if ($type === '14') {
                buySession($pdo, $clientId, 14, 2800);
            }
        }
    }

    if (isset($_POST['use'])) {
        $id = (int) $_POST['training_id'];
        useSession($pdo, $id);
    }

    header('Location: ctr_personal-trainer.php');
    exit;
}

$clients = getClients($pdo);
$sessions = getActiveSessions($pdo);

require_once __DIR__ . '/../views/vw_personal-trainer.php';