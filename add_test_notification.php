<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    die("Lütfen giriş yapın");
}

try {
    // Test bildirimi ekle
    $sql = "INSERT INTO notifications (user_id, type, message) VALUES (?, 'system', 'Bu bir test bildirimidir.')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if($stmt->execute()) {
        echo "Test bildirimi başarıyla eklendi!";
    } else {
        echo "Bildirim eklenirken hata oluştu: " . $stmt->error;
    }
    
    // Mevcut bildirimleri göster
    echo "<h3>Mevcut Bildirimler:</h3>";
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ccc;'>";
            echo "ID: " . $row['id'] . "<br>";
            echo "Tip: " . $row['type'] . "<br>";
            echo "Mesaj: " . $row['message'] . "<br>";
            echo "Tarih: " . $row['created_at'] . "<br>";
            echo "Okundu: " . ($row['is_read'] ? 'Evet' : 'Hayır') . "<br>";
            echo "</div>";
        }
    } else {
        echo "<p>Hiç bildirim bulunmuyor.</p>";
    }
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
    echo "<pre>";
    print_r($e->getTrace());
    echo "</pre>";
}
?>
