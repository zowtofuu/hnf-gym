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

        $subscriptionSql = "INSERT INTO subscriptions (
                                client_id,
                                plan_id,
                                subscription_start,
                                subscription_end,
                                subscription_token,
                                status
                            )
                            VALUES (
                                :client_id,
                                :plan_id,
                                :subscription_start,
                                :subscription_end,
                                :subscription_token,
                                'active'
                            )";

        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([
            ':client_id' => $clientId,
            ':plan_id' => $planId,
            ':subscription_start' => $subscriptionStart,
            ':subscription_end' => $subscriptionEnd,
            ':subscription_token' => $subscriptionToken
        ]);

        $subscriptionId = (int) $pdo->lastInsertId();

        $plan = getEnforcedMembershipPlanById($pdo, $planId);

        if (!$plan) {
            throw new Exception('Membership plan not found.');
        }

        $itemName = membershipPlanName($plan);

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
