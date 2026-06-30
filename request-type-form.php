<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$requestTypeId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $requestTypeId > 0;
$requestType = [
    'RequestTypeName' => '',
    'Description' => '',
    'ShowRequest' => '1',
];
$errors = [];

$redirectIfRequestTypeInactive = static function (array|false $existingRequestType): void {
    if (!$existingRequestType) {
        set_flash_message('danger', 'Selected request type record was not found.');
        header('Location: request-types.php');
        exit;
    }

    if ((int) ($existingRequestType['IsActive'] ?? 1) !== 1) {
        set_flash_message('danger', 'This request type has been deleted and cannot be edited.');
        header('Location: request-types.php');
        exit;
    }
};

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT RequestTypeId,
                RequestTypeName,
                Description,
                IsActive,
                CAST(ShowRequest AS UNSIGNED) AS ShowRequest
         FROM RequestTypeMaster
         WHERE RequestTypeId = :id'
    );
    $stmt->execute(['id' => $requestTypeId]);
    $existingRequestType = $stmt->fetch();

    $redirectIfRequestTypeInactive($existingRequestType);

    $requestType = $existingRequestType;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestTypeId = (int) ($_POST['request_type_id'] ?? $requestTypeId);
    $isEditMode = $requestTypeId > 0;
    $requestType['RequestTypeName'] = trim($_POST['request_type_name'] ?? '');
    $requestType['Description'] = trim($_POST['description'] ?? '');
    $requestType['ShowRequest'] = isset($_POST['show_request']) ? '1' : '0';

    if ($requestType['RequestTypeName'] === '') {
        $errors['RequestTypeName'] = 'Request type name is required.';
    } elseif (mb_strlen($requestType['RequestTypeName']) > 30) {
        $errors['RequestTypeName'] = 'Request type name must be 30 characters or fewer.';
    }

    if ($requestType['Description'] !== '' && mb_strlen($requestType['Description']) > 200) {
        $errors['Description'] = 'Description must be 200 characters or fewer.';
    }

    if ($errors === []) {
        $duplicateStmt = $pdo->prepare(
            'SELECT RequestTypeId
             FROM RequestTypeMaster
             WHERE LOWER(TRIM(RequestTypeName)) = LOWER(:request_type_name)
               AND RequestTypeId <> :request_type_id
             LIMIT 1'
        );
        $duplicateStmt->execute([
            'request_type_name' => $requestType['RequestTypeName'],
            'request_type_id' => $requestTypeId,
        ]);

        if ($duplicateStmt->fetch()) {
            $errors['RequestTypeName'] = 'Request Type Name already exists.';
        }
    }

    if ($errors === []) {
        if ($isEditMode) {
            $existingRequestTypeStmt = $pdo->prepare(
                'SELECT RequestTypeId, IsActive
                 FROM RequestTypeMaster
                 WHERE RequestTypeId = :id'
            );
            $existingRequestTypeStmt->execute(['id' => $requestTypeId]);
            $redirectIfRequestTypeInactive($existingRequestTypeStmt->fetch());

            $stmt = $pdo->prepare(
                'UPDATE RequestTypeMaster
                 SET RequestTypeName = :request_type_name,
                     Description = :description,
                     ShowRequest = :show_request
                 WHERE RequestTypeId = :request_type_id'
            );
            $stmt->execute([
                'request_type_name' => $requestType['RequestTypeName'],
                'description' => $requestType['Description'] !== '' ? $requestType['Description'] : null,
                'show_request' => (int) $requestType['ShowRequest'],
                'request_type_id' => $requestTypeId,
            ]);

            set_flash_message('success', 'Request Type updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO RequestTypeMaster (RequestTypeName, Description, IsActive, ShowRequest)
                 VALUES (:request_type_name, :description, 1, :show_request)'
            );
            $stmt->execute([
                'request_type_name' => $requestType['RequestTypeName'],
                'description' => $requestType['Description'] !== '' ? $requestType['Description'] : null,
                'show_request' => (int) $requestType['ShowRequest'],
            ]);

            set_flash_message('success', 'Request Type added successfully.');
        }

        header('Location: request-types.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Request Type' : 'Add Request Type';

render_admin_header($pageTitle, [], 'request-type', false);
?>
<style>
    .form-control {
        display: block;
        width: 30% !important;
    }
    textarea.form-control {
        width: 50% !important;
    }
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .form-check.form-switch {
        padding-left: 3rem;
    }
    .form-check-input.request-toggle {
        width: 2.8rem;
        height: 1.4rem;
        margin-left: -3rem;
        cursor: pointer;
    }
    .switch-label {
        font-size: 0.95rem;
        color: #475569;
        margin-left: 0.5rem;
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
                                <li class="breadcrumb-item"><a href="request-types.php">Configuration</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="request_type_id" value="<?php echo (int) $requestTypeId; ?>">

                    <div class="mb-3">
                        <label for="request_type_name" class="form-label">Request Type Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['RequestTypeName']) ? ' is-invalid' : ''; ?>"
                            id="request_type_name"
                            name="request_type_name"
                            required
                            maxlength="30"
                            value="<?php echo htmlspecialchars((string) $requestType['RequestTypeName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter request type name"
                            data-parsley-required-message="Request type name is required."
                            data-parsley-maxlength="30"
                            data-parsley-maxlength-message="Request type name must be 30 characters or fewer."
                            data-parsley-whitespace="trim"
                        >
                        <?php if (isset($errors['RequestTypeName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['RequestTypeName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea
                            class="form-control<?php echo isset($errors['Description']) ? ' is-invalid' : ''; ?>"
                            id="description"
                            name="description"
                            maxlength="200"
                            rows="4"
                            placeholder="Enter description"
                            data-parsley-maxlength="200"
                            data-parsley-maxlength-message="Description must be 200 characters or fewer."
                            data-parsley-whitespace="trim"
                        ><?php echo htmlspecialchars((string) ($requestType['Description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <?php if (isset($errors['Description'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['Description'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Request Enabled</label>
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input request-toggle"
                                type="checkbox"
                                role="switch"
                                id="show_request"
                                name="show_request"
                                value="1"
                                <?php echo (string) ($requestType['ShowRequest'] ?? '1') === '1' ? 'checked' : ''; ?>
                            >
                            <label class="form-check-label switch-label" for="show_request" id="show-request-label">
                                <?php echo (string) ($requestType['ShowRequest'] ?? '1') === '1' ? 'Yes' : 'No'; ?>
                            </label>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="request-types.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    window.Parsley.addValidator('whitespace', {
        validateString: function (value) {
            return value.trim().length > 0 || value.length === 0;
        },
        messages: {
            en: 'Request type name is required.'
        }
    });

    (function () {
        var showRequestToggle = document.getElementById('show_request');
        var showRequestLabel = document.getElementById('show-request-label');

        if (!showRequestToggle || !showRequestLabel) {
            return;
        }

        var syncShowRequestLabel = function () {
            showRequestLabel.textContent = showRequestToggle.checked ? 'Yes' : 'No';
        };

        showRequestToggle.addEventListener('change', syncShowRequestLabel);
        syncShowRequestLabel();
    })();
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
