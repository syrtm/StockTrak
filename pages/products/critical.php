<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';

// Kritik stoktaki ürünleri çek
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name,
        CASE 
            WHEN p.stock_quantity = 0 THEN 'bg-danger text-white'
            WHEN p.stock_quantity <= p.minimum_quantity THEN 'bg-warning'
            ELSE ''
        END as row_class
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.stock_quantity <= p.minimum_quantity
        ORDER BY p.stock_quantity ASC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-exclamation-triangle text-warning me-2"></i>Kritik Stok Listesi</h2>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Mevcut Stok</th>
                        <th>Minimum Stok</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $product): ?>
                        <tr class="<?php echo $product['row_class']; ?>">
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td><?php echo $product['minimum_quantity']; ?></td>
                            <td>
                                <?php if($product['stock_quantity'] == 0): ?>
                                    <span class="badge bg-danger">Stok Yok</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Kritik Seviye</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../stock/movement.php?product_id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i>Stok Ekle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 