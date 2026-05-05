<?php
require_once __DIR__ . '/../config/database.php';

function getAllClients(PDO $pdo): array
{
    $sql = "SELECT 
                client_id,
                first_name,
                last_name
            FROM clients
            ORDER BY first_name ASC, last_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClientIdByToken(PDO $pdo, string $token): ?int
{
    $sql = "SELECT 
                client_id
            FROM subscriptions
            WHERE subscription_token = :token
            LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':token' => $token
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int) $row['client_id'] : null;
}

function hasValidSubscription(PDO $pdo, int $clientId, string $date): bool
{
    $sql = "SELECT 
                subscription_id
            FROM subscriptions
            WHERE client_id = :client_id
            AND status = 'active'
            AND :date BETWEEN subscription_start AND subscription_end
            LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function attendanceExists(PDO $pdo, int $clientId, string $date): bool
{
    $sql = "SELECT 
                attendance_id
            FROM attendance
            WHERE client_id = :client_id
            AND attendance_date = :date
            LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function insertAttendance(PDO $pdo, int $clientId, string $date): bool
{
    $sql = "INSERT INTO attendance (
                client_id,
                attendance_date,
                check_in_time
            ) VALUES (
                :client_id,
                :date,
                :time
            )
    ";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date,
        ':time' => date('H:i:s')
    ]);
}
function getActiveSubscription(PDO $pdo, int $clientId, string $date): ?array
{
    $sql = "SELECT 
                subscription_id,
                subscription_start,
                subscription_end,
                status
            FROM subscriptions
            WHERE client_id = :client_id
            AND status = 'active'
            AND :date BETWEEN subscription_start AND subscription_end
            ORDER BY subscription_end DESC
            LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}