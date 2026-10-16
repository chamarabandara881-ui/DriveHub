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

$success_msg = "";
$error_msg = "";

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    try {
        $del_stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        if ($del_stmt->execute([$delete_id])) {
   
            header("Location: " . $current_page . "?success=1");
            exit();
        } else {
            $error_msg = "Failed to purge the selected message.";
        }
    } catch (\PDOException $e) {
        $error_msg = "Error: " . htmlspecialchars($e->getMessage());
    }
}


if (isset($_GET['success'])) {
    $success_msg = "Message deleted successfully from the terminal.";
}


$messages = [];
try {
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY id DESC");
    $messages = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error_msg = "Database Error: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console | User Transmissions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #0f172a;
            --panel-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-teal: #0d9488;
            --danger-red: #f43f5e;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

 
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--panel-bg);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 35px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
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

        .sidebar .logo i {
            color: var(--accent-teal);
        }

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

        .menu-list li a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }


        .admin-main-content {
            flex: 1;
            padding: 40px 40px;
            min-height: 100vh;
            box-sizing: border-box;
            background-color: var(--bg-dark);
            margin-left: var(--sidebar-width);
            overflow-x: hidden;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h2 {
            font-size: 28px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 5px;
            color: var(--text-main);
        }
        p.subtitle {
            color: var(--text-muted);
            margin-bottom: 30px;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: rgba(13, 148, 136, 0.2); color: #2dd4bf; border: 1px solid var(--accent-teal); }
        .alert-danger { background: rgba(244, 63, 94, 0.2); color: #fda4af; border: 1px solid var(--danger-red); }
        
        .msg-table-wrapper {
            background: var(--panel-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 16px 20px;
            font-size: 14px;
        }
        th {
            background-color: rgba(15, 23, 42, 0.4);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        tr:last-child { border: none; }
        td strong { color: #fff; }
        .msg-body {
            max-width: 350px;
            color: #cbd5e1;
            white-space: pre-line;
            background: rgba(15, 23, 42, 0.2);
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
        }
        .btn-delete {
            background: var(--danger-red);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-delete:hover {
            background: #e11d48;
            transform: scale(1.03);
        }
        .empty-tr {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
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
                <a href="admin_message.php" class="<?php echo ($current_page == 'admin_message.php' || $current_page == 'admin_messages.php') ? 'active' : ''; ?>">
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
        </ul>
    </div>  
    
    <ul class="menu-list" style="margin-top: auto;">
        <li><a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> System Exit</a></li>
    </ul>
</div>


<div class="admin-main-content">
    <div class="admin-container">
        <h2><i class="fa-solid fa-envelope-open-text"></i> Inbound Transmission Terminal</h2>
        <p class="subtitle">Review and manage secure operational messages sent by clients via the contact portal.</p>

 
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="msg-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="20%">Sender Intel</th>
                        <th width="25%">Subject Core</th>
                        <th width="40%">Message Content</th>
                        <th width="15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($messages) > 0): ?>
                        <?php foreach($messages as $msg): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['name'] ?? 'Unknown'); ?></strong><br>
                                    <span style="color: var(--accent-teal); font-size: 12px;"><?php echo htmlspecialchars($msg['email'] ?? 'No Email'); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['subject'] ?? 'No Subject'); ?></strong>
                                </td>
                                <td>
                                    <div class="msg-body"><?php echo htmlspecialchars($msg['message'] ?? ''); ?></div>
                                </td>
                                <td>
   
                                    <a href="<?php echo $current_page; ?>?delete_id=<?php echo $msg['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to permanently purge this transmission?');">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-tr">
                                <i class="fa-solid fa-folder-open" style="font-size: 30px; margin-bottom: 10px; display:block;"></i>
                                No inbound transmissions detected in the log matrix.
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