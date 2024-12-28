<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';

// Bakım kayıtlarını çek
try {
    $stmt = $conn->query("
        SELECT m.*, p.name as product_name, u.username as user_name, mt.name as maintenance_type_text, mt.category,
               CASE 
                   WHEN m.status = 'pending' THEN 'Bekliyor'
                   WHEN m.status = 'in_progress' THEN 'Devam Ediyor'
                   WHEN m.status = 'completed' THEN 'Tamamlandı'
                   WHEN m.status = 'cancelled' THEN 'İptal Edildi'
               END as status_text
        FROM maintenance m
        JOIN products p ON m.product_id = p.id
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
        ORDER BY m.created_at DESC
    ");
    $maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}

// Durum etiketleri
$status_badges = [
    'pending' => 'bg-warning',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger'
];
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Bakım Kayıtları</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="add.php" class="btn btn-primary">Yeni Bakım Kaydı</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ürün</th>
                        <th>Bakım Türü</th>
                        <th>Kullanıcı</th>
                        <th>Sürüm Bilgisi</th>
                        <th>Maliyet</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($maintenances as $maintenance): ?>
                    <tr>
                        <td><?php echo $maintenance['id']; ?></td>
                        <td><?php echo htmlspecialchars($maintenance['product_name']); ?></td>
                        <td>
                            <?php 
                                echo htmlspecialchars($maintenance['maintenance_type_text']);
                                if ($maintenance['category'] == 'software') {
                                    echo ' <span class="badge bg-info">Yazılım</span>';
                                } else {
                                    echo ' <span class="badge bg-secondary">Donanım</span>';
                                }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($maintenance['user_name']); ?></td>
                        <td>
                            <?php if ($maintenance['category'] == 'software' && ($maintenance['current_version'] || $maintenance['new_version'])): ?>
                                <?php if ($maintenance['current_version']): ?>
                                    <span class="text-muted">Mevcut: <?php echo htmlspecialchars($maintenance['current_version']); ?></span>
                                <?php endif; ?>
                                <?php if ($maintenance['new_version']): ?>
                                    <br>
                                    <span class="text-success">Yeni: <?php echo htmlspecialchars($maintenance['new_version']); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($maintenance['cost'] > 0): ?>
                                <?php echo number_format($maintenance['cost'], 2, ',', '.'); ?> TL
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            echo '<span class="badge ' . $status_badges[$maintenance['status']] . '">' . $maintenance['status_text'] . '</span>';
                            ?>
                        </td>
                        <td><?php echo $maintenance['created_at'] ? date('d.m.Y', strtotime($maintenance['created_at'])) : '-'; ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-info">Görüntüle</a>
                            <?php if($_SESSION['role'] == 'teknisyen' && $maintenance['status'] != 'completed'): ?>
                            <a href="edit.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
