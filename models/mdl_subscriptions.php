<?php
require_once __DIR__ . '/../config/database.php';

function downgradeExpiredMembers(PDO $pdo): void
{
    $sql = "
        UPDATE clients
        SET membership_expires_at = NULL
        WHERE membership_expires_at IS NOT NULL
          AND membership_expires_at < CURDATE()
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

function updateSubscriptionStatuses(PDO $pdo): void
{
    $sql = "
        UPDATE subscriptions
        SET status = CASE
            WHEN status = 'suspended' THEN 'suspended'
            WHEN subscription_end < CURDATE() THEN 'expired'
            ELSE 'active'
        END
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

function getSubscriptionCounts(PDO $pdo): array
{
    $sql = "
        SELECT
            COUNT(*) AS total,
            SUM(status = 'active') AS active,
            SUM(status = 'expired') AS expired,
            SUM(status = 'suspended') AS suspended,
            SUM(
                status = 'active'
                AND subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ) AS expiring_soon
        FROM subscriptions
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total' => 0,
        'active' => 0,
        'expired' => 0,
        'suspended' => 0,
        'expiring_soon' => 0,
    ];
}

function getSubscriptions(PDO $pdo, string $search = '', string $status = 'all'): array
{
    $allowedStatuses = ['all', 'active', 'expired', 'suspended', 'expiring'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'all';
    }

    $sql = "SELECT
            s.subscription_id,
            s.client_id,
            c.first_name,
            c.last_name,
            CONCAT(c.first_name, ' ', c.last_name) AS client_name,
            mp.membership_type,
            mp.pass_type,
            mp.price,
            s.subscription_start,
            s.subscription_end,
            s.subscription_token,
            s.status,
            s.created_at
            FROM subscriptions s
            LEFT JOIN clients c ON s.client_id = c.client_id
            LEFT JOIN membership_plans mp ON s.plan_id = mp.id
            WHERE (
            c.first_name LIKE :search_first_name
            OR c.last_name LIKE :search_last_name
            OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search_full_name
            OR COALESCE(s.subscription_token, '') LIKE :search_token)
    ";

    // Apply status filter
    if ($status === 'active') {
        $sql .= " AND s.status = 'active' ";
    } elseif ($status === 'expired') {
        $sql .= " AND s.status = 'expired' ";
    } elseif ($status === 'suspended') {
        $sql .= " AND s.status = 'suspended' ";
    } elseif ($status === 'expiring') {
        $sql .= "
            AND s.status = 'active'
            AND s.subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ";
    }

    $sql .= " ORDER BY s.subscription_id DESC ";

    $searchLike = "{$search}%";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search_first_name' => $searchLike,
        ':search_last_name' => $searchLike,
        ':search_full_name' => $searchLike,
        ':search_token' => $searchLike,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}