<?php
$env = parse_ini_file(__DIR__ . '/../.env');

define('FB_APP_ID', $env['FB_APP_ID']);
define('FB_APP_SECRET', $env['FB_APP_SECRET']);
define('FB_REDIRECT_URI', $env['FB_REDIRECT_URI']);
?>
