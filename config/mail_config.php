<?php
// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');

define('SENDGRID_API_KEY', $env['SENDGRID_API_KEY']);

// Mail ayarları
define('SMTP_HOST', $env['SMTP_HOST']);
define('SMTP_PORT', $env['SMTP_PORT']);
define('SMTP_USERNAME', $env['SMTP_USERNAME']);
define('SMTP_PASSWORD', $env['SMTP_PASSWORD']);

// Mail gönderme özelliğini açıp kapatmak için
define('ENABLE_MAIL', true);  // Mail göndermek için true yapın

define('MAIL_FROM', $env['MAIL_FROM']);  // Gönderen email adresi
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME']);  // Gönderen adı
?>
