<?php
require_once '../../includes/header.php';
require_once '../../includes/db.php';

try {
    $query = "
        SELECT p.*, c.name as category_name, b.barcode_number, b.location,
        CASE 
            WHEN p.stock_quantity <= p.minimum_quantity AND p.stock_quantity > 0 THEN 'table-warning'
            WHEN p.stock_quantity = 0 THEN 'table-danger'
            ELSE ''
        END as row_class
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN barcodes b ON p.id = b.product_id
        ORDER BY p.id DESC
    ";
    
    $result = mysqli_query($conn, $query);
    $products = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        mysqli_free_result($result);
    }
} catch(Exception $e) {
    echo "Hata: " . $e->getMessage();
    $products = [];
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Ürün Listesi</h2>
        </div>
        <div class="col text-end">
            <a href="add.php" class="btn btn-primary">Yeni Ürün Ekle</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Barkod</th>
                    <th>Lokasyon</th>
                    <th>Stok Miktarı</th>
                    <th>Minimum Miktar</th>
                    <th>Alış Fiyatı</th>
                    <th>Satış Fiyatı</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                <?php foreach($products as $product): ?>
                <tr id="product-row-<?php echo $product['id']; ?>" class="<?php echo $product['row_class']; ?>">
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td><?php echo $product['category_id'] != 2 ? htmlspecialchars($product['barcode_number']) : 'N/A'; ?></td>
                    <td><?php echo $product['category_id'] != 2 ? htmlspecialchars($product['location']) : 'N/A'; ?></td>
                    <td>
                        <?php if($product['stock_quantity'] == 0): ?>
                            <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?></span>
                        <?php elseif($product['stock_quantity'] <= $product['minimum_quantity']): ?>
                            <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['minimum_quantity']; ?></td>
                    <td><?php echo number_format($product['purchase_price'], 2); ?> ₺</td>
                    <td><?php echo number_format($product['sale_price'], 2); ?> ₺</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                            <a href="../stock/movement.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success">Stok Ekle</a>
                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="btn btn-sm btn-danger">Sil</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">Henüz ürün eklenmemiş.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function deleteProduct(id) {
    if(confirm('Bu ürünü silmek istediğinizden emin misiniz?')) {
        fetch('delete.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                console.log('Sunucu yanıtı:', data);
                
                if(data.success) {
                    // Tablodan satırı kaldır
                    const row = document.getElementById('product-row-' + id);
                    if(row) {
                        row.remove();
                        
                        // Eğer tabloda başka ürün kalmadıysa mesaj göster
                        const tbody = document.querySelector('tbody');
                        if(tbody.children.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Henüz ürün eklenmemiş.</td></tr>';
                        }
                    }
                } else {
                    // Hata mesajını göster
                    alert('Hata: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Ürün silinirken bir hata oluştu.');
            });
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
