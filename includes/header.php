<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if(!isset($_SESSION['user_id'])) {
    header("Location: /stok-takip/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/mail_helper.php';

// Stok kontrolü ve bildirim oluşturma fonksiyonu
function createStockNotification($conn, $product_id) {
    $stmt = $conn->prepare("
        SELECT name, stock_quantity 
        FROM products 
        WHERE id = ? AND NOT EXISTS (
            SELECT 1 
            FROM notifications n 
            WHERE n.product_id = products.id 
            AND n.message LIKE '%stok%' 
            AND (n.deleted_at IS NULL OR DATE(n.created_at) = CURRENT_DATE())
        )
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['stock_quantity'] <= 0) {
        $message = $product['name'] . ' ürününün stoğu tükendi! Mevcut stok: 0';
        
        // Bildirim oluştur
        $stmt = $conn->prepare("
            INSERT INTO notifications (message, product_id, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$message, $product_id]);
        
        // E-posta gönder
        $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'teknisyen' AND email != ''");
        $stmt->execute();
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($technicians as $technician) {
            sendNotificationEmail(
                $technician['email'],
                'Stok Uyarısı',
                $message
            );
        }
    }
}

// Bakım bildirimleri kontrolü
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT m.*, p.name as product_name 
    FROM maintenance m 
    JOIN products p ON m.product_id = p.id 
    WHERE m.status = 'pending' 
    AND m.next_maintenance_date <= DATE_ADD(?, INTERVAL 7 DAY)
    AND NOT EXISTS (
        SELECT 1 
        FROM notifications n 
        WHERE n.maintenance_id = m.id 
        AND (n.deleted_at IS NULL OR DATE(n.created_at) = CURRENT_DATE())
    )
");
$stmt->execute([$today]);
$upcoming_maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($upcoming_maintenance as $maintenance) {
    $days_until = (strtotime($maintenance['next_maintenance_date']) - strtotime($today)) / (60 * 60 * 24);
    $message = $maintenance['product_name'] . ' ürünü için bakım yapılması gerekiyor!';
    
    // Bildirim oluştur
    $stmt = $conn->prepare("
        INSERT INTO notifications (message, product_id, maintenance_id, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$message, $maintenance['product_id'], $maintenance['id']]);
    
    // E-posta gönder
    $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'teknisyen' AND email != ''");
    $stmt->execute();
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($technicians as $technician) {
        sendNotificationEmail(
            $technician['email'],
            'Bakım Hatırlatması',
            $message
        );
    }
}

// Stok kontrolü
$stmt = $conn->prepare("SELECT id FROM products WHERE stock_quantity <= 0");
$stmt->execute();
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($low_stock_products as $product) {
    createStockNotification($conn, $product['id']);
}

// Bildirimleri al
$stmt = $conn->prepare("
    SELECT n.*, p.name as product_name 
    FROM notifications n
    LEFT JOIN products p ON n.product_id = p.id 
    WHERE n.deleted_at IS NULL 
    ORDER BY n.created_at DESC
");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bildirim sayısını al
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE deleted_at IS NULL
");
$stmt->execute();
$unread = $stmt->fetch(PDO::FETCH_ASSOC);

// Bildirim silme işlemi
if(isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    $stmt = $conn->prepare("UPDATE notifications SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Header yönlendirmeleri burada yapılmalı
if(isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'mark_read':
            if(isset($_POST['id'])) {
                $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit();
            }
            break;
    }
}

// Kullanıcı rollerini tanımla
$userRole = $_SESSION['role'] ?? 'guest';
$isAdmin = $userRole === 'admin';
$isTeknisyen = $userRole === 'teknisyen';
$isUser = $userRole === 'user'; 

// Debug bilgisi
error_log("User Role: " . $userRole);
error_log("Is Admin: " . ($isAdmin ? 'true' : 'false'));
error_log("Is User: " . ($isUser ? 'true' : 'false'));

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Takip Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --info-color: #4895ef;
            --warning-color: #f72585;
            --danger-color: #e63946;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            padding: 0.5rem 1rem;
            margin-right: 2rem;
        }
        
        .navbar-nav {
            gap: 1rem;
            align-items: center;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 0.7rem 1.2rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-link i {
            font-size: 1.1rem;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 0.5rem;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 0.5rem;
            min-width: 200px;
        }
        
        .dropdown-menu-end {
            left: auto;
            right: 0;
        }
        
        .dropdown {
            position: relative;
        }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                padding: 1rem;
                border-radius: 12px;
                margin-top: 1rem;
            }
            
            .nav-link {
                padding: 0.8rem 1.5rem !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="/stok-takip/index.php">
                <i class="fas fa-boxes me-2"></i>
                Stok Takip Sistemi
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/stok-takip/index.php">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/stok-takip/pages/products/list.php">
                            <i class="fas fa-box me-2"></i>Ürünler
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/stok-takip/pages/stock/movement.php">
                            <i class="fas fa-exchange-alt me-2"></i>Stok Hareketi
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/stok-takip/pages/maintenance/list.php">
                            <i class="fas fa-tools me-2"></i>Bakım
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownReports" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-bar me-2"></i>Raporlar
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownReports">
                            <li><a class="dropdown-item" href="/stok-takip/pages/reports/stock.php">Stok Raporu</a></li>
                            <li><a class="dropdown-item" href="/stok-takip/pages/reports/maintenance.php">Bakım Raporu</a></li>
                        </ul>
                    </li>
                    <?php if($isAdmin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUsers" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownUsers">
                            <li><a class="dropdown-item" href="/stok-takip/pages/users/list.php">Kullanıcı Listesi</a></li>
                            <li><a class="dropdown-item" href="/stok-takip/pages/users/add.php">Kullanıcı Ekle</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="modal" data-bs-target="#notificationModal">
                            <i class="fas fa-bell"></i>
                            <?php if($unread['count'] > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread['count']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i>
                            <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Kullanıcı'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/stok-takip/logout.php">Çıkış Yap</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Bildirim Modal -->
        <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notificationModalLabel">Bildirimler</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if(!empty($notifications)): ?>
                            <ul class="list-group">
                            <?php foreach($notifications as $notification): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $notification['is_read'] ? 'text-muted' : ''; ?>" id="notification-<?php echo $notification['id']; ?>">
                                    <span><?php echo htmlspecialchars($notification['message']); ?></span>
                                    <button type="button" class="btn btn-sm btn-success delete-notification" data-id="<?php echo $notification['id']; ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-center">Bildirim bulunmamaktadır.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bildirim silme işlemi
            document.querySelectorAll('.delete-notification').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const notificationElement = document.getElementById('notification-' + id);
                    
                    fetch('/stok-takip/notifications/delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            // Bildirimi listeden kaldır
                            notificationElement.remove();
                            
                            // Bildirim sayacını güncelle
                            const badge = document.querySelector('.badge');
                            if(badge) {
                                const currentCount = parseInt(badge.textContent);
                                if(currentCount > 1) {
                                    badge.textContent = currentCount - 1;
                                } else {
                                    badge.style.display = 'none';
                                }
                            }
                        }
                    });
                });
            });
        });
        </script>