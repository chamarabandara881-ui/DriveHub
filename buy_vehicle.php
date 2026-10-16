<?php 
require_once 'header.php'; 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: vehicle_list.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND purpose = 'sale' AND status = 'available'");
$stmt->execute([intval($_GET['id'])]); $car = $stmt->fetch();
if (!$car) { die("Asset not available for acquisition."); }

if (isset($_POST['confirm_buy'])) {
    $_SESSION['checkout_data'] = [
        'vehicle_id' => $car['id'], 'booking_type' => 'sale', 'start_date' => null, 'end_date' => null, 'total_cost' => $car['selling_price']
    ];
    header("Location: payment.php"); exit;
}
?>
<div class="form-container" style="margin-top:40px; max-width:600px; text-align:center;">
    <h2>Confirm Fleet Unit Purchase Acquisition</h2><br>
    <img src="<?php echo htmlspecialchars($car['image_url']); ?>" style="width:100%; height:250px; object-fit:cover; border-radius:8px; margin-bottom:15px;">
    <h3><?php echo htmlspecialchars($car['title']); ?></h3>
    <p style="color:var(--text-muted); margin-top:5px;">Manufacturing Brand Line: <?php echo htmlspecialchars($car['brand']); ?></p>
    <div style="font-size:24px; font-weight:700; color:var(--accent-green); margin: 20px 0;">Rs. <?php echo number_format($car['selling_price'], 2); ?></div>
    <form action="" method="POST">
        <button type="submit" name="confirm_buy" class="btn-submit" style="background:var(--accent-orange);">Proceed to Legal Secure Escrow Settlement</button>
    </form>
</div>
<?php require_once 'footer.php'; ?>