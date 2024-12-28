<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /stok-takip/index.php");
    exit();
}

require_once '../../config/db.php';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Kullanıcı adı kontrolü
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if($stmt->fetch()) {
            $error = "Bu kullanıcı adı zaten kullanılıyor.";
        } else {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $role = $_POST['role'];

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $email, $phone, $role]);
            header("Location: list.php");
            exit();
        }
    } catch(PDOException $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Yeni Kullanıcı Ekle</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="list.php" class="btn btn-secondary">Geri Dön</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">Telefon Numarası</label>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="05XX XXX XX XX">
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="user">Kullanıcı</option>
                    <option value="teknisyen">Teknisyen</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
