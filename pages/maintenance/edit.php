<?php
session_start();
require_once '../../includes/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teknisyen') {
    header("Location: ../../pages/login.php");
    exit();
}

// Bakım kaydını çek
if(!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

try {
    // Bakım kaydını çek
    $query = "
        SELECT m.*, p.name as product_name, mt.name as maintenance_type_text, mt.category
        FROM maintenance m
        JOIN products p ON m.product_id = p.id
        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
        WHERE m.id = $id AND m.status != 'completed'
    ";
    $result = mysqli_query($conn, $query);
    
    if(!$result || mysqli_num_rows($result) == 0) {
        header("Location: list.php");
        exit();
    }
    
    $maintenance = mysqli_fetch_assoc($result);

    // Bakım tiplerini çek
    $result = mysqli_query($conn, "SELECT * FROM maintenance_types ORDER BY name");
    $maintenance_types = [];
    while($row = mysqli_fetch_assoc($result)) {
        $maintenance_types[] = $row;
    }

} catch(Exception $e) {
    echo "Hata: " . $e->getMessage();
}

// Form gönderildiğinde
if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Saklı yordamı çağır*******************
        $query = "CALL update_maintenance_status(?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iss", $id, $_POST['status'], $_POST['solution']);
            
            if (mysqli_stmt_execute($stmt)) {
                header("Location: view.php?id=" . $id);
                exit();
            } else {
                throw new Exception("Güncelleme hatası: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Sorgu hazırlanırken hata: " . mysqli_error($conn));
        }
    } catch(Exception $e) {
        $error = "Hata: " . $e->getMessage();
        error_log("Bakım güncelleme hatası: " . $e->getMessage());
    } finally {
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = "Bakım Düzenle";
require_once '../../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Bakım Düzenle #<?php echo $maintenance['id']; ?></h2>
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
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Ürün</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($maintenance['product_name']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label">Durum</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="pending" <?php echo ($maintenance['status'] == 'pending') ? 'selected' : ''; ?>>Bekliyor</option>
                        <option value="in_progress" <?php echo ($maintenance['status'] == 'in_progress') ? 'selected' : ''; ?>>Devam Ediyor</option>
                        <option value="completed" <?php echo ($maintenance['status'] == 'completed') ? 'selected' : ''; ?>>Tamamlandı</option>
                        <option value="cancelled" <?php echo ($maintenance['status'] == 'cancelled') ? 'selected' : ''; ?>>İptal Edildi</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="maintenance_type_id" class="form-label">Bakım Tipi</label>
                <select class="form-select" id="maintenance_type_id" name="maintenance_type_id" required>
                    <?php foreach($maintenance_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo ($maintenance['maintenance_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="solution" class="form-label">Çözüm/Açıklama</label>
                <textarea class="form-control" id="solution" name="solution" rows="4"><?php echo htmlspecialchars($maintenance['solution']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="cost" class="form-label">Maliyet (TL)</label>
                <input type="number" step="0.01" class="form-control" id="cost" name="cost" value="<?php echo $maintenance['cost']; ?>">
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
