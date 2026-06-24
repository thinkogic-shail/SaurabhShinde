<?php

function app_asset(string $path): string
{
    return 'themesdesign.in/nazox/layouts/' . ltrim($path, '/');
}

function is_menu_active(string $menuKey, string $activeMenu): string
{
    return $menuKey === $activeMenu ? 'mm-active' : '';
}

function is_link_active(string $menuKey, string $activeMenu): string
{
    return $menuKey === $activeMenu ? 'active' : '';
}

function render_admin_header(string $title, array $extraCss = [], string $activeMenu = 'dashboard', bool $showPageTitle = true): void
{
    $mobile = $_SESSION['admin_mobile'] ?? 'Admin';
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> | Saurabh Shinde Foundation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?php echo app_asset('assets/images/favicon.ico'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo app_asset('assets/css/bootstrap.min.css'); ?>" id="bootstrap-style" rel="stylesheet" type="text/css">
    <link href="<?php echo app_asset('assets/css/icons.min.css'); ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo app_asset('assets/css/app.min.css'); ?>" id="app-style" rel="stylesheet" type="text/css">
<?php foreach ($extraCss as $css): ?>
    <link href="<?php echo htmlspecialchars($css, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" type="text/css">
<?php endforeach; ?>
    <style>
        body,
        .page-content,
        #layout-wrapper {
            background-color: #fff !important;
        }
        body,
        button,
        input,
        select,
        textarea,
        .btn,
        .form-control,
        .form-select,
        .page-title-box,
        .navbar-brand-box,
        .vertical-menu,
        .card,
        .table,
        .breadcrumb,
        .alert,
        .badge {
            font-family: 'Josefin Sans', sans-serif !important;
        }
        .badge.bg-success,
        .badge.rounded-pill.bg-success {
            background-color: #16A34A !important;
        }
        .badge.rounded-pill {
            border-radius: 0.375rem !important;
            padding: 0.45em 0.7em !important;
            line-height: 1.1 !important;
        }
        .page-title-box h4 {
            font-size: 23px !important;
        }
        .breadcrumb,
        .breadcrumb-item,
        .breadcrumb-item a {
            font-size: 13px !important;
        }
        .navbar-brand-box .logo {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        .navbar-brand-box .logo-sm,
        .navbar-brand-box .logo-lg {
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .navbar-brand-box .logo-sm {
            display: none;
        }
        .navbar-brand-box .logo-lg {
            display: flex;
        }
        .vertical-collpsed .navbar-brand-box .logo-sm {
            display: flex;
        }
        .vertical-collpsed .navbar-brand-box .logo-lg {
            display: none;
        }
        .navbar-brand-box .logo-sm .header-logo-image {
            max-width: 34px;
            max-height: 34px;
            width: auto;
            height: auto;
            display: block;
            margin: 18px auto;
            object-fit: contain;
        }
        .navbar-brand-box .logo-lg .header-logo-image {
            max-width: 165px;
            max-height: 60px;
            width: auto;
            height: auto;
            display: block;
            margin: 16px auto;
            object-fit: contain;
        }
        .pagination {
            --bs-pagination-padding-x: 0.75rem;
            --bs-pagination-padding-y: 0.5rem;
            --bs-pagination-font-size: 0.9rem;
            --bs-pagination-color: #002253;
            --bs-pagination-active-bg: #002253;
            --bs-pagination-active-border-color: #002253;
            --bs-pagination-hover-color: #00183b;
        }
        .page-item.active .page-link {
            background-color: #002253 !important;
            border-color: #002253 !important;
        }
        .page-link {
            color: #002253;
        }
    </style>
</head>
<body data-sidebar="dark" data-keep-enlarged="true" class="vertical-collpsed">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box">
                        <a href="dashboard.php" class="logo logo-light">
                            <span class="logo-sm">
                                <img src="<?php echo app_asset('assets/images/sidebar-logo-collapsed.svg'); ?>" alt="Saurabh Shinde Foundation Logo" class="header-logo-image">
                            </span>
                            <span class="logo-lg">
                                <img src="<?php echo app_asset('assets/images/sidebar-logo-expanded.svg'); ?>" alt="Saurabh Shinde Foundation Logo" class="header-logo-image">
                            </span>
                        </a>
                    </div>

                    <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect" id="vertical-menu-btn">
                        <i class="ri-menu-2-line align-middle"></i>
                    </button>
                </div>

                <div class="d-flex">
                    <div class="dropdown d-inline-block user-dropdown">
                        <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img class="rounded-circle header-profile-user" src="<?php echo app_asset('assets/images/users/avatar-2.jpg'); ?>"
                                alt="Header Avatar">
                            <span class="d-none d-xl-inline-block ms-1"><?php echo htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="#"><i class="ri-user-line align-middle me-1"></i> Profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="logout.php"><i class="ri-shut-down-line align-middle me-1 text-danger"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="vertical-menu">
            <div data-simplebar class="h-100">
                <div id="sidebar-menu">
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li class="menu-title">Menu</li>

                        <li class="<?php echo is_menu_active('dashboard', $activeMenu); ?>">
                            <a href="dashboard.php" class="waves-effect <?php echo is_link_active('dashboard', $activeMenu); ?>">
                                <i class="ri-dashboard-line"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('ward', $activeMenu); ?>">
                            <a href="wards.php" class="waves-effect <?php echo is_link_active('ward', $activeMenu); ?>">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Ward</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('area', $activeMenu); ?>">
                            <a href="areas.php" class="waves-effect <?php echo is_link_active('area', $activeMenu); ?>">
                                <i class="ri-road-map-line"></i>
                                <span>Area</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('age-category', $activeMenu); ?>">
                            <a href="age-categories.php" class="waves-effect <?php echo is_link_active('age-category', $activeMenu); ?>">
                                <i class="ri-user-star-line"></i>
                                <span>Age Category</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('request-type', $activeMenu); ?>">
                            <a href="request-types.php" class="waves-effect <?php echo is_link_active('request-type', $activeMenu); ?>">
                                <i class="ri-file-list-3-line"></i>
                                <span>Request Type</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('my-requests', $activeMenu); ?>">
                            <a href="my-requests.php" class="waves-effect <?php echo is_link_active('my-requests', $activeMenu); ?>">
                                <i class="ri-inbox-archive-line"></i>
                                <span>My Requests</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
<?php if ($showPageTitle): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="mb-1"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>
                                <div>
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
<?php endif; ?>
<?php
}

function render_admin_footer(array $extraJs = []): void
{
    ?>
                </div>
            </div>
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> © Saurabh Shinde Foundation.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Admin Panel
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="<?php echo app_asset('assets/libs/jquery/jquery.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/metismenu/metisMenu.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/simplebar/simplebar.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/node-waves/waves.min.js'); ?>"></script>
<?php foreach ($extraJs as $js): ?>
    <script src="<?php echo htmlspecialchars($js, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>
    <script src="<?php echo app_asset('assets/js/app.js'); ?>"></script>
</body>
</html>
<?php
}
