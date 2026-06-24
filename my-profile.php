<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$employeeId = (int) ($_SESSION['admin_id'] ?? 0);

if ($employeeId <= 0) {
    set_flash_message('danger', 'Your session has expired. Please log in again.');
    header('Location: admin-login.php');
    exit;
}

$profileStmt = $pdo->prepare(
    'SELECT EmployeeId, UserName, UserPassword, MobileNo, Email
     FROM Employee
     WHERE EmployeeId = :employee_id
     LIMIT 1'
);
$profileStmt->execute(['employee_id' => $employeeId]);
$employee = $profileStmt->fetch();

if (!$employee) {
    set_flash_message('danger', 'Employee profile not found.');
    header('Location: dashboard.php');
    exit;
}

$flash = get_flash_message();
$profileErrors = [];
$passwordErrors = [];
$profileData = [
    'UserName' => (string) ($employee['UserName'] ?? ''),
    'MobileNo' => (string) ($employee['MobileNo'] ?? ''),
    'Email' => (string) ($employee['Email'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save_profile') {
        $profileData['MobileNo'] = trim($_POST['mobile_no'] ?? '');
        $profileData['Email'] = trim($_POST['email'] ?? '');

        if ($profileData['MobileNo'] === '') {
            $profileErrors['MobileNo'] = 'Mobile number is required.';
        } elseif (!preg_match('/^[0-9]{10}$/', $profileData['MobileNo'])) {
            $profileErrors['MobileNo'] = 'Mobile number must be exactly 10 digits.';
        }

        if ($profileData['Email'] === '') {
            $profileErrors['Email'] = 'Email address is required.';
        } elseif (!filter_var($profileData['Email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors['Email'] = 'Please enter a valid email address.';
        }

        if ($profileErrors === []) {
            $duplicateMobileStmt = $pdo->prepare(
                'SELECT EmployeeId
                 FROM Employee
                 WHERE MobileNo = :mobile_no
                   AND EmployeeId <> :employee_id
                 LIMIT 1'
            );
            $duplicateMobileStmt->execute([
                'mobile_no' => $profileData['MobileNo'],
                'employee_id' => $employeeId,
            ]);

            if ($duplicateMobileStmt->fetch()) {
                $profileErrors['MobileNo'] = 'Mobile number already exists.';
            }
        }

        if ($profileErrors === []) {
            $updateProfileStmt = $pdo->prepare(
                'UPDATE Employee
                 SET MobileNo = :mobile_no,
                     Email = :email
                 WHERE EmployeeId = :employee_id'
            );
            $updateProfileStmt->execute([
                'mobile_no' => $profileData['MobileNo'],
                'email' => $profileData['Email'],
                'employee_id' => $employeeId,
            ]);

            $_SESSION['admin_mobile'] = $profileData['MobileNo'];
            $_SESSION['admin_name'] = $profileData['UserName'];

            set_flash_message('success', 'Profile updated successfully.');
            header('Location: dashboard.php');
            exit;
        }
    }
}

render_admin_header('My Profile', [], 'my-profile', false);
?>
<style>
    .profile-readonly-value {
        width: 100%;
        min-height: calc(1.5em + 1.25rem + 2px);
        padding: 0.625rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        background-color: #f8fafc;
        font-weight: 600;
        color: #1f2937;
    }
    .page-title-box {
        padding-bottom: 0 !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="page-title-box">
                        <h4 class="mb-1">My Profile</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">My Profile</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="form_action" value="save_profile">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <div class="profile-readonly-value"><?php echo htmlspecialchars($profileData['UserName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="mobile_no" class="form-label">Mobile Number</label>
                            <input
                                type="text"
                                class="form-control<?php echo isset($profileErrors['MobileNo']) ? ' is-invalid' : ''; ?>"
                                id="mobile_no"
                                name="mobile_no"
                                required
                                maxlength="10"
                                value="<?php echo htmlspecialchars($profileData['MobileNo'], ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Enter mobile number"
                                data-parsley-required-message="Mobile number is required."
                                data-parsley-pattern="^[0-9]{10}$"
                                data-parsley-pattern-message="Mobile number must be exactly 10 digits."
                            >
                            <?php if (isset($profileErrors['MobileNo'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($profileErrors['MobileNo'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input
                                type="email"
                                class="form-control<?php echo isset($profileErrors['Email']) ? ' is-invalid' : ''; ?>"
                                id="email"
                                name="email"
                                required
                                maxlength="200"
                                value="<?php echo htmlspecialchars($profileData['Email'], ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="Enter email address"
                                data-parsley-required-message="Email address is required."
                                data-parsley-type="email"
                                data-parsley-type-message="Please enter a valid email address."
                            >
                            <?php if (isset($profileErrors['Email'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($profileErrors['Email'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            Update Profile
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary waves-effect">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        </div>
    </div>
</div>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
