<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $userIdToDelete = (int) ($_POST['user_id'] ?? 0);

    if ($userIdToDelete > 0) {
        $deleteStmt = $pdo->prepare('UPDATE Employee SET IsActive = 0 WHERE EmployeeId = :employee_id');
        $deleteStmt->execute(['employee_id' => $userIdToDelete]);

        if ($deleteStmt->rowCount() > 0) {
            set_flash_message('success', 'User deactivated successfully.');
        } else {
            set_flash_message('danger', 'Selected user record was not found.');
        }
    }

    header('Location: users.php');
    exit;
}

$flash = get_flash_message();
$users = $pdo->query(
    'SELECT e.EmployeeId, e.UserName, e.MobileNo, e.Email, e.IsMobileVerified, e.IsActive, rm.RoleName
     FROM Employee e
     LEFT JOIN RoleMaster rm ON rm.RoleId = e.RoleId
     WHERE e.IsActive = 1
     ORDER BY e.EmployeeId DESC'
)->fetchAll();

render_admin_header('User Management', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'user', false);
?>
<style>
    #user-table td, #user-table th {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .dataTables_paginate .page-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                setTimeout(function() {
                    var alertNode = document.querySelector('.alert');
                    if (alertNode) {
                        var alert = new bootstrap.Alert(alertNode);
                        alert.close();
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="page-title-box">
                        <h4 class="mb-1">User Management</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">User Management</li>
                            </ol>
                        </div>
                    </div>
                    <a href="user-form.php" class="btn btn-primary waves-effect waves-light" style="background-color: #002253; border-color: #002253;">
                        <i class="ri-add-line align-middle me-1"></i> Add User
                    </a>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="search-box">
                            <div class="position-relative">
                                <input type="search" id="custom-search" class="form-control rounded" placeholder="Search...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="user-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Username</th>
                                <th>Mobile Number</th>
                                <th>Email</th>
                                <th>Role</th>

                                <th>Status</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $fullUserName = (string) $user['UserName'];
                                $fullEmail = (string) ($user['Email'] ?? '');
                                $fullRole = (string) ($user['RoleName'] ?? '');
                                $displayUserName = mb_strlen($fullUserName) > 20 ? mb_substr($fullUserName, 0, 20) . '...' : $fullUserName;
                                $displayEmail = mb_strlen($fullEmail) > 25 ? mb_substr($fullEmail, 0, 25) . '...' : $fullEmail;
                                $displayRole = mb_strlen($fullRole) > 20 ? mb_substr($fullRole, 0, 20) . '...' : $fullRole;
                                ?>
                                <tr>
                                    <td title="<?php echo htmlspecialchars($fullUserName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayUserName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $user['MobileNo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td title="<?php echo htmlspecialchars($fullEmail, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayEmail, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullRole, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $user['IsActive'] === 1): ?>
                                            <span class="badge rounded-pill bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php
                                            $isActive = (int) $user['IsActive'] === 1;
                                            $btnOpacity = $isActive ? '' : ' opacity: 0.5; pointer-events: none;';
                                            ?>
                                            <a href="<?php echo $isActive ? 'user-form.php?id=' . (int) $user['EmployeeId'] : '#'; ?>" class="btn btn-sm<?php echo $isActive ? '' : ' disabled'; ?>" style="background-color: #002253; border-color: #002253; color: white;<?php echo $btnOpacity; ?>">
                                                <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="m-0 delete-user-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo (int) $user['EmployeeId']; ?>">
                                                <button type="submit" class="btn btn-sm" style="background-color: #dc3545; border-color: #dc3545; color: white;<?php echo $btnOpacity; ?>" <?php echo $isActive ? '' : 'disabled'; ?>>
                                                    <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userTable = $('#user-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[0, 'asc']]
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function() {
            userTable.search(this.value).draw();
        });

        document.querySelectorAll('.delete-user-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Are you sure you want to delete this user?')) {
                    event.preventDefault();
                }
            });
        });
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/datatables.net/js/jquery.dataTables.min.js'),
    app_asset('assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js'),
    app_asset('assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js'),
    app_asset('assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js'),
]);
