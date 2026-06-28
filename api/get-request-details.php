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
        'SELECT cr.CitizenRequestId,
                cr.RequestNo,
                cr.CitizenUserId,
                cr.Address,
                cr.AadhaarNo,
                cr.Description,
                cr.Remark,
                cr.RaisedDate,
                cr.ClosedDate,
                cu.Name AS CitizenName,
                cu.MobileNo,
                rtm.RequestTypeName,
                w.WardName,
                a.AreaName,
                rsm.StatusName
         FROM CitizenRequest cr
         LEFT JOIN CitizenUser cu ON cu.CitizenUserId = cr.CitizenUserId
         LEFT JOIN RequestTypeMaster rtm ON rtm.RequestTypeId = cr.RequestTypeId
         LEFT JOIN Ward w ON w.WardId = cr.WardId
         LEFT JOIN Area a ON a.AreaId = cr.AreaId
         LEFT JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE cr.CitizenRequestId = :citizen_request_id
           AND cr.IsActive = 1
         LIMIT 1'
    );
    $requestStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);

    $request = $requestStmt->fetch();

    if (!$request) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Request not found.',
        ]);
    }

    $attachmentsStmt = $pdo->prepare(
        'SELECT AttachmentId, FileName, FilePath
         FROM RequestAttachment
         WHERE CitizenRequestId = :citizen_request_id
           AND IsActive = 1
         ORDER BY AttachmentId ASC'
    );
    $attachmentsStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);

    $attachments = $attachmentsStmt->fetchAll();
    $attachmentData = [];

    foreach ($attachments as $attachment) {
        $attachmentData[] = [
            'AttachmentId' => (int) $attachment['AttachmentId'],
            'FileName' => (string) ($attachment['FileName'] ?? ''),
            'FilePath' => (string) ($attachment['FilePath'] ?? ''),
        ];
    }

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Request details fetched successfully.',
        'Data' => [
            'CitizenRequestId' => (int) $request['CitizenRequestId'],
            'RequestNo' => (string) ($request['RequestNo'] ?? ''),
            'RequestTypeName' => (string) ($request['RequestTypeName'] ?? ''),
            'WardName' => (string) ($request['WardName'] ?? ''),
            'AreaName' => (string) ($request['AreaName'] ?? ''),
            'Address' => (string) ($request['Address'] ?? ''),
            'AadhaarNo' => (string) ($request['AadhaarNo'] ?? ''),
            'Description' => (string) ($request['Description'] ?? ''),
            'StatusName' => (string) ($request['StatusName'] ?? ''),
            'Remark' => (string) ($request['Remark'] ?? ''),
            'RaisedDate' => (string) ($request['RaisedDate'] ?? ''),
            'ClosedDate' => (string) ($request['ClosedDate'] ?? ''),
            'CitizenUserId' => (int) $request['CitizenUserId'],
            'CitizenName' => (string) ($request['CitizenName'] ?? ''),
            'MobileNo' => (string) ($request['MobileNo'] ?? ''),
            'Attachments' => $attachmentData,
        ],
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to fetch request details.',
    ]);
}
