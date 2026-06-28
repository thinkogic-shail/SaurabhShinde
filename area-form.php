<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$areaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $areaId > 0;
$area = [
    'WardId' => 0,
    'AreaName' => '',
];
$errors = [];

$wards = $pdo->query('SELECT WardId, WardName FROM Ward ORDER BY WardName ASC')->fetchAll();

if ($isEditMode) {
    $stmt = $pdo->prepare('SELECT AreaId, WardId, AreaName, IsActive FROM Area WHERE AreaId = :id');
    $stmt->execute(['id' => $areaId]);
    $existingArea = $stmt->fetch();

    if (!$existingArea) {
        set_flash_message('danger', 'Selected area record was not found.');
        header('Location: areas.php');
        exit;
    }

    $area = $existingArea;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $areaId = (int) ($_POST['area_id'] ?? $areaId);
    $isEditMode = $areaId > 0;
    $area['WardId'] = (int) ($_POST['ward_id'] ?? 0);
    $area['AreaName'] = trim($_POST['area_name'] ?? '');

    $validWardIds = array_map(static fn(array $ward): int => (int) $ward['WardId'], $wards);

    if ($area['WardId'] <= 0 || !in_array($area['WardId'], $validWardIds, true)) {
        $errors['WardId'] = 'Please select a valid Ward.';
    }

    if ($area['AreaName'] === '') {
        $errors['AreaName'] = 'Area name is required.';
    } elseif (mb_strlen($area['AreaName']) > 30) {
        $errors['AreaName'] = 'Area name must be 30 characters or fewer.';
    }

    if ($errors === []) {
        $duplicateStmt = $pdo->prepare(
            'SELECT AreaId
             FROM Area
             WHERE WardId = :ward_id
               AND LOWER(TRIM(AreaName)) = LOWER(:area_name)
               AND AreaId <> :area_id
             LIMIT 1'
        );
        $duplicateStmt->execute([
            'ward_id' => $area['WardId'],
            'area_name' => $area['AreaName'],
            'area_id' => $areaId,
        ]);

        if ($duplicateStmt->fetch()) {
            $errors['AreaName'] = 'Area Name already exists for the selected Ward.';
        }
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare(
                'UPDATE Area
                 SET WardId = :ward_id, AreaName = :area_name
                 WHERE AreaId = :area_id'
            );
            $stmt->execute([
                'ward_id' => $area['WardId'],
                'area_name' => $area['AreaName'],
                'area_id' => $areaId,
            ]);

            set_flash_message('success', 'Area updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO Area (WardId, AreaName, IsActive)
                 VALUES (:ward_id, :area_name, 1)'
            );
            $stmt->execute([
                'ward_id' => $area['WardId'],
                'area_name' => $area['AreaName'],
            ]);

            set_flash_message('success', 'Area added successfully.');
        }

        header('Location: areas.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Area' : 'Add Area';

render_admin_header($pageTitle, [], 'area', false);
?>
<style>
    .form-control,
    .form-select {
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
                                <li class="breadcrumb-item"><a href="areas.php">Configuration</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                        
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="area_id" value="<?php echo (int) $areaId; ?>">

                    <div class="mb-3">
                        <label for="ward_id" class="form-label">Ward</label>
                        <select
                            class="form-select<?php echo isset($errors['WardId']) ? ' is-invalid' : ''; ?>"
                            id="ward_id"
                            name="ward_id"
                            required
                            data-parsley-required-message="Ward is required."
                            data-parsley-whitespace="trim"
                        >
                            <option value="">Select Ward</option>
                            <?php foreach ($wards as $ward): ?>
                                <option
                                    value="<?php echo (int) $ward['WardId']; ?>"
                                    <?php echo (int) $area['WardId'] === (int) $ward['WardId'] ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($ward['WardName'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['WardId'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['WardId'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="area_name" class="form-label">Area Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['AreaName']) ? ' is-invalid' : ''; ?>"
                            id="area_name"
                            name="area_name"
                            required
                            maxlength="30"
                            value="<?php echo htmlspecialchars((string) $area['AreaName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter area name"
                            data-parsley-required-message="Area name is required."
                            data-parsley-maxlength="30"
                            data-parsley-maxlength-message="Area name must be 30 characters or fewer."
                            data-parsley-whitespace="trim"
                        >
                        <?php if (isset($errors['AreaName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['AreaName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="areas.php" class="btn btn-secondary waves-effect">Cancel</a>
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
            en: 'This field is required.'
        }
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
