<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /stok-takip/index.php");
    exit();
}

require_once '../../config/db.php';

// Kullanıcı ID kontrolü
if(!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];

// Kullanıcı bilgilerini çek
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$user) {
        header("Location: list.php");
        exit();
    }
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Form gönderildiğinde
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    try {
        // Kullanıcı adı kontrolü (eğer değiştiyse)
        if($user['username'] != $_POST['username']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$_POST['username'], $id]);
            if($stmt->fetch()) {
                $error = "Bu kullanıcı adı zaten kullanılıyor.";
            }
        }
        
        if(!isset($error)) {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $email, $phone, $password, $role, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $email, $phone, $role, $id]);
            }
            
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
        <h2>Kullanıcı Düzenle</h2>
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
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">Telefon Numarası</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="05XX XXX XX XX">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control" id="password" name="password">
                <div class="form-text">Şifreyi değiştirmek istemiyorsanız boş bırakın.</div>
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>Kullanıcı</option>
                    <option value="teknisyen" <?php echo ($user['role'] == 'teknisyen') ? 'selected' : ''; ?>>Teknisyen</option>
                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Güncelle</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
