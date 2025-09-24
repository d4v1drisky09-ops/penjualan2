CREATE DATABASE IF NOT EXISTS penjualan_db;
USE penjualan_db;

-- Tabel admins
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE
);

-- Tabel products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    category_id INT,
    deskripsi TEXT,
    harga_jual DECIMAL(10,2) NOT NULL,
    harga_beli DECIMAL(10,2),
    stok INT NOT NULL DEFAULT 0,
    gambar_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tabel customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telepon VARCHAR(20),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel sales
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    total_items INT NOT NULL,
    pembayaran_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Tabel sale_items
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Index untuk optimasi
CREATE INDEX idx_sku ON products(sku);
CREATE INDEX idx_email ON customers(email);
CREATE INDEX idx_invoice_no ON sales(invoice_no);
CREATE INDEX idx_created_at ON sales(created_at);

-- Insert sample data
INSERT INTO admins (nama, email, password_hash) VALUES 
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

INSERT INTO categories (nama, slug) VALUES 
('Elektronik', 'elektronik'),
('Pakaian', 'pakaian'),
('Makanan', 'makanan'),
('Minuman', 'minuman');

INSERT INTO products (sku, nama, category_id, deskripsi, harga_jual, harga_beli, stok) VALUES 
('ELEC001', 'Laptop', 1, 'Laptop dengan performa tinggi', 15000000.00, 12000000.00, 10),
('ELEC002', 'Smartphone', 1, 'Smartphone dengan kamera bagus', 5000000.00, 4000000.00, 20),
('PAK001', 'Kaos', 2, 'Kaos katun nyaman', 150000.00, 100000.00, 50),
('MAK001', 'Keripik Kentang', 3, 'Keripik kentang renyah', 10000.00, 7000.00, 100),
('MIN001', 'Air Mineral', 4, 'Air mineral kemasan 600ml', 5000.00, 3000.00, 200);

INSERT INTO customers (nama, email, telepon, alamat) VALUES 
('John Doe', 'john@example.com', '08123456789', 'Jl. Contoh No. 123'),
('Jane Smith', 'jane@example.com', '08987654321', 'Jl. Contoh No. 456');