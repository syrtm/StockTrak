<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';

// Kategorileri çek
try {
    $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Tedarikçileri çek
try {
    $suppliers_stmt = $conn->query("SELECT supplier_id, supplier_name FROM tbl_suppliers");
    $suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Lokasyonları çek
try {
    $stmt = $conn->query("SELECT * FROM locations ORDER BY name");
    $locations = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Form gönderildiğinde
if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Benzersiz barkod oluştur
        $prefix = 'PRD';
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(barcode_number, 4) AS UNSIGNED)) as max_number FROM barcodes WHERE barcode_number LIKE ?");
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();
        $next_number = ($result['max_number'] ?? 0) + 1;
        $barcode = $prefix . str_pad($next_number, 8, '0', STR_PAD_LEFT);

        // Ana ürün bilgilerini ekle
        $stmt = $conn->prepare("
            INSERT INTO products (name, category_id, description, stock_quantity, minimum_quantity, purchase_price, sale_price, supplier_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['category_id'],
            $_POST['description'],
            $_POST['stock_quantity'],
            $_POST['minimum_quantity'],
            $_POST['purchase_price'],
            $_POST['sale_price'],
            $_POST['supplier_id']
        ]);

        $product_id = $conn->lastInsertId();

        // Yazılım kategorisi dışındaki ürünler için barkod ve lokasyon ekle
        if ($_POST['category_id'] != '2') { // 2 = Yazılım kategorisi
            // Barkod bilgisini ekle
            $stmt = $conn->prepare("
                INSERT INTO barcodes (product_id, barcode_number, location, active_status) 
                VALUES (?, ?, ?, TRUE)
            ");
            $stmt->execute([
                $product_id,
                $barcode,
                $_POST['location']
            ]);
        }

        // Kategori detaylarını ekle
        switch($_POST['category_id']) {
            case '1': // Donanım
                $stmt = $conn->prepare("INSERT INTO hardware_details (product_id, processor_model, ram_capacity, storage_type, power_consumption) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $product_id,
                    $_POST['processor_model'],
                    $_POST['ram_capacity'],
                    $_POST['storage_type'],
                    $_POST['power_consumption']
                ]);
                break;

            case '3': // Gömülü
                $stmt = $conn->prepare("INSERT INTO embedded_details (product_id, processor_model, connection_interfaces, power_consumption) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $product_id,
                    $_POST['embedded_processor'],
                    $_POST['connection_interfaces'],
                    $_POST['embedded_power']
                ]);
                break;

            case '2': // Yazılım
                $stmt = $conn->prepare("INSERT INTO software_details (product_id, version, license_type, license_duration, support_end_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $product_id,
                    $_POST['version'],
                    $_POST['license_type'],
                    $_POST['license_duration'],
                    $_POST['support_end_date']
                ]);
                break;
        }

        $conn->commit();
        echo "<script>window.location.href = 'list.php';</script>";
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Hata: " . $e->getMessage();
    }
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Yeni Ürün Ekle</h2>
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
                <label for="name" class="form-label">Ürün Adı</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            
            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select class="form-select" id="category_id" name="category_id" required onchange="showCategoryDetails(this.value)">
                    <option value="">Kategori Seçin</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label">Stok Miktarı</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="minimum_quantity" class="form-label">Minimum Stok</label>
                        <input type="number" class="form-control" id="minimum_quantity" name="minimum_quantity" required min="0">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="purchase_price" class="form-label">Alış Fiyatı (₺)</label>
                <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" required>
            </div>

            <div class="mb-3">
                <label for="sale_price" class="form-label">Satış Fiyatı (₺)</label>
                <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" required>
            </div>

            <div class="mb-3">
                <label for="supplier_id" class="form-label">Tedarikçi</label>
                <select class="form-select" id="supplier_id" name="supplier_id" required>
                    <option value="">Tedarikçi Seçin</option>
                    <?php foreach($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['supplier_id']; ?>">
                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="location-select" class="mb-3">
                <label for="location" class="form-label">Lokasyon</label>
                <select class="form-select" id="location" name="location">
                    <option value="">Lokasyon Seçin</option>
                    <?php foreach($locations as $location): ?>
                        <option value="<?php echo htmlspecialchars($location['name']); ?>">
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Donanım Detayları -->
            <div id="hardware-details" style="display: none;">
                <h4>Donanım Detayları</h4>
                <div class="mb-3">
                    <label for="processor_model" class="form-label">İşlemci Modeli</label>
                    <input type="text" class="form-control" id="processor_model" name="processor_model">
                </div>
                <div class="mb-3">
                    <label for="ram_capacity" class="form-label">RAM Kapasitesi</label>
                    <input type="text" class="form-control" id="ram_capacity" name="ram_capacity">
                </div>
                <div class="mb-3">
                    <label for="storage_type" class="form-label">Depolama Tipi</label>
                    <input type="text" class="form-control" id="storage_type" name="storage_type">
                </div>
                <div class="mb-3">
                    <label for="power_consumption" class="form-label">Güç Tüketimi</label>
                    <input type="text" class="form-control" id="power_consumption" name="power_consumption">
                </div>
            </div>

            <!-- Gömülü Sistem Detayları -->
            <div id="embedded-details" style="display: none;">
                <h4>Gömülü Sistem Detayları</h4>
                <div class="mb-3">
                    <label for="embedded_processor" class="form-label">İşlemci Modeli</label>
                    <input type="text" class="form-control" id="embedded_processor" name="embedded_processor">
                </div>
                <div class="mb-3">
                    <label for="connection_interfaces" class="form-label">Bağlantı Arayüzleri</label>
                    <input type="text" class="form-control" id="connection_interfaces" name="connection_interfaces">
                </div>
                <div class="mb-3">
                    <label for="embedded_power" class="form-label">Güç Tüketimi</label>
                    <input type="text" class="form-control" id="embedded_power" name="embedded_power">
                </div>
            </div>

            <!-- Yazılım Detayları -->
            <div id="software-details" style="display: none;">
                <h4>Yazılım Detayları</h4>
                <div class="mb-3">
                    <label for="version" class="form-label">Versiyon</label>
                    <input type="text" class="form-control" id="version" name="version">
                </div>
                <div class="mb-3">
                    <label for="license_type" class="form-label">Lisans Tipi</label>
                    <input type="text" class="form-control" id="license_type" name="license_type">
                </div>
                <div class="mb-3">
                    <label for="license_duration" class="form-label">Lisans Süresi (Ay)</label>
                    <input type="number" class="form-control" id="license_duration" name="license_duration">
                </div>
                <div class="mb-3">
                    <label for="support_end_date" class="form-label">Destek Bitiş Tarihi</label>
                    <input type="date" class="form-control" id="support_end_date" name="support_end_date">
                </div>
            </div>

            <div class="mb-3">
                <p class="text-muted">Barkod otomatik olarak oluşturulacaktır.</p>
            </div>

            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="list.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>

<script>
function showCategoryDetails(categoryId) {
    // Tüm detay formlarını gizle
    document.getElementById('hardware-details').style.display = 'none';
    document.getElementById('embedded-details').style.display = 'none';
    document.getElementById('software-details').style.display = 'none';
    
    // Lokasyon seçimini varsayılan olarak göster ve required yap
    const locationSelect = document.getElementById('location-select');
    const locationInput = document.getElementById('location');
    locationSelect.style.display = 'block';
    locationInput.required = true;
    
    // Kategori ID'lerini kontrol edelim
    console.log('Selected Category ID:', categoryId);

    // Seçilen kategoriye göre ilgili formu göster
    switch(categoryId) {
        case '1': // Donanım kategorisi
            document.getElementById('hardware-details').style.display = 'block';
            break;
        case '3': // Gömülü Sistemler kategorisi
            document.getElementById('embedded-details').style.display = 'block';
            break;
        case '2': // Yazılım kategorisi
            document.getElementById('software-details').style.display = 'block';
            // Yazılım kategorisi seçildiğinde lokasyon seçimini gizle
            locationSelect.style.display = 'none';
            locationInput.required = false;
            locationInput.value = '';
            break;
    }
}

// Sayfa yüklendiğinde mevcut seçili kategoriye göre formu göster
document.addEventListener('DOMContentLoaded', function() {
    var categorySelect = document.getElementById('category_id');
    if (categorySelect) {
        showCategoryDetails(categorySelect.value);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
