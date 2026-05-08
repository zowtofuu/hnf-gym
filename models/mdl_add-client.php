<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/membership_rules.php';

function getMembershipTypes(PDO $pdo): array
{
    ensureMembershipPlans($pdo);

    return membershipTypeLabels();
}

function getPassTypes(PDO $pdo): array
{
    ensureMembershipPlans($pdo);

    return passTypeLabels();
}

function getMembershipPlan(PDO $pdo, string $membershipType, string $passType): ?array
{
    return getEnforcedMembershipPlan($pdo, $membershipType, $passType);
}

function getMembershipPlanOptions(PDO $pdo): array
{
    return getEnforcedMembershipPlans($pdo);
}

function getOtherPricing(PDO $pdo, string $item): ?float
{
    $sql = "SELECT amount FROM other_pricings WHERE item = :item LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':item' => $item]);

    $amount = $stmt->fetchColumn();

    return $amount !== false ? (float) $amount : null;
}

function addClientWithSubscription(
    PDO $pdo,
    string $firstName,
    string $lastName,
    string $contact,
    int $planId,
    string $subscriptionStart,
    string $subscriptionEnd,
    string $subscriptionToken
): bool {
    try {
        $pdo->beginTransaction();

        $clientSql = "INSERT INTO clients (first_name, last_name, contact)
                      VALUES (:first_name, :last_name, :contact)";

        $clientStmt = $pdo->prepare($clientSql);
        $clientStmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':contact' => $contact
        ]);

        $clientId = (int) $pdo->lastInsertId();

        $membershipStart = null;
        $membershipEnd = null;

        $plan = getEnforcedMembershipPlanById($pdo, $planId);

        if (!$plan) {
            throw new Exception('Membership plan not found.');
        }

        if ($plan['membership_type'] === 'member') {
            $membershipStart = date('Y-m-d');
            $membershipEnd = date('Y-m-d', strtotime('+1 year'));
        }

        $subscriptionSql = "INSERT INTO subscriptions (
                            client_id,
                            plan_id,
                            membership_start,
                            membership_end,
                            subscription_start,
                            subscription_end,
                            subscription_token,
                            status
                            ) VALUES (
                            :client_id,
                            :plan_id,
                            :membership_start,
                            :membership_end,
                            :subscription_start,
                            :subscription_end,
                            :subscription_token,
                            'active'
                        )";

        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([
            ':client_id' => $clientId,
            ':plan_id' => $planId,
            ':membership_start' => $membershipStart,
            ':membership_end' => $membershipEnd,
            ':subscription_start' => $subscriptionStart,
            ':subscription_end' => $subscriptionEnd,
            ':subscription_token' => $subscriptionToken
        ]);

        $subscriptionId = (int) $pdo->lastInsertId();

        $itemName = membershipPlanName($plan);
        $annualFee = getOtherPricing($pdo, 'annual_membership_fee');

        $salesSql = "INSERT INTO sales (
                        transaction_type,
                        reference_id,
                        client_id,
                        item_name,
                        quantity,
                        amount
                    )
                    VALUES (
                        'subscription',
                        :reference_id,
                        :client_id,
                        :item_name,
                        1,
                        :amount
                    )";

        $salesStmt = $pdo->prepare($salesSql);
        $salesStmt->execute([
            ':reference_id' => $subscriptionId,
            ':client_id' => $clientId,
            ':item_name' => $itemName,
            ':amount' => $plan['price']
        ]);

        if ($plan['membership_type'] === 'member') {
            $annualFeeSql = "
        INSERT INTO sales (
            transaction_type,
            reference_id,
            client_id,
            item_name,
            quantity,
            amount
        ) VALUES (
            'annual_membership',
            :reference_id,
            :client_id,
            :amount => $annualFee
        )
    ";

            $annualFeeStmt = $pdo->prepare($annualFeeSql);
            $annualFeeStmt->execute([
                ':reference_id' => $subscriptionId,
                ':client_id' => $clientId
            ]);
        }

        $pdo->commit();

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error adding client with subscription: ' . $e->getMessage());
        return false;
    }
}
