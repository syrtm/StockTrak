<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

include '../../includes/db.php';

// Ensure UTF-8 encoding for the database connection
$conn->set_charset("utf8");

// Filtreleme parametreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$technician_id = isset($_GET['technician_id']) ? $_GET['technician_id'] : '';

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
    $result = $stmt->get_result();
    $maintenances = [];
    while ($row = $result->fetch_assoc()) {
        $maintenances[] = $row;
    }

    // Excel için başlıkları ayarla
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=bakim_kayitlari.xls');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Excel tablosunu oluştur
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Tarih</th>';
    echo '<th>Ürün</th>';
    echo '<th>Teknisyen</th>';
    echo '<th>Durum</th>';
    echo '<th>Sorun</th>';
    echo '<th>Çözüm</th>';
    echo '<th>Başlangıç</th>';
    echo '<th>Bitiş</th>';
    echo '</tr>';

    $status_labels = [
        'pending' => 'Bekliyor',
        'in_progress' => 'Devam Ediyor',
        'completed' => 'Tamamlandı',
        'cancelled' => 'İptal'
    ];

    foreach($maintenances as $m) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars(date('d.m.Y H:i', strtotime($m['created_at'])), ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($m['product_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($m['username'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($status_labels[$m['status']], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($m['issue'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($m['solution'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars(($m['start_date'] ? date('d.m.Y', strtotime($m['start_date'])) : ''), ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars(($m['end_date'] ? date('d.m.Y', strtotime($m['end_date'])) : ''), ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
} catch(Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>