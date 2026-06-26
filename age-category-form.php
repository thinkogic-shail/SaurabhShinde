<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$ageCategoryId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $ageCategoryId > 0;
$ageCategory = [
    'CategoryName' => '',
];
$errors = [];

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT AgeCategoryId, CategoryName, IsActive
         FROM AgeCategoryMaster
         WHERE AgeCategoryId = :id'
    );
    $stmt->execute(['id' => $ageCategoryId]);
    $existingAgeCategory = $stmt->fetch();

    if (!$existingAgeCategory) {
        set_flash_message('danger', 'Selected age category record was not found.');
        header('Location: age-categories.php');
        exit;
    }

    $ageCategory = $existingAgeCategory;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ageCategoryId = (int) ($_POST['age_category_id'] ?? $ageCategoryId);
    $isEditMode = $ageCategoryId > 0;
    $ageCategory['CategoryName'] = trim($_POST['category_name'] ?? '');

    if ($ageCategory['CategoryName'] === '') {
        $errors['CategoryName'] = 'Category name is required.';
    } elseif (mb_strlen($ageCategory['CategoryName']) > 30) {
        $errors['CategoryName'] = 'Category name must be 30 characters or fewer.';
    }

    if ($errors === []) {
        $duplicateStmt = $pdo->prepare(
            'SELECT AgeCategoryId
             FROM AgeCategoryMaster
             WHERE LOWER(TRIM(CategoryName)) = LOWER(:category_name)
               AND AgeCategoryId <> :age_category_id
             LIMIT 1'
        );
        $duplicateStmt->execute([
            'category_name' => $ageCategory['CategoryName'],
            'age_category_id' => $ageCategoryId,
        ]);

        if ($duplicateStmt->fetch()) {
            $errors['CategoryName'] = 'Category Name already exists.';
        }
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare(
                'UPDATE AgeCategoryMaster
                 SET CategoryName = :category_name
                 WHERE AgeCategoryId = :age_category_id'
            );
            $stmt->execute([
                'category_name' => $ageCategory['CategoryName'],
                'age_category_id' => $ageCategoryId,
            ]);

            set_flash_message('success', 'Age Category updated successfully.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO AgeCategoryMaster (CategoryName, IsActive)
                 VALUES (:category_name, 1)'
            );
            $stmt->execute([
                'category_name' => $ageCategory['CategoryName'],
            ]);

            set_flash_message('success', 'Age Category added successfully.');
        }

        header('Location: age-categories.php');
        exit;
    }
}

$pageTitle = $isEditMode ? 'Edit Age Category' : 'Add Age Category';

render_admin_header($pageTitle, [], 'age-category', false);
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
                                <li class="breadcrumb-item"><a href="age-categories.php">Configuration</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" novalidate>
                    <input type="hidden" name="age_category_id" value="<?php echo (int) $ageCategoryId; ?>">

                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input
                            type="text"
                            class="form-control<?php echo isset($errors['CategoryName']) ? ' is-invalid' : ''; ?>"
                            id="category_name"
                            name="category_name"
                            required
                            maxlength="30"
                            value="<?php echo htmlspecialchars((string) $ageCategory['CategoryName'], ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Enter category name"
                            data-parsley-required-message="Category name is required."
                            data-parsley-maxlength="30"
                            data-parsley-maxlength-message="Category name must be 30 characters or fewer."
                            data-parsley-whitespace="trim"
                        >
                        <?php if (isset($errors['CategoryName'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['CategoryName'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="age-categories.php" class="btn btn-secondary waves-effect">Cancel</a>
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
            en: 'Category name is required.'
        }
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
