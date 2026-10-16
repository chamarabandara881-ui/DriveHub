<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tables in " . $db . ":\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
