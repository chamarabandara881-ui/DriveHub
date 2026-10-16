<?php

$current_page = basename($_SERVER['PHP_SELF']);


$current_tab = isset($_GET['tab']) ? $_GET['tab'] : '';
?>


<div class="sidebar">
    <div class="logo"><i class="fa-solid fa-gauge-high"></i> DriveHub</div>
    

    <div>
        <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; padding-left: 12px;">Admin Controls</p>
        <ul class="menu-list">
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> Control Panel
                </a>
            </li>
            <li>
                <a href="manage_payments.php" class="<?php echo ($current_page == 'manage_payments.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-credit-card"></i> Manage Payments
                </a>
            </li>
            <li>
                <a href="manage_rent_requests.php" class="<?php echo ($current_page == 'manage_rent_requests.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-check"></i> Rent Requests
                </a>
            </li>
            <li>
                <a href="add_vehicle.php" class="<?php echo ($current_page == 'add_vehicle.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-plus"></i> Add New Vehicle
                </a>
            </li>
            <li>
                <a href="feedback.php" class="<?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-star"></i> Testimonial Control
                </a>
            </li>
            <li>
                <a href="admin_message.php" class="<?php echo ($current_page == 'admin_message.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-envelope-open-text"></i> Inbound Messages
                </a>
            </li>
        </ul>
    </div>

    <div>
        <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; padding-left: 12px;">Main Navigation</p>
        <ul class="menu-list">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Showroom</a></li>
            <li><a href="vehicle_list.php"><i class="fa-solid fa-car"></i> Explore Fleet</a></li>
            <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About Us</a></li>
            <li><a href="contact.php"><i class="fa-solid fa-envelope"></i> Contact</a></li>
        </ul>
    </div>

    <ul class="menu-list" style="margin-top: auto;">
        <li><a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> System Exit</a></li>
    </ul>
</div>