<?php
require_once __DIR__ . '/../config/database.php';

// Fetch all clients from the database
function getAllClients(PDO $pdo)
{
    $sql = "SELECT 
            client_id,
            first_name,
            last_name,
            birthday,
            created_at,
            contact
            FROM clients
            ORDER BY client_id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Search clients based on the search term
function searchClients(PDO $pdo, string $searchTerm): array
{
    $sql = "SELECT client_id, first_name, last_name, contact, birthday
            FROM clients
            WHERE first_name LIKE :first_name
            OR last_name LIKE :last_name
            OR CONCAT(first_name, ' ', last_name) LIKE :full_name
            ORDER BY client_id DESC
    ";

    $stmt = $pdo->prepare($sql);

    $keyword = "{$searchTerm}%";
    $stmt->execute([
        ':first_name' => $keyword,
        ':last_name'  => $keyword,
        ':full_name'  => $keyword
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteClient(PDO $pdo, int $clientId): bool
{
    $sql = "DELETE FROM clients WHERE client_id = :client_id";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':client_id' => $clientId
    ]);
}

function updateClient(PDO $pdo, int $clientId, string $firstName, string $lastName, string $birthday, string $contact): bool
{
    $sql = "UPDATE clients
            SET first_name = :first_name,
                last_name = :last_name,
                birthday = :birthday,
                contact = :contact
            WHERE client_id = :client_id";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':birthday' => $birthday,
        ':contact' => $contact,
        ':client_id' => $clientId
    ]);
}

function getClientById(PDO $pdo, int $clientId): ?array
{
    $sql = "SELECT client_id, first_name, last_name, contact, birthday
            FROM clients
            WHERE client_id = :client_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':client_id' => $clientId]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ?: null;
}