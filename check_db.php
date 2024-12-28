<?php
include 'includes/db.php';

// Check if notifications table exists
$sql = "SHOW TABLES LIKE 'notifications'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Notifications table exists\n";
    
    // Check table structure
    $sql = "DESCRIBE notifications";
    $result = $conn->query($sql);
    
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Notifications table does not exist\n";
    
    // Create the table
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('stock_alert', 'maintenance_due', 'system') NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Notifications table created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }
}
?>
