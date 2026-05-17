<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_subscription-renew.php';

$subscriptionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($subscriptionId <= 0) {
    exit('Invalid subscription id.');
}

$successMessage = '';
$errorMessage = '';

$subscription = hnfRenewalGetSubscription($pdo, $subscriptionId);

if (!$subscription) {
    exit('Subscription not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'membership_type' => $_POST['membership_type'] ?? '',
        'pass_type' => $_POST['pass_type'] ?? '',
        'membership_start' => $_POST['membership_start'] ?? '',
        'membership_end' => $_POST['membership_end'] ?? '',
        'subscription_start' => $_POST['subscription_start'] ?? '',
        'subscription_end' => $_POST['subscription_end'] ?? ''
    ];

    try {
        hnfRenewalSave($pdo, $subscription, $data);

        $successMessage = 'Renewal saved.';

        $subscription = hnfRenewalGetSubscription($pdo, $subscriptionId);
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}

$plans = hnfRenewalGetAllPlans($pdo);
$annualMembershipFee = hnfRenewalGetAnnualMembershipFee($pdo);

$membershipTypes = [
    'member' => 'Member',
    'non_member' => 'Non-member',
    'student_senior' => 'Student / Senior'
];

$passTypes = [
    'daily' => 'Daily',
    'monthly' => 'Monthly'
];

$plansForJs = [];

foreach ($plans as $plan) {
    $plansForJs[$plan['membership_type']][$plan['pass_type']] = (float) $plan['price'];
}

$clientName = trim(($subscription['first_name'] ?? '') . ' ' . ($subscription['last_name'] ?? ''));

$membershipStatus = hnfRenewalStatus($subscription['membership_end'] ?? null);
$passStatus = hnfRenewalStatus($subscription['subscription_end'] ?? null);

$isMembershipLocked = $membershipStatus === 'Active';
$isPassLocked = $passStatus === 'Active';

$currentMembershipStart = hnfRenewalInputDate($subscription['membership_start'] ?? null);
$currentMembershipEnd = hnfRenewalInputDate($subscription['membership_end'] ?? null);

$currentSubscriptionStart = hnfRenewalInputDate($subscription['subscription_start'] ?? null);
$currentSubscriptionEnd = hnfRenewalInputDate($subscription['subscription_end'] ?? null);

require_once __DIR__ . '/../views/vw_subscription-renew.php';