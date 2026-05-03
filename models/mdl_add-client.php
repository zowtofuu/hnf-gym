<?php
require_once __DIR__ . '/../config/database.php';

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

        // ✅ FETCH PLAN FIRST (fix undefined $plan)
        $planSql = "SELECT membership_type, pass_type, price
                    FROM membership_plans
                    WHERE id = :plan_id
                    LIMIT 1";

        $planStmt = $pdo->prepare($planSql);
        $planStmt->execute([
            ':plan_id' => $planId
        ]);

        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            throw new Exception('Membership plan not found.');
        }

        // ✅ COMPUTE MEMBERSHIP EXPIRY
        $membershipExpiresAt = null;

        if ($plan['membership_type'] === 'member') {
            $membershipExpiresAt = date('Y-m-d', strtotime('+1 year'));
        }

        // ✅ INSERT CLIENT (now with expiry)
        $clientSql = "INSERT INTO clients (
                        first_name,
                        last_name,
                        contact,
                        membership_expires_at
                      )
                      VALUES (
                        :first_name,
                        :last_name,
                        :contact,
                        :membership_expires_at
                      )";

        $clientStmt = $pdo->prepare($clientSql);
        $clientStmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':contact' => $contact,
            ':membership_expires_at' => $membershipExpiresAt
        ]);

        $clientId = (int) $pdo->lastInsertId();

        // ✅ INSERT SUBSCRIPTION
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

        // ✅ SALES RECORD
        $itemName = ucwords(str_replace('_', ' ', $plan['membership_type']))
            . ' - '
            . ucwords(str_replace('_', ' ', $plan['pass_type']));

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