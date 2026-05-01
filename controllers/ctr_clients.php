<?php
require_once __DIR__ . '/../models/mdl_clients.php';



$searchTerm = trim($_GET['search'] ?? '');
$clients = ($searchTerm !== '') ? searchClients($pdo, $searchTerm) : getAllClients($pdo);

require_once __DIR__ . '/../views/vw_clients.php';
