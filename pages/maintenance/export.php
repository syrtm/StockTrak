<?php
require '../../config/db.php';

// Excel başlık ayarları
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="bakim_raporu.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
    <table border="1">
        <tr style="background-color: #f0f0f0;">
            <th>Tarih</th>
            <th>Açan</th>
            <th>Teknisyen</th>
            <th>Durum</th>
            <th>Sorun</th>
            <th>Açıklama</th>
            <th>Başlangıç</th>
            <th>Bitiş</th>
        </tr>';

try {
    // Bakım kayıtlarını çek
    $stmt = $conn->query("
        SELECT 
            m.*,
            u.name as user_name,
            t.name as technician_name
        FROM maintenance m
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN users t ON m.technician_id = t.id
        ORDER BY m.created_at DESC
    ");
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Durumu Türkçe olarak yaz
        $status = [
            'pending' => 'Bekliyor',
            'in_progress' => 'Devam Ediyor',
            'completed' => 'Tamamlandı',
            'cancelled' => 'İptal Edildi'
        ];

        echo '<tr>';
        echo '<td>' . date('d.m.Y H:i', strtotime($row['created_at'])) . '</td>';
        echo '<td>' . htmlspecialchars($row['user_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['technician_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($status[$row['status']] ?? $row['status'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['issue'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . ($row['start_date'] ? date('d.m.Y', strtotime($row['start_date'])) : '') . '</td>';
        echo '<td>' . ($row['end_date'] ? date('d.m.Y', strtotime($row['end_date'])) : '') . '</td>';
        echo '</tr>';
    }
    
} catch(PDOException $e) {
    echo "Hata: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

echo '</table></body></html>';
?>
