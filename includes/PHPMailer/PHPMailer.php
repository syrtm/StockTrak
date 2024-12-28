<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    public $SMTPDebug = 0;
    public $isSMTP = true;
    public $Host = '';
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = 'tls';
    public $Port = 587;
    public $CharSet = 'UTF-8';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $isHTML = true;
    private $to = [];
    private $socket = null;
    
    public function __construct($exceptions = null) {
        $this->isSMTP = true;
    }
    
    public function addAddress($address) {
        $this->to[] = $address;
        return true;
    }
    
    public function isHTML($ishtml = true) {
        $this->isHTML = $ishtml;
        return true;
    }
    
    private function getResponse() {
        $response = '';
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    private function sendCommand($command) {
        fputs($this->socket, $command . "\r\n");
        return $this->getResponse();
    }
    
    public function send() {
        try {
            // SSL/TLS bağlantısı kur
            $this->socket = stream_socket_client(
                "tls://{$this->Host}:{$this->Port}", 
                $errno, 
                $errstr, 
                15,
                STREAM_CLIENT_CONNECT,
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            );
            
            if (!$this->socket) {
                throw new Exception("Connection failed: $errstr ($errno)");
            }
            
            // SMTP diyaloğu başlat
            $this->getResponse(); // Sunucu karşılama mesajını al
            $this->sendCommand("EHLO " . gethostname());
            
            // Kimlik doğrulama
            $this->sendCommand("AUTH LOGIN");
            $this->sendCommand(base64_encode($this->Username));
            $this->sendCommand(base64_encode($this->Password));
            
            // Mail gönderimi
            $this->sendCommand("MAIL FROM:<{$this->From}>");
            
            foreach ($this->to as $to) {
                $this->sendCommand("RCPT TO:<$to>");
            }
            
            // Mail içeriği
            $this->sendCommand("DATA");
            
            $headers = [
                "MIME-Version: 1.0",
                "Content-type: text/html; charset=UTF-8",
                "From: {$this->FromName} <{$this->From}>",
                "Subject: {$this->Subject}",
                "",
                $this->Body
            ];
            
            fputs($this->socket, implode("\r\n", $headers) . "\r\n.\r\n");
            $this->getResponse();
            
            // Bağlantıyı kapat
            $this->sendCommand("QUIT");
            fclose($this->socket);
            
            return true;
        } catch (Exception $e) {
            if ($this->socket) {
                fclose($this->socket);
            }
            throw $e;
        }
    }
}
?>
