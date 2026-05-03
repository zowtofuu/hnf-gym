<?php
declare(strict_types=1);

function formatPlanName(array $plan): string
{
    $membershipType = str_replace('_', ' ', (string) $plan['membership_type']);
    $passType = (string) $plan['pass_type'];

    return ucwords($membershipType) . ' - ' . ucwords($passType);
}

function computeEndDate(string $startDate, string $passType): string
{
    $date = new DateTime($startDate);

    if ($passType === 'monthly') {
        $date->modify('+1 month');
        $date->modify('-1 day');
    }

    return $date->format('Y-m-d');
}

function getMembershipPlans(PDO $pdo): array
{
    $sql = "SELECT 
                id,
                membership_type,
                pass_type,
                price,
                duration_days
            FROM membership_plans
            ORDER BY 
                FIELD(membership_type, 'non_member', 'member', 'student_senior'),
                FIELD(pass_type, 'daily', 'monthly')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMembershipPlanById(PDO $pdo, int $planId): ?array
{
    $sql = "SELECT 
                id,
                membership_type,
                pass_type,
                price,
                duration_days
            FROM membership_plans
            WHERE id = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$planId]);

    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    return $plan ?: null;
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
                mp.price,
                mp.duration_days
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

    return $subscription ?: null;
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