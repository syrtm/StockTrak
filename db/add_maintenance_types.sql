-- Bakım türleri tablosu
CREATE TABLE maintenance_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('hardware', 'software') NOT NULL DEFAULT 'hardware',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bakım tablosuna bakım türü, versiyon ve maliyet bilgileri ekleme
ALTER TABLE maintenance
DROP COLUMN maintenance_type,
ADD COLUMN maintenance_type ENUM('hardware_cleaning', 'part_replacement', 'software_update', 'security_patch', 'data_backup', 'driver_update') NOT NULL DEFAULT 'hardware_cleaning' AFTER product_id,
ADD COLUMN current_version VARCHAR(50) AFTER issue,
ADD COLUMN new_version VARCHAR(50) AFTER current_version,
ADD COLUMN cost DECIMAL(10,2) DEFAULT 0.00 AFTER new_version;
