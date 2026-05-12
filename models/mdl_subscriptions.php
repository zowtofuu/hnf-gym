<?php

function hnfFormatDate(?string $date): string
{
    if (empty($date)) {
        return 'N/A';
    }

    return date('F j, Y', strtotime($date));
}

function hnfMembershipTypeLabel(?string $type): string
{
    return match ($type) {
        'member' => 'Member',
        'non_member' => 'Non-member',
        'student_senior' => 'Student/Senior',
        default => 'N/A',
    };
}

function hnfPassTypeLabel(?string $type): string
{
    return match ($type) {
        'daily' => 'Daily',
        'monthly' => 'Monthly',
        default => 'N/A',
    };
}

function hnfStatusLabel(?string $status): string
{
    return match ($status) {
        'active' => 'Active',
        'expired' => 'Expired',
        'suspended' => 'Suspended',
        'expired_membership' => 'Expired Membership',
        'expired_pass' => 'Expired Pass',
        default => 'N/A',
    };
}

function getSubscriptionFilterOptions(PDO $pdo): array
{
    $sql = "SELECT DISTINCT membership_type, pass_type
            FROM membership_plans
            ORDER BY membership_type ASC, pass_type ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $membershipTypes = [];
    $passTypes = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['membership_type'])) {
            $membershipTypes[$row['membership_type']] = $row['membership_type'];
        }

        if (!empty($row['pass_type'])) {
            $passTypes[$row['pass_type']] = $row['pass_type'];
        }
    }

    return [
        'membership_types' => array_values($membershipTypes),
        'pass_types' => array_values($passTypes),
    ];
}

function getFilteredSubscriptions(PDO $pdo, array $filters = []): array
{
    $sql = "SELECT 
                s.subscription_id,
                CONCAT(c.first_name, ' ', c.last_name) AS client_name,

                mp.membership_type,
                s.membership_start,
                s.membership_end,

                mp.pass_type,
                s.subscription_start,
                s.subscription_end,

                s.status AS saved_status,

                CASE
                    WHEN s.membership_end IS NOT NULL 
                         AND s.membership_end < CURDATE()
                    THEN 'expired_membership'

                    WHEN s.subscription_end < CURDATE()
                    THEN 'expired_pass'

                    ELSE s.status
                END AS display_status,

                CASE
                    WHEN s.membership_end IS NULL THEN NULL
                    ELSE GREATEST(DATEDIFF(s.membership_end, CURDATE()), 0)
                END AS membership_days_remaining,

                GREATEST(DATEDIFF(s.subscription_end, CURDATE()), 0) AS pass_days_remaining

            FROM subscriptions s
            INNER JOIN clients c 
                ON s.client_id = c.client_id
            INNER JOIN membership_plans mp 
                ON s.plan_id = mp.id
            WHERE 1 = 1";

    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND CONCAT(c.first_name, ' ', c.last_name) LIKE :search";
        $params[':search'] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['membership_type'])) {
        $sql .= " AND mp.membership_type = :membership_type";
        $params[':membership_type'] = $filters['membership_type'];
    }

    if (!empty($filters['pass_type'])) {
        $sql .= " AND mp.pass_type = :pass_type";
        $params[':pass_type'] = $filters['pass_type'];
    }

    if (!empty($filters['expiring_filter'])) {
        if ($filters['expiring_filter'] === 'membership') {
            $sql .= " AND s.membership_end IS NOT NULL
                  AND s.membership_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }

        if ($filters['expiring_filter'] === 'pass') {
            $sql .= " AND s.subscription_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        }
    }

    if (!empty($filters['status_filter'])) {
        if ($filters['status_filter'] === 'active') {
            $sql .= " AND s.status = 'active'
                      AND s.subscription_end >= CURDATE()";
        }

        if ($filters['status_filter'] === 'suspended') {
            $sql .= " AND s.status = 'suspended'";
        }

        if ($filters['status_filter'] === 'expired_membership') {
            $sql .= " AND s.membership_end IS NOT NULL
                      AND s.membership_end < CURDATE()";
        }

        if ($filters['status_filter'] === 'expired_pass') {
            $sql .= " AND s.subscription_end < CURDATE()";
        }
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}