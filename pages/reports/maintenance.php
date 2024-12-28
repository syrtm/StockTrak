<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';

// Filtreleme parametreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$technician_id = isset($_GET['technician_id']) ? $_GET['technician_id'] : '';

// Ürünleri çek
try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Teknisyenleri çek
try {
    $stmt = $conn->query("SELECT * FROM users WHERE role = 'teknisyen' ORDER BY username");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// İstatistikleri sıfırla
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

// Bakım kayıtlarını çek
try {
    $query = "
        SELECT m.*, p.name as product_name, u.username
        FROM maintenance m
        JOIN products p ON m.product_id = p.id
        JOIN users u ON m.user_id = u.id
        WHERE m.created_at BETWEEN ? AND ?
    ";
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    
    if($product_id) {
        $query .= " AND m.product_id = ?";
        $params[] = $product_id;
    }
    if($status) {
        $query .= " AND m.status = ?";
        $params[] = $status;
    }
    if($technician_id) {
        $query .= " AND m.user_id = ?";
        $params[] = $technician_id;
    }
    
    $query .= " ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İstatistikleri hesapla
    $stats['total'] = count($maintenances);
    foreach($maintenances as $m) {
        $stats[$m['status']]++;
    }
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

$status_labels = [
    'pending' => '<span class="badge bg-warning">Bekliyor</span>',
    'in_progress' => '<span class="badge bg-primary">Devam Ediyor</span>',
    'completed' => '<span class="badge bg-success">Tamamlandı</span>',
    'cancelled' => '<span class="badge bg-danger">İptal</span>'
];
?>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Bakım Kayıtları Raporu</h2>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Filtrele</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ürün</label>
                <select class="form-select" name="product_id">
                    <option value="">Tümü</option>
                    <?php foreach($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo ($product_id == $product['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Durum</label>
                <select class="form-select" name="status">
                    <option value="">Tümü</option>
                    <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Bekliyor</option>
                    <option value="in_progress" <?php echo ($status == 'in_progress') ? 'selected' : ''; ?>>Devam Ediyor</option>
                    <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Tamamlandı</option>
                    <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>İptal</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Teknisyen</label>
                <select class="form-select" name="technician_id">
                    <option value="">Tümü</option>
                    <?php foreach($technicians as $tech): ?>
                        <option value="<?php echo $tech['id']; ?>" <?php echo ($technician_id == $tech['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tech['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Filtrele</button>
                <a href="export_maintenance.php<?php echo isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>Excel İndir
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Toplam Kayıt</h5>
                <h3 class="card-text"><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">Bekleyen</h5>
                <h3 class="card-text"><?php echo $stats['pending']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Devam Eden</h5>
                <h3 class="card-text"><?php echo $stats['in_progress']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Tamamlanan</h5>
                <h3 class="card-text"><?php echo $stats['completed']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">İptal</h5>
                <h3 class="card-text"><?php echo $stats['cancelled']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Ürün</th>
                        <th>Teknisyen</th>
                        <th>Durum</th>
                        <th>Sorun</th>
                        <th>Çözüm</th>
                        <th>Başlangıç</th>
                        <th>Bitiş</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($maintenances as $m): ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($m['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($m['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['username']); ?></td>
                        <td><?php echo $status_labels[$m['status']]; ?></td>
                        <td><?php echo htmlspecialchars(substr($m['issue'], 0, 50)) . '...'; ?></td>
                        <td><?php echo $m['solution'] ? htmlspecialchars(substr($m['solution'], 0, 50)) . '...' : '-'; ?></td>
                        <td><?php echo $m['start_date'] ? date('d.m.Y', strtotime($m['start_date'])) : '-'; ?></td>
                        <td><?php echo $m['end_date'] ? date('d.m.Y', strtotime($m['end_date'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
