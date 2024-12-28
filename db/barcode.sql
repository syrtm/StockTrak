ALTER TABLE products ADD COLUMN barcode VARCHAR(50) UNIQUE;

-- Mevcut ürünlere benzersiz barkod ekle
UPDATE products SET barcode = CONCAT('PRD', LPAD(id, 8, '0')) WHERE barcode IS NULL;
