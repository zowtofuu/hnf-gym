<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/membership_rules.php';

function formatPlanName(array $plan): string
{
    return membershipPlanName($plan);
}

function computeEndDate(string $startDate, string $passType): string
{
    return computeMembershipEndDate($startDate, $passType);
}

function getMembershipPlans(PDO $pdo): array
{
    return getEnforcedMembershipPlans($pdo);
}

function getMembershipPlanById(PDO $pdo, int $planId): ?array
{
    return getEnforcedMembershipPlanById($pdo, $planId);
}

function getSubscriptionById(PDO $pdo, int $subscriptionId): ?array
{
    $sql = "SELECT
                s.subscription_id,
                s.client_id,
                s.plan_id,
                s.subscription_start,
                s.subscription_end,
                s.subscription_token,
                s.status,

                c.first_name,
                c.last_name,

                mp.membership_type,
                mp.pass_type,
                mp.price
            FROM subscriptions s
            INNER JOIN clients c 
                ON c.client_id = s.client_id
            INNER JOIN membership_plans mp 
                ON mp.id = s.plan_id
            WHERE s.subscription_id = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$subscriptionId]);

    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        return null;
    }

    return normalizeMembershipPlan($subscription);
}

function renewSubscription(
    PDO $pdo,
    array $subscription,
    array $selectedPlan,
    string $newStartDate,
    string $newEndDate
): void {
    $pdo->beginTransaction();

    try {
        $historySql = "INSERT INTO subscriptions_history (
                            subscription_id,
                            client_id,
                            plan_id,
                            subscription_start,
                            subscription_end,
                            subscription_token,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $historyStmt = $pdo->prepare($historySql);
        $historyStmt->execute([
            $subscription['subscription_id'],
            $subscription['client_id'],
            $subscription['plan_id'],
            $subscription['subscription_start'],
            $subscription['subscription_end'],
            $subscription['subscription_token'],
            $subscription['status']
        ]);

        $updateSql = "UPDATE subscriptions
                      SET 
                          plan_id = ?,
                          subscription_start = ?,
                          subscription_end = ?,
                          status = 'active'
                      WHERE subscription_id = ?
                      LIMIT 1";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $selectedPlan['id'],
            $newStartDate,
            $newEndDate,
            $subscription['subscription_id']
        ]);

        $salesSql = "INSERT INTO sales (
                        transaction_type,
                        reference_id,
                        client_id,
                        item_name,
                        quantity,
                        amount
                    ) VALUES (
                        'renewal',
                        ?,
                        ?,
                        ?,
                        1,
                        ?
                    )";

        $salesStmt = $pdo->prepare($salesSql);
        $salesStmt->execute([
            $subscription['subscription_id'],
            $subscription['client_id'],
            formatPlanName($selectedPlan),
            $selectedPlan['price']
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
