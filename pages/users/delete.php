<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /stok-takip/index.php");
    exit();
}

include '../../config/db.php';

if(isset($_GET['id']) && $_GET['id'] != $_SESSION['user_id']) {
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } catch(PDOException $e) {
        // Hata durumunda işlem yapılabilir
    }
}

header("Location: list.php");
exit();
?>
