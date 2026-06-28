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

function build_asset_url(string $relativePath): string
{
    $relativePath = trim($relativePath);

    if ($relativePath === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $relativePath) === 1) {
        return $relativePath;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');

    $scheme = $isHttps ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim(str_replace('/api', '', dirname($scriptName)), '/');

    return $scheme . '://' . $host . $basePath . '/' . ltrim($relativePath, '/');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(200, [
        'success' => false,
        'message' => 'Only POST method is allowed.',
        'data' => null,
    ]);
}

$rawInput = file_get_contents('php://input');

if ($rawInput === false) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Unable to read request body.',
        'data' => null,
    ]);
}

$trimmedInput = trim($rawInput);
$decodedInput = $trimmedInput === '' ? [] : json_decode($trimmedInput, true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Invalid JSON input.',
        'data' => null,
    ]);
}

try {
    $pdo = app_pdo();
    $stmt = $pdo->prepare(
        'SELECT FoundationId, FoundationName, AboutFoundation, ContactNo1, ContactNo2, Logo
         FROM FoundationInfo
         WHERE IsActive = 1
         ORDER BY FoundationId DESC
         LIMIT 1'
    );
    $stmt->execute();

    $foundationInfo = $stmt->fetch();

    if (!$foundationInfo) {
        send_json_response(200, [
            'success' => false,
            'message' => 'No active foundation information found.',
            'data' => null,
        ]);
    }

    send_json_response(200, [
        'success' => true,
        'message' => 'Foundation information fetched successfully.',
        'data' => [
            'FoundationId' => (int) $foundationInfo['FoundationId'],
            'FoundationName' => (string) ($foundationInfo['FoundationName'] ?? ''),
            'AboutFoundation' => (string) ($foundationInfo['AboutFoundation'] ?? ''),
            'ContactNo1' => (string) ($foundationInfo['ContactNo1'] ?? ''),
            'ContactNo2' => (string) ($foundationInfo['ContactNo2'] ?? ''),
            'Logo' => build_asset_url((string) ($foundationInfo['Logo'] ?? '')),
        ],
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Unable to fetch foundation information.',
        'data' => null,
    ]);
}
