<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_add-client.php';

$errors = [];

$old = [
    'first_name' => '',
    'last_name' => '',
    'contact' => '',
    'birthday' => '',
    'membership_type' => 'non_member',
    'pass_type' => 'daily',
];

try {
    $plans = getAddClientMembershipPlans($pdo);
    $annualMembershipFee = getAnnualMembershipFee($pdo);
} catch (Throwable $e) {
    $plans = [];
    $annualMembershipFee = 0.00;
    $errors[] = 'Unable to load membership plans: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'contact' => trim((string) ($_POST['contact'] ?? '')),
        'birthday' => trim((string) ($_POST['birthday'] ?? '')),
        'membership_type' => trim((string) ($_POST['membership_type'] ?? '')),
        'pass_type' => trim((string) ($_POST['pass_type'] ?? '')),
    ];

    try {
        addClient($pdo, $old);

        header('Location: ../controllers/ctr_clients.php?added=1');
        // header("Refresh:0; url=../controllers/ctr_checkin.php");
        // header('Location: ../controllers/ctr_checkin.php?added=1');
        exit;
    } catch (Throwable $e) {
        $errors[] = 'Something went wrong: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../views/vw_add-client.php';