<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $wardIdToDelete = (int) ($_POST['ward_id'] ?? 0);

    if ($wardIdToDelete > 0) {
        try {
            $deleteStmt = $pdo->prepare('UPDATE Ward SET IsActive = 0 WHERE WardId = :ward_id');
            $deleteStmt->execute(['ward_id' => $wardIdToDelete]);

            if ($deleteStmt->rowCount() > 0) {
                set_flash_message('success', 'Ward deactivated successfully.');
            } else {
                set_flash_message('danger', 'Selected ward record was not found.');
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Ward could not be deleted because it is linked to other records.');
        }
    }

    header('Location: wards.php');
    exit;
}

$flash = get_flash_message();
$wards = $pdo->query('SELECT WardId, WardName, IsActive FROM Ward WHERE IsActive = 1 ORDER BY WardId DESC')->fetchAll();

render_admin_header('Ward Master', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'ward', false);
?>
<style>
    #ward-table td, #ward-table th {
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
                        <h4 class="mb-1">Ward Master</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Ward Master</li>
                            </ol>
                        </div>
                    </div>
                    <a href="ward-form.php" class="btn btn-primary waves-effect waves-light" style="background-color: #002253; border-color: #002253;">
                        <i class="ri-add-line align-middle me-1"></i> Add New Ward
                    </a>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <!--<label class="form-label" for="custom-search">Search</label>-->
                        <div class="search-box">
                            <div class="position-relative">
                                <input type="search" id="custom-search" class="form-control rounded" placeholder="Search...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="ward-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Ward ID</th>
                                <th>Ward Name</th>
                                <th>Status</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wards as $ward): ?>
                                <?php
                                $fullWardName = (string) $ward['WardName'];
                                $displayWardName = mb_strlen($fullWardName) > 20
                                    ? mb_substr($fullWardName, 0, 20) . '...'
                                    : $fullWardName;
                                ?>
                                <tr>
                                    <td><?php echo (int) $ward['WardId']; ?></td>
                                    <td title="<?php echo htmlspecialchars($fullWardName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayWardName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $ward['IsActive'] === 1): ?>
                                            <span class="badge rounded-pill bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php
                                            $isActive = (int) $ward['IsActive'] === 1;
                                            $btnOpacity = $isActive ? '' : ' opacity: 0.5; pointer-events: none;';
                                            ?>
                                            <a href="<?php echo $isActive ? 'ward-form.php?id=' . (int) $ward['WardId'] : '#'; ?>" class="btn btn-sm<?php echo $isActive ? '' : ' disabled'; ?>" style="background-color: #002253; border-color: #002253; color: white;<?php echo $btnOpacity; ?>">
                                                <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="m-0 delete-ward-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="ward_id" value="<?php echo (int) $ward['WardId']; ?>">
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
        const wardTable = $('#ward-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[0, 'desc']]
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function() {
            wardTable.search(this.value).draw();
        });

        document.querySelectorAll('.delete-ward-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Are you sure you want to delete this ward?')) {
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
