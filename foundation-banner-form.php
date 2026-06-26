<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

require_admin_login();

$pdo = app_pdo();
$bannerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEditMode = $bannerId > 0;
$banner = [
    'BannerTitle' => '',
    'BannerDescription' => '',
    'BannerImage' => '',
    'IsActive' => 1,
];
$errors = [];

if ($isEditMode) {
    $stmt = $pdo->prepare(
        'SELECT FoundatationBannerId, BannerTitle, BannerDescription, BannerImage, IsActive
         FROM FoundationBanner
         WHERE FoundatationBannerId = :id'
    );
    $stmt->execute(['id' => $bannerId]);
    $existingBanner = $stmt->fetch();

    if (!$existingBanner) {
        set_flash_message('danger', 'Selected foundation banner record was not found.');
        header('Location: foundation-banners.php');
        exit;
    }

    $banner = [
        'BannerTitle' => (string) ($existingBanner['BannerTitle'] ?? ''),
        'BannerDescription' => (string) ($existingBanner['BannerDescription'] ?? ''),
        'BannerImage' => (string) ($existingBanner['BannerImage'] ?? ''),
        'IsActive' => (int) ($existingBanner['IsActive'] ?? 1),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bannerId = (int) ($_POST['banner_id'] ?? $bannerId);
    $isEditMode = $bannerId > 0;
    $banner['BannerTitle'] = trim((string) ($_POST['banner_title'] ?? ''));
    $banner['BannerDescription'] = trim((string) ($_POST['banner_description'] ?? ''));
    $banner['BannerImage'] = trim((string) ($_POST['existing_banner_image'] ?? $banner['BannerImage']));
    $banner['IsActive'] = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

    if ($banner['BannerTitle'] === '') {
        $errors['BannerTitle'] = 'Banner title is required.';
    } elseif (mb_strlen($banner['BannerTitle']) > 200) {
        $errors['BannerTitle'] = 'Banner title must be 200 characters or fewer.';
    }

    $uploadedFile = $_FILES['banner_image'] ?? null;
    $hasUploadedFile = is_array($uploadedFile) && (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if (!$isEditMode && !$hasUploadedFile) {
        $errors['BannerImage'] = 'Banner image is required.';
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $uploadedExtension = '';

    if ($hasUploadedFile) {
        if ((int) $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $errors['BannerImage'] = 'Banner image could not be uploaded.';
        } else {
            $uploadedExtension = strtolower(pathinfo((string) $uploadedFile['name'], PATHINFO_EXTENSION));
            if (!in_array($uploadedExtension, $allowedExtensions, true)) {
                $errors['BannerImage'] = 'Please upload a valid image file (JPG, JPEG, PNG, or WEBP).';
            }
        }
    }

    if ($errors === []) {
        if ($isEditMode) {
            $stmt = $pdo->prepare(
                'UPDATE FoundationBanner
                 SET BannerTitle = :banner_title,
                     BannerDescription = :banner_description,
                     IsActive = :is_active
                 WHERE FoundatationBannerId = :banner_id'
            );
            $stmt->execute([
                'banner_title' => $banner['BannerTitle'],
                'banner_description' => $banner['BannerDescription'],
                'is_active' => $banner['IsActive'],
                'banner_id' => $bannerId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO FoundationBanner (BannerTitle, BannerDescription, BannerImage, IsActive, CreatedBy)
                 VALUES (:banner_title, :banner_description, :banner_image, :is_active, :created_by)'
            );
            $stmt->execute([
                'banner_title' => $banner['BannerTitle'],
                'banner_description' => $banner['BannerDescription'],
                'banner_image' => '',
                'is_active' => $banner['IsActive'],
                'created_by' => !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null,
            ]);
            $bannerId = (int) $pdo->lastInsertId();
            $isEditMode = true;
        }

        if ($hasUploadedFile) {
            $bannerDirectory = __DIR__ . '/uploads/banners';
            if (!is_dir($bannerDirectory)) {
                mkdir($bannerDirectory, 0777, true);
            }

            foreach (glob($bannerDirectory . '/' . $bannerId . '.*') ?: [] as $existingImageFile) {
                if (is_file($existingImageFile)) {
                    unlink($existingImageFile);
                }
            }

            $targetRelativePath = 'uploads/banners/' . $bannerId . '.' . $uploadedExtension;
            $targetAbsolutePath = __DIR__ . '/' . $targetRelativePath;

            if (!move_uploaded_file((string) $uploadedFile['tmp_name'], $targetAbsolutePath)) {
                $errors['BannerImage'] = 'Banner image could not be saved.';
            } else {
                $banner['BannerImage'] = $targetRelativePath;
                $updateImageStmt = $pdo->prepare(
                    'UPDATE FoundationBanner
                     SET BannerImage = :banner_image
                     WHERE FoundatationBannerId = :banner_id'
                );
                $updateImageStmt->execute([
                    'banner_image' => $targetRelativePath,
                    'banner_id' => $bannerId,
                ]);
            }
        }

        if ($errors === []) {
            if ($isEditMode && isset($_POST['banner_id']) && (int) $_POST['banner_id'] > 0) {
                set_flash_message('success', 'Foundation banner updated successfully.');
            } else {
                set_flash_message('success', 'Foundation banner added successfully.');
            }

            header('Location: foundation-banners.php');
            exit;
        }
    }
}

$pageTitle = $isEditMode ? 'Edit Foundation Banner' : 'Add Foundation Banner';

render_admin_header($pageTitle, [], 'foundation-banner', false);
?>
<style>
    .page-title-box {
        padding-bottom: 0 !important;
    }
    .banner-preview {
        max-width: 280px;
        max-height: 160px;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        display: block;
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
                                <li class="breadcrumb-item"><a href="foundation-banners.php">Configuration</a></li>
                                <li class="breadcrumb-item"><a href="foundation-banners.php">Foundation Banner Management</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>

                <form class="custom-validation" method="POST" action="" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="banner_id" value="<?php echo (int) $bannerId; ?>">
                    <input type="hidden" name="existing_banner_image" value="<?php echo htmlspecialchars($banner['BannerImage'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="banner_title" class="form-label">Banner Title</label>
                                <input
                                    type="text"
                                    class="form-control<?php echo isset($errors['BannerTitle']) ? ' is-invalid' : ''; ?>"
                                    id="banner_title"
                                    name="banner_title"
                                    required
                                    maxlength="200"
                                    value="<?php echo htmlspecialchars($banner['BannerTitle'], ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Enter banner title"
                                    data-parsley-required-message="Banner title is required."
                                    data-parsley-maxlength="200"
                                    data-parsley-maxlength-message="Banner title must be 200 characters or fewer."
                                    data-parsley-whitespace="trim"
                                >
                                <?php if (isset($errors['BannerTitle'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['BannerTitle'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="banner_description" class="form-label">Banner Description</label>
                                <textarea
                                    class="form-control<?php echo isset($errors['BannerDescription']) ? ' is-invalid' : ''; ?>"
                                    id="banner_description"
                                    name="banner_description"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="Enter banner description"
                                ><?php echo htmlspecialchars($banner['BannerDescription'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <?php if (isset($errors['BannerDescription'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['BannerDescription'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="1" <?php echo $banner['IsActive'] === 1 ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $banner['IsActive'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="banner_image" class="form-label">Banner Image</label>
                                <input
                                    type="file"
                                    class="form-control<?php echo isset($errors['BannerImage']) ? ' is-invalid' : ''; ?>"
                                    id="banner_image"
                                    name="banner_image"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    <?php echo $isEditMode ? '' : 'required'; ?>
                                    data-parsley-required-message="Banner image is required."
                                >
                                <?php if (isset($errors['BannerImage'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['BannerImage'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image Preview</label>
                                <div>
                                    <?php if ($banner['BannerImage'] !== ''): ?>
                                        <img
                                            id="banner-image-preview"
                                            src="<?php echo htmlspecialchars($banner['BannerImage'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="Banner Preview"
                                            class="banner-preview"
                                        >
                                        <p id="no-image-message" class="text-muted mt-2 mb-0" style="display: none;">No image selected</p>
                                    <?php else: ?>
                                        <img
                                            id="banner-image-preview"
                                            src=""
                                            alt="Banner Preview"
                                            class="banner-preview"
                                            style="display: none;"
                                        >
                                        <p id="no-image-message" class="text-muted mt-2 mb-0">No image selected</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <button type="submit" class="btn btn-primary waves-effect waves-light me-1" style="background-color: #002253; border-color: #002253;">
                            <?php echo $isEditMode ? 'Update' : 'Save'; ?>
                        </button>
                        <a href="foundation-banners.php" class="btn btn-secondary waves-effect">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('banner_image').addEventListener('change', function (event) {
        var preview = document.getElementById('banner-image-preview');
        var message = document.getElementById('no-image-message');
        var file = event.target.files && event.target.files[0];

        if (!file) {
            preview.style.display = 'none';
            preview.src = '';
            if (message) message.style.display = 'block';
            return;
        }

        var reader = new FileReader();
        reader.onload = function (loadEvent) {
            preview.src = loadEvent.target.result;
            preview.style.display = 'block';
            if (message) message.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    document.addEventListener("DOMContentLoaded", function() {
        if (window.Parsley) {
            window.Parsley.addValidator('whitespace', {
                validateString: function (value) {
                    return value.trim().length > 0;
                },
                messages: {
                    en: 'Banner title is required.'
                }
            });
        }
    });
</script>
<?php
render_admin_footer([
    app_asset('assets/libs/parsleyjs/parsley.min.js'),
    app_asset('assets/js/pages/form-validation.init.js'),
]);
