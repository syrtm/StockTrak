<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// JSON verilerini al
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['name']) || !isset($input['email'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

try {
    // Kullanıcıyı veritabanında kontrol et
    $stmt = $conn->prepare("SELECT * FROM users WHERE facebook_id = ?");
    $stmt->execute([$input['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Kullanıcı zaten var, giriş yap
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
    } else {
        // Yeni kullanıcı oluştur
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, facebook_id, role) 
            VALUES (?, ?, ?, 'kullanici')
        ");
        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['id']
        ]);
        
        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['username'] = $input['name'];
        $_SESSION['role'] = 'kullanici';
    }

    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu']);
}
?>
