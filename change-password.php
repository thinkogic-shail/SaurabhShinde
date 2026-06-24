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

$passwordStmt = $pdo->prepare(
    'SELECT EmployeeId, UserPassword
     FROM Employee
     WHERE EmployeeId = :employee_id
     LIMIT 1'
);
$passwordStmt->execute(['employee_id' => $employeeId]);
$employee = $passwordStmt->fetch();

if (!$employee) {
    set_flash_message('danger', 'Employee profile not found.');
    header('Location: dashboard.php');
    exit;
}

$flash = get_flash_message();
$errors = [];
$formData = [
    'CurrentPassword' => '',
    'NewPassword' => '',
    'ConfirmPassword' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['CurrentPassword'] = trim((string) ($_POST['current_password'] ?? ''));
    $formData['NewPassword'] = trim((string) ($_POST['new_password'] ?? ''));
    $formData['ConfirmPassword'] = trim((string) ($_POST['confirm_password'] ?? ''));

    if ($formData['CurrentPassword'] === '') {
        $errors['CurrentPassword'] = 'Current Password is required.';
    }

    if ($formData['NewPassword'] === '') {
        $errors['NewPassword'] = 'New Password is required.';
    }

    if ($formData['ConfirmPassword'] === '') {
        $errors['ConfirmPassword'] = 'Confirm Password is required.';
    }

    $storedPassword = (string) ($employee['UserPassword'] ?? '');
    $isCurrentPasswordValid = false;

    if (!isset($errors['CurrentPassword'])) {
        $isCurrentPasswordValid = password_verify($formData['CurrentPassword'], $storedPassword)
            || hash_equals($storedPassword, $formData['CurrentPassword']);

        if (!$isCurrentPasswordValid) {
            $errors['CurrentPassword'] = 'Current Password is incorrect.';
        }
    }

    if (
        !isset($errors['CurrentPassword'])
        && !isset($errors['NewPassword'])
        && $formData['CurrentPassword'] === $formData['NewPassword']
    ) {
        $errors['NewPassword'] = 'New Password must be different from the Current Password.';
    }

    if (
        !isset($errors['NewPassword'])
        && !isset($errors['ConfirmPassword'])
        && $formData['NewPassword'] !== $formData['ConfirmPassword']
    ) {
        $errors['ConfirmPassword'] = 'New Password and Confirm Password do not match.';
    }

    if ($errors === []) {
        $updatePasswordStmt = $pdo->prepare(
            'UPDATE Employee
             SET UserPassword = :user_password
             WHERE EmployeeId = :employee_id'
        );
        $updatePasswordStmt->execute([
            'user_password' => password_hash($formData['NewPassword'], PASSWORD_DEFAULT),
            'employee_id' => $employeeId,
        ]);

        set_flash_message('success', 'Password updated successfully.');
        header('Location: dashboard.php');
        exit;
    }
}

render_admin_header('Change Password', [], 'change-password', false);
?>
<style>
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .password-form-field {
        max-width: 420px;
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

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="page-title-box">
                        <h4 class="mb-1">Change Password</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Change Password</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="password-form-field">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="position-relative">
                                    <input
                                        type="password"
                                        class="form-control<?php echo isset($errors['CurrentPassword']) ? ' is-invalid' : ''; ?>"
                                        id="current_password"
                                        name="current_password"
                                        required
                                        value="<?php echo htmlspecialchars($formData['CurrentPassword'], ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Enter current password"
                                        data-parsley-required-message="Current Password is required."
                                        data-parsley-whitespace="trim"
                                        style="padding-right: 40px;"
                                    >
                                    <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-decoration-none text-muted" type="button" id="toggle-current-password" style="padding: 0.47rem 0.75rem; border: none; box-shadow: none; background: transparent; z-index: 5;" tabindex="-1">
                                        <i class="ri-eye-off-line"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['CurrentPassword'])): ?>
                                    <div class="text-danger mt-1" style="font-size: .875em;"><?php echo htmlspecialchars($errors['CurrentPassword'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="password-form-field">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="position-relative">
                                    <input
                                        type="password"
                                        class="form-control<?php echo isset($errors['NewPassword']) ? ' is-invalid' : ''; ?>"
                                        id="new_password"
                                        name="new_password"
                                        required
                                        value="<?php echo htmlspecialchars($formData['NewPassword'], ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Enter new password"
                                        data-parsley-required-message="New Password is required."
                                        data-parsley-whitespace="trim"
                                        style="padding-right: 40px;"
                                    >
                                    <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-decoration-none text-muted" type="button" id="toggle-new-password" style="padding: 0.47rem 0.75rem; border: none; box-shadow: none; background: transparent; z-index: 5;" tabindex="-1">
                                        <i class="ri-eye-off-line"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['NewPassword'])): ?>
                                    <div class="text-danger mt-1" style="font-size: .875em;"><?php echo htmlspecialchars($errors['NewPassword'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="password-form-field">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="position-relative">
                                    <input
                                        type="password"
                                        class="form-control<?php echo isset($errors['ConfirmPassword']) ? ' is-invalid' : ''; ?>"
                                        id="confirm_password"
                                        name="confirm_password"
                                        required
                                        value="<?php echo htmlspecialchars($formData['ConfirmPassword'], ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="Confirm new password"
                                        data-parsley-required-message="Confirm Password is required."
                                        data-parsley-whitespace="trim"
                                        style="padding-right: 40px;"
                                    >
                                    <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-decoration-none text-muted" type="button" id="toggle-confirm-password" style="padding: 0.47rem 0.75rem; border: none; box-shadow: none; background: transparent; z-index: 5;" tabindex="-1">
                                        <i class="ri-eye-off-line"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['ConfirmPassword'])): ?>
                                    <div class="text-danger mt-1" style="font-size: .875em;"><?php echo htmlspecialchars($errors['ConfirmPassword'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            Update Password
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function setupPasswordToggle(buttonId, inputId) {
        var btn = document.getElementById(buttonId);
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            var passwordInput = document.getElementById(inputId);
            var icon = this.querySelector('i');

            if (!passwordInput || !icon) {
                return;
            }

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.Parsley) {
            window.Parsley.addValidator('whitespace', {
                validateString: function (value) {
                    return value.trim().length > 0;
                },
                messages: {
                    en: 'This field is required.'
                }
            });
        }

        setupPasswordToggle('toggle-current-password', 'current_password');
        setupPasswordToggle('toggle-new-password', 'new_password');
        setupPasswordToggle('toggle-confirm-password', 'confirm_password');
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
