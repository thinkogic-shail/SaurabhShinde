<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $userId > 0;
$defaultRoleIdStmt = $pdo->query(
    "SELECT RoleId
     FROM RoleMaster
     WHERE IsActive = 1
       AND LOWER(TRIM(RoleName)) = 'admin'
     LIMIT 1"
);
$defaultRoleId = (int) $defaultRoleIdStmt->fetchColumn();
$user = [
    'UserName' => '',
    'UserPassword' => '',
    'MobileNo' => '',
    'Email' => '',
    'RoleId' => $defaultRoleId > 0 ? (string) $defaultRoleId : '',
    'IsMobileVerified' => '0',
    'IsActive' => '1',
];
$errors = [];

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT EmployeeId, UserName, MobileNo, Email, RoleId, IsMobileVerified, IsActive
         FROM Employee
         WHERE EmployeeId = :id'
    );
    $stmt->execute(['id' => $userId]);
    $existingUser = $stmt->fetch();

    if (!$existingUser) {
        set_flash_message('danger', 'Selected user record was not found.');
        header('Location: users.php');
        exit;
    }

    $user = [
        'UserName' => (string) $existingUser['UserName'],
        'UserPassword' => '',
        'MobileNo' => (string) $existingUser['MobileNo'],
        'Email' => (string) ($existingUser['Email'] ?? ''),
        'RoleId' => (string) $existingUser['RoleId'],
        'IsMobileVerified' => (string) $existingUser['IsMobileVerified'],
        'IsActive' => (string) $existingUser['IsActive'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? $userId);
    $isEditMode = $userId > 0;

    $user['UserName'] = trim($_POST['user_name'] ?? '');
    $user['UserPassword'] = (string) ($_POST['user_password'] ?? '');
    $user['MobileNo'] = trim($_POST['mobile_no'] ?? '');
    $user['Email'] = trim($_POST['email'] ?? '');
    $user['IsMobileVerified'] = (string) ($_POST['is_mobile_verified'] ?? '0');
    $user['IsActive'] = (string) ($_POST['is_active'] ?? '1');

    if ($user['UserName'] === '') {
        $errors['UserName'] = 'Username is required.';
    } elseif (mb_strlen($user['UserName']) > 100) {
        $errors['UserName'] = 'Username must be 100 characters or fewer.';
    }

    if (!$isEditMode && trim($user['UserPassword']) === '') {
        $errors['UserPassword'] = 'Password is required.';
    }

    if ($user['MobileNo'] === '') {
        $errors['MobileNo'] = 'Mobile number is required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $user['MobileNo'])) {
        $errors['MobileNo'] = 'Mobile number must be exactly 10 digits.';
    }

    if ($user['Email'] === '') {
        $errors['Email'] = 'Email address is required.';
    } elseif (!filter_var($user['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors['Email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($user['Email']) > 200) {
        $errors['Email'] = 'Email address must be 200 characters or fewer.';
    }

    if (!in_array($user['IsMobileVerified'], ['0', '1'], true)) {
        $errors['IsMobileVerified'] = 'Please select mobile verification status.';
    }

    if (!in_array($user['IsActive'], ['0', '1'], true)) {
        $errors['IsActive'] = 'Please select a valid status.';
    }

    if ($errors === []) {
        if (!$isEditMode && $defaultRoleId <= 0) {
            $errors['RoleId'] = 'No active role is available for new users.';
        }
    }

    if ($errors === []) {
        $duplicateUserStmt = $pdo->prepare(
            'SELECT EmployeeId
             FROM Employee
             WHERE LOWER(TRIM(UserName)) = LOWER(:user_name)
               AND EmployeeId <> :employee_id
             LIMIT 1'
        );
        $duplicateUserStmt->execute([
            'user_name' => $user['UserName'],
            'employee_id' => $userId,
        ]);

        if ($duplicateUserStmt->fetch()) {
            $errors['UserName'] = 'Username already exists.';
        }
    }

    if ($errors === []) {
        $duplicateMobileStmt = $pdo->prepare(
            'SELECT EmployeeId
             FROM Employee
             WHERE TRIM(MobileNo) = :mobile_no
               AND EmployeeId <> :employee_id
             LIMIT 1'
        );
        $duplicateMobileStmt->execute([
            'mobile_no' => $user['MobileNo'],
            'employee_id' => $userId,
        ]);

        if ($duplicateMobileStmt->fetch()) {
            $errors['MobileNo'] = 'Mobile number already exists.';
        }
    }

    if ($errors === []) {
        $duplicateEmailStmt = $pdo->prepare(
            'SELECT EmployeeId
             FROM Employee
             WHERE LOWER(TRIM(Email)) = LOWER(:email)
               AND EmployeeId <> :employee_id
             LIMIT 1'
        );
        $duplicateEmailStmt->execute([
            'email' => $user['Email'],
            'employee_id' => $userId,
        ]);

        if ($duplicateEmailStmt->fetch()) {
            $errors['Email'] = 'Email address already exists.';
        }
    }

    if ($errors === []) {
        if ($isEditMode) {
            if (trim($user['UserPassword']) !== '') {
                $updateStmt = $pdo->prepare(
                    'UPDATE Employee
                     SET UserName = :user_name,
                         UserPassword = :user_password,
                         MobileNo = :mobile_no,
                         Email = :email,
                         RoleId = :role_id,
                         IsMobileVerified = :is_mobile_verified,
                         IsActive = :is_active
                     WHERE EmployeeId = :employee_id'
                );
                $updateStmt->execute([
                    'user_name' => $user['UserName'],
                    'user_password' => password_hash($user['UserPassword'], PASSWORD_DEFAULT),
                    'mobile_no' => $user['MobileNo'],
                    'email' => $user['Email'],
                    'role_id' => (int) $user['RoleId'],
                    'is_mobile_verified' => (int) $user['IsMobileVerified'],
                    'is_active' => (int) $user['IsActive'],
                    'employee_id' => $userId,
                ]);
            } else {
                $updateStmt = $pdo->prepare(
                    'UPDATE Employee
                     SET UserName = :user_name,
                         MobileNo = :mobile_no,
                         Email = :email,
                         RoleId = :role_id,
                         IsMobileVerified = :is_mobile_verified,
                         IsActive = :is_active
                     WHERE EmployeeId = :employee_id'
                );
                $updateStmt->execute([
                    'user_name' => $user['UserName'],
                    'mobile_no' => $user['MobileNo'],
                    'email' => $user['Email'],
                    'role_id' => (int) $user['RoleId'],
                    'is_mobile_verified' => (int) $user['IsMobileVerified'],
                    'is_active' => (int) $user['IsActive'],
                    'employee_id' => $userId,
                ]);
            }

            set_flash_message('success', 'User updated successfully.');
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO Employee (UserName, UserPassword, MobileNo, Email, RoleId, IsMobileVerified, IsActive)
                 VALUES (:user_name, :user_password, :mobile_no, :email, :role_id, :is_mobile_verified, :is_active)'
            );
            $insertStmt->execute([
                'user_name' => $user['UserName'],
                'user_password' => password_hash($user['UserPassword'], PASSWORD_DEFAULT),
                'mobile_no' => $user['MobileNo'],
                'email' => $user['Email'],
                'role_id' => $defaultRoleId,
                'is_mobile_verified' => (int) $user['IsMobileVerified'],
                'is_active' => (int) $user['IsActive'],
            ]);

            set_flash_message('success', 'User added successfully.');
        }

        header('Location: users.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit User' : 'Add User';

render_admin_header($pageTitle, [], 'user', false);
?>
<style>
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .user-form-field {
        max-width: 420px;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="page-title-box">
                        <h4 class="mb-1"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="users.php">User Management</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" autocomplete="off" novalidate>
                    <input type="hidden" name="user_id" value="<?php echo (int) $userId; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="user-form-field">
                                <label for="user_name" class="form-label">Username</label>
                                <input
                                    type="text"
                                    class="form-control<?php echo isset($errors['UserName']) ? ' is-invalid' : ''; ?>"
                                    id="user_name"
                                    name="user_name"
                                    required
                                    maxlength="100"
                                    value="<?php echo htmlspecialchars($user['UserName'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter username"
                                    autocomplete="off"
                                    data-parsley-required-message="Username is required."
                                    data-parsley-maxlength="100"
                                    data-parsley-maxlength-message="Username must be 100 characters or fewer."
                                    data-parsley-whitespace="trim"
                                >
                                <?php if (isset($errors['UserName'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['UserName'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="user-form-field">
                                <label for="user_password" class="form-label">Password</label>
                                <input
                                    type="password"
                                    class="form-control<?php echo isset($errors['UserPassword']) ? ' is-invalid' : ''; ?>"
                                    id="user_password"
                                    name="user_password"
                                    <?php echo $isEditMode ? '' : 'required'; ?>
                                    placeholder="<?php echo $isEditMode ? 'Leave blank to keep current password' : 'Enter password'; ?>"
                                    autocomplete="new-password"
                                    data-parsley-required-message="Password is required."
                                >
                                <?php if (isset($errors['UserPassword'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['UserPassword'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="user-form-field">
                                <label for="mobile_no" class="form-label">Mobile Number</label>
                                <input
                                    type="text"
                                    class="form-control<?php echo isset($errors['MobileNo']) ? ' is-invalid' : ''; ?>"
                                    id="mobile_no"
                                    name="mobile_no"
                                    required
                                    maxlength="10"
                                    value="<?php echo htmlspecialchars($user['MobileNo'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter mobile number"
                                    data-parsley-required-message="Mobile number is required."
                                    data-parsley-pattern="^[0-9]{10}$"
                                    data-parsley-pattern-message="Mobile number must be exactly 10 digits."
                                >
                                <?php if (isset($errors['MobileNo'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['MobileNo'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="user-form-field">
                                <label for="email" class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    class="form-control<?php echo isset($errors['Email']) ? ' is-invalid' : ''; ?>"
                                    id="email"
                                    name="email"
                                    required
                                    maxlength="200"
                                    value="<?php echo htmlspecialchars($user['Email'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter email address"
                                    data-parsley-required-message="Email address is required."
                                    data-parsley-type="email"
                                    data-parsley-type-message="Please enter a valid email address."
                                >
                                <?php if (isset($errors['Email'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['Email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="users.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    window.Parsley.addValidator('whitespace', {
        validateString: function (value) {
            return value.trim().length > 0;
        },
        messages: {
            en: 'Username is required.'
        }
    });

    document.getElementById('mobile_no').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
