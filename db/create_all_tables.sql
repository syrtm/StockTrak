-- Users tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role ENUM('admin', 'kullanici', 'teknisyen') DEFAULT 'kullanici',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Suppliers tablosu
CREATE TABLE IF NOT EXISTS tbl_suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) UNIQUE NOT NULL,
    contact_details VARCHAR(150),
    address TEXT
);

-- Tedarikçiler tablosu
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Products tablosu
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    stock_quantity INT DEFAULT 0,
    critical_stock_level INT DEFAULT 5,
    location VARCHAR(100),
    supplier_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES tbl_suppliers(supplier_id)
);

-- Stock_movements tablosu
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    quantity INT NOT NULL,
    movement_type ENUM('in', 'out') NOT NULL,
    description TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Maintenance tablosu
CREATE TABLE IF NOT EXISTS maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    maintenance_type VARCHAR(50),
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    scheduled_date DATE,
    completed_date DATE,
    user_id INT,
    technician_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (technician_id) REFERENCES users(id)
);

-- Notifications tablosu
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    user_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User_activity_logs tablosu
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Satış tablosu
CREATE TABLE IF NOT EXISTS tbl_sales (
    sales_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    user_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2),
    total_amount DECIMAL(10, 2),
    sales_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Donanım detayları tablosu
CREATE TABLE IF NOT EXISTS hardware_details ( 
    hardware_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNIQUE,
    processor_model VARCHAR(50),
    ram_capacity VARCHAR(50),
    storage_type VARCHAR(50),
    power_consumption VARCHAR(50),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Gömülü sistemler detayları tablosu
CREATE TABLE IF NOT EXISTS embedded_details ( 
    embedded_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNIQUE,
    processor_model VARCHAR(50),
    connection_interfaces VARCHAR(100),
    power_consumption VARCHAR(50),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Yazılım detayları tablosu
CREATE TABLE IF NOT EXISTS software_details ( 
    software_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNIQUE,
    version VARCHAR(50),
    license_type VARCHAR(50),
    license_duration INT,
    support_end_date DATETIME,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Barkod tablosu
CREATE TABLE IF NOT EXISTS barcodes ( 
    barcode_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    barcode_number VARCHAR(50) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    active_status BOOLEAN DEFAULT TRUE,
    location VARCHAR(50),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Lokasyon tablosu
CREATE TABLE IF NOT EXISTS locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
