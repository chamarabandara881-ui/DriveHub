<?php
require_once 'db.php';

try {
    
    $pdo->exec("ALTER TABLE payments MODIFY COLUMN status ENUM('Pending', 'Confirmed', 'Completed', 'Failed') DEFAULT 'Pending'");
    echo "✓ Payments table modified successfully.<br>";

   
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        status ENUM('Pending', 'Approved') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ Feedback table created successfully.<br>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS rent_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        vehicle_id INT NOT NULL,
        rent_date DATE NOT NULL,
        status ENUM('Pending', 'Confirmed') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ Rent requests table created successfully.<br>";


    $users = $pdo->query("SELECT id FROM users LIMIT 3")->fetchAll();
    $vehicles = $pdo->query("SELECT id FROM vehicles LIMIT 3")->fetchAll();

    if (count($users) > 0) {
        $user1 = $users[0]['id'];
        $user2 = isset($users[1]) ? $users[1]['id'] : $user1;
        
     
        $pay_count = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
        if ($pay_count == 0 && count($vehicles) > 0) {
            $veh1 = $vehicles[0]['id'];
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, vehicle_id, amount, payment_method, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->execute([$user1, $veh1, 15000.00, 'Visa/Master']);
            $stmt->execute([$user2, $veh1, 25000.00, 'Bank Transfer']);
            echo "✓ Seeded mock pending payments.<br>";
        }


        $fb_count = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
        if ($fb_count == 0) {
            $stmt = $pdo->prepare("INSERT INTO feedback (user_id, content, status) VALUES (?, ?, 'Pending')");
            $stmt->execute([$user1, 'The Tesla Model S was in absolutely pristine condition. Smooth ride and clean interior!', 'Pending']);
            $stmt->execute([$user2, 'Excellent support from the team, but the booking confirmation took slightly longer than expected.', 'Pending']);
            echo "✓ Seeded mock pending feedback.<br>";
        }
        
        $rent_count = $pdo->query("SELECT COUNT(*) FROM rent_requests")->fetchColumn();
        if ($rent_count == 0 && count($vehicles) > 0) {
            $veh1 = $vehicles[0]['id'];
            $veh2 = isset($vehicles[1]) ? $vehicles[1]['id'] : $veh1;
            $stmt = $pdo->prepare("INSERT INTO rent_requests (user_id, vehicle_id, rent_date, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$user1, $veh1, date('Y-m-d', strtotime('+2 days'))]);
            $stmt->execute([$user2, $veh2, date('Y-m-d', strtotime('+5 days'))]);
            echo "✓ Seeded mock pending rent requests.<br>";
        }
    } else {
        echo "⚠ No users found in database. Please register some users first to link payments/feedback/rentals correctly.<br>";
    }

    echo "<strong>Database updates complete!</strong>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
