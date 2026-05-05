<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_subscription-edit.php';

$errors = [];
$successMessage = '';
$allowedStatuses = ['active', 'expired', 'suspended'];

$subscriptionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriptionId = filter_input(INPUT_POST, 'subscription_id', FILTER_VALIDATE_INT);
}

if (!$subscriptionId) {
    die('Invalid or missing subscription ID.');
}

$subscription = getEditableSubscription($pdo, $subscriptionId);

if (!$subscription) {
    die('Subscription not found.');
}

$membershipTypes = getMembershipTypes($pdo);
$passTypes = getPassTypes($pdo);

if (isset($_GET['success'])) {
    $successMessage = 'Subscription updated successfully.';
}

$membershipType = trim((string) ($_POST['membership_type'] ?? $subscription['membership_type']));
$passType = trim((string) ($_POST['pass_type'] ?? $subscription['pass_type']));
$membershipStart = trim((string) ($_POST['membership_start'] ?? ($subscription['membership_start'] ?? '')));
$subscriptionStart = trim((string) ($_POST['subscription_start'] ?? $subscription['subscription_start']));
$formStatus = trim((string) ($_POST['status'] ?? $subscription['status']));

if (!in_array($formStatus, $allowedStatuses, true)) {
    $formStatus = (string) $subscription['status'];
}

$subscriptionEnd = (string) $subscription['subscription_end'];

if (isValidMembershipDate($subscriptionStart)) {
    $subscriptionEnd = computeMembershipEndDate($subscriptionStart, $passType);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validMembershipTypes = array_column($membershipTypes, 'membership_type');
    $validPassTypes = array_column($passTypes, 'pass_type');

    if (!in_array($membershipType, $validMembershipTypes, true)) {
        $errors[] = 'Invalid membership type.';
    }

    if (!in_array($passType, $validPassTypes, true)) {
        $errors[] = 'Invalid pass type.';
    }

    if ($membershipType === 'member' && !isValidMembershipDate($membershipStart)) {
        $errors[] = 'Select a valid membership start date.';
    }

    if (!isValidMembershipDate($subscriptionStart)) {
        $errors[] = 'Select a valid subscription start date.';
    }

    if (!in_array($formStatus, $allowedStatuses, true)) {
        $errors[] = 'Select a valid status.';
    }

    $selectedPlan = getSelectedPlan($pdo, $membershipType, $passType);

    if (!$selectedPlan) {
        $errors[] = 'Selected membership and pass combination does not exist.';
    }

    if (empty($errors) && $selectedPlan) {
        $updated = updateEditableSubscription(
            $pdo,
            $subscriptionId,
            (int) $subscription['client_id'],
            $selectedPlan,
            $membershipType === 'member' ? $membershipStart : null,
            $subscriptionStart,
            $formStatus
        );

        if ($updated) {
            header('Location: ctr_subscription-edit.php?id=' . $subscriptionId . '&success=1');
            exit;
        }

        $errors[] = 'Subscription update failed.';
    }
}

$clientName = trim((string) $subscription['first_name'] . ' ' . (string) $subscription['last_name']);

require_once __DIR__ . '/../views/vw_subscription-edit.php';