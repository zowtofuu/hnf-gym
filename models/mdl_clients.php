<?php
require_once __DIR__ . '/../config/database.php';

// Fetch all clients from the database
function getAllClients(PDO $pdo)
{
    $sql = "SELECT 
            client_id,
            first_name,
            last_name,
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
    $sql = "SELECT client_id, first_name, last_name, contact
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