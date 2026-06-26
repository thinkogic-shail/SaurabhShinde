<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$wardId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $wardId > 0;
$ward = [
    'WardName' => '',
];
$errors = [];

if ($isEditMode) {
    $stmt = $pdo->prepare('SELECT WardId, WardName FROM Ward WHERE WardId = :id');
    $stmt->execute(['id' => $wardId]);
    $existingWard = $stmt->fetch();

    if (!$existingWard) {
        set_flash_message('danger', 'Selected ward record was not found.');
        header('Location: wards.php');
        exit;
    }

    $ward = $existingWard;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wardId = (int) ($_POST['ward_id'] ?? $wardId);
    $isEditMode = $wardId > 0;
    $ward['WardName'] = trim($_POST['ward_name'] ?? '');

    if ($ward['WardName'] === '') {
        $errors['WardName'] = 'Ward name is required.';
    } elseif (mb_strlen($ward['WardName']) > 30) {
        $errors['WardName'] = 'Ward name must be 30 characters or fewer.';
    }

    if ($errors === []) {
        $duplicateStmt = $pdo->prepare(
            'SELECT WardId
             FROM Ward
             WHERE LOWER(TRIM(WardName)) = LOWER(:ward_name)
               AND WardId <> :ward_id
             LIMIT 1'
        );
        $duplicateStmt->execute([
            'ward_name' => $ward['WardName'],
            'ward_id' => $wardId,
        ]);

        if ($duplicateStmt->fetch()) {
            $errors['WardName'] = 'Ward Name already exists.';
        }
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare('UPDATE Ward SET WardName = :ward_name WHERE WardId = :ward_id');
            $stmt->execute([
                'ward_name' => $ward['WardName'],
                'ward_id' => $wardId,
            ]);

            set_flash_message('success', 'Ward updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO Ward (WardName, IsActive) VALUES (:ward_name, 1)');
            $stmt->execute([
                'ward_name' => $ward['WardName'],
            ]);

            set_flash_message('success', 'Ward added successfully.');
        }

        header('Location: wards.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Ward' : 'Add Ward';

render_admin_header($pageTitle, [], 'ward', false);
?>
<style>
    .form-control {
        display: block;
        width: 30% !important;
    }
    .page-title-box {
        padding-bottom: 0 !important;
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
                                <li class="breadcrumb-item"><a href="wards.php">Configuration</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                    </div>
                    <!--<div>
                        <p class="text-muted mb-0">
                            <?php /* echo $isEditMode ? 'Update the selected Ward record.' : 'Create a new Ward record.';*/ ?>
                        </p>
                    </div>-->
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="ward_id" value="<?php echo (int) $wardId; ?>">

                    <div class="mb-3">
                        <label for="ward_name" class="form-label">Ward Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['WardName']) ? ' is-invalid' : ''; ?>"
                            id="ward_name"
                            name="ward_name"
                            required
                            maxlength="30"
                            value="<?php echo htmlspecialchars((string) $ward['WardName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter ward name"
                            data-parsley-required-message="Ward name is required."
                            data-parsley-maxlength="30"
                            data-parsley-maxlength-message="Ward name must be 30 characters or fewer."
                            data-parsley-whitespace="trim"
                        >
                        <?php if (isset($errors['WardName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['WardName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="wards.php" class="btn btn-secondary waves-effect">Cancel</a>
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
            en: 'Ward name is required.'
        }
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
