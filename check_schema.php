<?php
require_once 'db.php';
try {
    $db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Connected to database: $db_name\n\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "\n\n";
    foreach ($tables as $table) {
        echo "=== $table table columns ===\n";
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo $c['Field'] . ' | ' . $c['Type'] . ' | Null:' . $c['Null'] . ' | Key:' . $c['Key'] . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

