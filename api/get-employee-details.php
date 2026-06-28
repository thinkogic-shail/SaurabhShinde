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
    ]);
}

$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput ?: '', true);

if (!is_array($decodedInput)) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Invalid JSON input.',
    ]);
}

$mobileNumber = trim((string) ($decodedInput['mobile_number'] ?? ''));

if ($mobileNumber === '') {
    send_json_response(200, [
        'success' => false,
        'message' => 'Mobile number is required.',
    ]);
}

if (!preg_match('/^[0-9]{10}$/', $mobileNumber)) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Mobile number must be exactly 10 digits.',
    ]);
}

try {
    $pdo = app_pdo();
    $stmt = $pdo->prepare(
        'SELECT e.EmployeeId,
                e.UserName,
                e.MobileNo,
                e.Email,
                e.RoleId,
                COALESCE(r.RoleName, \'\') AS RoleName,
                e.IsMobileVerified,
                e.IsActive,
                e.CreatedDate
         FROM Employee e
         LEFT JOIN RoleMaster r ON r.RoleId = e.RoleId
         WHERE e.MobileNo = :mobile_no
           AND e.IsActive = 1
         LIMIT 1'
    );
    $stmt->execute([
        'mobile_no' => $mobileNumber,
    ]);

    $employee = $stmt->fetch();

    if (!$employee) {
        send_json_response(200, [
            'success' => false,
            'message' => 'Employee not found.',
        ]);
    }

    send_json_response(200, [
        'success' => true,
        'message' => 'Employee details fetched successfully.',
        'data' => [
            'employee_id' => (int) $employee['EmployeeId'],
            'username' => (string) ($employee['UserName'] ?? ''),
            'mobile_number' => (string) ($employee['MobileNo'] ?? ''),
            'email' => (string) ($employee['Email'] ?? ''),
            'role_id' => isset($employee['RoleId']) ? (int) $employee['RoleId'] : null,
            'role_name' => (string) ($employee['RoleName'] ?? ''),
            'mobile_verified' => (int) ($employee['IsMobileVerified'] ?? 0) === 1,
            'status' => (int) ($employee['IsActive'] ?? 0) === 1 ? 'Active' : 'Inactive',
            'created_date' => (string) ($employee['CreatedDate'] ?? ''),
        ],
    ]);
} catch (Throwable $exception) {
    send_json_response(200, [
        'success' => false,
        'message' => 'Unable to fetch employee details.',
    ]);
}
