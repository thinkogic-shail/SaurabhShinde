<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$flash = get_flash_message();
$totalRequests = 0;
$closedRequests = 0;
$openRequests = 0;
$inProgressRequests = 0;

try {
    $pdo = app_pdo();
    $totalRequests = (int) $pdo->query('SELECT COUNT(*) FROM CitizenRequest')->fetchColumn();
    $closedRequestsStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE TRIM(LOWER(rsm.StatusName)) IN ('completed', 'declined')"
    );
    $closedRequests = (int) $closedRequestsStmt->fetchColumn();
    $openRequestsStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE TRIM(LOWER(rsm.StatusName)) = 'raised'"
    );
    $openRequests = (int) $openRequestsStmt->fetchColumn();
    $inProgressRequestsStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM CitizenRequest cr
         INNER JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE TRIM(LOWER(rsm.StatusName)) = 'in progress'"
    );
    $inProgressRequests = (int) $inProgressRequestsStmt->fetchColumn();
} catch (Throwable $e) {
    $totalRequests = 0;
    $closedRequests = 0;
    $openRequests = 0;
    $inProgressRequests = 0;
}

render_admin_header('Dashboard', [], 'dashboard');
?>
<style>
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
    </div>
</div>
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #2b5ab4; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($totalRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">Total Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-file-list-3-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #16A34A; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($closedRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">Closed Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-checkbox-circle-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #9242E3; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($openRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">Open Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-folder-open-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card" style="background-color: #e0a800; border: none; border-radius: 0.25rem;">
            <div class="card-body position-relative p-4">
                <h3 class="text-white fw-bold mb-1" style="font-size: 38px; position: relative; z-index: 2;"><?php echo number_format($inProgressRequests); ?></h3>
                <p class="text-white mb-0" style="font-size: 15px; position: relative; z-index: 2;">In progress Requests</p>
                <div class="position-absolute" style="top: 15px; right: 20px; z-index: 1;">
                    <i class="ri-loader-4-line text-white" style="font-size: 70px; opacity: 0.4;"></i>
                </div>
            </div>
            <a href="my-requests.php" class="d-block text-center text-white py-1" style="background-color: rgba(0,0,0,0.1); text-decoration: none; font-size: 13px;">
                More info <i class="mdi mdi-arrow-right-circle ms-1"></i>
            </a>
        </div>
    </div>
</div>
<?php
render_admin_footer();
