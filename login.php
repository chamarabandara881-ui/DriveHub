<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';


if (isset($_SESSION['role']) && isset($_SESSION['user_email'])) {
    header("Location: " . (strcasecmp($_SESSION['role'], 'Admin') === 0 ? 'dashboard.php' : 'index.php'));
    exit;
}

$error_msg   = "";
$success_msg = "";
$redirect_js = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error_msg = "Please enter your email (or username) and password.";
    } else {
        try {
          
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['user_name']   = $user['name'];
                $_SESSION['user_email']  = $user['email'];
                $_SESSION['role']        = $user['role'];

                $success_msg = "Login successful. Redirecting...";

               
                session_write_close();

                if (strcasecmp($user['role'], 'Admin') === 0) {
                    $redirect_js = "<script>setTimeout(function(){ window.location.href='dashboard.php'; }, 1500);</script>";
                } else {
                    $redirect_js = "<script>setTimeout(function(){ window.location.href='index.php'; }, 1500);</script>";
                }
            } else {
                $error_msg = "Invalid credentials. Please try again.";
            }
        } catch (\PDOException $e) {
            $error_msg = "A database error occurred. Please try again: " . $e->getMessage();
        }
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-container" style="max-width: 460px;">
        <div class="auth-header">
            <h2>Portal Authentication</h2>
            <p>Provide your registered network credentials to establish a secure session.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="auth-alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div class="auth-alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="form-group-custom">
                <label><i class="fa-solid fa-envelope"></i> Identity Email or Username</label>
                <input type="text" name="email" placeholder="johndoe@example.com or username" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group-custom">
                <label><i class="fa-solid fa-lock"></i> Security Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="login-pass" name="password" placeholder="••••••••" required>
                    <i class="fa-solid fa-eye toggle-password-icon" onclick="toggleLoginPassVisibility()"></i>
                </div>
            </div>

            <button type="submit" class="btn-auth-submit">Authorize Session Login</button>
        </form>

        <div class="auth-footer">
            <p>New to the platform? <a href="register.php">Create Secure Identity</a></p>
        </div>
    </div>
</div>

<script>
function toggleLoginPassVisibility() {
    const passwordField = document.getElementById("login-pass");
    const iconElement = document.querySelector(".toggle-password-icon");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        iconElement.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        passwordField.type = "password";
        iconElement.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

<?php echo $redirect_js; ?>
<?php require_once 'footer.php'; ?>