<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/mdl_subscription-renew.php';

$message = '';
$error = '';

$subscriptionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriptionId = filter_input(INPUT_POST, 'subscription_id', FILTER_VALIDATE_INT);
}

if (!$subscriptionId) {
    die('Invalid or missing subscription ID.');
}

$plans = getMembershipPlans($pdo);
$subscription = getSubscriptionById($pdo, $subscriptionId);

if (!$subscription) {
    die('Subscription not found.');
}

$fullName = trim($subscription['first_name'] . ' ' . $subscription['last_name']);

$currentPlanName = formatPlanName($subscription);
$currentStartDate = (string) $subscription['subscription_start'];
$currentEndDate = (string) $subscription['subscription_end'];
$currentStatus = (string) $subscription['status'];
$currentToken = (string) $subscription['subscription_token'];

$newPlanId = (int) $subscription['plan_id'];
$newStartDate = date('Y-m-d');
$newEndDate = computeEndDate($newStartDate, (string) $subscription['pass_type']);

if (isset($_GET['success'])) {
    $message = 'Subscription renewed successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPlanId = filter_input(INPUT_POST, 'new_plan_id', FILTER_VALIDATE_INT);
    $newStartDate = trim((string) ($_POST['new_start_date'] ?? ''));

    if (!$newPlanId) {
        $error = 'Please select a plan.';
    } elseif ($newStartDate === '') {
        $error = 'Please select a start date.';
    } else {
        $selectedPlan = getMembershipPlanById($pdo, $newPlanId);

        if (!$selectedPlan) {
            $error = 'Selected plan does not exist.';
        } else {
            $newEndDate = computeEndDate($newStartDate, (string) $selectedPlan['pass_type']);

            try {
                renewSubscription(
                    $pdo,
                    $subscription,
                    $selectedPlan,
                    $newStartDate,
                    $newEndDate
                );

                header('Location: ../views/vw_subscription-renew.php?id=' . $subscriptionId . '&success=1');
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$planJs = [];

foreach ($plans as $plan) {
    $planJs[] = [
        'id' => (int) $plan['id'],
        'pass_type' => (string) $plan['pass_type']
    ];
}

require_once __DIR__ . '/../views/vw_subscription-renew.php';