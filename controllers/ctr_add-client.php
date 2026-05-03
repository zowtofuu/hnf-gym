<?php
require_once __DIR__ . '/../models/mdl_add-client.php';

$errors = [];
$success = '';

$membershipTypes = getMembershipTypes($pdo);
$passTypes = getPassTypes($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize input
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $membershipType = trim($_POST['membership_type'] ?? '');
    $passType = trim($_POST['pass_type'] ?? '');

    // validation
    if ($firstName === '') {
        $errors[] = 'First name is required.';
    }

    if ($lastName === '') {
        $errors[] = 'Last name is required.';
    }

    if ($contact === '') {
        $errors[] = 'Contact number is required.';
    }

    if ($membershipType === '') {
        $errors[] = 'Membership type is required.';
    }

    if ($passType === '') {
        $errors[] = 'Pass type is required.';
    }

    if (empty($errors)) {
        // fetch the plan to get its ID and validate existence
        $plan = getMembershipPlan($pdo, $membershipType, $passType);

        if (!$plan) {
            $errors[] = 'Selected membership plan does not exist.';
        } else {
            $today = date('Y-m-d');

            if ($passType === 'daily') {
                $subscriptionStart = $today;
                $subscriptionEnd = $today;
            } elseif ($passType === 'monthly') {
                $subscriptionStart = $today;
                $subscriptionEnd = date('Y-m-d', strtotime('+1 month', strtotime($today)));
            } else {
                $errors[] = 'Invalid pass type.';
            }

            // continue only if no new errors
            if (empty($errors)) {
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
                } else {
                    $errors[] = 'Failed to add client.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../views/vw_add-client.php';