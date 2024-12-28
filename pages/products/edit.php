<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';

// Ürün ID kontrolü
if(!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];

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
    $locations_stmt = $conn->query("SELECT location_id, name FROM locations ORDER BY name");
    $locations = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Ürün bilgilerini çek
try {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$product) {
        header("Location: list.php");
        exit();
    }

    // Kategori tipine göre detay bilgilerini çek
    $details = null;
    switch(strtolower($product['category_name'])) {
        case 'yazılım':
            $detail_stmt = $conn->prepare("SELECT * FROM software_details WHERE product_id = ?");
            $detail_stmt->execute([$id]);
            $details = $detail_stmt->fetch(PDO::FETCH_ASSOC);
            break;
        case 'donanım':
            $detail_stmt = $conn->prepare("SELECT * FROM hardware_details WHERE product_id = ?");
            $detail_stmt->execute([$id]);
            $details = $detail_stmt->fetch(PDO::FETCH_ASSOC);
            break;
        case 'gömülü sistem':
            $detail_stmt = $conn->prepare("SELECT * FROM embedded_details WHERE product_id = ?");
            $detail_stmt->execute([$id]);
            $details = $detail_stmt->fetch(PDO::FETCH_ASSOC);
            break;
    }
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Ürünün barkod ve lokasyon bilgisini çek
try {
    $barcode_stmt = $conn->prepare("SELECT b.barcode_id, b.location_id FROM barcodes b WHERE b.product_id = ?");
    $barcode_stmt->execute([$id]);
    $barcode = $barcode_stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Form gönderildiğinde
if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Ürün bilgilerini güncelle
        $stmt = $conn->prepare("
            UPDATE products 
            SET category_id = ?, name = ?, description = ?, stock_quantity = ?, purchase_price = ?, sale_price = ?, minimum_quantity = ?, supplier_id = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['category_id'],
            $_POST['name'],
            $_POST['description'],
            $_POST['stock_quantity'],
            $_POST['purchase_price'],
            $_POST['sale_price'],
            $_POST['minimum_quantity'],
            $_POST['supplier_id'],
            $id
        ]);

        // Kategori tipine göre detay bilgilerini güncelle
        switch(strtolower($product['category_name'])) {
            case 'yazılım':
                if($details) {
                    $detail_stmt = $conn->prepare("
                        UPDATE software_details 
                        SET version = ?, license_type = ?, license_duration = ?, support_end_date = ?
                        WHERE product_id = ?
                    ");
                } else {
                    $detail_stmt = $conn->prepare("
                        INSERT INTO software_details (version, license_type, license_duration, support_end_date, product_id)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                }
                $detail_stmt->execute([
                    $_POST['version'],
                    $_POST['license_type'],
                    $_POST['license_duration'],
                    $_POST['support_end_date'],
                    $id
                ]);
                break;

            case 'donanım':
                if($details) {
                    $detail_stmt = $conn->prepare("
                        UPDATE hardware_details 
                        SET processor_model = ?, ram_capacity = ?, storage_type = ?, power_consumption = ?
                        WHERE product_id = ?
                    ");
                } else {
                    $detail_stmt = $conn->prepare("
                        INSERT INTO hardware_details (processor_model, ram_capacity, storage_type, power_consumption, product_id)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                }
                $detail_stmt->execute([
                    $_POST['processor_model'],
                    $_POST['ram_capacity'],
                    $_POST['storage_type'],
                    $_POST['power_consumption'],
                    $id
                ]);
                break;

            case 'gömülü sistem':
                if($details) {
                    $detail_stmt = $conn->prepare("
                        UPDATE embedded_details 
                        SET processor_model = ?, connection_interfaces = ?, power_consumption = ?
                        WHERE product_id = ?
                    ");
                } else {
                    $detail_stmt = $conn->prepare("
                        INSERT INTO embedded_details (processor_model, connection_interfaces, power_consumption, product_id)
                        VALUES (?, ?, ?, ?)
                    ");
                }
                $detail_stmt->execute([
                    $_POST['embedded_processor_model'],
                    $_POST['connection_interfaces'],
                    $_POST['embedded_power_consumption'],
                    $id
                ]);
                break;
        }

        // Barkodun lokasyonunu güncelle
        if ($barcode) {
            $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
            $location_stmt = $conn->prepare("UPDATE barcodes SET location_id = ? WHERE barcode_id = ?");
            $location_stmt->execute([$location_id, $barcode['barcode_id']]);
        }
        
        echo "<script>window.location.href = 'list.php';</script>";
        exit();
    } catch(PDOException $e) {
        $error = "Hata: " . $e->getMessage();
    }
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Ürün Düzenle</h2>
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
                <label for="category_id" class="form-label">Kategori</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Kategori Seçin</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="name" class="form-label">Ürün Adı</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="stock_quantity" class="form-label">Stok Miktarı</label>
                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required min="0">
            </div>
            
            <div class="mb-3">
                <label for="purchase_price" class="form-label">Alış Fiyatı (₺)</label>
                <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" value="<?php echo htmlspecialchars($product['purchase_price']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="sale_price" class="form-label">Satış Fiyatı (₺)</label>
                <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($product['sale_price']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="minimum_quantity" class="form-label">Minimum Miktar</label>
                <input type="number" class="form-control" id="minimum_quantity" name="minimum_quantity" value="<?php echo $product['minimum_quantity']; ?>" required min="0">
            </div>
            
            <div class="mb-3">
                <label for="supplier_id">Tedarikçi:</label>
                <select name="supplier_id" id="supplier_id" class="form-control" required>
                    <option value="">Tedarikçi Seçin</option>
                    <?php foreach($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo $product['supplier_id'] == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($barcode): ?>
            <div class="mb-3">
                <label for="location_id">Lokasyon:</label>
                <select name="location_id" id="location_id" class="form-select">
                    <option value="">Lokasyon Seçin</option>
                    <?php foreach($locations as $location): ?>
                        <option value="<?php echo $location['location_id']; ?>" <?php echo ($barcode['location_id'] == $location['location_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Kategori tipine göre detay alanları -->
            <?php if(strtolower($product['category_name']) == 'yazılım'): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Yazılım Detayları</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="version" class="form-label">Versiyon</label>
                        <input type="text" class="form-control" id="version" name="version" value="<?php echo htmlspecialchars($details['version'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="license_type" class="form-label">Lisans Tipi</label>
                        <select class="form-select" id="license_type" name="license_type">
                            <option value="">Seçin</option>
                            <option value="perpetual" <?php echo ($details['license_type'] ?? '') == 'perpetual' ? 'selected' : ''; ?>>Süresiz</option>
                            <option value="subscription" <?php echo ($details['license_type'] ?? '') == 'subscription' ? 'selected' : ''; ?>>Abonelik</option>
                            <option value="trial" <?php echo ($details['license_type'] ?? '') == 'trial' ? 'selected' : ''; ?>>Deneme</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="license_duration" class="form-label">Lisans Süresi (Gün)</label>
                        <input type="number" class="form-control" id="license_duration" name="license_duration" value="<?php echo htmlspecialchars($details['license_duration'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="support_end_date" class="form-label">Destek Bitiş Tarihi</label>
                        <input type="datetime-local" class="form-control" id="support_end_date" name="support_end_date" value="<?php echo ($details['support_end_date'] ?? '') ? date('Y-m-d\TH:i', strtotime($details['support_end_date'])) : ''; ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(strtolower($product['category_name']) == 'donanım'): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Donanım Detayları</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="processor_model" class="form-label">İşlemci Modeli</label>
                        <input type="text" class="form-control" id="processor_model" name="processor_model" value="<?php echo htmlspecialchars($details['processor_model'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="ram_capacity" class="form-label">RAM Kapasitesi</label>
                        <input type="text" class="form-control" id="ram_capacity" name="ram_capacity" value="<?php echo htmlspecialchars($details['ram_capacity'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="storage_type" class="form-label">Depolama Tipi</label>
                        <input type="text" class="form-control" id="storage_type" name="storage_type" value="<?php echo htmlspecialchars($details['storage_type'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="power_consumption" class="form-label">Güç Tüketimi</label>
                        <input type="text" class="form-control" id="power_consumption" name="power_consumption" value="<?php echo htmlspecialchars($details['power_consumption'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if(strtolower($product['category_name']) == 'gömülü sistem'): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Gömülü Sistem Detayları</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="embedded_processor_model" class="form-label">İşlemci Modeli</label>
                        <input type="text" class="form-control" id="embedded_processor_model" name="embedded_processor_model" value="<?php echo htmlspecialchars($details['processor_model'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="connection_interfaces" class="form-label">Bağlantı Arayüzleri</label>
                        <input type="text" class="form-control" id="connection_interfaces" name="connection_interfaces" value="<?php echo htmlspecialchars($details['connection_interfaces'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="embedded_power_consumption" class="form-label">Güç Tüketimi</label>
                        <input type="text" class="form-control" id="embedded_power_consumption" name="embedded_power_consumption" value="<?php echo htmlspecialchars($details['power_consumption'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">Güncelle</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
