<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; 

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name      = trim($_POST['user_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $dob       = $_POST['dob'] ?? '';
    $gender    = $_POST['gender'] ?? '';
    $address   = trim($_POST['address'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm_p = $_POST['confirm_password'] ?? '';


    if (empty($name) || empty($email) || empty($username) || empty($phone) || empty($dob) || empty($gender) || empty($address) || empty($password)) {
        $error_msg = "All system registration parameters are mandatory.";
    } elseif ($password !== $confirm_p) {
        $error_msg = "Security access keys do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters.";
    } else {
        try {
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $chk->execute([$email, $username]);
            
            if ($chk->fetch()) {
                $error_msg = "This email or username is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, username, phone, dob, gender, address, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Customer')");
                
                if ($stmt->execute([$name, $email, $username, $phone, $dob, $gender, $address, $hashed_password])) {
                    $success_msg = "Identity created successfully! Redirecting...";
                    echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
                } else {
                    $error_msg = "Database communication failure.";
                }
            }
        } catch (\PDOException $e) {
            $error_msg = "System Error: " . $e->getMessage();
        }
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-header">
            <h2>Join the Matrix</h2>
            <p>Create your DriveHub Consumer Identity profile.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="auth-alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div class="auth-alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form" autocomplete="off">
            <div class="form-group-row-custom">
                <div class="form-group-custom">
                    <label>Full Legal Name</label>
                    <input type="text" name="user_name" value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group-custom">
                    <label>System Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group-row-custom">
                <div class="form-group-custom">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group-custom">
                    <label>Mobile Contact</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group-row-custom">
                <div class="form-group-custom">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required>
                </div>
                <div class="form-group-custom">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="" disabled selected>Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="form-group-custom">
                <label>Residential Address</label>
                <textarea name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group-row-custom">
                <div class="form-group-custom">
                    <label>Password</label>
                    <input type="password" id="reg-pass" name="password" required>
                </div>
                <div class="form-group-custom">
                    <label>Confirm Password</label>
                    <input type="password" id="reg-confirm" name="confirm_password" required>
                </div>
            </div>

            <button type="submit" class="btn-auth-submit">Initialize Registration</button>
        </form>

        <div class="auth-footer">
            <p>Already an authenticated user? <a href="login.php">Access Secure Portal</a></p>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>