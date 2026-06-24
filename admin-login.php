<?php
session_start();

$config = require __DIR__ . '/config/database.php';

$mobile = '';
$error_msg = '';

function is_valid_identifier(string $value): bool
{
    return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($mobile === '' || $pass === '') {
        $error_msg = 'Please fill in all fields.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error_msg = 'Mobile number must be exactly 10 digits.';
    } else {
        $table = $config['admin_table'] ?? '';
        $mobileColumn = $config['mobile_column'] ?? '';
        $passwordColumn = $config['password_column'] ?? '';
        $idColumn = $config['id_column'] ?? '';

        if (
            !is_valid_identifier($table) ||
            !is_valid_identifier($mobileColumn) ||
            !is_valid_identifier($passwordColumn) ||
            !is_valid_identifier($idColumn)
        ) {
            $error_msg = 'Database configuration is invalid. Check config/database.php.';
        } else {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['dbname'],
                    $config['charset']
                );

                $pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                $sql = sprintf(
                    'SELECT `%s`, `%s` FROM `%s` WHERE `%s` = :mobile AND IsActive = 1 LIMIT 1',
                    $idColumn,
                    $passwordColumn,
                    $table,
                    $mobileColumn
                );

                $stmt = $pdo->prepare($sql);
                $stmt->execute(['mobile' => $mobile]);
                $admin = $stmt->fetch();

                if (!$admin) {
                    $error_msg = 'Invalid mobile number or password.';
                } else {
                    $storedPassword = (string) ($admin[$passwordColumn] ?? '');
                    $isPasswordValid = password_verify($pass, $storedPassword) || hash_equals($storedPassword, $pass);

                    if ($isPasswordValid) {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $admin[$idColumn] ?? null;
                        $_SESSION['admin_mobile'] = $mobile;

                        header('Location: dashboard.php');
                        exit;
                    }

                    $error_msg = 'Invalid mobile number or password.';
                }
            } catch (PDOException $e) {
                $error_msg = 'Database connection failed. Check config/database.php and your server credentials.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Admin Panel Login - Saurabh Shinde Foundation</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- App favicon -->
        <link rel="shortcut icon" href="themesdesign.in/nazox/layouts/assets/images/favicon.ico">
        <!-- Bootstrap Css -->
        <link href="themesdesign.in/nazox/layouts/assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <!-- Icons Css -->
        <link href="themesdesign.in/nazox/layouts/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="themesdesign.in/nazox/layouts/assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
        <style>
            .auth-body-bg,
            body {
                background:
                    radial-gradient(circle at top left, rgba(232, 199, 163, 0.35) 0%, rgba(232, 199, 163, 0) 42%),
                    linear-gradient(135deg, #f4eadc 0%, #f8f2ea 36%, #fbf7f2 100%) !important;
            }
            .error-message {
                color: #dc3545;
                font-size: 0.875rem;
                margin-top: 0.4rem;
                display: none;
            }
            .authentication-page-content {
                background: #002253;
            }
            .login-visual-panel .authentication-bg {
                min-height: 100vh;
                background-image: url('uploads/admin-login-side-panel.png');
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
            }
            .login-visual-panel .bg-overlay {
                background: transparent;
            }
        </style>
    </head>
    <body class="auth-body-bg">
        <div>
            <div class="container-fluid p-0">
                <div class="row g-0">
                    <div class="col-lg-4">
                        <div class="authentication-page-content p-4 d-flex align-items-center min-vh-100">
                            <div class="w-100">
                                <div class="row justify-content-center">
                                    <div class="col-lg-9">
                                        <div>
                                            <div class="text-center">
                                                <div>
                                                    <a href="#" class="authentication-logo">
                                                        <img src="uploads/admin-login-logo.svg" alt="Saurabh Shinde Foundation Logo" height="92" class="auth-logo mx-auto">
                                                    </a>
                                                </div>
    
                                                <!--<h4 class="font-size-18 mt-4 text-primary" style="color: #002b6b !important; font-weight: 700;">Saurabh Shinde Foundation</h4>-->
                                                <!--<p>Admin Panel Login</p>-->
                                            </div>

                                            <div class="p-2 mt-5">
                                                <?php if ($error_msg !== ''): ?>
                                                    <div class="alert alert-danger" role="alert">
                                                        <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <form id="loginForm" method="POST" action="" novalidate>
                    
                                                    <div class="mb-3 auth-form-group-custom mb-4">
                                                        <i class="ri-phone-line auti-custom-input-icon"></i>
                                                        <label for="mobile" class="fw-semibold">Mobile Number</label>
                                                        <input type="text" class="form-control" id="mobile" name="mobile" placeholder="Enter 10-digit mobile number" value="<?php echo htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                                                        <div id="mobileError" class="error-message">Valid 10-digit mobile number is required.</div>
                                                    </div>
                            
                                                    <div class="mb-3 auth-form-group-custom mb-4">
                                                        <i class="ri-lock-2-line auti-custom-input-icon"></i>
                                                        <label for="password" class="fw-semibold">Password</label>
                                                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
                                                        <div id="passwordError" class="error-message">Password is required.</div>
                                                    </div>
                            
                                                    <div class="mt-4 text-center">
                                                        <button class="btn btn-primary w-md waves-effect waves-light" type="submit" style="background-color: #F6AF2E; border-color: #F6AF2E; color: #ffffff;">Log In</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <div class="mt-5 text-center text-white">
                                                <p>© <script>document.write(new Date().getFullYear())</script> Saurabh Shinde Foundation.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 login-visual-panel">
                        <div class="authentication-bg">
                            <div class="bg-overlay"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JAVASCRIPT -->
        <script src="themesdesign.in/nazox/layouts/assets/libs/jquery/jquery.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/metismenu/metisMenu.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/simplebar/simplebar.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/libs/node-waves/waves.min.js"></script>
        <script src="themesdesign.in/nazox/layouts/assets/js/app.js"></script>
        
        <script>
            document.getElementById('loginForm').addEventListener('submit', function (e) {
                let isValid = true;
    
                const mobile = document.getElementById('mobile').value.trim();
                const mobileError = document.getElementById('mobileError');
                const mobileRegex = /^[0-9]{10}$/;
    
                if (!mobile || !mobileRegex.test(mobile)) {
                    mobileError.style.display = 'block';
                    document.getElementById('mobile').classList.add('is-invalid');
                    isValid = false;
                } else {
                    mobileError.style.display = 'none';
                    document.getElementById('mobile').classList.remove('is-invalid');
                }
    
                const password = document.getElementById('password').value;
                const passwordError = document.getElementById('passwordError');
    
                if (!password) {
                    passwordError.style.display = 'block';
                    document.getElementById('password').classList.add('is-invalid');
                    isValid = false;
                } else {
                    passwordError.style.display = 'none';
                    document.getElementById('password').classList.remove('is-invalid');
                }
    
                if (!isValid) {
                    e.preventDefault();
                }
            });
    
            document.getElementById('mobile').addEventListener('input', function () {
                document.getElementById('mobileError').style.display = 'none';
                this.classList.remove('is-invalid');
                this.value = this.value.replace(/[^0-9]/g, '');
    
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
    
            document.getElementById('password').addEventListener('input', function () {
                document.getElementById('passwordError').style.display = 'none';
                this.classList.remove('is-invalid');
            });
        </script>
    </body>
</html>
