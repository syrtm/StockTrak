<?php
include '../../includes/header.php';
include '../../config/db.php';

// Kullanıcı ID'si
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

try {
    // Kullanıcı bilgilerini çek
    if ($user_id) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Aktiviteleri çek
    $query = "
        SELECT ul.*, u.username
        FROM user_logs ul
        JOIN users u ON ul.user_id = u.id
    ";
    
    $params = [];
    
    if ($user_id) {
        $query .= " WHERE ul.user_id = ?";
        $params[] = $user_id;
    }
    
    $query .= " ORDER BY ul.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><?php echo $user_id ? htmlspecialchars($user['username']) . ' Kullanıcısının Aktiviteleri' : 'Tüm Aktiviteler'; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="list.php" class="btn btn-secondary">Geri Dön</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Kullanıcı</th>
                            <th>İşlem Tipi</th>
                            <th>Detay</th>
                            <th>IP Adresi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($activities as $activity): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                <td>
                                    <?php
                                    $activity_labels = [
                                        'login' => '<span class="badge bg-success">Giriş</span>',
                                        'logout' => '<span class="badge bg-secondary">Çıkış</span>',
                                        'stock_in' => '<span class="badge bg-primary">Stok Girişi</span>',
                                        'stock_out' => '<span class="badge bg-warning">Stok Çıkışı</span>'
                                    ];
                                    echo $activity_labels[$activity['activity_type']] ?? '<span class="badge bg-info">Diğer</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
