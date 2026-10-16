<?php
require_once 'db.php';

$message = "";


try {
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
} catch (\PDOException $e) {
    die("Database Matrix Error: " . $e->getMessage() . "<br>Please ensure your 'users' table structure is imported.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
    $adminName = trim($_POST['admin_name']);
    $adminEmail = trim($_POST['admin_email']);
    $adminPhone = trim($_POST['admin_phone']);
    $adminPassword = $_POST['admin_password'];
    

    $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);
    
    try {
    
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, dob, gender, address, role) VALUES (?, ?, ?, ?, '1990-01-01', 'Male', 'DriveHub HQ, Colombo', 'admin')");
        $stmt->execute([$adminName, $adminEmail, $hashedPassword, $adminPhone]);
        
        $message = "<div class='status-badge status-approved' style='display:block; padding:15px; margin-bottom:15px; text-align:center;'>
                        <i class='fa-solid fa-circle-check'></i> Master Admin Account Created Successfully!
                    </div>";
        

        $checkAdmin = 1;
        
    } catch (\PDOException $e) {
        $message = "<div class='status-badge status-sold' style='display:block; padding:15px; margin-bottom:15px; text-align:center;'>
                        Execution Failed: Email already exists or table structure mismatch.
                    </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub - Admin Deployment Interface</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; background:#1a202c; min-height:100vh; padding:20px;">

    <div class="form-container" style="width:100%; max-width:500px; padding:40px;">
        <div style="text-align:center; margin-bottom:25px;">
            <span style="font-size: 45px; color: var(--accent-teal);"><i class="fa-solid fa-user-shield"></i></span>
            <h2 style="margin-top:10px; color: var(--text-dark);">Admin Initialization Node</h2>
            <p style="font-size:13px; color: var(--text-muted); margin-top:5px;">Deploy master administrative credentials into core database</p>
        </div>

        <?php echo $message; ?>

        <?php if ($checkAdmin > 0 && !isset($_POST['admin_name'])): ?>
      
            <div style="background:#fffaf0; border-left:4px solid var(--accent-orange); padding:15px; border-radius:4px; font-size:14px; line-height:1.6; color:#c05621;">
                <i class="fa-solid fa-triangle-exclamation"></i> <b>Security Notice:</b> An administrative account already exists within the live database matrix. For security reasons, multi-initialization is locked.
            </div>
            <a href="login.php" class="btn-submit" style="display:block; text-align:center; text-decoration:none; margin-top:20px; background:var(--bg-sidebar);">Proceed to Login Terminal</a>
        <?php else: ?>
       
            <form action="create_admin.php" method="POST">
                <div class="form-group">
                    <label><i class="fa-solid fa-user"></i> Full Administrator Identity Name</label>
                    <input type="text" name="admin_name" placeholder="e.g., Chief Admin" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fa-solid fa-envelope"></i> Secure System Email Address</label>
                    <input type="email" name="admin_email" placeholder="admin@drivehub.lk" required>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-phone"></i> Direct Telecom Line</label>
                    <input type="text" name="admin_phone" placeholder="+94771234567" required>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-key"></i> Master Authorization Password</label>
                    <input type="password" name="admin_password" placeholder="••••••••" required minlength="6">
                </div>

                <button type="submit" class="btn-submit" style="background: var(--accent-teal); margin-top:10px;">
                    Inject Admin Credentials
                </button>
                
                <p style="text-align:center; margin-top:20px; font-size:13px;">
                    <a href="login.php" style="color:var(--accent-blue); text-decoration:none; font-weight:600;">Cancel and Return to Terminal</a>
                </p>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>