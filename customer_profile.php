<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php'; 

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

$current_avatar = !empty($user['profile_image']) ? $user['profile_image'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name              = trim($_POST['full_name']);
    $phone             = trim($_POST['phone']);
    $dob               = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $gender            = $_POST['gender'] ?? null;
    $address           = trim($_POST['address']);
    $zipcode           = trim($_POST['zipcode']);
    $nic               = trim($_POST['nic']);
    $license_no        = trim($_POST['license_no']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $pref_comm         = $_POST['pref_comm'] ?? 'email';
    $profile_image_path = $user['profile_image'];


    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
  
            if (!is_dir('./uploads')) {
                mkdir('./uploads', 0777, true);
            }

            $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExtension;
            $dest_path = './uploads/' . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
             
                if (!empty($user['profile_image']) && file_exists($user['profile_image']) && strpos($user['profile_image'], 'default') === false) {
                    unlink($user['profile_image']);
                }
                $profile_image_path = $dest_path;
            } else {
                $error_msg = "Error moving the uploaded file to server.";
            }
        } else {
            $error_msg = "Formats allowed: JPG, JPEG, PNG, WEBP.";
        }
    }

    if (empty($error_msg)) {
        if (empty($name) || empty($phone)) {
            $error_msg = "Core identity fields (Name & Phone) are absolute requirements.";
        } else {
            $update_query = "UPDATE users SET 
                name = ?, phone = ?, dob = ?, gender = ?, 
                address = ?, zipcode = ?, nic = ?, license_no = ?, 
                emergency_contact = ?, pref_comm = ?, profile_image = ? 
                WHERE id = ?";
            
            $update_stmt = $pdo->prepare($update_query);
            $params = [$name, $phone, $dob, $gender, $address, $zipcode, $nic, $license_no, $emergency_contact, $pref_comm, $profile_image_path, $user_id];
            
            if ($update_stmt->execute($params)) {
                $success_msg = "DriveHub Enterprise Profile Matrix updated successfully!";
                $_SESSION['user_name'] = $name;

                echo "<script>setTimeout(function(){ window.location.href = 'customer_profile.php'; }, 1500);</script>";
            } else {
                $error_msg = "Database transactional failure.";
            }
        }
    }
}


require_once 'header.php'; 
?>

<div class="profile-wrapper">
    <div class="profile-container">
        
   
        <div class="profile-sidebar-card">
            <div class="avatar-wrapper">
                <img src="<?php echo htmlspecialchars($current_avatar); ?>" id="profile-display" alt="User Avatar">
            </div>
            <h3><?php echo htmlspecialchars($user['name'] ?? 'DriveHub User'); ?></h3>
            <p><span class="role-pill"><?php echo strtoupper($user['role'] ?? 'CUSTOMER'); ?> SECURITY CLEARED</span></p>
            
            <div class="profile-meta-info">
                <span><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                <span><i class="fa-solid fa-id-card"></i> NIC: <?php echo !empty($user['nic']) ? htmlspecialchars($user['nic']) : 'Not Verified'; ?></span>
                <span><i class="fa-solid fa-address-card"></i> DL: <?php echo !empty($user['license_no']) ? htmlspecialchars($user['license_no']) : 'No License Linked'; ?></span>
                <span><i class="fa-solid fa-bell"></i> Alerts: <?php echo ucfirst($user['pref_comm'] ?? 'email'); ?></span>
            </div>
        </div>

   
        <div class="profile-main-card">
            <h2>Enterprise Identity Matrix</h2>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 25px;">Provide comprehensive KYC (Know Your Customer) tokens to allow automated vehicle check-outs.</p>

         
            <?php if (!empty($error_msg)): ?>
                <div class="auth-alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>
            <?php if (!empty($success_msg)): ?>
                <div class="auth-alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <form action="customer_profile.php" method="POST" enctype="multipart/form-data" class="profile-form">
                
        
                <div class="form-section-title"><i class="fa-solid fa-camera"></i> Profile Image Vector</div>
                <div class="form-group-custom">
                    <input type="file" name="profile_pic" accept="image/*" onchange="previewImage(this)">
                </div>

                <div class="form-section-title"><i class="fa-solid fa-user-tie"></i> Personal Dossier</div>
                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-user"></i> Full Legal Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-venus-mars"></i> Gender Spectrum</label>
                        <select name="gender" class="form-select-custom">
                            <option value="" <?php echo empty($user['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="male" <?php echo (($user['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (($user['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo (($user['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Prefer Not to Say</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-calendar-days"></i> Chronological DOB</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                    </div>
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-phone"></i> Contact Vector</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>

              
                <div class="form-section-title"><i class="fa-solid fa-shield-halved"></i> Legal & Security Verification</div>
                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-id-card"></i> NIC / Passport Identifier</label>
                        <input type="text" name="nic" placeholder="e.g., 1999XXXXXX or V" value="<?php echo htmlspecialchars($user['nic'] ?? ''); ?>">
                    </div>
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-address-card"></i> Driving License Serial</label>
                        <input type="text" name="license_no" placeholder="e.g., B1234567" value="<?php echo htmlspecialchars($user['license_no'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group-custom">
                    <label><i class="fa-solid fa-heart-pulse"></i> Emergency Contact (Name & Mobile)</label>
                    <input type="text" name="emergency_contact" placeholder="Amma - 0771234567" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                </div>

                <div class="form-section-title"><i class="fa-solid fa-map-location-dot"></i> Physical Location & Preferences</div>
                <div class="form-group-custom">
                    <label><i class="fa-solid fa-house-chimney"></i> Verification Residential Address</label>
                    <textarea name="address" rows="2" placeholder="Street name, City, Sri Lanka"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-map-pin"></i> Postal / ZIP Code</label>
                        <input type="text" name="zipcode" placeholder="10000" value="<?php echo htmlspecialchars($user['zipcode'] ?? ''); ?>">
                    </div>
                    <div class="form-group-custom">
                        <label><i class="fa-solid fa-circle-nodes"></i> Notification Channel</label>
                        <select name="pref_comm" class="form-select-custom">
                            <option value="email" <?php echo (($user['pref_comm'] ?? 'email') === 'email') ? 'selected' : ''; ?>>Email Protocols</option>
                            <option value="whatsapp" <?php echo (($user['pref_comm'] ?? '') === 'whatsapp') ? 'selected' : ''; ?>>WhatsApp Sync</option>
                            <option value="sms" <?php echo (($user['pref_comm'] ?? '') === 'sms') ? 'selected' : ''; ?>>Direct SMS Network</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit" style="background: var(--accent-teal); margin-top: 15px;">Commit Full Profile Verification</button>
            </form>
        </div>

    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-display').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'footer.php'; ?>