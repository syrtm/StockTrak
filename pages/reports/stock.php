<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Excel oluştur
if(isset($_GET['export']) && $_GET['export'] == 'excel') {
    require_once '../../config/db.php';
    
    // Filtreleme parametreleri
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
    $movement_type = isset($_GET['movement_type']) ? $_GET['movement_type'] : '';

    // Stok hareketlerini çek
    try {
        $query = "
            SELECT sm.*, p.name as product_name, u.username
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            JOIN users u ON sm.user_id = u.id
            WHERE sm.created_at BETWEEN ? AND ?
        ";
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if($product_id) {
            $query .= " AND sm.product_id = ?";
            $params[] = $product_id;
        }
        if($movement_type) {
            $query .= " AND sm.type = ?";
            $params[] = $movement_type;
        }
        
        $query .= " ORDER BY sm.created_at DESC, sm.id DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Excel başlıkları
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=stok_hareketleri.xls');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Excel içeriği
        echo '<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
<Worksheet ss:Name="Stok Hareketleri">
<Table>
<Row>
<Cell><Data ss:Type="String">Tarih</Data></Cell>
<Cell><Data ss:Type="String">Ürün</Data></Cell>
<Cell><Data ss:Type="String">Hareket</Data></Cell>
<Cell><Data ss:Type="String">Miktar</Data></Cell>
<Cell><Data ss:Type="String">Kullanıcı</Data></Cell>
<Cell><Data ss:Type="String">Not</Data></Cell>
</Row>';
        
        // Verileri yaz
        foreach($movements as $move) {
            echo '<Row>
<Cell><Data ss:Type="String">' . date('d.m.Y H:i', strtotime($move['created_at'])) . '</Data></Cell>
<Cell><Data ss:Type="String">' . htmlspecialchars($move['product_name']) . '</Data></Cell>
<Cell><Data ss:Type="String">' . ($move['type'] == 'in' ? 'Giriş' : 'Çıkış') . '</Data></Cell>
<Cell><Data ss:Type="Number">' . $move['quantity'] . '</Data></Cell>
<Cell><Data ss:Type="String">' . htmlspecialchars($move['username']) . '</Data></Cell>
<Cell><Data ss:Type="String">' . (isset($move['note']) ? htmlspecialchars($move['note']) : '') . '</Data></Cell>
</Row>';
        }
        
        echo '</Table>
</Worksheet>
</Workbook>';
        exit();
    } catch(PDOException $e) {
        die("Hata: " . $e->getMessage());
    }
}

include '../../includes/header.php';
include '../../config/db.php';

// Filtreleme parametreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$movement_type = isset($_GET['movement_type']) ? $_GET['movement_type'] : '';

// Ürünleri çek
try {
    $stmt = $conn->query("SELECT * FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Toplam değerleri sıfırla
$total_in = 0;
$total_out = 0;
$movements = array();

// Stok hareketlerini çek
try {
    $query = "
        SELECT sm.*, p.name as product_name, u.username
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        JOIN users u ON sm.user_id = u.id
        WHERE sm.created_at BETWEEN ? AND ?
    ";
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    
    if($product_id) {
        $query .= " AND sm.product_id = ?";
        $params[] = $product_id;
    }
    if($movement_type) {
        $query .= " AND sm.type = ?";
        $params[] = $movement_type;
    }
    
    $query .= " ORDER BY sm.created_at DESC, sm.id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Toplam giriş/çıkış hesapla
    foreach($movements as $move) {
        if($move['type'] == 'in') {
            $total_in += intval($move['quantity']);
        } else {
            $total_out += intval($move['quantity']);
        }
    }
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2><i class="fas fa-chart-line me-2"></i>Stok Hareket Raporu</h2>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-calendar me-1"></i>Başlangıç Tarihi</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-calendar me-1"></i>Bitiş Tarihi</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-box me-1"></i>Ürün</label>
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
                <label class="form-label"><i class="fas fa-exchange-alt me-1"></i>Hareket Tipi</label>
                <select class="form-select" name="movement_type">
                    <option value="">Tümü</option>
                    <option value="in" <?php echo ($movement_type == 'in') ? 'selected' : ''; ?>>Giriş</option>
                    <option value="out" <?php echo ($movement_type == 'out') ? 'selected' : ''; ?>>Çıkış</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Filtrele
                </button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i>Excel İndir
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-arrow-circle-up me-1"></i>Toplam Giriş
                </h5>
                <h3 class="card-text mt-3"><?php echo number_format($total_in); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-arrow-circle-down me-1"></i>Toplam Çıkış
                </h5>
                <h3 class="card-text"><?php echo number_format($total_out); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-balance-scale me-1"></i>Net Değişim
                </h5>
                <h3 class="card-text"><?php echo number_format($total_in - $total_out); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Stok Hareketleri</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th><i class="fas fa-calendar me-1"></i>Tarih</th>
                        <th><i class="fas fa-box me-1"></i>Ürün</th>
                        <th><i class="fas fa-exchange-alt me-1"></i>Hareket</th>
                        <th><i class="fas fa-sort-numeric-up me-1"></i>Miktar</th>
                        <th><i class="fas fa-user me-1"></i>Kullanıcı</th>
                        <th><i class="fas fa-sticky-note me-1"></i>Not</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($movements)): ?>
                    <?php foreach($movements as $move): ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i', strtotime($move['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($move['product_name']); ?></td>
                        <td>
                            <?php if($move['type'] == 'in'): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-arrow-circle-up me-1"></i>Giriş
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-arrow-circle-down me-1"></i>Çıkış
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($move['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($move['username']); ?></td>
                        <td><?php echo isset($move['note']) ? htmlspecialchars($move['note']) : ''; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Kayıt bulunamadı</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
