<?php
require_once __DIR__ . '/../models/mdl_add-client.php';

$errors = [];
$success = '';

$membershipTypes = getMembershipTypes($pdo);
$passTypes = getPassTypes($pdo);
$planOptions = getMembershipPlanOptions($pdo);
$selectedMembershipType = trim($_POST['membership_type'] ?? '');
$selectedPassType = trim($_POST['pass_type'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $membershipType = $selectedMembershipType;
    $passType = $selectedPassType;

    if ($firstName === '') {
        $errors[] = 'First name is required.';
    }

    if ($lastName === '') {
        $errors[] = 'Last name is required.';
    }

    if ($contact === '') {
        $errors[] = 'Contact number is required.';
    }

    if (!isValidMembershipType($membershipType)) {
        $errors[] = 'Select a valid membership type.';
    }

    if (!isValidPassType($passType)) {
        $errors[] = 'Select a valid pass type.';
    }

    if (empty($errors)) {
        $plan = getMembershipPlan($pdo, $membershipType, $passType);

        if (!$plan) {
            $errors[] = 'Selected membership plan does not exist.';
        } else {
            $today = date('Y-m-d');
            $subscriptionStart = $today;
            $subscriptionEnd = computeMembershipEndDate($subscriptionStart, $passType);
            $subscriptionToken = bin2hex(random_bytes(16));

            $isAdded = addClientWithSubscription(
                $pdo,
                $firstName,
                $lastName,
                $contact,
                (int) $plan['id'],
                $subscriptionStart,
                $subscriptionEnd,
                $subscriptionToken
            );

            if ($isAdded) {
                $success = 'Client added successfully.';
                $selectedMembershipType = '';
                $selectedPassType = '';
                $_POST = [];
            } else {
                $errors[] = 'Failed to add client.';
            }
        }
    }
}

require_once __DIR__ . '/../views/vw_add-client.php';
