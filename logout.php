<?php
session_start();
session_destroy();
header("Location: /stok-takip/login.php");
exit();
?>
