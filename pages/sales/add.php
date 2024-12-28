<?php
require_once '../../includes/db.php';
require_once '../../includes/header.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user')) {
    header("Location: /stok-takip/index.php");
    exit();
}

// Ürünleri çek
try {
    $products_stmt = $conn->query("SELECT id, name, stock_quantity, minimum_quantity, sale_price FROM products WHERE stock_quantity > 0");
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Satış işleminde trigger'ı tetikleyen kod örneği
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Stok kontrolü
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);

        // Ürün bilgilerini al
        $query = "SELECT stock_quantity, minimum_quantity FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Yeni stok miktarını hesapla
        $new_stock = $product['stock_quantity'] - $quantity;

        // Stok kontrolü
        if ($new_stock < 0) {
            throw new Exception("Yetersiz stok! Mevcut stok: " . $product['stock_quantity']);
        }

        // Kritik stok kontrolü
        if ($new_stock <= $product['minimum_quantity']) {
            // Satışa devam et ama uyarı ver
            $_SESSION['warning'] = "Dikkat: Bu satıştan sonra ürün stok seviyesi kritik seviyenin altına düşecek!";
        }

        // Satış işlemini başlat
        $conn->beginTransaction();

        // Satış kaydı oluştur
        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, unit_price, total_amount, created_at) VALUES (?, ?, ?, ?, NOW())");
        $total_amount = $quantity * floatval($_POST['price']);
        $stmt->execute([$product_id, $quantity, $_POST['price'], $total_amount]);

        // Stok hareketi oluştur (Bu işlem trigger'ı tetikleyecek)************
        $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, quantity, type, reference_id, created_at) VALUES (?, ?, 'sale', LAST_INSERT_ID(), NOW())");
        $negative_quantity = -$quantity; // Satış olduğu için eksi değer
        $stmt->execute([$product_id, $negative_quantity]);

        $conn->commit();
        header("Location: list.php");
        exit();

    } catch (Exception $e) {
            // Hata durumunda geri al
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h3>Yeni Satış</h3>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="product_id" class="form-label">Ürün</label>
                    <select class="form-select" id="product_id" name="product_id" required>
                        <option value="">Ürün Seçin</option>
                        <?php foreach($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" 
                                    data-price="<?php echo $product['sale_price']; ?>"
                                    data-stock="<?php echo $product['stock_quantity']; ?>"
                                    data-minimum="<?php echo $product['minimum_quantity']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> 
                                (Stok: <?php echo $product['stock_quantity']; ?>) - 
                                <?php echo number_format($product['sale_price'], 2, ',', '.'); ?> TL
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Miktar</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                    <small class="text-muted">Mevcut Stok: <span id="available-stock">0</span></small>
                </div>

                <div class="mb-3">
                    <label for="price" class="form-label">Fiyat</label>
                    <input type="number" class="form-control" id="price" name="price" required step="0.01">
                </div>

                <div class="mb-3">
                    <label class="form-label">Toplam Tutar</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="total_amount" readonly>
                        <span class="input-group-text">TL</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Satışı Tamamla</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price);
    const stock = parseInt(selectedOption.dataset.stock);
    const minimum = parseInt(selectedOption.dataset.minimum);
    
    document.getElementById('available-stock').textContent = stock;
    document.getElementById('quantity').max = stock;
    document.getElementById('price').value = price;
    
    calculateTotal();
});

document.getElementById('quantity').addEventListener('input', calculateTotal);

function calculateTotal() {
    const selectedOption = document.getElementById('product_id').options[document.getElementById('product_id').selectedIndex];
    const price = parseFloat(selectedOption.dataset.price);
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    
    const total = price * quantity;
    document.getElementById('total_amount').value = total.toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' TL';
}
</script>
