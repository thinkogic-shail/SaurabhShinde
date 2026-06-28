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
$decodedInput = json_decode($rawInput, true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Invalid JSON input.',
    ]);
}

$citizenRequestId = (int) ($decodedInput['CitizenRequestId'] ?? 0);

if ($citizenRequestId <= 0) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'CitizenRequestId is required.',
    ]);
}

try {
    $pdo = app_pdo();

    $requestStmt = $pdo->prepare(
        'SELECT CitizenRequestId
         FROM CitizenRequest
         WHERE CitizenRequestId = :citizen_request_id and RequestStatusId =1
         LIMIT 1'
    );

    $requestStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);

    if (!$requestStmt->fetch()) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Invalid CitizenRequestId or Not fresh request.',
        ]);
    }

    $attachmentStmt = $pdo->prepare(
        'SELECT AttachmentId, FilePath
         FROM RequestAttachment
         WHERE CitizenRequestId = :citizen_request_id'
    );

    $attachmentStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);

    $attachments = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $pdo->beginTransaction();

    $deleteAttachmentsStmt = $pdo->prepare(
        'DELETE FROM RequestAttachment
         WHERE CitizenRequestId = :citizen_request_id'
    );

    $deleteAttachmentsStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);
    
    $deleteRequestHistoryStmt = $pdo->prepare(
        'DELETE FROM RequestHistory
         WHERE RequestId  = :citizen_request_id'
    );

    $deleteRequestHistoryStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);
    

    $deleteRequestStmt = $pdo->prepare(
        'DELETE FROM CitizenRequest
         WHERE CitizenRequestId = :citizen_request_id'
    );

    $deleteRequestStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);

    $pdo->commit();

    foreach ($attachments as $attachment) {
        $filePath = trim((string) ($attachment['FilePath'] ?? ''));

        if ($filePath === '') {
            continue;
        }

        $absolutePath = __DIR__ . '/../' . $filePath;

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Request deleted successfully.',
        'CitizenRequestId' => $citizenRequestId,
    ]);

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to delete request.',
        'Error' => $exception->getMessage(),
    ]);
}