<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /stok-takip/index.php");
    exit();
}

include '../../includes/header.php';
include '../../config/db.php';

try {
    $stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>

<div class="row mb-3">
    <div class="col-md-6">
        <h2>Kullanıcı Listesi</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="activity.php" class="btn btn-info me-2">Tüm Aktiviteler</a>
        <a href="add.php" class="btn btn-primary">Yeni Kullanıcı Ekle</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Telefon</th>
                        <th>Rol</th>
                        <th>Kayıt Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <?php 
                            $role_labels = [
                                'admin' => '<span class="badge bg-danger">Admin</span>',
                                'user' => '<span class="badge bg-primary">Kullanıcı</span>',
                                'teknisyen' => '<span class="badge bg-success">Teknisyen</span>',
                                'kullanici' => '<span class="badge bg-primary">Kullanıcı</span>',
                                '' => '<span class="badge bg-secondary">Belirsiz</span>'
                            ];
                            $role = $user['role'] ?? '';
                            echo $role_labels[$role] ?? $role_labels[''];
                            ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="activity.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">Aktiviteler</a>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Düzenle</a>
                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">Sil</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
