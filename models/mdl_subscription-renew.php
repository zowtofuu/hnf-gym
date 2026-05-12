<?php

function hnfRenewalGetSubscription(PDO $pdo, int $subscriptionId): ?array
{
    $sql = "SELECT 
                s.subscription_id,
                s.client_id,
                s.plan_id,
                s.membership_start,
                s.membership_end,
                s.subscription_start,
                s.subscription_end,
                s.status,
                c.first_name,
                c.last_name,
                mp.membership_type,
                mp.pass_type,
                mp.price AS pass_price
            FROM subscriptions s
            INNER JOIN clients c ON c.client_id = s.client_id
            INNER JOIN membership_plans mp ON mp.id = s.plan_id
            WHERE s.subscription_id = :subscription_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':subscription_id' => $subscriptionId
    ]);

    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    return $subscription ?: null;
}

function hnfRenewalGetAllPlans(PDO $pdo): array
{
    $sql = "SELECT id, membership_type, pass_type, price
            FROM membership_plans
            ORDER BY membership_type ASC, pass_type ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function hnfRenewalGetPlan(PDO $pdo, string $membershipType, string $passType): ?array
{
    $sql = "SELECT id, membership_type, pass_type, price
            FROM membership_plans
            WHERE membership_type = :membership_type
              AND pass_type = :pass_type
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':membership_type' => $membershipType,
        ':pass_type' => $passType
    ]);

    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    return $plan ?: null;
}

function hnfRenewalGetAnnualMembershipFee(PDO $pdo): float
{
    $sql = "SELECT price
            FROM other_pricings
            WHERE item = :item
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':item' => 'annual_mem_fee'
    ]);

    return (float) ($stmt->fetchColumn() ?: 0);
}

function hnfRenewalUpdateSubscription(
    PDO $pdo,
    int $subscriptionId,
    int $planId,
    ?string $membershipStart,
    ?string $membershipEnd,
    string $subscriptionStart,
    string $subscriptionEnd
): void {
    $sql = "UPDATE subscriptions
            SET 
                plan_id = :plan_id,
                membership_start = :membership_start,
                membership_end = :membership_end,
                subscription_start = :subscription_start,
                subscription_end = :subscription_end,
                status = 'active'
            WHERE subscription_id = :subscription_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':plan_id' => $planId,
        ':membership_start' => $membershipStart,
        ':membership_end' => $membershipEnd,
        ':subscription_start' => $subscriptionStart,
        ':subscription_end' => $subscriptionEnd,
        ':subscription_id' => $subscriptionId
    ]);
}

function hnfRenewalInsertSale(
    PDO $pdo,
    int $subscriptionId,
    int $clientId,
    string $transactionType,
    string $itemName,
    float $amount
): void {
    if ($amount <= 0) {
        return;
    }

    $sql = "INSERT INTO sales (
                transaction_type,
                reference_id,
                client_id,
                item_name,
                quantity,
                amount
            ) VALUES (
                :transaction_type,
                :reference_id,
                :client_id,
                :item_name,
                :quantity,
                :amount
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':transaction_type' => $transactionType,
        ':reference_id' => $subscriptionId,
        ':client_id' => $clientId,
        ':item_name' => $itemName,
        ':quantity' => 1,
        ':amount' => $amount
    ]);
}

function hnfRenewalSave(
    PDO $pdo,
    array $subscription,
    array $data
): void {
    $subscriptionId = (int) $subscription['subscription_id'];
    $clientId = (int) $subscription['client_id'];

    $selectedMembershipType = trim((string) ($data['membership_type'] ?? ''));
    $selectedPassType = trim((string) ($data['pass_type'] ?? ''));

    $isMembershipRenewed = $selectedMembershipType !== '';
    $isPassRenewed = $selectedPassType !== '';

    if (!$isMembershipRenewed && !$isPassRenewed) {
        throw new Exception('Please select membership type, pass type, or both.');
    }

    $currentMembershipType = (string) $subscription['membership_type'];
    $currentPassType = (string) $subscription['pass_type'];

    $finalMembershipType = $isMembershipRenewed ? $selectedMembershipType : $currentMembershipType;
    $finalPassType = $isPassRenewed ? $selectedPassType : $currentPassType;

    $plan = hnfRenewalGetPlan($pdo, $finalMembershipType, $finalPassType);

    if (!$plan) {
        throw new Exception('Selected plan does not exist.');
    }

    $membershipStart = $subscription['membership_start'];
    $membershipEnd = $subscription['membership_end'];

    $subscriptionStart = $subscription['subscription_start'];
    $subscriptionEnd = $subscription['subscription_end'];

    $membershipFee = 0.00;
    $passFee = 0.00;

    if ($isMembershipRenewed) {
        $membershipStart = trim((string) ($data['membership_start'] ?? '')) !== ''
            ? trim((string) $data['membership_start'])
            : date('Y-m-d');

        if ($finalMembershipType === 'member') {
            $membershipEnd = trim((string) ($data['membership_end'] ?? '')) !== ''
                ? trim((string) $data['membership_end'])
                : date('Y-m-d', strtotime($membershipStart . ' +1 year'));

            $membershipFee = hnfRenewalGetAnnualMembershipFee($pdo);
        } else {
            $membershipEnd = null;
            $membershipFee = 0.00;
        }
    }

    if ($isPassRenewed) {
        $subscriptionStart = trim((string) ($data['subscription_start'] ?? '')) !== ''
            ? trim((string) $data['subscription_start'])
            : date('Y-m-d');

        $subscriptionEnd = trim((string) ($data['subscription_end'] ?? '')) !== ''
            ? trim((string) $data['subscription_end'])
            : hnfRenewalCalculatePassEnd($subscriptionStart, $finalPassType);

        $passFee = (float) $plan['price'];
    }

    $pdo->beginTransaction();

    try {
        hnfRenewalUpdateSubscription(
            $pdo,
            $subscriptionId,
            (int) $plan['id'],
            $membershipStart,
            $membershipEnd,
            $subscriptionStart,
            $subscriptionEnd
        );

        /*
            Separate sales records, same logic as add client:
            1. Annual membership fee is saved separately.
            2. Pass fee is saved separately.
        */

        if ($isMembershipRenewed && $finalMembershipType === 'member' && $membershipFee > 0) {
            hnfRenewalInsertSale(
                $pdo,
                $subscriptionId,
                $clientId,
                'annual_membership',
                'Annual Membership Fee',
                $membershipFee
            );
        }

        if ($isPassRenewed && $passFee > 0) {
            hnfRenewalInsertSale(
                $pdo,
                $subscriptionId,
                $clientId,
                'renewal',
                hnfRenewalPlanName($plan),
                $passFee
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function hnfRenewalPlanName(array $plan): string
{
    return hnfRenewalLabel((string) $plan['membership_type']) . ' - ' . hnfRenewalLabel((string) $plan['pass_type']);
}

function hnfRenewalCalculatePassEnd(string $startDate, string $passType): string
{
    if ($passType === 'monthly') {
        return date('Y-m-d', strtotime($startDate . ' +1 month'));
    }

    return $startDate;
}

function hnfRenewalStatus(?string $endDate): string
{
    if (!$endDate) {
        return 'N/A';
    }

    return $endDate >= date('Y-m-d') ? 'Active' : 'Expired';
}

function hnfRenewalInputDate(?string $date): string
{
    if (!$date) {
        return '';
    }

    return date('Y-m-d', strtotime($date));
}

function hnfRenewalLabel(?string $value): string
{
    return match ($value) {
        'member' => 'Member',
        'non_member' => 'Non-member',
        'student_senior' => 'Student / Senior',
        'daily' => 'Daily',
        'monthly' => 'Monthly',
        default => 'N/A'
    };
}

function hnfRenewalEscape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}