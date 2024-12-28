<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/facebook_config.php';

$fb = new Facebook\Facebook([
    'app_id' => FB_APP_ID,
    'app_secret' => FB_APP_SECRET,
    'default_graph_version' => 'v12.0',
]);

$helper = $fb->getRedirectLoginHelper();
$permissions = ['email']; // Gerekli izinler
$loginUrl = $helper->getLoginUrl(FB_REDIRECT_URI, $permissions);

// Facebook login butonuna yönlendir
header("Location: " . $loginUrl);
exit;
?>
