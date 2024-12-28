<?php
namespace PHPMailer\PHPMailer;

class SMTP {
    const VERSION = '6.8.1';
    const CRLF = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;
    
    public static function validateAddress($address) {
        return (bool)filter_var($address, FILTER_VALIDATE_EMAIL);
    }
}
?>
