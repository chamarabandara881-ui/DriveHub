<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub - Premium Automotive Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=2.0">
</head>
<body>

<header class="master-navbar">
    <a href="index.php" class="nav-logo">
        <i class="fa-solid fa-car-side logo-icon"></i>
        <span>Drive<span class="logo-accent">Hub</span></span>
    </a>

    <div class="nav-search-wrapper">
        <form action="vehicle_list.php" method="GET" class="nav-search-form">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="search" placeholder="Search brands, models, sedans, SUVs..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="nav-search-btn">Find</button>
        </form>
    </div>

    <nav class="nav-menu">
    <a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i> Showroom
    </a>
    <a href="vehicle_list.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'vehicle_list.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-car"></i> Explore Fleet
    </a>
    <a href="about.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-circle-info"></i> About
    </a>
    <a href="contact.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-headset"></i> Contact
    </a>

    <?php if (isset($_SESSION['user_id'])): ?>

        <?php if (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'Admin') === 0): ?>
            <a href="dashboard.php" class="btn-nav-portal" style="background: #0d9488; color: white; font-weight: 700; border-radius: 8px; padding: 8px 14px;">
                <i class="fa-solid fa-user-shield"></i> Admin Panel
            </a>
        <?php endif; ?>

        <span class="nav-divider"></span>

        <a href="customer_profile.php" class="nav-profile-link">
            <div class="nav-avatar"><i class="fa-solid fa-user"></i></div>
            <span>Profile</span>
        </a>
        <a href="logout.php" class="nav-auth-btn logout" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
        
    <?php else: ?>
     
        <a href="login.php" class="nav-auth-btn login"><i class="fa-solid fa-user-lock"></i> Portal Login</a>
    <?php endif; ?>
</nav>
</header>