<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: /stok-takip/login.php");
    exit();
}

include 'includes/header.php';
include 'includes/db.php';

// Filtreleme parametreleri
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '7'; // Varsayılan son 7 gün

// İstatistikleri çek
try {
    // Toplam ürün sayısı
    $sql = "SELECT COUNT(*) as total FROM products";
    if($category_filter) {
        $sql .= " WHERE category_id = " . $category_filter;
    }
    $result = $conn->query($sql);
    $totalProducts = $result->fetch_assoc()['total'];

    // Kritik stok seviyesindeki ürünler
    $sql = "SELECT COUNT(*) as critical FROM products WHERE stock_quantity <= minimum_quantity";
    if($category_filter) {
        $sql .= " AND category_id = " . $category_filter;
    }
    $result = $conn->query($sql);
    $criticalStock = $result->fetch_assoc()['critical'] ?? 0;

    // Son hareketler
    $sql = "SELECT COUNT(*) as movements FROM stock_movements WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    if($category_filter) {
        $sql .= " AND product_id IN (SELECT id FROM products WHERE category_id = ?)";
    }
    $stmt = $conn->prepare($sql);
    if($category_filter) {
        $stmt->bind_param("ii", $date_range, $category_filter);
    } else {
        $stmt->bind_param("i", $date_range);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $recentMovements = $result->fetch_assoc()['movements'] ?? 0;

    // Kategorileri çek
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($sql);
    $categories = $result->fetch_all(MYSQLI_ASSOC);

    // Okunmamış bildirimleri çek
    $notifications = [];
    $sql = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result) {
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Stok dağılımı
    $sql = "SELECT COUNT(*) as normal FROM products WHERE stock_quantity > minimum_quantity";
    $result = $conn->query($sql);
    $normalStock = $result->fetch_assoc()['normal'] ?? 0;

    $sql = "SELECT COUNT(*) as critical FROM products WHERE stock_quantity <= minimum_quantity AND stock_quantity > 0";
    $result = $conn->query($sql);
    $criticalStock = $result->fetch_assoc()['critical'] ?? 0;

    $sql = "SELECT COUNT(*) as out_of_stock FROM products WHERE stock_quantity = 0";
    $result = $conn->query($sql);
    $outOfStock = $result->fetch_assoc()['out_of_stock'] ?? 0;

    // Bakım durumu
    $sql = "SELECT COUNT(*) as completed FROM maintenance WHERE status = 'completed'";
    $result = $conn->query($sql);
    $completedMaintenance = $result->fetch_assoc()['completed'] ?? 0;

    $sql = "SELECT COUNT(*) as pending FROM maintenance WHERE status = 'pending'";
    $result = $conn->query($sql);
    $pendingMaintenance = $result->fetch_assoc()['pending'] ?? 0;

    $sql = "SELECT COUNT(*) as in_progress FROM maintenance WHERE status = 'in_progress'";
    $result = $conn->query($sql);
    $inProgressMaintenance = $result->fetch_assoc()['in_progress'] ?? 0;

    // Kategorilere göre stok dağılımı
    $sql = "SELECT c.name, COUNT(p.id) as total FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            GROUP BY c.id, c.name 
            ORDER BY total DESC";
    $result = $conn->query($sql);
    $categoryLabels = [];
    $categoryData = [];
    while($row = $result->fetch_assoc()) {
        $categoryLabels[] = $row['name'];
        $categoryData[] = (int)$row['total'];
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!-- Bildirim Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bildirimler</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if(!empty($notifications)): ?>
                    <ul class="list-group">
                    <?php foreach($notifications as $notification): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <button class="btn btn-sm btn-danger delete-notification" data-id="<?php echo $notification['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Yeni bildirim bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Genel Bakış</h2>
        <div class="d-flex gap-2">
            <!-- Filtreler -->
            <form method="GET" class="d-flex gap-2">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="date_range" class="form-select" onchange="this.form.submit()">
                    <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Son 7 Gün</option>
                    <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Son 30 Gün</option>
                    <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Son 90 Gün</option>
                </select>
            </form>
        </div>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- İstatistik Kartları -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <a href="pages/products/list.php" class="text-white text-decoration-none">
                        <h5 class="card-title">Toplam Ürün</h5>
                        <h1><?php echo $totalProducts; ?></h1>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <a href="pages/stock/movement.php" class="text-white text-decoration-none">
                        <h5 class="card-title">Son 7 Gün Hareket</h5>
                        <h1><?php echo $recentMovements ?? 0; ?></h1>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <a href="pages/products/critical.php" class="text-white text-decoration-none">
                        <h5 class="card-title">Kritik Stok</h5>
                        <h1><?php echo $criticalStock; ?></h1>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <a href="pages/maintenance/list.php?status=pending" class="text-white text-decoration-none">
                        <h5 class="card-title">Bekleyen Bakım</h5>
                        <h1><?php echo $pendingMaintenance; ?></h1>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Stok Dağılımı</h5>
                    <div style="height: 300px;">
                        <canvas id="stockDistribution"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bakım Durumu</h5>
                    <div style="height: 300px;">
                        <canvas id="maintenanceStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Kategori Dağılımı</h5>
                    <div style="height: 300px;">
                        <canvas id="categoryDistribution"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // Stok Dağılımı Grafiği
    var stockCtx = document.getElementById('stockDistribution').getContext('2d');
    var stockChart = new Chart(stockCtx, {
        type: 'doughnut',
        data: {
            labels: ['Normal', 'Kritik', 'Tükenen'],
            datasets: [{
                data: [<?php echo $normalStock; ?>, <?php echo $criticalStock; ?>, <?php echo $outOfStock; ?>],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Bakım Durumu Grafiği
    var maintenanceCtx = document.getElementById('maintenanceStatus').getContext('2d');
    var maintenanceChart = new Chart(maintenanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Tamamlanan', 'Bekleyen', 'Devam Eden'],
            datasets: [{
                data: [<?php echo $completedMaintenance; ?>, <?php echo $pendingMaintenance; ?>, <?php echo $inProgressMaintenance; ?>],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Kategori Dağılımı Grafiği
    var categoryCtx = document.getElementById('categoryDistribution').getContext('2d');
    var categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($categoryLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($categoryData); ?>,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(199, 199, 199, 0.8)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    </script>

<?php include 'includes/footer.php'; ?>