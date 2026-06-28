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
        'success' => false,
        'message' => 'Only POST method is allowed.',
        'data' => [],
    ]);
}

$rawInput = file_get_contents('php://input');

if ($rawInput === false) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Unable to read request body.',
        'data' => [],
    ]);
}

$trimmedInput = trim($rawInput);
$decodedInput = $trimmedInput === '' ? [] : json_decode($trimmedInput, true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Invalid JSON input.',
        'data' => [],
    ]);
}

try {
    $pdo = app_pdo();
    $stmt = $pdo->prepare(
        'SELECT WardId, WardName
         FROM Ward
         WHERE IsActive = 1 
         and WardId in (SELECT WardId from Area WHERE IsActive=1)
         ORDER BY WardName ASC'
    );
    $stmt->execute();

    $wards = $stmt->fetchAll();
    $data = [];

    foreach ($wards as $ward) {
        $data[] = [
            'WardId' => (int) $ward['WardId'],
            'WardName' => (string) ($ward['WardName'] ?? ''),
        ];
    }

    send_json_response(200, [
        'success' => true,
        'message' => empty($data) ? 'No active wards found.' : 'Ward list fetched successfully.',
        'data' => $data,
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Unable to fetch ward list.',
        'data' => [],
    ]);
}
