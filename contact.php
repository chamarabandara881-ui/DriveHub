<?php
require_once 'header.php';

$success_msg = "";
$error_msg = "";



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "All operational fields are mandatory to transmit message.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Invalid email routing vector format.";
    } else {

        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $success_msg = "Your secure transmission has been successfully routed to the DriveHub Executive Admin Console!";
       
            $name = $email = $subject = $message = "";
        } else {
            $error_msg = "Core critical error during message encryption pipeline.";
        }
    }
}
?>


<section class="contact-hero">
    <div class="contact-hero-overlay">
        <h1>Connect with Headquarters</h1>
        <p>Direct communication pipeline for elite fleet inquiries, corporate partnerships, and operational support.</p>
    </div>
</section>


<div class="contact-wrapper">
    <div class="contact-grid-container">
        
        
        <div class="contact-intel-panel">
            <h2>HQ Control Terminal</h2>
            <p class="intel-lead-text">Reach out to our centralized communication node for rapid structural response.</p>
            
            <div class="intel-list">
                <div class="intel-item">
                    <div class="intel-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="intel-text">
                        <h4>Lucky Rent</h4>
                        <p>No. 45, Kandy Road, Nawalapitiya, Sri Lanka.</p>
                    </div>
                </div>
                
                <div class="intel-item">
                    <div class="intel-icon"><i class="fa-solid fa-phone-volume"></i></div>
                    <div class="intel-text">
                        <h4>Direct Hotline</h4>
                        <p>+94 11 234 5678 / +94 77 123 4567</p>
                    </div>
                </div>
                
                <div class="intel-item">
                    <div class="intel-icon"><i class="fa-solid fa-headset"></i></div>
                    <div class="intel-text">
                        <h4>Secure Email Matrix</h4>
                        <p>luckyrent@drivehub.lk</p>
                    </div>
                </div>
            </div>

            <!-- Operating Hours Node -->
            <div class="intel-hours-card">
                <h5><i class="fa-solid fa-clock"></i> Fleet Operations Hours</h5>
                <p>Support Matrix: 24/7/365 Available</p>
                <p>Physical HQ Showroom: 08:00 AM – 06:00 PM (Daily)</p>
            </div>
        </div>

        <!-- Right Column: Secure Transmission Form -->
        <div class="contact-form-panel">
            <h3>Transmit Secure Message</h3>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Fill out the cryptographic data node below, and an executive administrator will review it shortly.</p>

            <!-- Dynamic Network Alerts -->
            <?php if (!empty($error_msg)): ?>
                <div class="auth-alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($success_msg)): ?>
                <div class="auth-alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form action="contact.php" method="POST" class="profile-form">
                
                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-signature"></i> Your Legal Name</label>
                        <input type="text" name="name" placeholder="e.g., ABC..." value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    </div>
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-envelope"></i> Routing Email Vector</label>
                        <input type="email" name="email" placeholder="e.g., abc@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label><i class="fa-solid fa-circle-info"></i> Inquiry Subject Core</label>
                    <input type="text" name="subject" placeholder="e.g., Luxury Fleet Long-Term Rental Quote" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                </div>

                <div class="form-group-custom">
                    <label><i class="fa-solid fa-pen-to-square"></i> Comprehensive Message Body</label>
                    <textarea name="message" rows="5" placeholder="Elaborate your automotive requirements in full detail..." style="width:100%; background:white; border:1px solid #334155; border-radius:var(--radius-sm); padding:12px; color:black; font-family:inherit; font-size:14px; outline:none; resize:vertical;" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-auth-submit" style="background: var(--accent-teal); margin-top: 10px;">Transmit Encrypted Message</button>
            </form>
        </div>

    </div>
</div>

<?php require_once 'footer.php'; ?>