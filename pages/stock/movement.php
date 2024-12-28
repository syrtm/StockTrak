<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';
require_once '../../includes/mail_helper.php';

// Ürünleri çek
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name, b.barcode_number as barcode
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN barcodes b ON p.id = b.product_id
        ORDER BY p.name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Form gönderildiğinde
if($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Ürün bilgilerini al
        $stmt = $conn->prepare("SELECT stock_quantity, purchase_price, sale_price FROM products WHERE id = ?");
        $stmt->execute([$_POST['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $new_quantity = $product['stock_quantity'];
        if($_POST['type'] == 'in') {
            $new_quantity += $_POST['quantity'];
            $purchase_price = $product['purchase_price'];
            $total_price = $purchase_price * $_POST['quantity'];
            $sale_price = 0; // Default value when not applicable
        } else {
            $new_quantity -= $_POST['quantity'];
            $sale_price = $product['sale_price'];
            $total_price = $sale_price * $_POST['quantity'];
            $purchase_price = 0; // Default value when not applicable
        }
        
        // Stok miktarı 0'ın altına düşemez
        if($new_quantity < 0) {
            $error = "Uyarı: Stok miktarı 0'ın altına düşemez! Mevcut stok: " . $product['stock_quantity'];
        } else {
            // İşlemleri başlat
            $conn->beginTransaction();

            // Stok hareketini kaydet
            $stmt = $conn->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, description, user_id, purchase_price, sale_price, total_price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['product_id'],
                $_POST['type'],
                $_POST['quantity'],
                $_POST['description'],
                $_SESSION['user_id'],
                $purchase_price,
                $sale_price,
                $total_price
            ]);

            // Stok miktarını güncelle
            $stmt = $conn->prepare("
                UPDATE products 
                SET stock_quantity = ? 
                WHERE id = ?
            ");
            $stmt->execute([$new_quantity, $_POST['product_id']]);

            // Minimum stok kontrolü ve bildirim oluşturma
            $stmt = $conn->prepare("
                SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$_POST['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($new_quantity == 0) {
                // Stok tamamen bittiğinde bildirim oluştur
                $message = $product['name'] . ' ürününün stoğu tükendi! Mevcut stok: 0';
                $stmt = $conn->prepare("INSERT INTO notifications (message, product_id) VALUES (?, ?)");
                $stmt->execute([$message, $_POST['product_id']]);
                
                // E-posta gönder
                $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'teknisyen' AND email != ''");
                $stmt->execute();
                $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($technicians as $technician) {
                    sendNotificationEmail(
                        $technician['email'],
                        'Stok Uyarısı',
                        $message
                    );
                }
            } elseif ($new_quantity <= $product['minimum_quantity']) {
                // Minimum seviyenin altına indiğinde bildirim oluştur
                $message = $product['name'] . ' ürününün stok seviyesi kritik seviyede! Mevcut stok: ' . $new_quantity;
                $stmt = $conn->prepare("INSERT INTO notifications (message, product_id) VALUES (?, ?)");
                $stmt->execute([$message, $_POST['product_id']]);
                
                // E-posta gönder
                $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'teknisyen' AND email != ''");
                $stmt->execute();
                $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach($technicians as $technician) {
                    sendNotificationEmail(
                        $technician['email'],
                        'Stok Uyarısı',
                        $message
                    );
                }
            }
            
            

            // İşlemleri onayla
            $conn->commit();
            
            $success = "Stok hareketi başarıyla kaydedildi.";
        }
    } catch(Exception $e) {
        // Hata durumunda işlemleri geri al
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Son hareketleri çek
try {
    $stmt = $conn->query("
        SELECT sm.*, p.name as product_name, u.username as user_name, sm.purchase_price, sm.sale_price, (sm.purchase_price * sm.quantity) as total_purchase_price, (sm.sale_price * sm.quantity) as total_sale_price
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        JOIN users u ON sm.user_id = u.id
        ORDER BY sm.created_at DESC
        LIMIT 10
    ");
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$movements) {
        echo "No movements found.";
    }
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
    $movements = [];
}

?>

<div class="row mb-3">
    <div class="col">
        <h2>Stok Hareketi</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Stok Hareketi Ekle</h5>
            </div>
            <div class="card-body">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="barcode" class="form-label">Barkod</label>
                        <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Barkod okutun veya girin">
                    </div>
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Ürün</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Ürün Seçin</option>
                            <?php foreach($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-barcode="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    <?php if($product['barcode']): ?>
                                        (<?php echo htmlspecialchars($product['barcode']); ?>)
                                    <?php endif; ?> 
                                    - Stok: <?php echo $product['stock_quantity']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Hareket Tipi</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="in">Giriş</option>
                            <option value="out">Çıkış</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Miktar</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Son Hareketler</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Ürün</th>
                                <th>Tip</th>
                                <th>Miktar</th>
                                <th>Kullanıcı</th>
                                <th>Açıklama</th>
                                <th>Alış Fiyatı</th>
                                <th>Satış Fiyatı</th>
                                <th>Toplam Fiyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($movements as $movement): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($movement['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                <td>
                                    <?php if($movement['type'] == 'in'): ?>
                                        <span class="badge bg-success">Giriş</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Çıkış</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $movement['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($movement['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($movement['description']); ?></td>
                                <td><?php echo number_format($movement['purchase_price'], 2); ?></td>
                                <td><?php echo number_format($movement['sale_price'], 2); ?></td>
                                <td><?php echo number_format($movement['type'] == 'in' ? $movement['total_purchase_price'] : $movement['total_sale_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Barkod ve ürün seçimi senkronizasyonu
document.getElementById('barcode').addEventListener('input', function(e) {
    const barcode = e.target.value;
    const productSelect = document.getElementById('product_id');
    const options = productSelect.options;

    for(let i = 0; i < options.length; i++) {
        if(options[i].dataset.barcode === barcode) {
            productSelect.selectedIndex = i;
            break;
        }
    }
});

document.getElementById('product_id').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    document.getElementById('barcode').value = selectedOption.dataset.barcode || '';
});
</script>

<?php include '../../includes/footer.php'; ?>
