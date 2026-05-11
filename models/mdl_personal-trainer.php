<?php
declare(strict_types=1);

function getClients(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT 
            c.client_id,
            CONCAT(c.first_name, ' ', c.last_name) AS name,

            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM personal_training pt
                    WHERE pt.client_id = c.client_id
                    AND pt.remaining_sessions > 0
                ) THEN 1
                ELSE 0
            END AS has_active_session

        FROM clients c
        ORDER BY c.first_name ASC, c.last_name ASC
    ");

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveSessions(PDO $pdo): array
{
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT 
            pt.id,
            pt.client_id,
            CONCAT(c.first_name, ' ', c.last_name) AS client_name,
            pt.remaining_sessions,

            CASE
                WHEN a.attendance_id IS NULL THEN 0
                WHEN a.training_session_used = 1 THEN 1
                ELSE 0
            END AS used_today,

            CASE
                WHEN a.attendance_id IS NULL THEN 0
                ELSE 1
            END AS has_attendance_today

        FROM personal_training pt
        JOIN clients c 
            ON pt.client_id = c.client_id

        LEFT JOIN attendance a
            ON a.client_id = pt.client_id
            AND a.attendance_date = :today

        WHERE pt.remaining_sessions > 0
        ORDER BY pt.created_at DESC
    ");

    $stmt->execute([
        ':today' => $today
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function clientHasActiveSession(PDO $pdo, int $clientId): bool
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM personal_training
        WHERE client_id = :client_id
        AND remaining_sessions > 0
        LIMIT 1
    ");

    $stmt->execute([
        ':client_id' => $clientId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function buySession(PDO $pdo, int $clientId, int $sessions, float $price): void
{
    $stmt = $pdo->prepare("
        INSERT INTO personal_training (client_id, total_sessions, remaining_sessions)
        VALUES (:client_id, :total, :remaining)
    ");
    $stmt->execute([
        ':client_id' => $clientId,
        ':total' => $sessions,
        ':remaining' => $sessions,
    ]);

    // insert into sales (IMPORTANT)
    $stmtSales = $pdo->prepare("
        INSERT INTO sales (client_id, transaction_type, item_name, quantity, amount)
        VALUES (:client_id, 'personal_training', :item, 1, :amount)
    ");

    $itemName = $sessions === 1 ? '1 Session' : '14 Sessions';

    $stmtSales->execute([
        ':client_id' => $clientId,
        ':item' => $itemName,
        ':amount' => $price,
    ]);
}

function useSession(PDO $pdo, int $trainingId): bool
{
    $today = date('Y-m-d');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT 
                pt.id,
                pt.client_id,
                pt.remaining_sessions,
                a.attendance_id,
                a.training_session_used
            FROM personal_training pt
            LEFT JOIN attendance a
                ON a.client_id = pt.client_id
                AND a.attendance_date = :today
            WHERE pt.id = :training_id
            LIMIT 1
        ");

        $stmt->execute([
            ':training_id' => $trainingId,
            ':today' => $today
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $pdo->rollBack();
            return false;
        }

        if ((int) $row['remaining_sessions'] <= 0) {
            $pdo->rollBack();
            return false;
        }

        if (empty($row['attendance_id'])) {
            $pdo->rollBack();
            return false;
        }

        if ((int) $row['training_session_used'] === 1) {
            $pdo->rollBack();
            return false;
        }

        $updateTraining = $pdo->prepare("
            UPDATE personal_training
            SET remaining_sessions = remaining_sessions - 1
            WHERE id = :training_id
            AND remaining_sessions > 0
        ");

        $updateTraining->execute([
            ':training_id' => $trainingId
        ]);

        if ($updateTraining->rowCount() < 1) {
            $pdo->rollBack();
            return false;
        }

        $updateAttendance = $pdo->prepare("
            UPDATE attendance
            SET training_session_used = 1
            WHERE attendance_id = :attendance_id
            AND training_session_used = 0
        ");

        $updateAttendance->execute([
            ':attendance_id' => (int) $row['attendance_id']
        ]);

        if ($updateAttendance->rowCount() < 1) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}