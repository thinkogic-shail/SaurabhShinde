<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/db.php';

function send_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Only POST method is allowed.',
    ]);
}

try {
    $pdo = app_pdo();

    $stmt = $pdo->query(
        'SELECT
            COUNT(CASE WHEN RequestStatusId = 1 THEN 1 END) AS OpenCount,
            COUNT(CASE WHEN RequestStatusId = 2 THEN 1 END) AS InProgressCount,
            COUNT(CASE WHEN RequestStatusId = 3 THEN 1 END) AS CompletedCount,
            COUNT(CASE WHEN RequestStatusId = 4 THEN 1 END) AS DeclinedCount,
            COUNT(*) AS TotalCount
         FROM CitizenRequest
         WHERE IsActive = 1'
    );

    $counts = $stmt->fetch();

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Dashboard counts fetched successfully.',
        'Data' => [
            'OpenCount' => (int) ($counts['OpenCount'] ?? 0),
            'InProgressCount' => (int) ($counts['InProgressCount'] ?? 0),
            'CompletedCount' => (int) ($counts['CompletedCount'] ?? 0),
            'DeclinedCount' => (int) ($counts['DeclinedCount'] ?? 0),
            'TotalCount' => (int) ($counts['TotalCount'] ?? 0),
        ],
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to fetch dashboard counts.',
    ]);
}