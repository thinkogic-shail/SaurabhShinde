<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($requestId <= 0) {
    header('Location: my-requests.php');
    exit;
}

$statusOptions = $pdo->query(
    'SELECT RequestStatusId, StatusName
     FROM RequestStatusMaster
     WHERE IsActive = 1
     ORDER BY RequestStatusId ASC'
)->fetchAll();

$requestStmt = $pdo->prepare(
    'SELECT cr.CitizenRequestId, cr.RequestNo, cr.Address, cr.AadhaarNo, cr.Description, cr.Remark,
            cr.RequestStatusId, cr.RaisedDate, cr.ClosedDate, cr.CreatedDate,
            cu.Name, cu.MobileNo,
            rtm.RequestTypeName,
            w.WardName,
            a.AreaName
     FROM CitizenRequest cr
     LEFT JOIN CitizenUser cu ON cu.CitizenUserId = cr.CitizenUserId
     LEFT JOIN RequestTypeMaster rtm ON rtm.RequestTypeId = cr.RequestTypeId
     LEFT JOIN Ward w ON w.WardId = cr.WardId
     LEFT JOIN Area a ON a.AreaId = cr.AreaId
     WHERE cr.CitizenRequestId = :id'
);
$requestStmt->execute(['id' => $requestId]);
$request = $requestStmt->fetch();

if (!$request) {
    set_flash_message('danger', 'Selected request record was not found.');
    header('Location: my-requests.php');
    exit;
}

$attachmentsStmt = $pdo->prepare(
    'SELECT AttachmentId, FileName, FilePath
     FROM RequestAttachment
     WHERE CitizenRequestId = :id AND IsActive = 1
     ORDER BY AttachmentId ASC'
);
$attachmentsStmt->execute(['id' => $requestId]);
$attachments = $attachmentsStmt->fetchAll();

$errors = [];
$selectedStatusId = (int) $request['RequestStatusId'];
$declinedStatusId = null;
$dropdownStatusOptions = [];

foreach ($statusOptions as $statusOption) {
    $statusName = strtolower(trim((string) $statusOption['StatusName']));
    if (in_array($statusName, ['declined', 'rejected', 'decline'], true)) {
        $declinedStatusId = (int) $statusOption['RequestStatusId'];
        continue;
    }

    if (in_array($statusName, ['in progress', 'completed', 'complete'], true)) {
        $dropdownStatusOptions[] = $statusOption;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? 'save';
    $selectedStatusId = (int) ($_POST['request_status_id'] ?? 0);
    $validStatusIds = array_map(static fn(array $row): int => (int) $row['RequestStatusId'], $dropdownStatusOptions);
    $remark = trim((string) ($_POST['remark'] ?? ''));

    if ($remark === '') {
        $errors['remark'] = 'Remark is required.';
    }

    if ($action === 'decline') {
        if ($declinedStatusId === null) {
            $errors['request_status_id'] = 'Declined status is not configured in RequestStatusMaster.';
        } else {
            $selectedStatusId = $declinedStatusId;
        }
    } else {
        if (!in_array($selectedStatusId, $validStatusIds, true)) {
            $errors['request_status_id'] = 'Please select a valid status.';
        }
    }

    if ($errors === []) {
        $updateStmt = $pdo->prepare(
            'UPDATE CitizenRequest
             SET RequestStatusId = :request_status_id,
                 Remark = :remark
             WHERE CitizenRequestId = :request_id'
        );
        $updateStmt->execute([
            'request_status_id' => $selectedStatusId,
            'remark' => $remark,
            'request_id' => $requestId,
        ]);

        set_flash_message('success', $action === 'decline' ? 'Request declined successfully.' : 'Request status updated successfully.');
        header('Location: my-requests.php');
        exit;
    }
}

render_admin_header('Edit My Request', [], 'my-requests', false);
?>
<style>
    .form-select {
        display: block;
        width: 30% !important;
    }
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .request-detail-value {
        font-weight: 600;
        padding: 0.625rem 0.75rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
        min-height: calc(1.5em + 1.25rem + 2px);
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="page-title-box">
                        <h4 class="mb-1">Edit My Request</h4>
                        <div>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="my-requests.php">My Requests</a></li>
                                <li class="breadcrumb-item active">Edit My Request</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="form_action" id="form_action" value="save">

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted mb-1">Request Type</label>
                            <div class="request-detail-value"><?php echo htmlspecialchars((string) ($request['RequestTypeName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted mb-1">Name</label>
                            <div class="request-detail-value"><?php echo htmlspecialchars((string) ($request['Name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted mb-1">Mobile Number</label>
                            <div class="request-detail-value"><?php echo htmlspecialchars((string) ($request['MobileNo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted mb-1">Aadhaar Number</label>
                            <div class="request-detail-value"><?php echo htmlspecialchars((string) ($request['AadhaarNo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted mb-1">Ward</label>
                            <div class="request-detail-value"><?php echo htmlspecialchars((string) ($request['WardName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted mb-1">Area</label>
                            <div class="request-detail-value"><?php echo htmlspecialchars((string) ($request['AreaName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted mb-1">Address</label>
                        <div class="request-detail-value"><?php echo nl2br(htmlspecialchars((string) ($request['Address'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted mb-1">Description</label>
                        <div class="request-detail-value"><?php echo nl2br(htmlspecialchars((string) ($request['Description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted mb-1">Photos</label>
                        <div class="border rounded p-3 bg-light">
                            <?php if ($attachments === []): ?>
                                <span class="text-muted">No photos uploaded.</span>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($attachments as $attachment): ?>
                                        <a href="<?php echo htmlspecialchars((string) $attachment['FilePath'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars((string) $attachment['FileName'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="request_status_id" class="form-label">Status</label>
                        <select
                            class="form-select<?php echo isset($errors['request_status_id']) ? ' is-invalid' : ''; ?>"
                            id="request_status_id"
                            name="request_status_id"
                        >
                            <option value="">Select Status</option>
                            <?php foreach ($dropdownStatusOptions as $statusOption): ?>
                                <option
                                    value="<?php echo (int) $statusOption['RequestStatusId']; ?>"
                                    <?php echo $selectedStatusId === (int) $statusOption['RequestStatusId'] ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars((string) $statusOption['StatusName'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['request_status_id'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['request_status_id'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php elseif ($dropdownStatusOptions === []): ?>
                            <div class="form-text text-danger">Only In Progress and Completed statuses should be active in RequestStatusMaster.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="remark" class="form-label">Remark <span class="text-danger">*</span></label>
                        <textarea
                            class="form-control<?php echo isset($errors['remark']) ? ' is-invalid' : ''; ?>"
                            id="remark"
                            name="remark"
                            rows="3"
                            required
                            data-parsley-required-message="Remark is required."
                        ><?php echo htmlspecialchars($_POST['remark'] ?? (string) ($request['Remark'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <?php if (isset($errors['remark'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['remark'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button
                            type="submit"
                            class="btn btn-danger waves-effect waves-light"
                            <?php echo $declinedStatusId === null ? 'disabled' : ''; ?>
                            onclick="document.getElementById('form_action').value='decline';"
                        >
                            Decline
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary waves-effect waves-light"
                            style="background-color: #002253; border-color: #002253;"
                            <?php echo $dropdownStatusOptions === [] ? 'disabled' : ''; ?>
                            onclick="document.getElementById('form_action').value='save';"
                        >
                            Save
                        </button>
                        <a href="my-requests.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
