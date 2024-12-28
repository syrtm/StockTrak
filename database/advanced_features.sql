-- Önce mevcut trigger ve stored procedure'ü kaldır
DROP TRIGGER IF EXISTS after_stock_movement;
DROP PROCEDURE IF EXISTS update_maintenance_status;

-- Stok hareketi sonrası tetikleyici
DELIMITER //
CREATE TRIGGER after_stock_movement
AFTER INSERT ON stock_movements
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;
    DECLARE min_quantity INT;
    
    -- Yeni stok miktarını hesapla
    UPDATE products 
    SET stock_quantity = stock_quantity + NEW.quantity
    WHERE id = NEW.product_id;
    
    -- Güncel stok ve minimum miktarı al
    SELECT stock_quantity, minimum_quantity 
    INTO current_stock, min_quantity
    FROM products 
    WHERE id = NEW.product_id;
    
    -- Stok kritik seviyeye düştüyse bildirim oluştur
    IF current_stock <= min_quantity THEN
        INSERT INTO notifications (product_id, type, message, created_at)
        SELECT 
            NEW.product_id,
            'critical_stock',
            CONCAT((SELECT name FROM products WHERE id = NEW.product_id), ' ürününün stok seviyesi kritik seviyeye düştü'),
            NOW();
    END IF;
END //
DELIMITER ;

--Stok azaltma (-40 adet) INSERT INTO stock_movements (product_id, quantity, type, user_id, created_at) VALUES (50, -40, 'manual', 1, NOW());


-- Bakım durumu güncelleme için saklı yordam
DELIMITER //
CREATE PROCEDURE update_maintenance_status(
    IN p_maintenance_id INT,
    IN p_status VARCHAR(20),
    IN p_solution TEXT
)
BEGIN
    -- Bakım kaydını güncelle
    UPDATE maintenance 
    SET status = p_status,
        solution = p_solution
    WHERE id = p_maintenance_id;
    
    -- Eğer durum tamamlandı ise bildirim oluştur
    IF p_status = 'completed' THEN
        INSERT INTO notifications (type, message, created_at)
        VALUES (
            'maintenance_completed',
            CONCAT('Bakım kaydı #', p_maintenance_id, ' tamamlandı'),
            NOW()
        );
    END IF;
END //
DELIMITER ;

--CALL update_maintenance_status(2, 'completed', 'Sorun cozüldü ve sistem stabil çalışıyor.');
