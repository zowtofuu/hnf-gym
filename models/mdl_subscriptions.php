<?php
require_once __DIR__ . '/../config/database.php';

function updateSubscriptionStatuses(PDO $pdo): void
{
    $sql = "UPDATE subscriptions
             SET status = CASE
            WHEN status = 'suspended' THEN 'suspended'
            WHEN subscription_end < CURDATE() THEN 'expired'
            ELSE 'active'
            END
    ";

    $pdo->prepare($sql)->execute();
}

function getSubscriptionCounts(PDO $pdo): array
{
    $sql = "SELECT
            COUNT(*) AS total,
            SUM(status = 'active') AS active,
            SUM(status = 'expired') AS expired,
            SUM(status = 'suspended') AS suspended
            FROM subscriptions
    ";

    return $pdo->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [
        'total' => 0,
        'active' => 0,
        'expired' => 0,
        'suspended' => 0
    ];
}

function getSubscriptions(PDO $pdo, string $search = '', string $status = 'all'): array
{
    $allowedStatuses = ['all', 'active', 'expired', 'suspended'];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'all';
    }

    $sql = "
        SELECT
            s.subscription_id,
            s.client_id,
            CONCAT(c.first_name, ' ', c.last_name) AS client_name,
            mp.membership_type,
            mp.pass_type,
            mp.price,
            s.membership_start,
            s.membership_end,
            s.subscription_start,
            s.subscription_end,
            s.status
        FROM subscriptions s
        LEFT JOIN clients c ON s.client_id = c.client_id
        LEFT JOIN membership_plans mp ON s.plan_id = mp.id
        WHERE (
            c.first_name LIKE :search_first_name
            OR c.last_name LIKE :search_last_name
            OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search_full_name
        )
    ";

    $params = [
        ':search_first_name' => "%{$search}%",
        ':search_last_name' => "%{$search}%",
        ':search_full_name' => "%{$search}%"
    ];

    if ($status !== 'all') {
        $sql .= " AND s.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY s.subscription_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}