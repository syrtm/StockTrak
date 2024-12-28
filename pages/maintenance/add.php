<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

include '../../config/db.php';

// Ürünleri çek
try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Bakım türlerini getir
$stmt = $conn->query("SELECT * FROM maintenance_types ORDER BY category, name");
$maintenance_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Teknisyenleri çek
try {
    $stmt = $conn->query("SELECT * FROM users WHERE role = 'teknisyen' ORDER BY username");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $maintenance_type = $_POST['maintenance_type'];
    $issue = $_POST['issue'];
    $current_version = $_POST['current_version'] ?? null;
    $new_version = $_POST['new_version'] ?? null;
    $cost = $_POST['cost'];
    $start_date = $_POST['start_date'];
    $technician_id = $_POST['technician_id'];

    try {
        $stmt = $conn->prepare("INSERT INTO maintenance (product_id, maintenance_type_id, issue, current_version, new_version, cost, start_date, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$product_id, $maintenance_type, $issue, $current_version, $new_version, $cost, $start_date, $_SESSION['user_id']]);

        header("Location: list.php");
        exit();
    } catch(PDOException $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Yeni Bakım Kaydı</h2>
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
                <label for="product_id" class="form-label">Ürün</label>
                <select class="form-select" id="product_id" name="product_id" required>
                    <option value="">Ürün Seçin</option>
                    <?php foreach($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="maintenance_type" class="form-label">Bakım Türü</label>
                <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                    <option value="">Bakım Türü Seçin</option>
                    <?php foreach($maintenance_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" data-category="<?php echo $type['category']; ?>">
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="version_fields" style="display: none;">
                <div class="mb-3">
                    <label for="current_version" class="form-label">Mevcut Sürüm</label>
                    <input type="text" class="form-control" id="current_version" name="current_version">
                </div>
                <div class="mb-3">
                    <label for="new_version" class="form-label">Yeni Sürüm</label>
                    <input type="text" class="form-control" id="new_version" name="new_version">
                </div>
            </div>

            <div class="mb-3">
                <label for="issue" class="form-label">Sorun Açıklaması</label>
                <textarea class="form-control" id="issue" name="issue" rows="3" required></textarea>
            </div>

            <div class="mb-3">
                <label for="cost" class="form-label">Maliyet (TL)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="cost" name="cost" value="0.00" required>
            </div>

            <div class="mb-3">
                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>

            <div class="mb-3">
                <label for="technician_id" class="form-label">Teknisyen</label>
                <select class="form-select" id="technician_id" name="technician_id" required>
                    <option value="">Teknisyen Seçin</option>
                    <?php foreach($technicians as $tech): ?>
                        <option value="<?php echo $tech['id']; ?>">
                            <?php echo htmlspecialchars($tech['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>
</div>

<script>
document.getElementById('maintenance_type').addEventListener('change', function() {
    var versionFields = document.getElementById('version_fields');
    var selectedOption = this.options[this.selectedIndex];
    var category = selectedOption.getAttribute('data-category');
    
    if (category === 'software') {
        versionFields.style.display = 'block';
    } else {
        versionFields.style.display = 'none';
        document.getElementById('current_version').value = '';
        document.getElementById('new_version').value = '';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
