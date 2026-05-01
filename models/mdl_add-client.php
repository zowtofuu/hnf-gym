<?php
require_once __DIR__ . '/../config/database.php';

function getMembershipTypes(PDO $pdo): array
{
    $sql = "SELECT DISTINCT membership_type
            FROM membership_plans
            ORDER BY 
                (membership_type = 'non_member') DESC,
                membership_type ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPassTypes(PDO $pdo): array
{
    $sql = "SELECT DISTINCT pass_type, duration_days
            FROM membership_plans
            ORDER BY 
                (pass_type = 'daily') DESC,
                pass_type ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMembershipPlan(PDO $pdo, string $membershipType, string $passType): ?array
{
    $sql = "SELECT id, membership_type, pass_type, price, duration_days
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

        $pdo->commit();

        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error adding client with subscription: ' . $e->getMessage());
        return false;
    }
}