<?php

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION['user_id'];


$sql = "SELECT b.*, v.name AS vehicle_name, v.image AS vehicle_images, 
               p.amount AS paid_amount, p.status AS p_status, p.payment_method 
        FROM bookings b 
        JOIN vehicles v ON b.vehicle_id = v.id 
        LEFT JOIN payments p ON b.id = p.booking_id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$u_id]); 
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}
?>

<table class="data-table" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background: #0f172a; color: white;">
            <th style="padding: 15px;">Vehicle</th>
            <th style="padding: 15px;">Dates</th>
            <th style="padding: 15px;">Total Cost</th>
            <th style="padding: 15px;">Paid Amount</th>
            <th style="padding: 15px;">Payment Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $row): 
    
            $images = json_decode($row['vehicle_images'], true);
            $img_src = (!empty($images) && is_array($images)) ? 'uploads/' . $images[0] : 'uploads/default.jpg';
        ?>
        <tr>
            <td style="padding: 15px; border-bottom: 1px solid #334155;"><?php echo htmlspecialchars($row['vehicle_name']); ?></td>
            <td style="padding: 15px; border-bottom: 1px solid #334155;"><?php echo $row['start_date'] . ' to ' . $row['end_date']; ?></td>
            <td style="padding: 15px; border-bottom: 1px solid #334155;">LKR <?php echo number_format($row['total_cost'], 2); ?></td>
            <td style="padding: 15px; border-bottom: 1px solid #334155;">
                <?php echo $row['paid_amount'] ? 'LKR ' . number_format($row['paid_amount'], 2) : '0.00'; ?>
            </td>
            <td style="padding: 15px; border-bottom: 1px solid #334155;">
                <?php 
                    $status = $row['p_status'] ?? 'Pending';
                    $color = (strtolower($status) == 'completed' || strtolower($status) == 'paid') ? '#10b981' : '#ef4444';
                    echo '<span style="color: ' . $color . '; font-weight: bold;">' . htmlspecialchars($status) . '</span>';
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>