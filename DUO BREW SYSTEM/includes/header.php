<?php
/**
 * Duo Brew POS & Self-Ordering System
 * Reusable Header Include
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Require authentication for pages using this header
if (!isset($publicPage) || !$publicPage) {
    requireAuth();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= BUSINESS_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    
    <!-- Page-specific CSS -->
    <?php if (isset($extraStyles)): ?>
        <?php foreach ((array)$extraStyles as $style): ?>
            <link href="<?= $style ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    
<?php if (isset($showSidebar) && $showSidebar !== false): ?>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>
    
    <!-- Page Content -->
    <div id="page-content-wrapper">
        <!-- Top Navigation -->
        <?php include_once __DIR__ . '/navbar.php'; ?>
        
        <div class="container-fluid px-4 py-3">
            <?= showFlashMessages() ?>
<?php endif; ?>