<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit();
}

require_once '../../includes/db.php';

// Debug için
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $response = ['success' => false, 'message' => ''];
    
    try {
        // İlişkili kayıtları sil
        $tables = [
            'software_details',
            'hardware_details',
            'embedded_details',
            'barcodes',
            'stock_movements',
            'maintenance',
            'notifications',
            'sales'  // Satış kayıtlarını da siliyoruz
        ];
        
        // Debug için
        error_log("Silinecek ürün ID: " . $id);
        
        // Her tablo için silme işlemini dene
        foreach($tables as $table) {
            $query = "DELETE FROM $table WHERE product_id = $id";
            error_log("Çalıştırılan sorgu: " . $query);
            
            // Silme işlemini dene ve hataları yakala
            if(!mysqli_query($conn, $query)) {
                $error = mysqli_error($conn);
                error_log("$table tablosundan silme hatası: " . $error);
                
                // Foreign key hatası varsa kullanıcıya anlamlı bir mesaj göster
                if(strpos($error, 'foreign key constraint fails') !== false) {
                    $response['message'] = "Bu ürün başka tablolarda kullanımda olduğu için silinemiyor. Lütfen önce ilişkili kayıtları silin.";
                } else {
                    $response['message'] = "$table tablosundan kayıtlar silinirken hata oluştu: " . $error;
                }
                
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }
        }
        
        // Ürünü sil
        $query = "DELETE FROM products WHERE id = $id";
        error_log("Çalıştırılan sorgu: " . $query);
        
        if(mysqli_query($conn, $query)) {
            $response['success'] = true;
            $response['message'] = "Ürün ve ilişkili tüm kayıtlar başarıyla silindi.";
            error_log("Ürün başarıyla silindi: " . $id);
        } else {
            $response['success'] = false;
            $response['message'] = "Ürün silinirken bir hata oluştu: " . mysqli_error($conn);
            error_log("Ürün silme hatası: " . mysqli_error($conn));
        }
    } catch(Exception $e) {
        $response['success'] = false;
        $response['message'] = "Ürün silinirken bir hata oluştu: " . $e->getMessage();
        error_log("Exception: " . $e->getMessage());
    }
    
    // AJAX yanıtı
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
