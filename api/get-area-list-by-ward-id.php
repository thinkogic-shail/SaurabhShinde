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

$decodedInput = json_decode($rawInput ?: '', true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Invalid JSON input.',
        'data' => [],
    ]);
}

$wardId = (int) ($decodedInput['WardId'] ?? 0);

if ($wardId <= 0) {
    send_json_response(200, [
        'success' => false,
        'message' => 'WardId is required.',
        'data' => [],
    ]);
}

try {
    $pdo = app_pdo();
    $stmt = $pdo->prepare(
        'SELECT AreaId, AreaName
         FROM Area
         WHERE WardId = :ward_id
           AND IsActive = 1
         ORDER BY AreaName ASC'
    );
    $stmt->execute([
        'ward_id' => $wardId,
    ]);

    $areas = $stmt->fetchAll();
    $data = [];

    foreach ($areas as $area) {
        $data[] = [
            'AreaId' => (int) $area['AreaId'],
            'AreaName' => (string) ($area['AreaName'] ?? ''),
        ];
    }

    send_json_response(200, [
        'success' => true,
        'message' => empty($data) ? 'No active areas found for the selected ward.' : 'Area list fetched successfully.',
        'data' => $data,
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Unable to fetch area list.',
        'data' => [],
    ]);
}
