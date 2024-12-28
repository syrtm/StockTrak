<?php
session_start();
require_once '../../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

// Bakım kaydını çek
$query = "
    SELECT m.*, p.name as product_name, u.username as user_name, mt.name as maintenance_type_text, mt.category
    FROM maintenance m
    JOIN products p ON m.product_id = p.id
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN maintenance_types mt ON m.maintenance_type_id = mt.id
    WHERE m.id = $id
";

$result = mysqli_query($conn, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    header("Location: list.php");
    exit();
}

$maintenance = mysqli_fetch_assoc($result);

// Durum etiketleri
$status_badges = [
    'pending' => 'bg-warning',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger'
];

// Durum metinleri
$status_texts = [
    'pending' => 'Bekliyor',
    'in_progress' => 'Devam Ediyor',
    'completed' => 'Tamamlandı',
    'cancelled' => 'İptal Edildi'
];

$page_title = "Bakım Detayı #" . $maintenance['id'];
require_once '../../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Bakım Detayı #<?php echo $maintenance['id']; ?></h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if($_SESSION['role'] == 'teknisyen' && $maintenance['status'] != 'completed'): ?>
            <a href="edit.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-primary">Düzenle</a>
        <?php endif; ?>
        <a href="list.php" class="btn btn-secondary">Geri Dön</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Ürün Bilgileri</h5>
                <table class="table">
                    <tr>
                        <th>Ürün Adı:</th>
                        <td><?php echo htmlspecialchars($maintenance['product_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Bakım Tipi:</th>
                        <td><?php echo htmlspecialchars($maintenance['maintenance_type_text']); ?></td>
                    </tr>
                    <tr>
                        <th>Durum:</th>
                        <td>
                            <span class="badge <?php echo $status_badges[$maintenance['status']]; ?>">
                                <?php echo $status_texts[$maintenance['status']]; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Oluşturan:</th>
                        <td><?php echo htmlspecialchars($maintenance['user_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Oluşturma Tarihi:</th>
                        <td><?php echo date('d.m.Y H:i', strtotime($maintenance['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Bakım Detayları</h5>
                <table class="table">
                    <tr>
                        <th>Sorun:</th>
                        <td><?php echo nl2br(htmlspecialchars($maintenance['issue'])); ?></td>
                    </tr>
                    <tr>
                        <th>Çözüm:</th>
                        <td><?php echo nl2br(htmlspecialchars($maintenance['solution'])); ?></td>
                    </tr>
                    <tr>
                        <th>Maliyet:</th>
                        <td>
                            <?php if ($maintenance['cost'] > 0): ?>
                                <?php echo number_format($maintenance['cost'], 2, ',', '.'); ?> TL
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
