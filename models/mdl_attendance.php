<?php

function getAttendanceList(PDO $pdo, array $filters = []): array
{
   $sql = "SELECT
            a.attendance_id,
            CONCAT(c.first_name, ' ', c.last_name) AS client_name,
            c.contact,
            mp.membership_type,
            mp.pass_type,
            a.attendance_date,
            a.check_in_time,
            CASE 
                WHEN a.training_session_used = 1 THEN 'Yes'
                ELSE 'No'
            END AS training_session_used
            FROM attendance a
            INNER JOIN clients c ON a.client_id = c.client_id
            LEFT JOIN subscriptions s ON c.client_id = s.client_id
            LEFT JOIN membership_plans mp ON s.plan_id = mp.id
            WHERE 1 = 1";

    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (
                    c.first_name LIKE :search_first_name
                    OR c.last_name LIKE :search_last_name
                    OR c.contact LIKE :search_contact
                )";

        $search = '%' . $filters['search'] . '%';

        $params[':search_first_name'] = $search;
        $params[':search_last_name'] = $search;
        $params[':search_contact'] = $search;
    }

    if (!empty($filters['date'])) {
        $sql .= " AND a.attendance_date = :attendance_date";
        $params[':attendance_date'] = $filters['date'];
    }

    if (!empty($filters['membership_type']) && $filters['membership_type'] !== 'all') {
        $sql .= " AND mp.membership_type = :membership_type";
        $params[':membership_type'] = $filters['membership_type'];
    }

    if (!empty($filters['pass_type']) && $filters['pass_type'] !== 'all') {
        $sql .= " AND mp.pass_type = :pass_type";
        $params[':pass_type'] = $filters['pass_type'];
    }

    $sql .= " ORDER BY a.attendance_date DESC, a.check_in_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAttendanceFilterOptions(PDO $pdo): array
{
    $sql = "SELECT DISTINCT membership_type, pass_type
            FROM membership_plans
            ORDER BY membership_type ASC, pass_type ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}