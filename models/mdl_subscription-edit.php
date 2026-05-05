<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/membership_rules.php';

function getEditableSubscription(PDO $pdo, int $subscriptionId): ?array
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
                mp.price
            FROM subscriptions s
            INNER JOIN clients c ON c.client_id = s.client_id
            INNER JOIN membership_plans mp ON mp.id = s.plan_id
            WHERE s.subscription_id = :subscription_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':subscription_id' => $subscriptionId]);

    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    return $subscription ?: null;
}

function getMembershipTypes(PDO $pdo): array
{
    $sql = "SELECT DISTINCT membership_type
            FROM membership_plans
            ORDER BY
                CASE membership_type
                    WHEN 'non_member' THEN 1
                    WHEN 'member' THEN 2
                    WHEN 'student_senior' THEN 3
                    ELSE 4
                END";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPassTypes(PDO $pdo): array
{
    $sql = "SELECT DISTINCT pass_type
            FROM membership_plans
            ORDER BY
                CASE pass_type
                    WHEN 'daily' THEN 1
                    WHEN 'monthly' THEN 2
                    ELSE 3
                END";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSelectedPlan(PDO $pdo, string $membershipType, string $passType): ?array
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

function resolveSubscriptionStatus(string $endDate, string $requestedStatus): string
{
    if ($requestedStatus === 'suspended') {
        return 'suspended';
    }

    return $endDate < date('Y-m-d') ? 'expired' : 'active';
}

function formatSubscriptionSaleItemName(string $membershipType, string $passType): string
{
    $membershipLabel = ucwords(str_replace('_', ' ', $membershipType));
    $passLabel = ucwords(str_replace('_', ' ', $passType));

    return $membershipLabel . ' - ' . $passLabel;
}

function updateEditableSubscription(
    PDO $pdo,
    int $subscriptionId,
    int $clientId,
    array $plan,
    ?string $membershipStart,
    string $subscriptionStart,
    string $status
): bool {
    $membershipType = (string) $plan['membership_type'];
    $passType = (string) $plan['pass_type'];
    $price = (float) $plan['price'];

    $membershipEnd = null;

    if ($membershipType === 'member' && $membershipStart !== null) {
        $membershipEnd = date('Y-m-d', strtotime($membershipStart . ' +1 year'));
    }

    $subscriptionEnd = computeMembershipEndDate($subscriptionStart, $passType);
    $status = resolveSubscriptionStatus($subscriptionEnd, $status);
    $itemName = formatSubscriptionSaleItemName($membershipType, $passType);

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE subscriptions
                SET
                    plan_id = :plan_id,
                    membership_start = :membership_start,
                    membership_end = :membership_end,
                    subscription_start = :subscription_start,
                    subscription_end = :subscription_end,
                    status = :status
                WHERE subscription_id = :subscription_id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':plan_id' => (int) $plan['id'],
            ':membership_start' => $membershipStart,
            ':membership_end' => $membershipEnd,
            ':subscription_start' => $subscriptionStart,
            ':subscription_end' => $subscriptionEnd,
            ':status' => $status,
            ':subscription_id' => $subscriptionId
        ]);

        $salesSql = "UPDATE sales
                     SET
                        client_id = :client_id,
                        item_name = :item_name,
                        amount = :amount
                     WHERE reference_id = :subscription_id
                       AND transaction_type IN ('subscription', 'renewal')";

        $salesStmt = $pdo->prepare($salesSql);
        $salesStmt->execute([
            ':client_id' => $clientId,
            ':item_name' => $itemName,
            ':amount' => $price,
            ':subscription_id' => $subscriptionId
        ]);

        $pdo->commit();

        return true;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}