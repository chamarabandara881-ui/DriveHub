<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';

if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: login.php"); exit();
}

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';



if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    try {
       
        if (($action === 'make_admin' || $action === 'remove_admin') && isset($_GET['user_id'])) {
            $target_user_id = (int)$_GET['user_id'];
            if ($target_user_id === (int)$_SESSION['user_id']) {
                echo "<script>alert('You cannot revoke your own admin rights!'); window.location.href='dashboard.php';</script>";
                exit();
            }
            $new_role = ($action === 'make_admin') ? 'Admin' : 'User';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_user_id]);
        }

        if (isset($_POST['add_review'])) {
            $user_name = $_POST['user_name'];
            $designation = $_POST['designation'];
            $rating = (int)$_POST['rating'];
            $review_text = $_POST['review_text'];
            
    
            $user_image = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=150'; // Default image

            if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === 0) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                $file_name = time() . '_' . basename($_FILES['user_image']['name']);
                if (move_uploaded_file($_FILES['user_image']['tmp_name'], $upload_dir . $file_name)) {
                    $user_image = $upload_dir . $file_name;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO reviews (user_name, designation, rating, review_text, user_image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_name, $designation, $rating, $review_text, $user_image]);
            
        
            header("Location: feedback.php?success=1");
            exit();
        }
        
 
        if ($action === 'delete_vehicle' && isset($_GET['id'])) {
            $vehicle_id = (int)$_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicle_id]);
        }


        if ($action === 'confirm_booking' && isset($_GET['booking_id'])) {
            $booking_id = (int)$_GET['booking_id'];
            $stmt = $pdo->prepare("UPDATE rentals SET status = 'Confirmed' WHERE id = ?");
            $stmt->execute([$booking_id]);

            $stmtGetVehicle = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE id = ?");
            $stmtGetVehicle->execute([$booking_id]);
            $vehicle_id = $stmtGetVehicle->fetchColumn();

            if ($vehicle_id) {
                $stmtVehicle = $pdo->prepare("UPDATE vehicles SET status = 'Not Available' WHERE id = ?");
                $stmtVehicle->execute([$vehicle_id]);
            }
        }

        if ($action === 'delete_booking' && isset($_GET['booking_id'])) {
            $booking_id = (int)$_GET['booking_id'];
            $stmt = $pdo->prepare("DELETE FROM rentals WHERE id = ?");
            $stmt->execute([$booking_id]);
        }
        
        header("Location: dashboard.php?tab=" . $current_tab);
        exit();
    } catch (\PDOException $e) {
        $safe_err = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        echo "<script>alert(" . json_encode('Error: ' . $safe_err) . ");</script>";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle_btn'])) {
    try {
        $name = $_POST['name'];
        $brand = $_POST['brand'];
        $purpose = $_POST['purpose'];
        $price_per_day = !empty($_POST['price_per_day']) ? $_POST['price_per_day'] : 0;
        $selling_price = !empty($_POST['selling_price']) ? $_POST['selling_price'] : 0;
        $status = 'Available';

        $stmt = $pdo->prepare("INSERT INTO vehicles (name, brand, purpose, price_per_day, selling_price, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $brand, $purpose, $price_per_day, $selling_price, $status]);
        
        echo "<script>alert('Vehicle Added Successfully!'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (\PDOException $e) {
        $safe_err = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        echo "<script>alert(" . json_encode('Error adding vehicle: ' . $safe_err) . ");</script>";
    }
}


try {
    $total_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $total_rented = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE LOWER(purpose) = 'rent'")->fetchColumn();
    $total_sold = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE LOWER(purpose) = 'sale'")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_bookings = $pdo->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
} catch (\PDOException $e) { $total_vehicles = $total_rented = $total_sold = $total_users = $total_bookings = 0; }


$all_vehicles = []; $all_users = []; $all_bookings = []; $all_feedbacks = []; $all_messages = [];
try {
    $all_vehicles = $pdo->query("SELECT id, name, brand, price_per_day, selling_price, status, LOWER(purpose) AS purpose FROM vehicles ORDER BY id DESC")->fetchAll();
    $all_users = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id DESC")->fetchAll();
    

    $booking_sql = "SELECT r.*,
                           v.brand,
                           v.name  AS vehicle_name,
                           COALESCE(u.name,  r.user_name)  AS user_name,
                           COALESCE(u.email, r.user_email) AS user_email,
                           r.total_price
                    FROM   rentals r
                    JOIN   vehicles v ON r.vehicle_id = v.id
                    LEFT   JOIN users u ON r.user_id = u.id
                    ORDER  BY r.id DESC";
    $all_bookings = $pdo->query($booking_sql)->fetchAll();

  
    $all_feedbacks = $pdo->query("SELECT * FROM feedback ORDER BY id DESC")->fetchAll();
    $all_messages = $pdo->query("SELECT * FROM messages ORDER BY id DESC")->fetchAll();
} catch (\PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub - Central Enterprise Controls</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --bg-dark: #0f172a; --bg-card: #1e293b; --accent: #0d9488; 
            --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155;
            --success: #10b981; --error: #ef4444; --warning: #f59e0b;
        }
        * { margin:0; padding:0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); display: flex; min-height: 100vh; }
        
        
        .sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; display: flex; flex-direction: column; gap: 30px; position: fixed; height: 100vh; }
        .logo { font-size: 22px; font-weight: 800; color: var(--accent); display: flex; align-items: center; gap: 10px; }
        .menu-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .menu-list a { display: flex; align-items: center; gap: 12px; color: var(--text-muted); padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .menu-list a:hover, .menu-list a.active { background: rgba(13, 148, 136, 0.1); color: var(--text-main); border-left: 4px solid var(--accent); }
        
       
        .main-content { flex: 1; margin-left: 260px; padding: 40px; display: flex; flex-direction: column; gap: 30px; }
       
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); padding: 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; }
        .stat-number { font-size: 28px; font-weight: 800; margin-top: 5px; }
        .stat-icon { font-size: 32px; color: var(--accent); opacity: 0.7; }
        
        
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 25px; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .card-title { font-size: 18px; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 10px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 14px; color: var(--text-muted); font-size: 13px; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid var(--border); }
        td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
   
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-muted); font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; background: var(--bg-dark); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 14px; }
        .form-control:focus { border-color: var(--accent); outline: none; }

        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .status-badge.pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .status-badge.confirmed { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        
        .type-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 800; }
        .type-badge.rent { background: rgba(13, 148, 136, 0.2); color: #2dd4bf; }
        .type-badge.sale { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }

        .btn-act { padding: 6px 12px; border-radius: 6px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: 0.2s; }
        .btn-act.confirm { background: var(--success); color: #fff; }
        .btn-act.delete { background: var(--error); color: #fff; }
        
        .btn-primary { background: var(--accent); color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }

        .tabs-nav { display: flex; gap: 10px; margin-bottom: 20px; background: var(--bg-dark); padding: 5px; border-radius: 8px; width: fit-content; }
        .tab-btn { background: transparent; border: none; color: var(--text-muted); padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .tab-btn.active { background: var(--bg-card); color: #fff; }
    </style>
</head>
<body>

   
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-gauge-high"></i> DriveHub</div>
        
        <div>
            <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; padding-left: 12px;">Admin Controls</p>
            <ul class="menu-list">
                <li>
                    <a href="dashboard.php" class="<?php echo ($current_tab === 'dashboard') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Control Panel
                    </a>
                </li>
                <li>
                    <a href="manage_payments.php">
                        <i class="fa-solid fa-credit-card"></i> Manage Payments
                    </a>
                </li>

                <li>
                    <a href="manage_rent_requests.php">
                        <i class="fa-solid fa-calendar-check"></i> Rent Requests
                    </a>
                </li>
                <li>
                    <a href="*.php" class="<?php echo ($current_tab === 'add_vehicle') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-plus"></i> Add New Vehicle
                    </a>
                </li>
                <li>
                    <a href="dashboard.php?tab=feedback" class="<?php echo ($current_tab === 'feedback') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-star"></i> Testimonial Control
                    </a>
                </li>
                <li>
                    <a href="dashboard.php?tab=messages" class="<?php echo ($current_tab === 'messages') ? 'active' : ''; ?>">
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
            </ul>
        </div>

        <ul class="menu-list" style="margin-top: auto;">
            <li><a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> System Exit</a></li>
        </ul>
    </div>

  
    <div class="main-content">
        <div>
            <h1 style="font-size: 28px; font-weight: 800;">Enterprise Analytics Panel</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Real-time administration oversight of assets, users, and transactions.</p>
        </div>

     
        <div class="stats-grid">
            <div class="stat-card">
                <div><p style="color: var(--text-muted); font-weight: 600;">Total Fleet Assets</p><div class="stat-number"><?php echo $total_vehicles; ?></div></div>
                <div class="stat-icon"><i class="fa-solid fa-car"></i></div>
            </div>
            <div class="stat-card">
                <div><p style="color: var(--text-muted); font-weight: 600;">Active Bookings</p><div class="stat-number"><?php echo $total_bookings; ?></div></div>
                <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            </div>
            <div class="stat-card">
                <div><p style="color: var(--text-muted); font-weight: 600;">Registered Clients</p><div class="stat-number"><?php echo $total_users; ?></div></div>
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div><p style="color: var(--text-muted); font-weight: 600;">For Sale Listings</p><div class="stat-number"><?php echo $total_sold; ?></div></div>
                <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
            </div>
        </div>

    
        <?php if ($current_tab === 'payments'): ?>
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-receipt"></i> Completed & Confirmed Payments</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Receipt ID</th>
                            <th>Customer Name</th>
                            <th>Vehicle Transacted</th>
                            <th>Total Amount</th>
                            <th>Transaction Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $confirmed_bookings = array_filter($all_bookings, function($b) {
                            return strtolower($b['status'] ?? '') === 'confirmed';
                        });
                        if (count($confirmed_bookings) > 0):
                            foreach ($confirmed_bookings as $pay): 
                        ?>
                            <tr>
                                <td>#PAY-0<?php echo $pay['id']; ?></td>
                                <td style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($pay['user_name'] ?? 'Client'); ?></td>
                                <td style="font-weight: 600; color: #34d399;"><?php echo htmlspecialchars(($pay['brand'] ?? '') . ' ' . ($pay['vehicle_name'] ?? '')); ?></td>
                                <td style="font-weight: 700; color: #10b981;">LKR <?php echo number_format($pay['total_price'] ?? 0, 2); ?></td>
                                <td><span class="status-badge confirmed"><i class="fa-solid fa-circle-check"></i> PAID & APPROVED</span></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No verified payments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

      
        <?php elseif ($current_tab === 'add_vehicle'): ?>
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-plus"></i> Add New Infrastructure Asset (Vehicle)</div>
                </div>
                <form action="dashboard.php?tab=add_vehicle" method="POST">
                    <div class="form-group">
                        <label>Vehicle Model Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Civic, Vitz, Axio" required>
                    </div>
                    <div class="form-group">
                        <label>Brand / Manufacturer</label>
                        <input type="text" name="brand" class="form-control" placeholder="e.g. Honda, Toyota" required>
                    </div>
                    <div class="form-group">
                        <label>Operation Purpose</label>
                        <select name="purpose" class="form-control" id="purposeSelect" onchange="togglePriceFields()" required>
                            <option value="rent">For Rent</option>
                            <option value="sale">For Sale</option>
                        </select>
                    </div>
                    <div class="form-group" id="rentPriceGroup">
                        <label>Price Per Day (LKR)</label>
                        <input type="number" name="price_per_day" class="form-control" placeholder="e.g. 5000">
                    </div>
                    <div class="form-group" id="salePriceGroup" style="display: none;">
                        <label>Selling Price (LKR)</label>
                        <input type="number" name="selling_price" class="form-control" placeholder="e.g. 7500000">
                    </div>
                    <button type="submit" name="add_vehicle_btn" class="btn-primary"><i class="fa-solid fa-save"></i> Save Asset to Database</button>
                </form>
            </div>

    
        <?php elseif ($current_tab === 'feedback'): ?>
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-comments"></i> User Experience & Feedback Logs</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Feedback ID</th>
                            <th>User Context</th>
                            <th>Message / Testimonial</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_feedbacks) > 0): foreach ($all_feedbacks as $f): ?>
                            <tr>
                                <td>#FB-0<?php echo $f['id']; ?></td>
                                <td style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($f['name'] ?? 'Anonymous'); ?></td>
                                <td><?php echo htmlspecialchars($f['message'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px;">No user feedback records available in database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

   
        <?php elseif ($current_tab === 'messages'): ?>
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-envelope-open-text"></i> Contact Us Inbound Messages</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Email Address</th>
                            <th>Subject</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_messages) > 0): foreach ($all_messages as $m): ?>
                            <tr>
                                <td style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($m['name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($m['email'] ?? ''); ?></td>
                                <td style="color: var(--accent); font-weight: 600;"><?php echo htmlspecialchars($m['subject'] ?? 'No Subject'); ?></td>
                                <td><?php echo htmlspecialchars($m['message'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">Inbound contact inbox is currently empty.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

   
        <?php else: ?>
          
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-calendar-days"></i> Client Vehicle Rental Bookings</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Client Details</th>
                            <th>Vehicle Requested</th>
                            <th>Pickup Date & Time</th>
                            <th>Return Date & Time</th>
                            <th>Status</th>
                            <th>Admin Decisions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($all_bookings) > 0): ?>
                            <?php foreach($all_bookings as $b): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: bold; color: #fff;"><?php echo htmlspecialchars($b['user_name'] ?? 'Unknown User'); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                        <i class="fa-solid fa-phone" style="font-size: 10px;"></i> <?php echo htmlspecialchars($b['user_phone'] ?? 'N/A'); ?> <br>
                                        <i class="fa-solid fa-id-card" style="font-size: 10px;"></i> <?php echo htmlspecialchars($b['nic_number'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td style="font-weight: 600; color: #34d399;"><?php echo htmlspecialchars(($b['brand'] ?? '') . ' ' . ($b['vehicle_name'] ?? '')); ?></td>
                                <td style="font-size: 13px;"><?php echo isset($b['pickup_date_time']) ? date('Y-m-d h:i A', strtotime($b['pickup_date_time'])) : ''; ?></td>
                                <td style="font-size: 13px;"><?php echo isset($b['return_date_time']) ? date('Y-m-d h:i A', strtotime($b['return_date_time'])) : ''; ?></td>
                                <td><span class="status-badge <?php echo strtolower($b['status'] ?? 'pending'); ?>"><?php echo strtoupper($b['status'] ?? 'PENDING'); ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <?php if(strtolower($b['status'] ?? '') === 'pending'): ?>
                                            <a href="dashboard.php?action=confirm_booking&booking_id=<?php echo $b['id']; ?>" class="btn-act confirm" onclick="return confirm('Approve this rental reservation?');"><i class="fa-solid fa-check"></i> Approve</a>
                                        <?php endif; ?>
                                        <a href="dashboard.php?action=delete_booking&booking_id=<?php echo $b['id']; ?>" class="btn-act delete" onclick="return confirm('Permanently delete/reject this booking?');"><i class="fa-solid fa-trash-can"></i> Reject</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No rental reservations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>




          
            <div class="card" id="fleet-section">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-car-side"></i> Vehicle Fleet Infrastructure</div>
                    <a href="add_vehicle.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Add Vehicle</a>
                </div>
                <div class="tabs-nav">
                    <button class="tab-btn active" onclick="switchTab('all', this)">All Fleet (<?php echo count($all_vehicles); ?>)</button>
                    <button class="tab-btn" onclick="switchTab('rent', this)">For Rent</button>
                    <button class="tab-btn" onclick="switchTab('sale', this)">For Sale</button>
                </div>
                <table id="vehicle-table">
                    <thead>
                        <tr>
                            <th>Vehicle Name</th>
                            <th>Brand</th>
                            <th>Price / Rate</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_vehicles as $v): $curr_purp = strtolower($v['purpose']); ?>
                        <tr class="vehicle-row" data-purpose="<?php echo $curr_purp; ?>">
                            <td style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($v['name']); ?></td>
                            <td><?php echo htmlspecialchars($v['brand']); ?></td>
                            <td style="color: #34d399; font-weight: 700;">
                                <?php echo ($curr_purp === 'sale') ? 'LKR '.number_format($v['selling_price'],2) : 'LKR '.number_format($v['price_per_day'],2).' / Day'; ?>
                            </td>
                            <td><span class="type-badge <?php echo $curr_purp; ?>"><?php echo strtoupper($curr_purp); ?></span></td>
                            <td><span class="status-badge <?php echo strtolower($v['status']); ?>"><?php echo htmlspecialchars($v['status']); ?></span></td>
                            <td>
                                
                                <a href="edit_vehicle.php?id=<?php echo $v['id']; ?>" class="btn-act edit" style="margin-right: 10px; color: #f59e0b;">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
                                
                               
                                <a href="dashboard.php?action=delete_vehicle&id=<?php echo $v['id']; ?>" class="btn-act delete" onclick="return confirm('Delete vehicle?');" style="color:rgb(8, 8, 8);">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

          
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-users-gear"></i> Core System Authentications (Users)</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Email Architecture</th>
                            <th>Assigned Authority</th>
                            <th>Privilege Modification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_users as $u): ?>
                        <tr>
                            <td>#US-0<?php echo $u['id']; ?></td>
                            <td style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: <?php echo $u['role'] === 'Admin' ? 'rgba(239,68,68,0.15)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $u['role'] === 'Admin' ? '#f87171' : 'var(--text-muted)'; ?>;"><?php echo htmlspecialchars($u['role']); ?></span></td>
                            <td>
                                <?php if((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <?php if($u['role'] === 'Admin'): ?>
                                        <a href="dashboard.php?action=remove_admin&user_id=<?php echo $u['id']; ?>" class="btn-act delete" style="background:#f97316;"><i class="fa-solid fa-user-minus"></i> Demote</a>
                                    <?php else: ?>
                                        <a href="dashboard.php?action=make_admin&user_id=<?php echo $u['id']; ?>" class="btn-act confirm" style="background:#3b82f6;"><i class="fa-solid fa-user-shield"></i> Promote</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size:12px; color:var(--text-muted); font-style:italic;">You (Current Session)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
    
        function switchTab(purpose, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const rows = document.querySelectorAll('.vehicle-row');
            rows.forEach(row => {
                if (purpose === 'all' || row.getAttribute('data-purpose') === purpose) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

   
        function togglePriceFields() {
            const purpose = document.getElementById('purposeSelect').value;
            if(purpose === 'rent') {
                document.getElementById('rentPriceGroup').style.display = 'block';
                document.getElementById('salePriceGroup').style.display = 'none';
            } else {
                document.getElementById('rentPriceGroup').style.display = 'none';
                document.getElementById('salePriceGroup').style.display = 'block';
            }
        }
    </script>
</body>
</html>