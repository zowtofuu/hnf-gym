<?php
declare(strict_types=1);

function getClients(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT client_id, CONCAT(first_name, ' ', last_name) AS name
        FROM clients
        ORDER BY first_name ASC
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveSessions(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT 
            pt.id,
            CONCAT(c.first_name, ' ', c.last_name) AS client_name,
            pt.remaining_sessions
        FROM personal_training pt
        JOIN clients c ON pt.client_id = c.client_id
        WHERE pt.remaining_sessions > 0
        ORDER BY pt.created_at DESC
    ");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

function useSession(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare("
        UPDATE personal_training
        SET remaining_sessions = remaining_sessions - 1
        WHERE id = :id AND remaining_sessions > 0
    ");
    $stmt->execute([':id' => $id]);
}