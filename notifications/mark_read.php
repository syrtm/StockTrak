<?php
require_once '../config/db.php';

if(isset($_POST['id'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$_POST['id']]);
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>
