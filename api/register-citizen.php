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

$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput ?: '', true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Invalid JSON input.',
    ]);
}

$mobileNo = trim((string) ($decodedInput['MobileNo'] ?? ''));
$name = trim((string) ($decodedInput['Name'] ?? ''));
$aadhaarNo = trim((string) ($decodedInput['AadhaarNo'] ?? ''));
$email = trim((string) ($decodedInput['Email'] ?? ''));
$genderId = (int) ($decodedInput['GenderId'] ?? 0);
$ageCategoryId = (int) ($decodedInput['AgeCategoryId'] ?? 0);

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

if ($aadhaarNo !== '' && !preg_match('/^[0-9]{12}$/', $aadhaarNo)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Aadhaar Number must contain exactly 12 digits.',
    ]);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Email is invalid.',
    ]);
}

try {
    $pdo = app_pdo();

    $duplicateMobileStmt = $pdo->prepare(
        'SELECT CitizenUserId
         FROM CitizenUser
         WHERE MobileNo = :mobile_no
         LIMIT 1'
    );
    $duplicateMobileStmt->execute([
        'mobile_no' => $mobileNo,
    ]);

    if ($duplicateMobileStmt->fetch()) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Mobile Number already exists.',
        ]);
    }

    if ($genderId > 0) {
        $genderStmt = $pdo->prepare(
            'SELECT GenderId
             FROM GenderMaster
             WHERE GenderId = :gender_id
               AND IsActive = 1
             LIMIT 1'
        );
        $genderStmt->execute([
            'gender_id' => $genderId,
        ]);

        if (!$genderStmt->fetch()) {
            send_json_response(200, [
                'Success' => false,
                'Message' => 'Invalid GenderId.',
            ]);
        }
    }

    if ($ageCategoryId > 0) {
        $ageCategoryStmt = $pdo->prepare(
            'SELECT AgeCategoryId
             FROM AgeCategoryMaster
             WHERE AgeCategoryId = :age_category_id
               AND IsActive = 1
             LIMIT 1'
        );
        $ageCategoryStmt->execute([
            'age_category_id' => $ageCategoryId,
        ]);

        if (!$ageCategoryStmt->fetch()) {
            send_json_response(200, [
                'Success' => false,
                'Message' => 'Invalid AgeCategoryId.',
            ]);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO CitizenUser (
            MobileNo,
            Name,
            AadhaarNo,
            Email,
            IsAdmin,
            GenderId,
            AgeCategoryId,
            ProfilePhoto,
            IsMobileVerified,
            CreatedDate,
            UpdatedDate,
            IsActive
         ) VALUES (
            :mobile_no,
            :name,
            :aadhaar_no,
            :email,
            0,
            :gender_id,
            :age_category_id,
            NULL,
            1,
            NOW(),
            NOW(),
            1
         )'
    );
    $stmt->execute([
        'mobile_no' => $mobileNo,
        'name' => $name,
        'aadhaar_no' => $aadhaarNo === '' ? null : $aadhaarNo,
        'email' => $email === '' ? null : $email,
        'gender_id' => $genderId > 0 ? $genderId : null,
        'age_category_id' => $ageCategoryId > 0 ? $ageCategoryId : null,
    ]);

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Citizen registered successfully.',
        'CitizenUserId' => (int) $pdo->lastInsertId(),
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to register citizen.',
    ]);
}
