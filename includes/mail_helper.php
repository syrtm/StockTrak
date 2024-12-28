<?php
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body) {
    // Mail gönderme devre dışı ise hiçbir şey yapma
    if (!ENABLE_MAIL) {
        return true;
    }

    try {
        $mail = new PHPMailer(true);
        
        // Debug modu aktif
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };
        
        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Gönderici ve alıcı ayarları
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        
        // İçerik ayarları
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->CharSet = 'UTF-8';
        
        // Mail göndermeyi dene
        $result = $mail->send();
        error_log("Mail gönderildi: $to, Konu: $subject");
        return $result;
    } catch (Exception $e) {
        error_log("Mail gönderme hatası: " . $mail->ErrorInfo);
        error_log("Hata detayı: " . $e->getMessage());
        return false;
    }
}

// Stok bildirimi gönder
function sendStockNotification($productName, $currentStock, $to) {
    error_log("Stok bildirimi gönderiliyor: $productName, Stok: $currentStock, Alıcı: $to");
    $subject = "Stok Uyarısı";
    $body = "
        <h2>Stok Uyarısı</h2>
        <p>Ürün: {$productName}</p>
        <p>Mevcut Stok: {$currentStock}</p>
        <p>Bu ürünün stok seviyesi kritik seviyenin altına düştü.</p>
    ";
    return sendEmail($to, $subject, $body);
}

// Bakım bildirimi gönder
function sendMaintenanceNotification($productName, $maintenanceType, $description, $to) {
    error_log("Bakım bildirimi gönderiliyor: $productName, Tür: $maintenanceType, Alıcı: $to");
    $subject = "Bakım Bildirimi";
    $body = "
        <h2>Bakım Bildirimi</h2>
        <p>Ürün: {$productName}</p>
        <p>Bakım Türü: {$maintenanceType}</p>
        <p>Açıklama: {$description}</p>
    ";
    return sendEmail($to, $subject, $body);
}

// Bildirim e-postası gönder
function sendNotificationEmail($to, $subject, $message) {
    error_log("Genel bildirim gönderiliyor: Konu: $subject, Alıcı: $to");
    $body = "
        <h2>{$subject}</h2>
        <p>{$message}</p>
    ";
    return sendEmail($to, $subject, $body);
}
?>
