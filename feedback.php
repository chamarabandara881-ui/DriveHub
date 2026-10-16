<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: login.php"); exit();
}

if (!defined('ROOT_DIR')) { define('ROOT_DIR', __DIR__); }
require_once ROOT_DIR . '/db.php';

if(!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']));
}

$message = '';
$message_type = '';

if (isset($_POST['add_review'])) {
    $user_name = $_POST['user_name'];
    $designation = $_POST['designation'];
    $rating = (int)$_POST['rating'];
    $review_text = $_POST['review_text'];
    $user_image = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=150';

    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $file_name = time() . '_' . basename($_FILES['user_image']['name']);
        if (move_uploaded_file($_FILES['user_image']['tmp_name'], $upload_dir . $file_name)) {
            $user_image = $upload_dir . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO reviews (user_name, designation, rating, review_text, user_image) VALUES (?, ?, ?, ?, ?)");
    if($stmt->execute([$user_name, $designation, $rating, $review_text, $user_image])) {
        $message = "Feedback added successfully!";
        $message_type = "success";
    } else {
        $message = "Database error occurred.";
        $message_type = "error";
    }
}

if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_id']]);
    header('Location: feedback.php');
    exit;
}

$all_reviews = $pdo->query("SELECT * FROM reviews ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub - Manage Testimonials</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --bg-dark: #0f172a; 
            --panel-bg: #1e293b; 
            --accent-teal: #0d9488; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8; 
            --border: #334155;
            --success: #10b981;
            --error: #ef4444;
            --sidebar-width: 260px;
        }
        
        * { margin:0; padding:0; box-sizing: border-box; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-dark); 
            color: var(--text-main); 
            display: flex; 
            min-height: 100vh; 
        }
        
     
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--panel-bg); 
            border-right: 1px solid rgba(255, 255, 255, 0.05); 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 35px; 
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .sidebar .logo { 
            font-size: 22px; 
            font-weight: 700; 
            color: #fff; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            padding-left: 12px;
        }
        
        .sidebar .logo i { color: var(--accent-teal); }
        
        .menu-list { 
            list-style: none; 
            padding: 0;
            margin: 0;
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }
        
        .menu-list li a { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            color: var(--text-muted); 
            text-decoration: none; 
            padding: 12px 15px; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 500; 
            transition: all 0.2s ease; 
        }
        
        .menu-list li a:hover { 
            background: rgba(255, 255, 255, 0.03); 
            color: #fff; 
        }
        
        .menu-list li a.active { 
            background: var(--accent-teal); 
            color: #fff; 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3); 
        }
        
        .menu-list li a i { font-size: 16px; width: 20px; text-align: center; }
  
        .main-content { 
            flex: 1; 
            padding: 40px; 
            margin-left: var(--sidebar-width); 
            display: flex; 
            flex-direction: column; 
            gap: 30px; 
        }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        
        .btn-primary { background: var(--accent-teal); color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; border: none; cursor: pointer; font-size: 14px; }
        .btn-primary:hover { background: #0f766e; box-shadow: 0 0 15px rgba(13, 148, 136, 0.4); }
        .btn-secondary { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s; cursor: pointer; }
        .btn-secondary:hover { background: rgba(255,255,255,0.05); color: #fff; }

        .card { background: var(--panel-bg); border: 1px solid var(--border); border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .card-title { font-size: 20px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; color: var(--accent-teal); border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 14px; font-weight: 600; color: var(--text-muted); }
        .form-group input, .form-group select, .form-group textarea { padding: 12px; background: var(--bg-dark); border: 1px solid var(--border); color: #fff; border-radius: 8px; font-size: 15px; outline: none; transition: 0.2s; font-family: inherit;}
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--accent-teal); box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2); }
        
     
        .alert { padding: 16px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid var(--success); }
        .alert.error { background: rgba(239, 68, 68, 0.15); color: var(--error); border: 1px solid var(--error); }

        .form-actions { display: flex; justify-content: flex-end; gap: 14px; margin-top: 20px; }

      
        .msg-table-wrapper { background: var(--panel-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.2); border: 1px solid var(--border); margin-top: 10px;}
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 16px 20px; font-size: 14px; }
        th { background-color: rgba(15, 23, 42, 0.4); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
        tr:last-child { border: none; }
        
        .btn-edit { color: #38bdf8; text-decoration: none; font-weight: 600; margin-right: 15px; transition: 0.2s; }
        .btn-edit:hover { color: #7dd3fc; }
        .btn-delete { color: var(--error); text-decoration: none; font-weight: 600; transition: 0.2s; }
        .btn-delete:hover { color: #fca5a5; }
        
        .review-snippet { background: rgba(15, 23, 42, 0.2); padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #cbd5e1; max-width: 320px;}
        .star-color { color: #f59e0b; }
    </style>
</head>
<body>

  
    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-gauge-high"></i> DriveHub</div>
        
        
        <div>
            <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 12px;">Admin Controls</p>
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
            <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-left: 12px;">Main Navigation</p>
            <ul class="menu-list">
                <li><a href="index.php"><i class="fa-solid fa-house"></i> Showroom</a></li>
                <li><a href="vehicle_list.php"><i class="fa-solid fa-car"></i> Explore Fleet</a></li>
                <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About Us</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope"></i> Contact</a></li>
                <li><a href="logout.php" style="color: #f87171; margin-top: 15px;"><i class="fa-solid fa-right-from-bracket"></i> System Exit</a></li>
            </ul>
        </div>  
    </div>

 
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1 style="font-size: 28px; font-weight: 800; margin: 0;">Manage Testimonials</h1>
                <p style="color: var(--text-muted); margin-top: 5px; margin-bottom: 0;">Moderate, edit and maintain user experience feedback.</p>
            </div>
            <a href="dashboard.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

       
        <div class="card">
            <div class="card-title"><i class="fa-solid fa-comment-plus"></i> Inject New Testimonial Node</div>
            
         
            <form action="<?php echo BASE_URL; ?>feedback.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Customer Name *</label>
                        <input type="text" name="user_name" required placeholder="e.g., Malinda Alwis">
                    </div>
                    
                    <div class="form-group">
                        <label>Designation / Position</label>
                        <input type="text" name="designation" placeholder="e.g., Managing Director, Avant Matrix">
                    </div>
                    
                    <div class="form-group">
                        <label>Rating (Stars)</label>
                        <select name="rating">
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    
                    
                    <div class="form-group">
                        <label>User Avatar (Photo) - Optional</label>
                        <input type="file" name="user_image" accept="image/*">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Review Text *</label>
                        <textarea name="review_text" rows="4" required placeholder="Type the customer experience here..."></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_review" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Review Vector</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-title"><i class="fa-solid fa-database"></i> Existing Testimonials Matrix</div>
            
            <div class="msg-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th width="20%">User</th>
                            <th width="25%">Designation</th>
                            <th width="15%">Rating</th>
                            <th width="25%">Review Snippet</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($all_reviews) > 0): ?>
                            <?php foreach ($all_reviews as $rev): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rev['user_name']); ?></strong></td>
                                <td><span style="color: #cbd5e1;"><?php echo htmlspecialchars($rev['designation']); ?></span></td>
                                <td>
                                    <span class="star-color">
                                        <?php 
                                        for($i=1; $i<=$rev['rating']; $i++) { echo "★"; }
                                        for($i=$rev['rating']; $i<5; $i++) { echo "☆"; }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="review-snippet"><?php echo htmlspecialchars(substr($rev['review_text'], 0, 60)) . '...'; ?></div>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>edit_feedback.php?id=<?php echo $rev['id']; ?>" class="btn-edit"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    <a href="<?php echo BASE_URL; ?>feedback.php?delete_id=<?php echo $rev['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to permanently purge this transmission?');"><i class="fa-solid fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    <i class="fa-solid fa-comment-slash" style="font-size: 26px; margin-bottom: 10px; display:block;"></i>
                                    No testimonials available in the current sector.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>