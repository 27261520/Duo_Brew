<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Landing Page / Redirect
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// If logged in, redirect to appropriate dashboard based on actual role
if (isset($_SESSION['user_id'])) {
    if (hasAnyRole(['Cashier', 'Cashier/Staff', 'Staff'])) {
        header('Location: ' . BASE_URL . 'cashier/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    }
    exit;
}

// Otherwise, show the landing page
$pageTitle = 'Welcome';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= BUSINESS_NAME ?> - <?= BUSINESS_TAGLINE ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6B3A2A 0%, #8B5E3C 50%, #D4A574 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border: none;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        .hero-logo {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #6B3A2A, #8B5E3C);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(107,58,42,0.3);
        }
        .hero-logo i {
            font-size: 3rem;
            color: #F5E6D3;
        }
        .hero-title {
            color: #6B3A2A;
            font-weight: 800;
            font-size: 2.5rem;
        }
        .hero-subtitle {
            color: #8B5E3C;
            font-size: 1.1rem;
        }
        .hero-btn {
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }
        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .btn-staff {
            background: linear-gradient(135deg, #6B3A2A, #8B5E3C);
            border: none;
            color: #fff;
        }
        .btn-staff:hover {
            color: #fff;
            box-shadow: 0 10px 30px rgba(107,58,42,0.4);
        }
        .btn-customer {
            background: #F5E6D3;
            color: #6B3A2A;
            border: 2px solid #6B3A2A;
        }
        .btn-customer:hover {
            background: #6B3A2A;
            color: #fff;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            color: #666;
        }
        .feature-list li i {
            color: #D4A574;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="hero-card text-center">
                        <div class="hero-logo">
                            <i class="fas fa-mug-hot"></i>
                        </div>
                        <h1 class="hero-title"><?= BUSINESS_NAME ?></h1>
                        <p class="hero-subtitle mb-4"><?= BUSINESS_TAGLINE ?></p>
                        
                        <div class="d-grid gap-3 mb-4">
                            <a href="auth/login.php" class="btn hero-btn btn-staff">
                                <i class="fas fa-user-tie me-2"></i>Staff Login
                            </a>
                            <a href="self-order/index.php" class="btn hero-btn btn-customer">
                                <i class="fas fa-utensils me-2"></i>Customer Self-Ordering
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <ul class="feature-list text-start">
                            <li><i class="fas fa-check-circle"></i> Point-of-Sale System</li>
                            <li><i class="fas fa-check-circle"></i> Self-Ordering Kiosk</li>
                            <li><i class="fas fa-check-circle"></i> Real-time Order Tracking</li>
                            <li><i class="fas fa-check-circle"></i> Inventory Management</li>
                            <li><i class="fas fa-check-circle"></i> Sales Analytics & Reports</li>
                        </ul>
                        
                        <p class="text-muted small mt-3">
                            &copy; <?= date('Y') ?> <?= BUSINESS_NAME ?>. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>