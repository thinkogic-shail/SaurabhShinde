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
$requestStatusId = (int) ($decodedInput['RequestStatusId'] ?? 0);
$updatedBy = (int) ($decodedInput['UpdatedBy'] ?? 0);
$remark = trim((string) ($decodedInput['Remark'] ?? ''));

if ($citizenRequestId <= 0) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'CitizenRequestId is required.',
    ]);
}

if ($requestStatusId <= 0) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'RequestStatusId is required.',
    ]);
}

if ($updatedBy <= 0) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'UpdatedBy is required.',
    ]);
}

try {
    $pdo = app_pdo();

    $statusStmt = $pdo->prepare(
        'SELECT RequestStatusId
         FROM RequestStatusMaster
         WHERE RequestStatusId = :request_status_id
           AND IsActive = 1
         LIMIT 1'
    );

    $statusStmt->execute([
        'request_status_id' => $requestStatusId,
    ]);

    if (!$statusStmt->fetch()) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Invalid RequestStatusId.',
        ]);
    }

    $employeeStmt = $pdo->prepare(
        'SELECT EmployeeId
         FROM Employee
         WHERE EmployeeId = :employee_id
         LIMIT 1'
    );

    $employeeStmt->execute([
        'employee_id' => $updatedBy,
    ]);

    if (!$employeeStmt->fetch()) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Invalid UpdatedBy employee.',
        ]);
    }

    $requestStmt = $pdo->prepare(
        'SELECT CitizenRequestId
         FROM CitizenRequest
         WHERE CitizenRequestId = :citizen_request_id
           AND IsActive = 1
         LIMIT 1'
    );

    $requestStmt->execute([
        'citizen_request_id' => $citizenRequestId,
    ]);

    if (!$requestStmt->fetch()) {
        send_json_response(200, [
            'Success' => false,
            'Message' => 'Citizen request not found.',
        ]);
    }

    $currentDateTimeIST = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))
        ->format('Y-m-d H:i:s');

    $closedDate = in_array($requestStatusId, [3, 4], true) ? $currentDateTimeIST : null;

    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare(
        'UPDATE CitizenRequest
         SET RequestStatusId = :request_status_id,
             Remark = :remark,
             ClosedDate = :closed_date
         WHERE CitizenRequestId = :citizen_request_id
           AND IsActive = 1'
    );

    $updateStmt->execute([
        'request_status_id' => $requestStatusId,
        'remark' => $remark,
        'closed_date' => $closedDate,
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
            :status_id,
            :remarks,
            :updated_by,
            :history_datetime
         )'
    );

    $historyStmt->execute([
        'request_id' => $citizenRequestId,
        'status_id' => $requestStatusId,
        'remarks' => $remark,
        'updated_by' => $updatedBy,
        'history_datetime' => $currentDateTimeIST,
    ]);

    $requestHistoryId = (int) $pdo->lastInsertId();

    $pdo->commit();

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Citizen request updated successfully.',
        'Data' => [
            'CitizenRequestId' => $citizenRequestId,
            'RequestStatusId' => $requestStatusId,
            'Remark' => $remark,
            'UpdatedBy' => $updatedBy,
            'ClosedDate' => $closedDate,
            'RequestHistoryId' => $requestHistoryId,
        ],
    ]);

} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to update citizen request.',
        'Error' => $exception->getMessage(),
    ]);
}