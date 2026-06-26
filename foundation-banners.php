<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $bannerIdToDelete = (int) ($_POST['banner_id'] ?? 0);

    if ($bannerIdToDelete > 0) {
        $deleteStmt = $pdo->prepare(
            'DELETE FROM FoundationBanner
             WHERE FoundatationBannerId = :banner_id'
        );
        $deleteStmt->execute(['banner_id' => $bannerIdToDelete]);

        if ($deleteStmt->rowCount() > 0) {
            set_flash_message('success', 'Foundation banner deleted successfully.');
        } else {
            set_flash_message('danger', 'Selected foundation banner record was not found.');
        }
    }

    header('Location: foundation-banners.php');
    exit;
}

$flash = get_flash_message();
$banners = $pdo->query(
    'SELECT FoundatationBannerId, BannerTitle, BannerDescription, BannerImage, IsActive, CreatedDate
     FROM FoundationBanner
     ORDER BY FoundatationBannerId DESC'
)->fetchAll();

render_admin_header('Foundation Banner Management', [
    app_asset('assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css'),
    app_asset('assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css'),
], 'foundation-banner', false);
?>
<style>
    #foundation-banner-table td, #foundation-banner-table th {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    .dataTables_paginate .page-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }
    .banner-thumbnail {
        width: 80px;
        height: 50px;
        object-fit: cover;
        border-radius: 0.375rem;
        border: 1px solid #e2e8f0;
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
                        <h4 class="mb-1">Foundation Banner Management</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="foundation-banners.php">Configuration</a></li>
                                <li class="breadcrumb-item active">Foundation Banner Management</li>
                            </ol>
                        </div>
                    </div>
                    <a href="foundation-banner-form.php" class="btn btn-primary waves-effect waves-light" style="background-color: #002253; border-color: #002253;">
                        <i class="ri-add-line align-middle me-1"></i> Add Foundation Banner
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
                    <table id="foundation-banner-table" class="table table-bordered dt-responsive nowrap w-100 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Banner Image</th>
                                <th>Banner Title</th>
                                <th>Banner Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banners as $banner): ?>
                                <?php
                                $fullTitle = (string) ($banner['BannerTitle'] ?? '');
                                $displayTitle = mb_strlen($fullTitle) > 25 ? mb_substr($fullTitle, 0, 25) . '...' : $fullTitle;
                                $fullDescription = (string) ($banner['BannerDescription'] ?? '');
                                $displayDescription = mb_strlen($fullDescription) > 40 ? mb_substr($fullDescription, 0, 40) . '...' : $fullDescription;
                                $imagePath = trim((string) ($banner['BannerImage'] ?? ''));
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($imagePath !== ''): ?>
                                            <a href="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                                <img src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="Banner Thumbnail" class="banner-thumbnail">
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td title="<?php echo htmlspecialchars($fullDescription, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($displayDescription, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $banner['IsActive'] === 1): ?>
                                            <span class="badge rounded-pill bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($banner['CreatedDate'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="foundation-banner-form.php?id=<?php echo (int) $banner['FoundatationBannerId']; ?>" class="btn btn-sm" style="background-color: #002253; border-color: #002253; color: white;">
                                                <i class="ri-edit-2-line align-middle me-1"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="m-0 delete-foundation-banner-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="banner_id" value="<?php echo (int) $banner['FoundatationBannerId']; ?>">
                                                <button type="submit" class="btn btn-sm" style="background-color: #dc3545; border-color: #dc3545; color: white;">
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
        const bannerTable = $('#foundation-banner-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            order: [[4, 'desc']]
        });

        $('.dataTables_filter').hide();

        $('#custom-search').on('keyup', function() {
            bannerTable.search(this.value).draw();
        });

        document.querySelectorAll('.delete-foundation-banner-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Are you sure you want to delete this foundation banner?')) {
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
