<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
include '../includes/header.php';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../config/db.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug bilgisi
        echo "<pre>";
        var_dump($user);
        var_dump($password);
        var_dump(password_verify($password, $user['password'])); // password_verify sonucunu görelim
        echo "</pre>";
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Kullanıcı girişini logla
            $stmt = $conn->prepare("
                INSERT INTO user_logs (user_id, activity_type, details, ip_address) 
                VALUES (?, 'login', 'Kullanıcı girişi yapıldı', ?)
            ");
            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
            
            header("Location: ../index.php");
            exit();
        } else {
            $error = "Kullanıcı adı veya şifre hatalı!";
        }
    } catch(PDOException $e) {
        $error = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Giriş Yap</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error)) { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>