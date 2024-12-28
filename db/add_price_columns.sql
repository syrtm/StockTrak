-- Ürünler tablosuna fiyat kolonları ekleme
ALTER TABLE products 
ADD COLUMN purchase_price DECIMAL(10,2) AFTER stock_quantity,
ADD COLUMN sale_price DECIMAL(10,2) AFTER purchase_price,
ADD COLUMN price_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER sale_price;
