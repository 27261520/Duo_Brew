<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Login Page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in based on user role
if (isset($_SESSION['user_id'])) {
    if (hasRole('Administrator')) {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'pos/index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $db = Database::getInstance();
        $user = $db->getRow(
            "SELECT u.*, r.role_name FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.username = ? AND u.status = 'active'",
            [$username],
            's'
        );

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['role_id'] = $user['role_id'];
            
            // Update last login
            $db->execute(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']],
                'i'
            );

            // Log activity
            logActivity('Login', 'User logged in');
            
            // Direct Non-Administrators straight to POS
            if ($user['role_name'] === 'Administrator') {
                $redirect = BASE_URL . 'admin/dashboard.php';
            } else {
                unset($_SESSION['redirect_url']);
                $redirect = BASE_URL . 'pos/index.php';
            }
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= BUSINESS_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6B3A2A 0%, #8B5E3C 50%, #D4A574 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #6B3A2A, #8B5E3C);
            padding: 2rem;
            text-align: center;
        }
        .login-header h1 {
            color: #F5E6D3;
            font-size: 1.8rem;
            font-weight: 700;
        }
        .login-header p {
            color: #D4A574;
            margin-bottom: 0;
        }
        .login-body {
            padding: 2.5rem;
            background: #fff;
        }
        .logo-icon {
            width: 80px;
            height: 80px;
            background: #F5E6D3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .logo-icon i {
            font-size: 2.5rem;
            color: #6B3A2A;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #6B3A2A;
            box-shadow: none;
        }
        .btn-login {
            background: linear-gradient(135deg, #6B3A2A, #8B5E3C);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            color: #fff;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(107, 58, 42, 0.4);
            color: #fff;
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 2px solid #e9ecef;
            border-right: none;
            background: #f8f9fa;
            color: #6B3A2A;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="login-header">
                        <div class="logo-icon">
                            <i class="fas fa-mug-hot"></i>
                        </div>
                        <h1><?= BUSINESS_NAME ?></h1>
                        <p><?= BUSINESS_TAGLINE ?></p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="username" class="form-control" 
                                           placeholder="Enter username" required autofocus>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Enter password" required>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <button type="submit" class="btn btn-login w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>

                        <hr class="my-4">
                        <div class="text-center">
                            <a href="<?= SELF_ORDER_URL ?>" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-utensils me-2"></i>Self-Ordering
                            </a>
                        </div>
                    </div>
                </div>
                <p class="text-center text-white mt-3 opacity-75 small">
                    &copy; <?= date('Y') ?> <?= BUSINESS_NAME ?>. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>