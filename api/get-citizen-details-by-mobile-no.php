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

function build_profile_photo_url(?string $relativePath): ?string
{
    $relativePath = trim((string) $relativePath);

    if ($relativePath === '') {
        return null;
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
        'Success' => false,
        'Message' => 'Only POST method is allowed.',
    ]);
}

$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput ?: '', true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Invalid JSON input.',
    ]);
}

$mobileNo = trim((string) ($decodedInput['MobileNo'] ?? ''));

if ($mobileNo === '') {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Mobile Number is required.',
    ]);
}

if (!preg_match('/^[0-9]{10}$/', $mobileNo)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Mobile Number must contain exactly 10 digits.',
    ]);
}

try {
    $pdo = app_pdo();
    $stmt = $pdo->prepare(
        'SELECT cu.CitizenUserId,
                cu.MobileNo,
                cu.Name,
                cu.AadhaarNo,
                cu.Email,
                cu.GenderId,
                gm.GenderName,
                cu.AgeCategoryId,
                acm.CategoryName AS AgeCategoryName,
                cu.ProfilePhoto,
                cu.IsMobileVerified,
                cu.IsAdmin
         FROM CitizenUser cu
         LEFT JOIN GenderMaster gm ON gm.GenderId = cu.GenderId
         LEFT JOIN AgeCategoryMaster acm ON acm.AgeCategoryId = cu.AgeCategoryId
         WHERE cu.MobileNo = :mobile_no
           AND cu.IsActive = 1
         LIMIT 1'
    );
    $stmt->execute([
        'mobile_no' => $mobileNo,
    ]);

    $citizen = $stmt->fetch();

    if (!$citizen) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Citizen not found.',
        ]);
    }

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Citizen details fetched successfully.',
        'Data' => [
            'CitizenUserId' => (int) $citizen['CitizenUserId'],
            'MobileNo' => (string) ($citizen['MobileNo'] ?? ''),
            'Name' => (string) ($citizen['Name'] ?? ''),
            'AadhaarNo' => (string) ($citizen['AadhaarNo'] ?? ''),
            'Email' => (string) ($citizen['Email'] ?? ''),
            'GenderId' => isset($citizen['GenderId']) ? (int) $citizen['GenderId'] : null,
            'GenderName' => (string) ($citizen['GenderName'] ?? ''),
            'AgeCategoryId' => isset($citizen['AgeCategoryId']) ? (int) $citizen['AgeCategoryId'] : null,
            'AgeCategoryName' => (string) ($citizen['AgeCategoryName'] ?? ''),
            // 'ProfilePhoto' => build_profile_photo_url($citizen['ProfilePhoto'] ?? null),
            'ProfilePhoto' => (string)($citizen['ProfilePhoto'] ?? null),
            'IsMobileVerified' => (int) ($citizen['IsMobileVerified'] ?? 0) === 1,
            'IsAdmin' => (int) ($citizen['IsAdmin'] ?? 0) === 1,
        ],
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to fetch citizen details.',
    ]);
}
