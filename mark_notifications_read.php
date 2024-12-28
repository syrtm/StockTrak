<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Tüm okunmamış bildirimleri okundu olarak işaretle
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
