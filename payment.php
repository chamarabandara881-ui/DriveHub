<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: vehicle_list.php");
    exit();
}

$vehicle_id = intval($_GET['id']);
$days = isset($_GET['days']) ? intval($_GET['days']) : 1;


$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$v = $stmt->fetch();

if (!$v) {
    header("Location: vehicle_list.php");
    exit();
}


$payable_amount = 0;
$payment_description = "";

if (strcasecmp($v['purpose'] ?? '', 'Sale') === 0) {
    $payable_amount = 100000.00; 
    $payment_description = "Advance Payment for Sale";
} else {
    $payable_amount = (float)($v['price_per_day'] * $days);
    $payment_description = "Full Rental Payment for " . $days . " Day(s)";
}


if(isset($_POST['pay_now'])) {
    $user = $_POST['user_name'];
    $amount = $_POST['amount'];
    
    
    $stmt = $pdo->prepare("INSERT INTO payments (user_name, amount, status) VALUES (?, ?, 'Pending')");
    $stmt->execute([$user, $amount]);
    
    $payment_id = $pdo->lastInsertId();
    header("Location: confirm_payment.php?id=$payment_id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_process_pay'])) {
    $user_id = $_SESSION['user_id'];
    $payment_method = $_POST['pay_method'];
    
  
    if (strcasecmp($v['purpose'] ?? '', 'Sale') !== 0 && isset($_POST['start_date']) && isset($_POST['end_date'])) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $daily_price = (float)($v['price_per_day'] ?? 0);

        $date1 = new DateTime($start_date);
        $date2 = new DateTime($end_date);
        $interval = $date1->diff($date2);
        $total_days = $interval->days;

        if ($total_days == 0) { $total_days = 1; }
        
        $payable_amount = $total_days * $daily_price;
        $payment_description = "Full Rental Payment for " . $total_days . " Day(s)";
    }
    
 
    $status = 'Pending'; 
    
    try {
        $pdo->beginTransaction(); 


        $stmt_pay = $pdo->prepare("INSERT INTO payments (user_id, vehicle_id, amount, payment_method, status) VALUES (?, ?, ?, ?, ?)");
        $stmt_pay->execute([$user_id, $vehicle_id, $payable_amount, $payment_method, $status]);

        $stmt_book = $pdo->prepare("INSERT INTO bookings (user_id, vehicle_id, total_amount, payment_status, booking_status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
        $stmt_book->execute([$user_id, $vehicle_id, $payable_amount, $status]);
        
        $pdo->commit(); 
        echo "<script>alert('Request Sent Successfully!'); window.location.href='vehicle_list.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack(); 
        $error_msg = "Transaction Failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveHub | Secure Checkout Terminal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

    <div class="details-container">
        <a href="vehicle_list.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Fleet Showroom</a>

        <div class="checkout-container">
            <div class="checkout-box">
                <div class="checkout-header">
                    <h2>Secure Gateway Terminal</h2>
                    <p>Authorize transaction vector to finalize vehicle reservation pipeline.</p>
                    <?php if (isset($error_msg)): ?>
                        <div style="color: #ef4444; background: rgba(239,68,68,0.1); padding: 10px; border-radius: 6px; margin-top: 10px;">
                            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="summary-card">
                    <?php 
                    
                        $images = json_decode($v['image_url'], true);
                  
                        $display_img = (is_array($images) && !empty($images)) ? $images[0] : ($v['image_url'] ?? 'uploads/default.jpg');
                    ?>
                    
                    <img src="<?php echo htmlspecialchars($display_img); ?>" 
                        class="summary-thumb" 
                        alt="Vehicle Thumb" 
                        onerror="this.src='uploads/default.jpg'">
                        
                    <div class="summary-info">
                        <span><?php echo htmlspecialchars($v['brand'] ?? ''); ?></span>
                        <h4><?php echo htmlspecialchars($v['name'] ?? ''); ?></h4>
                    </div>
                </div>

                <form action="" method="POST">
                    <input type="hidden" name="daily_price" value="<?php echo htmlspecialchars($v['price_per_day'] ?? 0); ?>">
                    
                    <?php if (strcasecmp($v['purpose'] ?? '', 'Rent') === 0): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div class="form-group-pay">
                                <label>Start Date</label>
                                <input type="date" name="start_date" required class="pay-input" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group-pay">
                                <label>End Date</label>
                                <input type="date" name="end_date" required class="pay-input" value="<?php echo date('Y-m-d', strtotime('+'.$days.' days')); ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="invoice-breakdown">
                        <div class="invoice-row">
                            <span>Transaction Classification</span>
                            <strong><?php echo htmlspecialchars($payment_description); ?></strong>
                        </div>
                        <div class="invoice-row total">
                            <span>Payable Amount</span>
                            <span>LKR <?php echo number_format($payable_amount); ?></span>
                        </div>
                    </div>

                    <div class="payment-method-selector">
                        <label>Select Payment Gateway Channel</label>
                        <div class="method-options-triple">
                            <label class="method-card">
                                <input type="radio" name="pay_method" value="Visa/Master" checked onclick="togglePaymentFields('card')">
                                <i class="fa-solid fa-credit-card"></i> Card
                            </label>
                            <label class="method-card">
                                <input type="radio" name="pay_method" value="Bank Transfer" onclick="togglePaymentFields('bank')">
                                <i class="fa-solid fa-building-columns"></i> Online Bank
                            </label>
                            <label class="method-card">
                                <input type="radio" name="pay_method" value="Handover Cash" onclick="togglePaymentFields('cash')">
                                <i class="fa-solid fa-money-bill-wave"></i> Handover Cash
                            </label>
                        </div>
                    </div>

                    <div id="card_fields_panel" class="dynamic-pay-panel">
                        <div class="form-group-pay">
                            <label>Cardholder Name</label>
                            <input type="text" name="card_name" placeholder="JOHN DOE" class="pay-input">
                        </div>
                        <div class="form-group-row" style="display: flex; gap: 15px;">
                            <div class="form-group-pay" style="flex: 2;">
                                <label>Card Number</label>
                                <input type="text" name="card_num" placeholder="0000 0000 0000 0000" class="pay-input">
                            </div>
                            <div class="form-group-pay" style="flex: 1;">
                                <label>Expiry / CVV</label>
                                <input type="text" name="card_cvv" placeholder="MM/YY - 000" class="pay-input">
                            </div>
                        </div>
                    </div>

                    <div id="bank_fields_panel" class="dynamic-pay-panel" style="display: none;">
                        <div class="bank-info-alert" style="background: rgba(6,182,212,0.1); padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; line-height: 1.5;">
                            <strong>DriveHub Bank Account Details:</strong><br>
                            BOC Account: 123456789 | Corporate Branch<br>
                            Sampath Account: 987654321 | Colombo Branch
                        </div>
                        <div class="form-group-pay">
                            <label>Your Bank Name & Account Number</label>
                            <input type="text" name="bank_details" placeholder="e.g., Commercial Bank - 88776655" class="pay-input">
                        </div>
                        <div class="form-group-pay">
                            <label>Transaction Reference ID / Receipt Number</label>
                            <input type="text" name="bank_ref" placeholder="Ref: TXN998822" class="pay-input">
                        </div>
                    </div>

                    <div id="cash_fields_panel" class="dynamic-pay-panel" style="display: none;">
                        <div class="cash-info-alert" style="background: rgba(13,148,136,0.1); padding: 12px; border-radius: 8px; font-size: 13px; line-height: 1.5;">
                            <i class="fa-solid fa-circle-check" style="color: #0d9488;"></i> <strong>Pending Approval Vector:</strong><br>
                            ඔබගේ ඇණවුම සාර්ථකව ඇඩ්මින් පැනලය වෙත යොමු කර ඇත. ඇඩ්මින් විසින් පරීක්ෂා කර අනුමත (Confirm) කළ පසු වාහනය වෙන් කරවා ගැනීම ස්ථිර වේ.
                        </div>
                    </div>

                    <button type="submit" name="action_process_pay" class="btn-pay-submit" style="margin-top: 20px; width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i> Send Booking Request to Admin
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePaymentFields(type) {
            document.getElementById('card_fields_panel').style.display = (type === 'card') ? 'block' : 'none';
            document.getElementById('bank_fields_panel').style.display = (type === 'bank') ? 'block' : 'none';
            document.getElementById('cash_fields_panel').style.display = (type === 'cash') ? 'block' : 'none';
        }
    </script>

</body>
</html>