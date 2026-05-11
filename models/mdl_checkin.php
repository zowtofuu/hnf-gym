<?php
require_once __DIR__ . '/../config/database.php';

function getAllClients(PDO $pdo): array
{
    $sql = "SELECT 
                client_id,
                first_name,
                last_name
            FROM clients
            ORDER BY first_name ASC, last_name ASC";

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
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':token' => $token
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int) $row['client_id'] : null;
}

function getClientName(PDO $pdo, int $clientId): string
{
    $sql = "SELECT 
                first_name,
                last_name
            FROM clients
            WHERE client_id = :client_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return 'Unknown client';
    }

    return trim($row['first_name'] . ' ' . $row['last_name']);
}

function getActiveSubscription(PDO $pdo, int $clientId, string $date): ?array
{
    $sql = "SELECT 
                s.subscription_id,
                s.client_id,
                s.membership_start,
                s.membership_end,
                s.subscription_start,
                s.subscription_end,
                s.status,
                mp.membership_type,
                mp.pass_type
            FROM subscriptions s
            INNER JOIN membership_plans mp
                ON mp.id = s.plan_id
            WHERE s.client_id = :client_id
            AND s.status = 'active'
            AND :date BETWEEN s.subscription_start AND s.subscription_end
            ORDER BY s.subscription_end DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function attendanceExists(PDO $pdo, int $clientId, string $date): bool
{
    $sql = "SELECT 
                attendance_id
            FROM attendance
            WHERE client_id = :client_id
            AND attendance_date = :date
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function insertAttendance(PDO $pdo, int $clientId, string $date, bool $useSession): bool
{
    $sql = "INSERT INTO attendance (
                client_id,
                attendance_date,
                check_in_time,
                training_session_used
            ) VALUES (
                :client_id,
                :date,
                :time,
                :training_session_used
            )";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':client_id' => $clientId,
        ':date' => $date,
        ':time' => date('H:i:s'),
        ':training_session_used' => $useSession ? 1 : 0
    ]);
}

function getLatestTrainingPackage(PDO $pdo, int $clientId): ?array
{
    $sql = "SELECT 
                id,
                total_sessions,
                remaining_sessions
            FROM personal_training
            WHERE client_id = :client_id
            ORDER BY id DESC
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function decrementTrainingSession(PDO $pdo, int $trainingId): bool
{
    $sql = "UPDATE personal_training
            SET remaining_sessions = remaining_sessions - 1
            WHERE id = :id
            AND remaining_sessions > 0";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':id' => $trainingId
    ]);
}

function daysRemaining(?string $endDate, string $selectedDate): ?int
{
    if (empty($endDate)) {
        return null;
    }

    $start = new DateTime($selectedDate);
    $end = new DateTime($endDate);

    if ($end < $start) {
        return 0;
    }

    return (int) $start->diff($end)->days;
}

function formatDisplayDate(?string $date): string
{
    if (empty($date)) {
        return 'N/A';
    }

    return date('F j, Y', strtotime($date));
}

function buildCheckinFeedback(PDO $pdo, int $clientId, string $selectedDate, ?array $subscription): array
{
    $training = getLatestTrainingPackage($pdo, $clientId);

    return [
        'client_name' => getClientName($pdo, $clientId),

        'pass_end' => $subscription ? formatDisplayDate($subscription['subscription_end']) : 'N/A',
        'pass_days_remaining' => $subscription ? daysRemaining($subscription['subscription_end'], $selectedDate) : null,

        'membership_end' => $subscription ? formatDisplayDate($subscription['membership_end']) : 'N/A',
        'membership_days_remaining' => $subscription ? daysRemaining($subscription['membership_end'], $selectedDate) : null,

        'remaining_sessions' => $training ? (int) $training['remaining_sessions'] : null
    ];
}

function processCheckin(PDO $pdo, int $clientId, string $selectedDate, bool $useSession): array
{
    $subscription = getActiveSubscription($pdo, $clientId, $selectedDate);

    if (!$subscription) {
        return [
            'status' => 'error',
            'message' => 'No available subscription',
            'data' => buildCheckinFeedback($pdo, $clientId, $selectedDate, null)
        ];
    }

    if (attendanceExists($pdo, $clientId, $selectedDate)) {
        return [
            'status' => 'warning',
            'message' => 'Already checked in.',
            'data' => buildCheckinFeedback($pdo, $clientId, $selectedDate, $subscription)
        ];
    }

    $training = getLatestTrainingPackage($pdo, $clientId);

    if ($useSession) {
        if (!$training || (int) $training['remaining_sessions'] <= 0) {
            return [
                'status' => 'error',
                'message' => 'No remaining session available.',
                'data' => buildCheckinFeedback($pdo, $clientId, $selectedDate, $subscription)
            ];
        }
    }

    try {
        $pdo->beginTransaction();

        $attendanceInserted = insertAttendance($pdo, $clientId, $selectedDate, $useSession);

        if (!$attendanceInserted) {
            $pdo->rollBack();

            return [
                'status' => 'error',
                'message' => 'Insert failed.',
                'data' => null
            ];
        }

        if ($useSession && $training) {
            decrementTrainingSession($pdo, (int) $training['id']);
        }

        $pdo->commit();

        return [
            'status' => 'success',
            'message' => 'Success!',
            'data' => buildCheckinFeedback($pdo, $clientId, $selectedDate, $subscription)
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return [
            'status' => 'error',
            'message' => 'Check-in failed.',
            'data' => null
        ];
    }
}