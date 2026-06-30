<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $requestTypeIdToDelete = (int) ($_POST['request_type_id'] ?? 0);

    if ($requestTypeIdToDelete > 0) {
        try {
            $deleteStmt = $pdo->prepare('UPDATE RequestTypeMaster SET IsActive = 0 WHERE RequestTypeId = :request_type_id');
            $deleteStmt->execute(['request_type_id' => $requestTypeIdToDelete]);

            if ($deleteStmt->rowCount() > 0) {
                set_flash_message('success', 'Request Type deactivated successfully.');
            } else {
                set_flash_message('danger', 'Selected request type record was not found.');
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Request Type could not be deleted because it is linked to other records.');
        }
    }

    header('Location: request-types.php');
    exit;
}

$flash = get_flash_message();
$requestTypes = $pdo->query(
    'SELECT RequestTypeId,
            RequestTypeName,
            Description,
            IsActive,
            CAST(ShowRequest AS UNSIGNED) AS ShowRequest
     FROM RequestTypeMaster
     WHERE IsActive = 1
     ORDER BY RequestTypeId DESC'
)->fetchAll();

render_admin_header('Request Type Master', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'request-type', false);
?>
<style>
    #request-type-table td, #request-type-table th {
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
                        <h4 class="mb-1">Request Type Master</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="request-types.php">Configuration</a></li>
                                <li class="breadcrumb-item active">Request Type Master</li>
                            </ol>
                        </div>
                    </div>
                    <a href="request-type-form.php" class="btn btn-primary waves-effect waves-light" style="background-color: #002253; border-color: #002253;">
                        <i class="ri-add-line align-middle me-1"></i> Add New Request Type
                    </a>
                </div>

                <div class="row mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label" for="request-enabled-filter">Request Enabled</label>
                        <select id="request-enabled-filter" class="form-select">
                            <option value="">All</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
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
                    <table id="request-type-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Request Type ID</th>
                                <th>Request Type Name</th>
                                <th>Description</th>
                                <th>Request Enabled</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requestTypes as $requestType): ?>
                                <?php
                                $fullRequestTypeName = (string) $requestType['RequestTypeName'];
                                $displayRequestTypeName = mb_strlen($fullRequestTypeName) > 20
                                    ? mb_substr($fullRequestTypeName, 0, 20) . '...'
                                    : $fullRequestTypeName;
                                $fullDescription = (string) ($requestType['Description'] ?? '');
                                $displayDescription = mb_strlen($fullDescription) > 20
                                    ? mb_substr($fullDescription, 0, 20) . '...'
                                    : $fullDescription;
                                ?>
                                <tr>
                                    <td><?php echo (int) $requestType['RequestTypeId']; ?></td>
                                    <td title="<?php echo htmlspecialchars($fullRequestTypeName, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayRequestTypeName, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullDescription, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayDescription, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo (int) ($requestType['ShowRequest'] ?? 0) === 1 ? 'Yes' : 'No'; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php
                                            $isActive = (int) $requestType['IsActive'] === 1;
                                            $btnOpacity = $isActive ? '' : ' opacity: 0.5; pointer-events: none;';
                                            ?>
                                            <a href="<?php echo $isActive ? 'request-type-form.php?id=' . (int) $requestType['RequestTypeId'] : '#'; ?>" class="btn btn-sm<?php echo $isActive ? '' : ' disabled'; ?>" style="background-color: #002253; border-color: #002253; color: white;<?php echo $btnOpacity; ?>">
                                                <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="m-0 delete-request-type-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="request_type_id" value="<?php echo (int) $requestType['RequestTypeId']; ?>">
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
        const requestTypeTable = $('#request-type-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[0, 'desc']]
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function() {
            requestTypeTable.search(this.value).draw();
        });

        $('#request-enabled-filter').on('change', function () {
            var selectedValue = this.value;
            requestTypeTable.column(3).search(selectedValue ? '^' + selectedValue + '$' : '', true, false).draw();
        });

        document.querySelectorAll('.delete-request-type-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Are you sure you want to delete this request type?')) {
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
