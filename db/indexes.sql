-- Ürünler tablosu indeksleri
ALTER TABLE products ADD INDEX idx_stock_quantity (stock_quantity);
ALTER TABLE products ADD INDEX idx_minimum_quantity (minimum_quantity);
ALTER TABLE products ADD INDEX idx_category_id (category_id);

-- Stok hareketleri tablosu indeksleri
ALTER TABLE stock_movements ADD INDEX idx_created_at (created_at);
ALTER TABLE stock_movements ADD INDEX idx_product_id (product_id);
ALTER TABLE stock_movements ADD INDEX idx_type (type);

-- Bakım tablosu indeksleri
ALTER TABLE maintenance ADD INDEX idx_status (status);
ALTER TABLE maintenance ADD INDEX idx_created_at (created_at);
ALTER TABLE maintenance ADD INDEX idx_product_id (product_id);
