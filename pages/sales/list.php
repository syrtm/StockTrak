<?php
require_once '../../includes/db.php';
require_once '../../includes/header.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user')) {
    header("Location: /stok-takip/index.php");
    exit();
}

try {
    $stmt = $conn->query("
        SELECT s.*, p.name as product_name, u.username as user_name 
        FROM sales s
        JOIN products p ON s.product_id = p.id
        JOIN users u ON s.user_id = u.id
        ORDER BY s.sales_date DESC
    ");
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Satış Listesi</h3>
            <a href="add.php" class="btn btn-primary">Yeni Satış</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ürün</th>
                            <th>Satış Yapan</th>
                            <th>Miktar</th>
                            <th>Birim Fiyat</th>
                            <th>Toplam Tutar</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['sales_id']; ?></td>
                                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($sale['user_name']); ?></td>
                                <td><?php echo $sale['quantity']; ?></td>
                                <td><?php echo number_format($sale['unit_price'], 2, ',', '.'); ?> TL</td>
                                <td><?php echo number_format($sale['total_amount'], 2, ',', '.'); ?> TL</td>
                                <td><?php echo date('d.m.Y H:i', strtotime($sale['sales_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
