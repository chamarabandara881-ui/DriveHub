<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';


if (!isset($_SESSION['role']) || strcasecmp($_SESSION['role'], 'Admin') !== 0) {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);


if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $feedback_id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE feedback SET status = 'Approved' WHERE id = ?");
        $stmt->execute([$feedback_id]);
        
   
        header("Location: manage_feedback.php?success=1");
        exit();
    } catch (\PDOException $e) {
        $error_msg = "Error updating database: " . $e->getMessage();
    }
}

$success_msg = "";
if (isset($_GET['success'])) {
    $success_msg = "User feedback approved and published successfully!";
}


$all_feedback = [];
try {
    $sql = "SELECT f.*, u.name AS user_name, u.email AS user_email 
            FROM feedback f 
            JOIN users u ON f.user_id = u.id
            ORDER BY f.id DESC";
    $all_feedback = $pdo->query($sql)->fetchAll();
} catch (\PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}

$pending_feedback = array_filter($all_feedback, function($f) {
    return strcasecmp($f['status'] ?? '', 'Pending') === 0;
});
$approved_feedback = array_filter($all_feedback, function($f) {
    return strcasecmp($f['status'] ?? '', 'Approved') === 0;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub Console | Feedback Approvals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-dark: #0f172a; --bg-card: #1e293b; --accent: #0d9488; 
            --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155;
            --success: #10b981; --error: #ef4444; --warning: #f59e0b;
        }
        * { margin:0; padding:0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); display: flex; min-height: 100vh; }
        
       
        .sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border); padding: 24px; display: flex; flex-direction: column; gap: 30px; position: fixed; height: 100vh; overflow-y: auto; }
        .logo { font-size: 22px; font-weight: 800; color: var(--accent); display: flex; align-items: center; gap: 10px; }
        .menu-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .menu-list a { display: flex; align-items: center; gap: 12px; color: var(--text-muted); padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .menu-list a:hover, .menu-list a.active { background: rgba(13, 148, 136, 0.1); color: var(--text-main); border-left: 4px solid var(--accent); }
        
  
        .main-content { flex: 1; margin-left: 260px; padding: 40px; display: flex; flex-direction: column; gap: 30px; }
        
      
        .alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 10px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: var(--error); border: 1px solid rgba(239, 68, 68, 0.2); }

   
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
        .card-title { font-size: 18px; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 10px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 14px; color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid var(--border); }
        td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-badge.pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .status-badge.approved { background: rgba(16, 185, 129, 0.15); color: var(--success); }

        .btn-act { padding: 8px 14px; border-radius: 6px; border: none; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; }
        .btn-act.confirm { background: var(--success); color: #fff; }
        .btn-act.confirm:hover { background: #059669; }
        
        .checked-badge { color: var(--success); font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; }

        .tabs-nav { display: flex; gap: 10px; margin-bottom: 20px; background: var(--bg-dark); padding: 5px; border-radius: 8px; width: fit-content; }
        .tab-btn { background: transparent; border: none; color: var(--text-muted); padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .tab-btn.active { background: var(--bg-card); color: #fff; }

        .feedback-text { max-width: 400px; word-break: break-word; color: #e2e8f0; font-style: italic; }
    </style>
</head>
<body>


    <div class="sidebar">
        <div class="logo"><i class="fa-solid fa-gauge-high"></i> DriveHub</div>
        
        <div>
            <p style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; padding-left: 12px;">Admin Controls</p>
            <ul class="menu-list">
                <li>
                    <a href="dashboard.php">
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
                    <a href="add_vehicle.php">
                        <i class="fa-solid fa-plus"></i> Add New Vehicle
                    </a>
                </li>
                <li>
                    <a href="feedback.php">
                        <i class="fa-solid fa-star"></i> Testimonial Control
                    </a>
                </li>
                <li>
                    <a href="admin_message.php">
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
            <h1 style="font-size: 28px; font-weight: 800;">Feedback Verification Center</h1>
            <p style="color: var(--text-muted); margin-top: 5px;">Moderate and approve user testimonials before publishing them live.</p>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header-flex">
                <div class="card-title"><i class="fa-solid fa-comments"></i> Customer Feedback Entries</div>
                <div class="tabs-nav">
                    <button class="tab-btn active" onclick="switchFilter('pending', this)">Pending (<?php echo count($pending_feedback); ?>)</button>
                    <button class="tab-btn" onclick="switchFilter('approved', this)">Approved (<?php echo count($approved_feedback); ?>)</button>
                    <button class="tab-btn" onclick="switchFilter('all', this)">All Feedback (<?php echo count($all_feedback); ?>)</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Feedback ID</th>
                        <th>User Profile</th>
                        <th>Testimonial / Review Content</th>
                        <th>Submission Date</th>
                        <th>Moderation Status</th>
                        <th>Approvals Action</th>
                    </tr>
                </thead>
                <tbody id="feedback-table-body">
                    <?php if (count($all_feedback) > 0): ?>
                        <?php foreach ($all_feedback as $f): 
                            $status_lower = strtolower($f['status'] ?? 'pending');
                        ?>
                        <tr class="feedback-row" data-status="<?php echo $status_lower; ?>">
                            <td>#FB-0<?php echo $f['id']; ?></td>
                            <td>
                                <div style="font-weight: 700; color: #fff;"><?php echo htmlspecialchars($f['user_name']); ?></div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    <i class="fa-solid fa-envelope" style="font-size: 9px;"></i> <?php echo htmlspecialchars($f['user_email']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="feedback-text">
                                    "<?php echo htmlspecialchars($f['content']); ?>"
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px;">
                                    <?php echo date('Y-m-d h:i A', strtotime($f['created_at'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $status_lower; ?>">
                                    <?php echo strtoupper($f['status'] ?? 'PENDING'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status_lower === 'pending'): ?>
                                    <a href="manage_feedback.php?action=approve&id=<?php echo $f['id']; ?>" 
                                       class="btn-act confirm" 
                                       onclick="return confirm('Do you want to publish this user testimonial?');">
                                        <i class="fa-solid fa-thumbs-up"></i> Approve Feedback
                                    </a>
                                <?php else: ?>
                                    <span class="checked-badge">
                                        <i class="fa-solid fa-circle-check"></i> Published
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="no-records-row"><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No feedback logs found in database.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function switchFilter(status, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const rows = document.querySelectorAll('.feedback-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    if (rowStatus === status) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            let noRecords = document.getElementById('temp-no-records');
            if (visibleCount === 0) {
                if (!noRecords) {
                    noRecords = document.createElement('tr');
                    noRecords.id = 'temp-no-records';
                    noRecords.innerHTML = `<td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No ${status} feedback submissions.</td>`;
                    document.getElementById('feedback-table-body').appendChild(noRecords);
                } else {
                    noRecords.innerHTML = `<td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">No ${status} feedback submissions.</td>`;
                    noRecords.style.display = '';
                }
            } else {
                if (noRecords) {
                    noRecords.style.display = 'none';
                }
            }
        }
        
        window.addEventListener('DOMContentLoaded', () => {
            switchFilter('pending', document.querySelector('.tab-btn'));
        });
    </script>
</body>
</html>
