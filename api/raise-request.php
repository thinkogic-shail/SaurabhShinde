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

function get_uploaded_files(): array
{
    $files = [];

    if (isset($_FILES['Photo']) && $_FILES['Photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $files[] = [
            'name' => $_FILES['Photo']['name'],
            'type' => $_FILES['Photo']['type'],
            'tmp_name' => $_FILES['Photo']['tmp_name'],
            'error' => $_FILES['Photo']['error'],
            'size' => $_FILES['Photo']['size'],
        ];
    }

    if (isset($_FILES['Attachments']) && is_array($_FILES['Attachments']['name'])) {
        foreach ($_FILES['Attachments']['name'] as $index => $name) {
            if ($_FILES['Attachments']['error'][$index] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = [
                'name' => $_FILES['Attachments']['name'][$index],
                'type' => $_FILES['Attachments']['type'][$index],
                'tmp_name' => $_FILES['Attachments']['tmp_name'][$index],
                'error' => $_FILES['Attachments']['error'][$index],
                'size' => $_FILES['Attachments']['size'][$index],
            ];
        }
    }

    return $files;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Only POST method is allowed.',
    ]);
}

$decodedInput = $_POST;

if (!is_array($decodedInput) || empty($decodedInput)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Invalid form data.',
    ]);
}

$citizenUserId = (int) ($decodedInput['CitizenUserId'] ?? 0);
$requestTypeId = (int) ($decodedInput['RequestTypeId'] ?? 0);
$wardId = (int) ($decodedInput['WardId'] ?? 0);
$areaId = (int) ($decodedInput['AreaId'] ?? 0);
$address = trim((string) ($decodedInput['Address'] ?? ''));
$aadhaarNo = trim((string) ($decodedInput['AadhaarNo'] ?? ''));
$description = trim((string) ($decodedInput['Description'] ?? ''));

if ($citizenUserId <= 0) {
    send_json_response(200, ['Success' => false, 'Message' => 'CitizenUserId is required.']);
}

if ($requestTypeId <= 0) {
    send_json_response(200, ['Success' => false, 'Message' => 'RequestTypeId is required.']);
}

if ($wardId <= 0) {
    send_json_response(200, ['Success' => false, 'Message' => 'WardId is required.']);
}

if ($areaId <= 0) {
    send_json_response(200, ['Success' => false, 'Message' => 'AreaId is required.']);
}

if ($address === '') {
    send_json_response(200, ['Success' => false, 'Message' => 'Address is required.']);
}

if (mb_strlen($address) > 200) {
    send_json_response(200, ['Success' => false, 'Message' => 'Address must be 200 characters or fewer.']);
}

if ($aadhaarNo === '') {
    send_json_response(200, ['Success' => false, 'Message' => 'Aadhaar Number is required.']);
}

if (!preg_match('/^[0-9]{12}$/', $aadhaarNo)) {
    send_json_response(200, ['Success' => false, 'Message' => 'Aadhaar Number must contain exactly 12 digits.']);
}

if ($description === '') {
    send_json_response(200, ['Success' => false, 'Message' => 'Description is required.']);
}

$uploadedFiles = get_uploaded_files();

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

$maxFileSize = 5 * 1024 * 1024;

foreach ($uploadedFiles as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        send_json_response(200, ['Success' => false, 'Message' => 'File upload failed.']);
    }

    if ((int) $file['size'] > $maxFileSize) {
        send_json_response(200, ['Success' => false, 'Message' => 'Each file size must be less than 5 MB.']);
    }

    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedMimeTypes[$mimeType])) {
        send_json_response(200, ['Success' => false, 'Message' => 'Only JPG, PNG, and WEBP files are allowed.']);
    }
}

try {
    $pdo = app_pdo();

    $currentDateTimeIST = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))
        ->format('Y-m-d H:i:s');

    $citizenStmt = $pdo->prepare(
        'SELECT CitizenUserId
         FROM CitizenUser
         WHERE CitizenUserId = :citizen_user_id
           AND IsActive = 1
         LIMIT 1'
    );
    $citizenStmt->execute(['citizen_user_id' => $citizenUserId]);

    if (!$citizenStmt->fetch()) {
        send_json_response(200, ['Success' => false, 'Message' => 'Invalid CitizenUserId.']);
    }

    $requestTypeStmt = $pdo->prepare(
        'SELECT RequestTypeId
         FROM RequestTypeMaster
         WHERE RequestTypeId = :request_type_id
           AND IsActive = 1
         LIMIT 1'
    );
    $requestTypeStmt->execute(['request_type_id' => $requestTypeId]);

    if (!$requestTypeStmt->fetch()) {
        send_json_response(200, ['Success' => false, 'Message' => 'Invalid RequestTypeId.']);
    }

    $wardStmt = $pdo->prepare(
        'SELECT WardId
         FROM Ward
         WHERE WardId = :ward_id
           AND IsActive = 1
         LIMIT 1'
    );
    $wardStmt->execute(['ward_id' => $wardId]);

    if (!$wardStmt->fetch()) {
        send_json_response(200, ['Success' => false, 'Message' => 'Invalid WardId.']);
    }

    $areaStmt = $pdo->prepare(
        'SELECT AreaId
         FROM Area
         WHERE AreaId = :area_id
           AND WardId = :ward_id
           AND IsActive = 1
         LIMIT 1'
    );
    $areaStmt->execute([
        'area_id' => $areaId,
        'ward_id' => $wardId,
    ]);

    if (!$areaStmt->fetch()) {
        send_json_response(200, ['Success' => false, 'Message' => 'Invalid AreaId.']);
    }

    $statusStmt = $pdo->prepare(
        'SELECT RequestStatusId
         FROM RequestStatusMaster
         WHERE RequestStatusId = 1
           AND IsActive = 1
         LIMIT 1'
    );
    $statusStmt->execute();

    if (!$statusStmt->fetch()) {
        send_json_response(200, ['Success' => false, 'Message' => 'Raised request status is not configured.']);
    }

    $pdo->beginTransaction();

    $temporaryRequestNo = 'TMP' . date('YmdHis') . bin2hex(random_bytes(4));

    $insertStmt = $pdo->prepare(
        'INSERT INTO CitizenRequest (
            RequestNo,
            CitizenUserId,
            RequestTypeId,
            WardId,
            AreaId,
            Address,
            AadhaarNo,
            Description,
            RequestStatusId,
            RaisedDate,
            CreatedDate,
            IsActive,
            Remark
         ) VALUES (
            :request_no,
            :citizen_user_id,
            :request_type_id,
            :ward_id,
            :area_id,
            :address,
            :aadhaar_no,
            :description,
            1,
            :raised_date,
            :created_date,
            1,
            :remark
         )'
    );

    $insertStmt->execute([
        'request_no' => $temporaryRequestNo,
        'citizen_user_id' => $citizenUserId,
        'request_type_id' => $requestTypeId,
        'ward_id' => $wardId,
        'area_id' => $areaId,
        'address' => $address,
        'aadhaar_no' => $aadhaarNo,
        'description' => $description,
        'raised_date' => $currentDateTimeIST,
        'created_date' => $currentDateTimeIST,
        'remark' => '',
    ]);

    $citizenRequestId = (int) $pdo->lastInsertId();
    $requestNo = 'REQ' . str_pad((string) $citizenRequestId, 5, '0', STR_PAD_LEFT);

    $updateRequestNoStmt = $pdo->prepare(
        'UPDATE CitizenRequest
         SET RequestNo = :request_no
         WHERE CitizenRequestId = :citizen_request_id'
    );
    $updateRequestNoStmt->execute([
        'request_no' => $requestNo,
        'citizen_request_id' => $citizenRequestId,
    ]);

    $historyStmt = $pdo->prepare(
        'INSERT INTO RequestHistory (
            RequestId,
            StatusId,
            Remarks,
            UpdatedBy,
            HistoryDateTime
         ) VALUES (
            :request_id,
            1,
            :remarks,
            NULL,
            :history_datetime
         )'
    );

    $historyStmt->execute([
        'request_id' => $citizenRequestId,
        'remarks' => 'Request raised',
        'history_datetime' => $currentDateTimeIST,
    ]);

    $requestHistoryId = (int) $pdo->lastInsertId();

    $savedAttachments = [];

    if (!empty($uploadedFiles)) {
        $uploadDir = __DIR__ . '/../uploads/request/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $attachmentStmt = $pdo->prepare(
            'INSERT INTO RequestAttachment (
                CitizenRequestId,
                FileName,
                FilePath,
                UploadedOn,
                IsActive
             ) VALUES (
                :citizen_request_id,
                :file_name,
                :file_path,
                :uploaded_on,
                1
             )'
        );

        foreach ($uploadedFiles as $index => $file) {
            $mimeType = mime_content_type($file['tmp_name']);
            $extension = $allowedMimeTypes[$mimeType];

            $fileName = $requestNo . '_' . ($index + 1) . '.' . $extension;
            $relativePath = 'uploads/request/' . $fileName;
            $destinationPath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                throw new RuntimeException('Unable to save uploaded file.');
            }

            $attachmentStmt->execute([
                'citizen_request_id' => $citizenRequestId,
                'file_name' => $fileName,
                'file_path' => $relativePath,
                'uploaded_on' => $currentDateTimeIST,
            ]);

            $savedAttachments[] = [
                'FileName' => $fileName,
                'FilePath' => $relativePath,
            ];
        }
    }

    $pdo->commit();

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Request raised successfully.',
        'CitizenRequestId' => $citizenRequestId,
        'RequestNo' => $requestNo,
        'RequestHistoryId' => $requestHistoryId,
        'Attachments' => $savedAttachments,
    ]);

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to raise request.',
        'Error' => $exception->getMessage(),
    ]);
}