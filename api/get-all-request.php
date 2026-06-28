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

function normalize_date_value(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);

    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
}

function parse_date_range(mixed $dateRange): array
{
    $fromDate = null;
    $toDate = null;

    if (is_array($dateRange)) {
        $fromDate = normalize_date_value($dateRange['FromDate'] ?? $dateRange['from_date'] ?? $dateRange['from'] ?? null);
        $toDate = normalize_date_value($dateRange['ToDate'] ?? $dateRange['to_date'] ?? $dateRange['to'] ?? null);
    } elseif (is_string($dateRange)) {
        $dateRange = trim($dateRange);

        if ($dateRange !== '') {
            $parts = preg_split('/\s*-\s*/', $dateRange);

            if (is_array($parts) && count($parts) === 2) {
                $fromDate = normalize_date_value($parts[0]);
                $toDate = normalize_date_value($parts[1]);
            }
        }
    }

    return [$fromDate, $toDate];
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

$requestType = (int) ($decodedInput['RequestType'] ?? 0);

[$fromDate, $toDate] = parse_date_range(
    $decodedInput['DateRange'] ?? [
        'FromDate' => $decodedInput['FromDate'] ?? null,
        'ToDate' => $decodedInput['ToDate'] ?? null,
    ]
);

if (!in_array($requestType, [1, 2], true)) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Invalid RequestType.',
    ]);
}

if (
    (
        array_key_exists('DateRange', $decodedInput) ||
        array_key_exists('FromDate', $decodedInput) ||
        array_key_exists('ToDate', $decodedInput)
    )
    && ($fromDate === null || $toDate === null)
) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'FromDate and ToDate must be in YYYY-MM-DD format.',
    ]);
}

if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'DateRange is invalid.',
    ]);
}

try {
    $pdo = app_pdo();

    $whereParts = [
        'cr.IsActive = 1',
    ];

    $queryParams = [];

    if ($requestType === 1) {
        $whereParts[] = 'cr.RequestStatusId IN (1, 2)';
    } elseif ($requestType === 2) {
        $whereParts[] = 'cr.RequestStatusId IN (3, 4)';
    }

    if ($fromDate !== null && $toDate !== null) {
        $whereParts[] = 'DATE(cr.RaisedDate) BETWEEN :from_date AND :to_date';
        $queryParams['from_date'] = $fromDate;
        $queryParams['to_date'] = $toDate;
    }

    $stmt = $pdo->prepare(
        'SELECT cr.CitizenRequestId,
                cr.RequestNo,
                cr.RequestTypeId,
                cr.WardId,
                cr.AreaId,
                cr.RequestStatusId,
                cu.Name,
                cu.MobileNo,
                cu.Email,
                cr.AadhaarNo,
                rtm.RequestTypeName,
                w.WardName,
                a.AreaName,
                cr.Address,
                cr.Description,
                cr.Remark,
                rsm.StatusName,
                cr.RaisedDate,
                cr.ClosedDate,
                ra.AttachmentId,
                ra.FileName,
                ra.FilePath,
                ra.UploadedOn
         FROM CitizenRequest cr
         INNER JOIN CitizenUser cu ON cu.CitizenUserId = cr.CitizenUserId
         LEFT JOIN RequestTypeMaster rtm ON rtm.RequestTypeId = cr.RequestTypeId
         LEFT JOIN Ward w ON w.WardId = cr.WardId
         LEFT JOIN Area a ON a.AreaId = cr.AreaId
         LEFT JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         LEFT JOIN RequestAttachment ra
                ON ra.CitizenRequestId = cr.CitizenRequestId
               AND ra.IsActive = 1
         WHERE ' . implode(' AND ', $whereParts) . '
         ORDER BY cr.CitizenRequestId DESC, ra.AttachmentId ASC'
    );

    $stmt->execute($queryParams);

    $rows = $stmt->fetchAll();
    $requestMap = [];

    foreach ($rows as $row) {
        $citizenRequestId = (int) $row['CitizenRequestId'];

        if (!isset($requestMap[$citizenRequestId])) {
            $requestMap[$citizenRequestId] = [
                'CitizenRequestId' => $citizenRequestId,
                'RequestNo' => (string) ($row['RequestNo'] ?? ''),
                'WardId' => isset($row['WardId']) ? (int) $row['WardId'] : null,
                'AreaId' => isset($row['AreaId']) ? (int) $row['AreaId'] : null,
                'RequestTypeId' => isset($row['RequestTypeId']) ? (int) $row['RequestTypeId'] : null,
                'RequestStatusId' => isset($row['RequestStatusId']) ? (int) $row['RequestStatusId'] : null,
                'RequestTypeName' => (string) ($row['RequestTypeName'] ?? ''),
                'Name' => (string) ($row['Name'] ?? ''),
                'MobileNo' => (string) ($row['MobileNo'] ?? ''),
                'Email' => (string) ($row['Email'] ?? ''),
                
                
                'AadhaarNo' => (string) ($row['AadhaarNo'] ?? ''),
                'WardName' => (string) ($row['WardName'] ?? ''),
                'AreaName' => (string) ($row['AreaName'] ?? ''),
                'Address' => (string) ($row['Address'] ?? ''),
                'Description' => (string) ($row['Description'] ?? ''),
                'StatusName' => (string) ($row['StatusName'] ?? ''),
                'Remark' => (string) ($row['Remark'] ?? ''),
                'RaisedDate' => (string) ($row['RaisedDate'] ?? ''),
                'ClosedDate' => (string) ($row['ClosedDate'] ?? ''),
                'Attachments' => [],
            ];
        }

        if (!empty($row['AttachmentId'])) {
            $requestMap[$citizenRequestId]['Attachments'][] = [
                'AttachmentId' => (int) $row['AttachmentId'],
                'FileName' => (string) ($row['FileName'] ?? ''),
                'FilePath' => (string) ($row['FilePath'] ?? ''),
                'UploadedOn' => (string) ($row['UploadedOn'] ?? ''),
            ];
        }
    }

    $data = array_values($requestMap);

    send_json_response(200, [
        'Success' => true,
        'Message' => empty($data)
            ? 'No requests found.'
            : 'Requests fetched successfully.',
        'Data' => $data,
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'Success' => false,
        'Message' => 'Unable to fetch requests.',
    ]);
}
