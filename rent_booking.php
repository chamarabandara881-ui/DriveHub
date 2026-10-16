<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=rent_booking.php&id=" . (isset($_GET['id']) ? (int)$_GET['id'] : 0));
    exit();
}

$logged_in_user_id = (int)$_SESSION['user_id'];

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$vehicle = null;
if ($vehicle_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch();
}

if (!$vehicle || strtolower($vehicle['purpose'] ?? $vehicle['listing_type'] ?? '') === 'sale') {
    header("Location: index.php");
    exit();
}

$images_array = json_decode($vehicle['image_url'] ?? '', true);
$img_src = (!empty($images_array) && is_array($images_array)) ? $images_array[0] : ($vehicle['image'] ?? 'uploads/default.jpg');

$message = "";
$message_type = "";

if (isset($_POST['submit_booking'])) {
    $user_name  = trim($_POST['user_name']);
    $user_email = trim($_POST['user_email']);
    $user_phone = trim($_POST['user_phone']);
    $nic_number = trim($_POST['nic_number']);
    $pickup      = $_POST['pickup_date'];
    $return      = $_POST['return_date'];
    $total_price = (float)$_POST['total_price'];

    if (strtotime($pickup) >= strtotime($return)) {
        $message = "Error: Return date must be after the Pickup date!";
        $message_type = "error";
    } else {
        try {
            $check_sql = "SELECT COUNT(*) FROM rentals
                          WHERE vehicle_id = ?
                            AND LOWER(status) NOT IN ('cancelled', 'rejected')
                            AND NOT (return_date_time <= ? OR pickup_date_time >= ?)";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$vehicle_id, $pickup, $return]);

            if ($check_stmt->fetchColumn() > 0) {
                $message = "Sorry! This vehicle is already booked for these dates.";
                $message_type = "error";
            } else {

                $ins_sql = "INSERT INTO rentals
                                (user_id, vehicle_id, user_name, user_email, user_phone, nic_number,
                                 pickup_date_time, return_date_time, total_price, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                $ins_stmt = $pdo->prepare($ins_sql);
                $ins_stmt->execute([
                    $logged_in_user_id,
                    $vehicle_id,
                    $user_name,
                    $user_email,
                    $user_phone,
                    $nic_number,
                    $pickup,
                    $return,
                    $total_price
                ]);

                $message = "Success! Your booking has been placed and is now Pending approval.";
                $message_type = "success";
            }
        } catch (\PDOException $e) {
            $message = "System Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Vehicle - <?php echo htmlspecialchars($vehicle['name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --bg-dark: #0f172a; --bg-card: #1e293b; --accent: #0d9488; 
            --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155;
            --success: #10b981; --error: #ef4444;
        }
        * { margin:0; padding:0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--bg-dark); color: var(--text-main); padding: 40px 20px; display: flex; justify-content: center; }
        .container { width: 100%; max-width: 700px; }
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 30px; }
        h2 { color: var(--accent); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .vehicle-info { background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid var(--accent); }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .full-width { grid-column: span 2; }
        label { font-size: 14px; font-weight: 600; color: var(--text-muted); }
        input { padding: 12px; background: var(--bg-dark); border: 1px solid var(--border); color: #fff; border-radius: 8px; font-size: 15px; outline: none; }
        input:focus { border-color: var(--accent); }
        
        .alert { padding: 15px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid var(--success); }
        .alert.error { background: rgba(239, 68, 68, 0.15); color: var(--error); border: 1px solid var(--error); }
        
        .btn-submit { background: var(--accent); color: #fff; padding: 14px; border: none; border-radius: 8px; font-weight: 700; width: 100%; cursor: pointer; margin-top: 25px; font-size: 16px; transition: 0.3s; }
        .btn-submit:hover { background: #0f766e; box-shadow: 0 0 15px rgba(13, 148, 136, 0.4); }
        .back-link { display: inline-block; margin-top: 15px; color: var(--text-muted); text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2><i class="fa-solid fa-key"></i> Vehicle Rental Booking</h2>

        <div class="vehicle-info">
   
            <img src="<?php echo htmlspecialchars($img_src); ?>" 
                style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;" 
                alt="Vehicle Image" 
                onerror="this.src='uploads/default.jpg'">
        </div>
        
        <div class="vehicle-info">
            <p style="font-weight: bold; font-size: 18px;"><?php echo htmlspecialchars($vehicle['brand'] . ' - ' . $vehicle['name']); ?></p>
            <p style="color: #34d399; margin-top: 5px; font-weight: 600;">Rate: LKR <?php echo number_format($vehicle['price_per_day'], 2); ?> / Day</p>
        </div>

      
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>


<form method="POST" id="bookingForm">
   
    <input type="hidden" id="dailyPrice" name="daily_price" value="<?php echo $vehicle['price_per_day']; ?>">

    <div class="form-grid">
       
        <div class="form-group"><label>Your Name</label><input type="text" name="user_name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="user_email" required></div>
        <div class="form-group"><label>Phone</label><input type="tel" name="user_phone" required></div>
        <div class="form-group"><label>NIC</label><input type="text" name="nic_number" required></div>
      
        <div class="form-group">
            <label>Pick-up Date:</label>
            <input type="date" id="startDate" name="pickup_date" min="<?php echo date('Y-m-d'); ?>" required onchange="calculateTotalDays()">
        </div>
        <div class="form-group">
            <label>Return Date:</label>
            <input type="date" id="endDate" name="return_date" min="<?php echo date('Y-m-d'); ?>" required onchange="calculateTotalDays()">
        </div>
    </div>

    
    <div class="price-summary-box" style="background: #1e293b; padding: 15px; border-radius: 8px; margin-top: 15px;">
        <p>Total Days: <span id="displayDays" style="color: #38bdf8;">0</span> Days</p>
        <p>Total Rent Price: Rs. <span id="displayTotal" style="color: #4ade80;">0.00</span></p>
    </div>


    <input type="hidden" id="totalPriceInput" name="total_price" value="0">

   
    <button type="submit" name="submit_booking" class="btn-submit">
        <i class="fa-solid fa-check-to-slot"></i> Confirm My Booking
    </button>
</form>
        <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Cancel and go back to Showroom</a>
    </div>
</div>
<script>
function calculateTotalDays() {
    const startDateVal = document.getElementById('startDate').value;
    const endDateVal = document.getElementById('endDate').value;
    const dailyPrice = parseFloat(document.getElementById('dailyPrice').value);

   
    if (startDateVal && endDateVal) {
        const start = new Date(startDateVal);
        const end = new Date(endDateVal);

        if (end < start) {
            alert("Return date cannot be before the start date!");
            document.getElementById('endDate').value = startDateVal;
            return;
        }

     
        const timeDiff = end.getTime() - start.getTime();
        let totalDays = Math.ceil(timeDiff / (1000 * 3600 * 24));

        
        if (totalDays === 0) {
            totalDays = 1;
        }

    
        const totalPrice = totalDays * dailyPrice;

        document.getElementById('displayDays').innerText = totalDays;
        document.getElementById('displayTotal').innerText = totalPrice.toLocaleString('en-US', { minimumFractionDigits: 2 });
        
        
        document.getElementById('totalPriceInput').value = totalPrice;
    }
}
</script>

</body>
</html>