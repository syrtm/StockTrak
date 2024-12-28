<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    // Kritik stok kontrolü
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.quantity <= p.min_quantity 
            AND p.id NOT IN (
                SELECT CAST(JSON_EXTRACT(message, '$.product_id') AS UNSIGNED) 
                FROM notifications 
                WHERE type = 'stock_alert' 
                AND user_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $message = [
            'product_id' => $row['id'],
            'text' => sprintf(
                '%s ürününün stok seviyesi kritik durumda! Mevcut stok: %d, Minimum stok: %d', 
                $row['name'], 
                $row['quantity'], 
                $row['min_quantity']
            )
        ];
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, product_id) VALUES (?, 'stock_alert', ?, ?)");
        $messageJson = json_encode($message);
        $stmt->bind_param("isi", $_SESSION['user_id'], $messageJson, $row['id']);
        $stmt->execute();
    }
    
    // Bakım kontrolü
    $sql = "SELECT p.*, m.next_maintenance_date, c.name as category_name 
            FROM products p 
            LEFT JOIN maintenance m ON p.id = m.product_id 
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE m.next_maintenance_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            AND p.id NOT IN (
                SELECT CAST(JSON_EXTRACT(message, '$.product_id') AS UNSIGNED)
                FROM notifications 
                WHERE type = 'maintenance_due' 
                AND user_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $message = [
            'product_id' => $row['id'],
            'text' => sprintf(
                '%s ürünü için bakım zamanı yaklaşıyor! Bakım tarihi: %s', 
                $row['name'], 
                date('d.m.Y', strtotime($row['next_maintenance_date']))
            )
        ];
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'maintenance_due', ?)");
        $messageJson = json_encode($message);
        $stmt->bind_param("is", $_SESSION['user_id'], $messageJson);
        $stmt->execute();
    }
    
    // Okunmamış bildirim sayısını döndür
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    echo json_encode(['success' => true, 'notifications' => $count]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
